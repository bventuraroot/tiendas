<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchasePaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra todas las compras con sus estados de pago
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Consulta para mostrar todas las compras con su saldo pendiente
        $purchases = Purchase::join('companies', 'companies.id', '=', 'purchases.company_id')
            ->join('providers', 'providers.id', '=', 'purchases.provider_id')
            ->leftJoin('purchase_payments', function($join) {
                $join->on('purchase_payments.purchase_id', '=', 'purchases.id')
                     ->whereRaw('purchase_payments.id = (SELECT MAX(p2.id) FROM purchase_payments p2 WHERE p2.purchase_id = purchases.id)');
            })
            ->select(
                'purchases.id as idpurchase',
                'purchases.number',
                'purchases.date',
                'purchases.total',
                'purchases.paid_amount',
                'purchases.payment_status',
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit',
                'companies.name as company_name',
                DB::raw('COALESCE(purchase_payments.current, (purchases.total - COALESCE(purchases.paid_amount, 0))) as current_balance'),
                DB::raw('COALESCE(purchase_payments.date_pay, NULL) as last_payment_date'),
                DB::raw('(CASE
                    WHEN purchases.payment_status = 2 THEN "PAGADO"
                    WHEN purchases.payment_status = 1 THEN "PARCIAL"
                    WHEN (purchases.total - COALESCE(purchases.paid_amount, 0)) <= 0 THEN "PAGADO"
                    ELSE "PENDIENTE"
                END) AS payment_status_display')
            )
            ->orderBy('purchases.date', 'desc')
            ->get();

        Log::info('🔍 Cuentas por pagar encontradas:', [
            'count' => $purchases->count()
        ]);

        return view('purchase-payments.index', array(
            "purchases" => $purchases
        ));
    }

    /**
     * Obtener información de saldo pendiente de una compra
     *
     * @param  string  $idpurchase (codificado en base64)
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseBalance($idpurchase)
    {
        $purchaseId = base64_decode($idpurchase);
        $purchase = Purchase::find($purchaseId);

        if (!$purchase) {
            return response()->json(['error' => 'Compra no encontrada'], 404);
        }

        // Obtener el último pago registrado
        $lastPayment = PurchasePayment::where('purchase_id', $purchaseId)
            ->orderBy('id', 'desc')
            ->first();

        // Calcular el saldo pendiente
        if ($lastPayment) {
            $balance = $lastPayment->current;
        } else {
            $balance = $purchase->total - ($purchase->paid_amount ?? 0);
        }

        return response()->json([
            'success' => true,
            'balance' => number_format($balance, 2, '.', ''),
            'total' => number_format($purchase->total, 2, '.', ''),
            'paid' => number_format($purchase->paid_amount ?? 0, 2, '.', '')
        ]);
    }

    /**
     * Registrar un pago para una compra
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addPayment(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validar datos de entrada
            $request->validate([
                'idpurchase' => 'required|exists:purchases,id',
                'amountpay' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string|max:500'
            ]);

            $purchase = Purchase::findOrFail($request->idpurchase);

            // Obtener el último pago registrado
            $lastPayment = PurchasePayment::where('purchase_id', $request->idpurchase)
                ->orderBy('id', 'desc')
                ->first();

            // Calcular el saldo actual
            if ($lastPayment) {
                $currentBalance = $lastPayment->current;
            } else {
                $currentBalance = $purchase->total - ($purchase->paid_amount ?? 0);
            }

            // Validar que el pago no exceda el saldo
            if ($request->amountpay > $currentBalance) {
                throw new \Exception('El monto a pagar no puede ser mayor al saldo pendiente');
            }

            // Calcular nuevo saldo
            $newBalance = $currentBalance - $request->amountpay;

            // Calcular nuevo monto pagado total
            $newPaidAmount = ($purchase->paid_amount ?? 0) + $request->amountpay;

            // Determinar el estado del pago
            $paymentStatus = 0; // Pendiente
            if ($newBalance <= 0) {
                $paymentStatus = 2; // Pagado completamente
            } elseif ($newPaidAmount > 0 && $newBalance > 0) {
                $paymentStatus = 1; // Parcial
            }

            // Crear nuevo registro de pago
            $payment = new PurchasePayment();
            $payment->purchase_id = $request->idpurchase;
            $payment->date_pay = now();
            $payment->current = $newBalance;
            $payment->initial = $purchase->total;
            $payment->amountpay = $request->amountpay;
            $payment->notes = $request->notes;
            $payment->user_id = auth()->user()->id;
            $payment->save();

            // Actualizar la compra con el nuevo monto pagado y estado
            $purchase->paid_amount = $newPaidAmount;
            $purchase->payment_status = $paymentStatus;
            $purchase->save();

            Log::info('✅ Pago registrado exitosamente', [
                'purchase_id' => $purchase->id,
                'amount' => $request->amountpay,
                'new_balance' => $newBalance
            ]);

            DB::commit();

            return redirect()->route('purchase-payment.index')
                ->with('success', 'Pago registrado correctamente. Saldo restante: $' . number_format($newBalance, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al procesar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('purchase-payment.index')
                ->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Obtener historial de pagos de una compra
     *
     * @param  string  $idpurchase (codificado en base64)
     * @return \Illuminate\Http\Response
     */
    public function getPaymentHistory($idpurchase)
    {
        try {
            $purchaseId = base64_decode($idpurchase);
            $purchase = Purchase::with(['payments.user', 'provider', 'company'])
                ->findOrFail($purchaseId);

            return response()->json([
                'success' => true,
                'purchase' => $purchase,
                'payments' => $purchase->payments,
                'total_paid' => $purchase->paid_amount,
                'balance' => $purchase->balance,
                'total' => $purchase->total
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchasePayment  $purchasePayment
     * @return \Illuminate\Http\Response
     */
    public function show(PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchasePayment  $purchasePayment
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchasePayment  $purchasePayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchasePayment $purchasePayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchasePayment  $purchasePayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchasePayment $purchasePayment)
    {
        //
    }
}
