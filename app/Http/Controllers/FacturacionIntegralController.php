<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Salesdetail;
use App\Models\Inventory;
use App\Models\Appointment;
use App\Models\MedicalConsultation;
use App\Models\LabOrder;
use App\Models\LabOrderExam;
use App\Models\Client;
use App\Models\Patient;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class FacturacionIntegralController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sale.index')->only(['index']);
        $this->middleware('permission:sale.create')->only(['create', 'store']);
    }

    /**
     * Vista principal de facturación integral
     */
    public function index(Request $request)
    {
        $tipo = $request->get('tipo', 'farmacia');

        // Estadísticas generales
        $ventasHoy = Sale::whereDate('date', Carbon::today())->count();
        $totalHoy = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereDate('sales.date', Carbon::today())
            ->sum('salesdetails.pricesale') ?: 0;

        // Las consultas médicas NO se facturan - solo control clínico
        $consultasPorFacturar = collect([]);

        // Órdenes de laboratorio por facturar (pueden estar desde pendiente, excluyendo canceladas y ya facturadas)
        // Excluir órdenes que ya tienen una venta asociada
        $productLabId = Product::where('code', 'LAB')->value('id');

        // Obtener IDs de órdenes que ya fueron facturadas
        // Buscar ventas que tengan el ID de la orden en acuenta
        $ordenesFacturadasIds = [];

        if ($productLabId) {
            // Si existe el producto LAB, buscar ventas con detalles de ese producto
            $ordenesFacturadasIds = Sale::whereHas('details', function($query) use ($productLabId) {
                    $query->where('product_id', $productLabId);
                })
                ->whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
                    // Extraer el ID de la orden del campo acuenta
                    if (preg_match('/LAB_ORDER_ID:(\d+)/', $sale->acuenta, $matches)) {
                        return (int)$matches[1];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        } else {
            // Si no existe el producto LAB, buscar solo por el patrón en acuenta
            $ordenesFacturadasIds = Sale::whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
                    // Extraer el ID de la orden del campo acuenta
                    if (preg_match('/LAB_ORDER_ID:(\d+)/', $sale->acuenta, $matches)) {
                        return (int)$matches[1];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        // Permitir facturar desde cualquier estado excepto cancelada
        // Excluir órdenes ya facturadas
        $ordenesLabPorFacturar = LabOrder::with(['patient', 'doctor', 'exams.exam'])
            ->where('estado', '!=', 'cancelada')
            ->whereNotIn('id', $ordenesFacturadasIds)
            // Mostrar siempre primero las órdenes más nuevas
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        // Productos de farmacia (para venta directa) - Usar Inventory en lugar de Product
        $productos = Inventory::with('product')
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->limit(100)
            ->get()
            ->map(function($inventory) {
                return [
                    'id' => $inventory->product_id,
                    'name' => $inventory->product->name ?? 'Sin nombre',
                    'quantity' => $inventory->quantity,
                    'price' => $inventory->product->pricesale ?? 0,
                    'product' => $inventory->product
                ];
            });

        return view('facturacion.integral', compact(
            'tipo',
            'ventasHoy',
            'totalHoy',
            'consultasPorFacturar',
            'ordenesLabPorFacturar',
            'productos'
        ));
    }

    /**
     * Obtener consultas médicas pendientes de facturar
     * NOTA: Las consultas médicas NO se facturan - solo control clínico
     */
    public function getConsultasPendientes()
    {
        // Las consultas médicas no se facturan
        return response()->json([]);
    }

    /**
     * Obtener órdenes de laboratorio pendientes de facturar
     */
    public function getOrdenesLabPendientes()
    {
        // Obtener IDs de órdenes que ya fueron facturadas
        $productLabId = Product::where('code', 'LAB')->value('id');

        $ordenesFacturadasIds = [];

        if ($productLabId) {
            // Si existe el producto LAB, buscar ventas con detalles de ese producto
            $ordenesFacturadasIds = Sale::whereHas('details', function($query) use ($productLabId) {
                    $query->where('product_id', $productLabId);
                })
                ->whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
                    // Extraer el ID de la orden del campo acuenta
                    if (preg_match('/LAB_ORDER_ID:(\d+)/', $sale->acuenta, $matches)) {
                        return (int)$matches[1];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        } else {
            // Si no existe el producto LAB, buscar solo por el patrón en acuenta
            $ordenesFacturadasIds = Sale::whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
                    // Extraer el ID de la orden del campo acuenta
                    if (preg_match('/LAB_ORDER_ID:(\d+)/', $sale->acuenta, $matches)) {
                        return (int)$matches[1];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        // Permitir facturar desde cualquier estado excepto cancelada
        // Excluir órdenes ya facturadas
        $ordenes = LabOrder::with(['patient', 'doctor', 'exams.exam'])
            ->where('estado', '!=', 'cancelada')
            ->whereNotIn('id', $ordenesFacturadasIds)
            // Mostrar siempre primero las órdenes más nuevas
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($ordenes);
    }

    /**
     * Crear factura desde consulta médica
     * NOTA: Las consultas médicas NO se facturan - solo control clínico
     */
    public function facturarConsulta(Request $request, $consultaId)
    {
        return response()->json([
            'success' => false,
            'message' => 'Las consultas médicas no se facturan. El módulo de clínica es solo para control de citas, pacientes y consultas médicas.'
        ], 403);
    }

    /**
     * Crear factura desde orden de laboratorio
     */
    public function facturarOrdenLab(Request $request, $ordenId)
    {
        DB::beginTransaction();

        try {
            $orden = LabOrder::with(['patient', 'exams.exam', 'company'])->findOrFail($ordenId);

            // Validar que la orden no esté cancelada
            if ($orden->estado === 'cancelada') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede facturar una orden cancelada'
                ], 400);
            }

            // Validar que la orden no esté ya facturada
            $productLabId = Product::where('code', 'LAB')->value('id');
            $ordenYaFacturada = false;

            if ($productLabId) {
                $ordenYaFacturada = Sale::whereHas('details', function($query) use ($productLabId) {
                        $query->where('product_id', $productLabId);
                    })
                    ->whereNotNull('acuenta')
                    ->where('acuenta', 'like', '%LAB_ORDER_ID:' . $orden->id . '%')
                    ->exists();
            } else {
                $ordenYaFacturada = Sale::whereNotNull('acuenta')
                    ->where('acuenta', 'like', '%LAB_ORDER_ID:' . $orden->id . '%')
                    ->exists();
            }

            if ($ordenYaFacturada) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta orden ya ha sido facturada anteriormente'
                ], 400);
            }

            // Usar "Clientes Varios" como cliente por defecto para facturas de laboratorio
            $client = Client::where('comercial_name', 'Clientes Varios')
                ->orWhere('firstname', 'Clientes')
                ->where('firstlastname', 'Varios')
                ->first();

            if (!$client) {
                // Crear cliente "Clientes Varios" si no existe
                $client = Client::create([
                    'firstname' => 'Clientes',
                    'firstlastname' => 'Varios',
                    'comercial_name' => 'Clientes Varios',
                    'nit' => '00000000-0',
                    'tpersona' => 'J', // Persona jurídica
                    'email' => '',
                    'tel1' => '',
                    'address' => '',
                ]);
            }

            // Buscar o crear producto con código LAB
            $productLab = Product::where('code', 'LAB')->first();

            if (!$productLab) {
                // Obtener proveedor y marca por defecto
                $defaultProvider = \App\Models\Provider::first();
                $defaultMarca = \App\Models\Marca::first();

                // Crear producto LAB si no existe
                $productData = [
                    'code' => 'LAB',
                    'name' => 'Servicio de Laboratorio',
                    'description' => 'Servicios de laboratorio clínico',
                    'price' => '0', // Precio base 0, se usará el precio del examen
                    'state' => 1,
                    'cfiscal' => 'SERV', // Código fiscal para servicios
                    'type' => 'SERVICIO',
                ];

                // Agregar campos opcionales solo si existen
                if ($defaultProvider) {
                    $productData['provider_id'] = $defaultProvider->id;
                }
                if ($defaultMarca) {
                    $productData['marca_id'] = $defaultMarca->id;
                }

                $productLab = Product::create($productData);
            }

            // Obtener tipo de documento por defecto (Factura = 6)
            $typedocumentId = $request->get('typedocument_id', 6);

            // Crear draft de venta (typesale = 2 es borrador)
            // Guardar el ID de la orden en acuenta para poder rastrear qué orden fue facturada
            $acuenta = ($orden->patient->nombre_completo ?? 'Cliente de Laboratorio') . ' - ' .
                      ($orden->patient->documento_identidad ?? '') .
                      ' | LAB_ORDER_ID:' . $orden->id;

            $sale = Sale::create([
                'client_id' => $client->id,
                'user_id' => auth()->id(),
                'company_id' => $orden->company_id,
                'typedocument_id' => $typedocumentId,
                'date' => now(),
                'state' => 1,
                'typesale' => 2, // Borrador de venta
                'totalamount' => 0, // Se calculará después
                'waytopay' => 1, // Forma de pago por defecto
                'acuenta' => $acuenta,
            ]);

            // Agregar cada examen como una línea separada en la factura
            // Si la orden tiene múltiples exámenes, cada uno será una línea independiente
            $totalVenta = 0;
            foreach ($orden->exams as $orderExam) {
                $exam = $orderExam->exam;

                // Validar que el examen existe
                if (!$exam) {
                    continue; // Saltar si el examen no existe
                }

                // Usar directamente el precio que viene del módulo de laboratorio sin calcular IVA
                $precio = $orderExam->precio ?? $exam->precio ?? 0;

                // Crear una nueva línea de detalle de venta para cada examen
                $saleDetail = new Salesdetail();
                $saleDetail->sale_id = $sale->id;
                $saleDetail->product_id = $productLab->id;
                $saleDetail->unit_id = 28; // Unidad por defecto para exámenes de laboratorio
                // Obtener el nombre de la unidad si existe
                $unit = Unit::find(28);
                $saleDetail->unit_name = $unit ? $unit->unit_name : 'Unidad';
                $saleDetail->amountp = 1; // Cantidad siempre 1 por examen
                $saleDetail->priceunit = $precio; // Usar precio directo del módulo de laboratorio
                $saleDetail->pricesale = $precio; // Usar precio directo del módulo de laboratorio
                $saleDetail->nosujeta = 0;
                $saleDetail->exempt = 0;
                $saleDetail->detained13 = 0; // No calcular IVA, usar precio tal cual viene
                $saleDetail->detained = 0;
                $saleDetail->renta = 0;
                $saleDetail->fee = 0;
                $saleDetail->feeiva = 0;
                $saleDetail->user_id = auth()->id();

                // Guardar el nombre del examen en el campo 'ruta' para usarlo como descripción personalizada
                // Siempre anteponer la palabra "Examen" al nombre del examen
                $saleDetail->ruta = 'Examen ' . $exam->nombre; // Guardar nombre del examen aquí

                $saleDetail->save();

                $totalVenta += $precio;
            }

            // Actualizar total de la venta
            $sale->totalamount = round($totalVenta, 2);
            $sale->save();

            DB::commit();

            // Generar URL de redirección con el mismo formato que sales.index
            $redirectUrl = route('sale.create-dynamic', [
                'corr' => $sale->id,
                'draft' => 'true',
                'typedocument' => $typedocumentId,
                'operation' => 'edit'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft de factura creado exitosamente',
                'sale_id' => $sale->id,
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al facturar orden de laboratorio: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'orden_id' => $ordenId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el draft de factura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar número de factura único
     */
    private function generateInvoiceNumber()
    {
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $nextNumber = $lastSale ? ($lastSale->id + 1) : 1;
        return 'FAC-' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener precio de servicio (consulta o examen)
     */
    public function getPrecioServicio(Request $request)
    {
        $tipo = $request->get('tipo'); // 'consulta' o 'laboratorio'
        $id = $request->get('id');

        if ($tipo === 'consulta') {
            // Las consultas médicas no se facturan
            return response()->json(['precio' => 0]);
        }

        if ($tipo === 'laboratorio') {
            $orden = LabOrder::with('exams.exam')->find($id);
            return response()->json(['precio' => $orden->total]);
        }

        return response()->json(['precio' => 0]);
    }
}

