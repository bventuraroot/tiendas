<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Consulta simplificada para mostrar todas las ventas a crédito con su saldo correcto
        $credit = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->leftJoin('credits', function($join) {
                $join->on('credits.sale_id', '=', 'sales.id')
                     ->whereRaw('credits.id = (SELECT MAX(c2.id) FROM credits c2 WHERE c2.sale_id = sales.id)');
            })
            ->where('sales.waytopay', 2)
            ->where('sales.typesale', 1) // Solo ventas finalizadas
            ->select(
                'sales.id as idsale',
                'sales.date',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.tipoContribuyente AS client_contribuyente',
                'clients.comercial_name',
                'sales.id AS corr',
                'clients.tpersona',
                'clients.name_contribuyente',
                'companies.name as NameCompany',
                'sales.totalamount',
                'sales.state_credit',
                DB::raw('COALESCE(credits.current, sales.totalamount) as current_balance'),
                DB::raw('COALESCE(credits.date_pay, NULL) as last_payment_date'),
                DB::raw('(CASE
                    WHEN sales.state_credit = 1 THEN "PAGADO"
                    WHEN COALESCE(credits.current, sales.totalamount) <= 0 THEN "PAGADO"
                    ELSE "PENDIENTE"
                END) AS state_credit_display')
            )
            ->orderBy('sales.date', 'desc')
            ->get();

        // Debug temporal - remover después de verificar
        \Log::info('🔍 Créditos encontrados:', [
            'count' => $credit->count(),
            'first_record' => $credit->first() ? $credit->first()->toArray() : null
        ]);

        return view('credits.index', array(
            "credits" => $credit
        ));
    }

    public function getinfocredit($idcredit) {
        $saleId = base64_decode($idcredit);
        $findcredit = Credit::where('credits.sale_id', '=', $saleId)->latest()->first();
        $findsale = Sale::find($saleId);

        if (!$findsale) {
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }

        // Calcular el saldo pendiente
        if ($findcredit) {
            // Si hay registros de crédito, usar el saldo actual del último registro
            $saldo = $findcredit->current;
        } else {
            // Si no hay registros de crédito, el saldo es el total de la venta
            $saldo = $findsale->totalamount;
        }

        return response()->json($saldo);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addpay(Request $request){
        DB::beginTransaction();

        try {
            // Validar datos de entrada
            $request->validate([
                'idsale' => 'required|exists:sales,id',
                'amountpay' => 'required|numeric|min:0.01'
            ]);

            $sale = Sale::find($request->idsale);
            if (!$sale) {
                throw new \Exception('Venta no encontrada');
            }

            // Verificar que la venta sea a crédito
            if ($sale->waytopay != 2) {
                throw new \Exception('Esta venta no es a crédito');
            }

            // Obtener el último registro de crédito
            $lastCredit = Credit::where('credits.sale_id', '=', $request->idsale)
                               ->orderBy('id', 'desc')
                               ->first();

            // Calcular el saldo actual
            $currentBalance = $lastCredit ? $lastCredit->current : $sale->totalamount;

            // Validar que el pago no exceda el saldo
            if ($request->amountpay > $currentBalance) {
                throw new \Exception('El monto a pagar no puede ser mayor al saldo pendiente');
            }

            // Calcular nuevo saldo
            $newBalance = $currentBalance - $request->amountpay;

            // Crear nuevo registro de pago
            $addcredit = new Credit();
            $addcredit->sale_id = $request->idsale;
            $addcredit->date_pay = now();
            $addcredit->current = $newBalance;
            $addcredit->initial = $sale->totalamount;
            $addcredit->amountpay = $request->amountpay;
            $addcredit->user_id = auth()->user()->id;
            $addcredit->save();

            // Si el saldo queda en 0, marcar la venta como pagada
            if ($newBalance <= 0) {
                $sale->state_credit = 1;
                $sale->save();
            }

            DB::commit();

            return redirect()->route('credit.index')
                           ->with('success', 'Pago registrado correctamente. Saldo restante: $' . number_format($newBalance, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('credit.index')
                           ->with('error', 'Error al procesar el pago: ' . $e->getMessage());
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
     * @param  \App\Models\Credit  $credit
     * @return \Illuminate\Http\Response
     */
    public function show(Credit $credit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Credit  $credit
     * @return \Illuminate\Http\Response
     */
    public function edit(Credit $credit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Credit  $credit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Credit $credit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Credit  $credit
     * @return \Illuminate\Http\Response
     */
    public function destroy(Credit $credit)
    {
        //
    }
}
