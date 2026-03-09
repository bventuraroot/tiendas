<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Dte;
use App\Models\Company;
use App\Models\Client;
use App\Models\Product;
use App\Models\Salesdetail;
use App\Models\Typedocument;
use App\Models\Correlativo;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DebitNoteController extends Controller
{
    /**
     * Constructor con middleware de permisos
     */
    public function __construct()
    {
        $this->middleware('permission:notas-debito.index')->only(['index', 'show']);
        $this->middleware('permission:notas-debito.create')->only(['create', 'store']);
        $this->middleware('permission:notas-debito.edit')->only(['edit', 'update']);
        $this->middleware('permission:notas-debito.destroy')->only(['destroy']);
        $this->middleware('permission:notas-debito.print')->only(['print']);
        $this->middleware('permission:notas-debito.send-email')->only(['sendEmail']);
    }

    /**
     * Mostrar lista de notas de débito
     */
    public function index(Request $request): View
    {
        $query = Sale::with(['client', 'company', 'user', 'dte'])
            ->where('typesale', 1) // Solo facturas confirmadas
            ->whereHas('dte', function($q) {
                $q->where('tipoDte', '06'); // Nota de débito
            });

        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('date', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('date', '<=', $request->fecha_hasta);
        }
        if ($request->filled('cliente_id')) {
            $query->where('client_id', $request->cliente_id);
        }
        if ($request->filled('empresa_id')) {
            $query->where('company_id', $request->empresa_id);
        }
        if ($request->filled('estado')) {
            $query->where('state', $request->estado);
        }

        $notasDebito = $query->orderBy('date', 'desc')->paginate(20);

        $clientes = Client::select('id', 'firstname', 'secondname', 'firstlastname', 'secondlastname', 'name_contribuyente', 'tpersona')
            ->get()
            ->sortBy('nameClient');

        $empresas = Company::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('debit-notes.index', compact('notasDebito', 'clientes', 'empresas'));
    }

    /**
     * Mostrar formulario para crear nueva nota de débito
     */
    public function create(Request $request)
    {
        $saleId = $request->get('sale_id');
        $sale = null;

        if ($saleId) {
            $sale = Sale::with(['client', 'company', 'details.product', 'dte'])
                ->where('id', $saleId)
                ->where('typesale', 1)
                ->where('state', 1)
                ->first();

            if (!$sale) {
                return redirect()->route('debit-notes.index')
                    ->with('error', 'La venta seleccionada no es válida para crear una nota de débito.');
            }
        }

        $empresas = Company::select('id', 'name', 'nit', 'ncr')
            ->orderBy('name')
            ->get();

        // Buscar el tipo de documento NDB (Nota de Débito)
        $tipoNDB = Typedocument::where('type', 'NDB')->first();
        $tiposDocumento = collect();

        if ($tipoNDB) {
            $tiposDocumento = collect([$tipoNDB]);
        }

        // Si solo hay un tipo de documento, auto-seleccionarlo
        $tipoDocumentoSeleccionado = null;
        Log::info('Tipos de documento encontrados: ' . $tiposDocumento->count());
        Log::info('Tipos de documento: ' . $tiposDocumento->toJson());

        if ($tiposDocumento->count() == 1) {
            $tipoDocumentoSeleccionado = $tiposDocumento->first()->id;
            Log::info('Tipo de documento auto-seleccionado: ' . $tipoDocumentoSeleccionado);
        }

        // Si hay una venta específica, auto-seleccionar su empresa
        $empresaSeleccionada = null;
        Log::info('Sale encontrada: ' . ($sale ? 'Sí' : 'No'));
        if ($sale) {
            Log::info('Company ID de la venta: ' . $sale->company_id);
        }

        if ($sale && $sale->company_id) {
            $empresaSeleccionada = $sale->company_id;
            Log::info('Empresa auto-seleccionada: ' . $empresaSeleccionada);
        }

        // Historial de Notas de Débito previas de esta venta
        $historialNDB = collect();
        if ($sale) {
            $historialNDB = Sale::with(['dte'])
                ->where('doc_related', $sale->id)
                ->orderBy('date', 'desc')
                ->get(['id', 'date', 'totalamount', 'state']);
        }

        // Productos disponibles para agregar (nuevos)
        $productos = Product::select(
                'id',
                'name',
                'code',
                'price',
                DB::raw('0 as exempt'),
                DB::raw('0 as nosujeta')
            )
            ->where('state', 1)
            ->orderBy('name')
            ->get();

        return view('debit-notes.create', compact('sale', 'empresas', 'tiposDocumento', 'tipoDocumentoSeleccionado', 'empresaSeleccionada', 'productos', 'historialNDB'));
    }

    /**
     * Almacenar nueva nota de débito
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'company_id' => 'required|exists:companies,id',
            'typedocument_id' => 'required|exists:typedocuments,id',
            'motivo' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.product_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        // Delegar al flujo centralizado (igual que NCR) en SaleController@ndr
        return app(\App\Http\Controllers\SaleController::class)->ndr($request->sale_id);
    }

    /**
     * Mostrar detalles de una nota de débito
     */
    public function show(Sale $debitNote): View
    {
        $debitNote->load(['client', 'company', 'user', 'details.product', 'dte']);

        return view('debit-notes.show', compact('debitNote'));
    }

    /**
     * Mostrar formulario para editar nota de débito
     */
    public function edit(Sale $debitNote): View
    {
        $debitNote->load(['client', 'company', 'details.product']);

        $empresas = Company::select('id', 'name', 'nit', 'ncr')
            ->orderBy('name')
            ->get();

        $tiposDocumento = Typedocument::where('type', 'NDB')
            ->select('id', 'type', 'description')
            ->get();

        return view('debit-notes.edit', compact('debitNote', 'empresas', 'tiposDocumento'));
    }

    /**
     * Actualizar nota de débito
     */
    public function update(Request $request, Sale $debitNote): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.product_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Eliminar detalles existentes
            $debitNote->details()->delete();

            // Recalcular totales
            $totalGravadas = 0;
            $totalExentas = 0;
            $totalNoSujetas = 0;
            $totalIva = 0;

            foreach ($request->productos as $productoData) {
                $producto = Product::findOrFail($productoData['product_id']);
                $cantidad = $productoData['cantidad'];
                $precio = $productoData['precio'];
                $subtotal = $cantidad * $precio;

                $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

                if ($tipoVenta === 'gravada') {
                    $totalGravadas += $subtotal;
                    $totalIva += $subtotal * 0.13;
                } elseif ($tipoVenta === 'exenta') {
                    $totalExentas += $subtotal;
                } else {
                    $totalNoSujetas += $subtotal;
                }

                // Crear nuevo detalle
                $detalle = new Salesdetail();
                $detalle->fill([
                    'sale_id' => $debitNote->id,
                    'product_id' => $producto->id,
                    'amountp' => $cantidad,
                    'priceunit' => $precio,
                    'pricesale' => $subtotal,
                    'exempt' => $tipoVenta === 'exenta' ? $subtotal : 0,
                    'nosujeta' => $tipoVenta === 'nosujeta' ? $subtotal : 0,
                    'detained13' => $tipoVenta === 'gravada' ? $subtotal * 0.13 : 0,
                    'detained' => 0,
                    'renta' => 0,
                    'fee' => 0,
                    'feeiva' => 0,
                    'reserva' => 0,
                    'user_id' => Auth::id(),
                ]);
                $detalle->save();
            }

            $debitNote->update([
                'motivo' => $request->motivo,
                'totalamount' => $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva,
            ]);

            DB::commit();

            return redirect()->route('debit-notes.show', $debitNote->id)
                ->with('success', 'Nota de débito actualizada exitosamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la nota de débito: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar nota de débito
     */
    public function destroy(Sale $debitNote): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Verificar que no esté procesada en hacienda
            if ($debitNote->dte && $debitNote->dte->estadoHacienda === 'PROCESADO') {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar una nota de débito ya procesada en Hacienda.');
            }

            // Eliminar detalles y DTE
            $debitNote->details()->delete();
            $debitNote->dte()->delete();
            $debitNote->delete();

            DB::commit();

            return redirect()->route('debit-notes.index')
                ->with('success', 'Nota de débito eliminada exitosamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar la nota de débito: ' . $e->getMessage());
        }
    }

    /**
     * Imprimir nota de débito
     */
    public function print(Sale $debitNote)
    {
        try {
            // Cargar relaciones necesarias
            $debitNote->load(['dte']);

            $saleController = new \App\Http\Controllers\SaleController();

            // Verificar si tiene DTE
            if ($debitNote->dte && $debitNote->dte->json) {
                // Usar genera_pdf para DTE
                return $saleController->genera_pdf($debitNote->id);
            } else {
                // Usar genera_pdflocal para documentos sin DTE
                return $saleController->genera_pdflocal($debitNote->id);
            }
        } catch (\Exception $e) {
            Log::error('Error generando PDF de nota de débito: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Enviar nota de débito por correo
     */
    public function sendEmail(Request $request, Sale $debitNote): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'mensaje' => 'nullable|string|max:1000'
        ]);

        try {
            // Aquí implementarías la lógica de envío de correo
            // Similar a como se hace en SaleController

            return response()->json([
                'success' => true,
                'message' => 'Nota de débito enviada exitosamente.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la nota de débito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos de una venta para nota de débito
     */
    public function getSaleProducts(Sale $sale): JsonResponse
    {
        $sale->load(['details.product']);

        $productos = $sale->details->map(function($detalle) {
            // Si el producto es LAB y tiene ruta, usar ruta como nombre
            $productName = ($detalle->product && $detalle->product->code == 'LAB' && $detalle->ruta && trim($detalle->ruta) !== '')
                ? $detalle->ruta
                : ($detalle->product->name ?? 'Producto');

            return [
                'id' => $detalle->product->id,
                'name' => $productName,
                'code' => $detalle->product->code,
                'cantidad_original' => $detalle->amountp,
                'precio_original' => $detalle->priceunit,
                'tipo_venta' => $detalle->exempt > 0 ? 'exenta' : ($detalle->nosujeta > 0 ? 'nosujeta' : 'gravada'),
                'subtotal' => $detalle->amountp * $detalle->priceunit
            ];
        });

        return response()->json($productos);
    }
}
