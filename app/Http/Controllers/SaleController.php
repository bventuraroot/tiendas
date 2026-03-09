<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\Client;
use App\Models\Company;
use App\Models\Dte;
use App\Models\Sale;
use App\Models\Config;
use App\Models\Salesdetail;
use App\Models\Product;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Mail\EnviarCorreo;
use App\Mail\EnviarFacturaOffline;
use App\Models\Correlativo;
use App\Models\InventoryMovement;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use App\Services\UnitConversionService;
use App\Traits\ErrorHandler;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    use ErrorHandler;

    protected $unitConversionService;

    public function __construct()
    {
        $this->unitConversionService = new UnitConversionService();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id_user = auth()->user()->id;
        // Consultar el rol del usuario (asumiendo que el rol de admin tiene role_id = 1 y ventas tiene role_id = 3)
        $rolQuery = "SELECT a.role_id FROM model_has_roles a WHERE a.model_id = ?";
        $rolResult = DB::select($rolQuery, [$id_user]);
        $roleId = !empty($rolResult) ? $rolResult[0]->role_id : null;
        $isAdmin = $roleId == 1;
        $isVentas = $roleId == 3;
        // Los usuarios admin y de ventas pueden ver todas las facturas
        $canViewAllSales = $isAdmin || $isVentas;

        $sales = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->leftjoin('dte', function($join) {
                $join->on('dte.sale_id', '=', 'sales.id')
                     ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = sales.id)');
            })
            ->select(
                'sales.*',
                'typedocuments.description AS document_name',
                'typedocuments.type AS document_type',
                'clients.firstname',
                'clients.firstlastname',
                'clients.name_contribuyente as nameClient',
                'clients.tpersona',
                'clients.email as mailClient',
                'companies.name AS company_name',
                'dte.tipoDte',
                'dte.estadoHacienda',
                'dte.id_doc',
                'dte.company_name',
                DB::raw('(SELECT dee.descriptionMessage FROM dte dee WHERE dee.id_doc_Ref2=sales.id) AS relatedSale'),
                DB::raw('CASE
                    WHEN sales.totalamount IS NULL OR sales.totalamount = 0 THEN
                        COALESCE((SELECT SUM(sd.nosujeta + sd.exempt + sd.pricesale + sd.detained13 - sd.renta - sd.detained)
                                 FROM salesdetails sd WHERE sd.sale_id = sales.id), 0)
                    ELSE sales.totalamount
                END AS calculated_total'),
                DB::raw('(SELECT COUNT(*) FROM sales ncr
                         INNER JOIN typedocuments tdncr ON ncr.typedocument_id = tdncr.id
                         WHERE ncr.doc_related = sales.id AND tdncr.type = "NCR") AS tiene_nota_credito'),
                DB::raw('(SELECT COUNT(*) FROM sales ndb
                         INNER JOIN typedocuments tdndb ON ndb.typedocument_id = tdndb.id
                         WHERE ndb.doc_related = sales.id AND tdndb.type = "NDB") AS tiene_nota_debito'),
                DB::raw('"" AS notas_credito'),
                DB::raw('"" AS notas_debito')
            );

        // Si no es admin ni usuario de ventas, solo muestra las facturas creadas por él
        if (!$canViewAllSales) {
            $sales->where('sales.user_id', $id_user);
        }

        // Mostrar todas las ventas (incluyendo preventas y borradores de factura)

        // Aplicar filtros
        if ($request->filled('fecha_desde')) {
            $sales->whereDate('sales.date', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $sales->whereDate('sales.date', '<=', $request->fecha_hasta);
        }

        if ($request->filled('tipo_documento')) {
            $sales->where('sales.typedocument_id', $request->tipo_documento);
        }

        if ($request->filled('correlativo')) {
            $sales->where(function($query) use ($request) {
                $query->where('sales.id', 'like', '%' . $request->correlativo . '%')
                      ->orWhere('dte.id_doc', 'like', '%' . $request->correlativo . '%');
            });
        }

        if ($request->filled('cliente_id')) {
            $sales->where('sales.client_id', $request->cliente_id);
        }

        // Obtener las ventas filtradas con DISTINCT para evitar duplicados
        // Ordenar por fecha de venta (date) descendente
        $sales = $sales->distinct()->orderBy('sales.date', 'desc')->orderBy('sales.created_at', 'desc')->get();


        // Obtener datos para los filtros
        $tiposDocumento = DB::table('typedocuments')->select('id', 'description')->get();
        $clientes = Client::select('id', 'firstname', 'firstlastname', 'name_contribuyente', 'comercial_name', 'tpersona')->get();

        return view('sales.index', compact('sales', 'tiposDocumento', 'clientes'));
    }

    public function impdoc($corr)
    {
        return view('sales.impdoc', array("corr" => $corr));
    }

    /**
     * Generar ticket de 80mm para impresión
     */
    public function printTicket(Request $request, $id)
    {
        try {


            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta con todas las relaciones necesarias incluyendo DTE
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca',
                'dte'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Calcular totales de forma segura
            $subtotal = 0;
            $totalIva = 0;
            $total = 0;
            foreach ($sale->details as $detail) {
                $subtotal += $detail->pricesale + $detail->nosujeta + $detail->exempt;
                $totalIva += $detail->detained13;
            }

            $total = $subtotal + $totalIva;

            // Verificar si debe auto-imprimir
            $autoprint = $request->query('autoprint', 'true') !== 'false';

            // Determinar qué vista usar basado en el tipo de documento
            $hasDte = $sale->hasDte();

            // Para facturas y créditos fiscales, siempre usar la vista minimal DTE
            $isFacturaOrCredito = false;
            if ($sale->typedocument) {
                $tipoDoc = strtolower($sale->typedocument->description ?? '');
                $isFacturaOrCredito = str_contains($tipoDoc, 'factura') ||
                                    str_contains($tipoDoc, 'crédito') ||
                                    str_contains($tipoDoc, 'credito') ||
                                    str_contains($tipoDoc, 'fiscal');
            }

            // Lógica simplificada: siempre usar ticket.blade.php (ya tiene DTE integrado)
            $view = 'sales.ticket';


            // Generar código QR si tiene DTE
            $qrCode = '';
            if ($hasDte && $sale->dte) {
                try {
                    if (function_exists('codigoQR')) {
                        $ambiente = $sale->dte->ambiente_id ?? '00';
                        $codigoGeneracion = $sale->dte->codigoGeneracion ?? '';
                        $fecha = $sale->dte->fhRecibido ?? $sale->date;

                        if ($codigoGeneracion) {
                            $qrCode = codigoQR($ambiente, $codigoGeneracion, $fecha);
                        }
                    }
                } catch (\Exception $e) {
                    $qrCode = '';
                }
            }

            return view($view, compact('sale', 'subtotal', 'totalIva', 'total', 'autoprint', 'hasDte', 'qrCode', 'isFacturaOrCredito'));

        } catch (\Exception $e) {


            // Si es una petición AJAX, devolver JSON con información detallada
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al generar el ticket: ' . $e->getMessage(),
                    'sale_id' => $id
                ], 500);
            }

            // Si no es AJAX, mostrar error en lugar de redirigir
            return response("Error al generar el ticket para la venta ID {$id}: " . $e->getMessage(), 500);
        }
    }

    /**
     * Método de prueba para verificar tickets
     */
    public function testTicket($id = 1)
    {
        try {
            $sale = Sale::with(['client', 'company', 'typedocument', 'details', 'details.product', 'dte'])->find($id);

            if (!$sale) {
                return response("Venta con ID {$id} no encontrada", 404);
            }

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'typedocument' => $sale->typedocument ? $sale->typedocument->description : 'N/A',
                'has_dte' => $sale->hasDte(),
                'dte_id' => $sale->dte ? $sale->dte->id : 'N/A',
                'ticket_url' => route('sale.ticket', $id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de impresoras del servidor (si está disponible)
     */
    public function getPrinterInfo()
    {
        try {
            $printerInfo = [
                'server_printers' => [],
                'recommendations' => [
                    'width' => '80mm',
                    'type' => 'thermal',
                    'margin' => '0mm'
                ],
                'common_80mm_printers' => [
                    'Epson TM-T88V',
                    'Epson TM-T88VI',
                    'Star TSP650II',
                    'Bixolon SRP-350plusIII',
                    'Citizen CT-S310A',
                    'POS-80 Series'
                ]
            ];

            // En sistemas Windows, podrías intentar ejecutar comandos del sistema
            // para obtener información de impresoras (requiere permisos especiales)
            if (PHP_OS_FAMILY === 'Windows') {
                $printerInfo['os'] = 'Windows';
                $printerInfo['note'] = 'Use las propiedades de impresora en Windows para configurar papel de 80mm';
            } else {
                $printerInfo['os'] = PHP_OS_FAMILY;
                $printerInfo['note'] = 'Configure su impresora térmica como predeterminada del sistema';
            }

            return response()->json($printerInfo);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener información de impresoras del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar ticket en formato ESC/POS RAW para impresoras térmicas
     */
    public function printTicketRaw(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Generar contenido RAW ESC/POS
            $escpos = $this->generateESCPOS($sale);

            // Devolver como archivo de descarga que se puede enviar directamente a la impresora
            return response($escpos)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="ticket_' . $id . '.prn"')
                ->header('Cache-Control', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al generar ticket RAW: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar comandos ESC/POS para impresora térmica
     */
    private function generateESCPOS($sale, $cutPaper = true)
    {
        $esc = "\x1B";  // ESC
        $gs = "\x1D";   // GS

        $escpos = "";

        // Inicializar impresora
        $escpos .= $esc . "@";  // Reset

        // Deshabilitar corte automático de papel
        $escpos .= $gs . "V\x00";  // Deshabilitar corte automático

        // Configurar papel de 80mm
        $escpos .= $esc . "D\x02\x05\x08\x00";  // Tabs

        // Centrar y nombre de empresa
        $escpos .= $esc . "a\x01";  // Centrar
        $escpos .= $esc . "!\x30";  // Doble alto y ancho
        $escpos .= ($sale->company->name ?? 'EMPRESA') . "\n";
        $escpos .= $esc . "!\x00";  // Tamaño normal

        // Información de empresa
        if ($sale->company->address) {
            $escpos .= $sale->company->address . "\n";
        }
        if ($sale->company->phone) {
            $escpos .= "Tel: " . $sale->company->phone . "\n";
        }

        // Línea separadora
        $escpos .= str_repeat("-", 48) . "\n";

        // Alinear a la izquierda
        $escpos .= $esc . "a\x00";

        // Info del ticket
        $escpos .= "TICKET #" . $sale->id . "\n";
        $escpos .= "Fecha: " . $sale->created_at->format('d/m/Y H:i:s') . "\n";
        $escpos .= "Cliente: " . ($sale->client->firstname ?? 'CLIENTE GENERAL') . "\n";

        $escpos .= str_repeat("-", 48) . "\n";

        // Productos
        $subtotal = 0;
        $totalIva = 0;

        foreach ($sale->details as $detail) {
            $subtotal += $detail->pricesale + $detail->nosujeta + $detail->exempt;
            $totalIva += $detail->detained13;

            $name = ($detail->product->name ?? 'Producto') . ' ' . ($detail->product->marca->name ?? '');
            if (strlen($name) > 32) {
                $name = substr($name, 0, 32);
            }

            $escpos .= $name . "\n";
            $escpos .= sprintf("  %d x $%.2f = $%.2f\n",
                $detail->amountp ?? 1,
                $detail->priceunit ?? 0,
                $detail->pricesale ?? 0
            );
        }

        $escpos .= str_repeat("-", 48) . "\n";

        // Totales
        $escpos .= sprintf("%-30s $%.2f\n", "Subtotal:", $subtotal);
        $escpos .= sprintf("%-30s $%.2f\n", "IVA:", $totalIva);
        $escpos .= str_repeat("=", 48) . "\n";

        // Total en grande
        $escpos .= $esc . "!\x20";  // Doble alto
        $escpos .= sprintf("TOTAL: $%.2f\n", $subtotal + $totalIva);
        $escpos .= $esc . "!\x00";  // Normal

        // Footer
        $escpos .= "\n";
        $escpos .= $esc . "a\x01";  // Centrar
        $escpos .= "¡GRACIAS POR SU COMPRA!\n";
        $escpos .= "Conserve este ticket\n";

        // Espacios para separar del siguiente ticket
        $escpos .= "\n\n\n";

        // Avanzar papel sin cortar
        $escpos .= $esc . "J\x30";  // Avanzar 48 puntos (6mm)

        // Controlar el corte de papel según el parámetro
        if ($cutPaper) {
            // Solo cortar al final del ticket completo
            $escpos .= $gs . "V\x41\x00";  // Corte parcial (no total)
        } else {
            // Solo avanzar papel sin cortar
            $escpos .= $esc . "J\x60";  // Avanzar más papel (12mm)
        }

        return $escpos;
    }

    /**
     * Generar comandos ESC/POS para impresora térmica sin cortes automáticos
     */
    private function generateESCPOSNoCut($sale)
    {
        return $this->generateESCPOS($sale, false);
    }

    /**
     * Imprimir ticket directamente en impresora sin diálogos
     */
    public function printTicketDirectToprinter(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Generar contenido ESC/POS para impresión directa
            $escpos = $this->generateESCPOS($sale);

            // Intentar imprimir directamente en la impresora del sistema
            $printResult = $this->sendToPrinter($escpos, $id);

            if ($printResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket enviado a la impresora correctamente',
                    'printer' => $printResult['printer'] ?? 'Predeterminada'
                ]);
            } else {
                // Si falla, devolver el contenido para descargar
                return response($escpos)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="ticket_' . $id . '.prn"');
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al imprimir directamente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar datos directamente a la impresora del sistema
     */
    private function sendToPrinter($data, $ticketId)
    {
        try {
            // Detectar sistema operativo
            $os = PHP_OS_FAMILY;

            // Crear archivo temporal
            $tempFile = sys_get_temp_dir() . '/ticket_' . $ticketId . '_' . time() . '.prn';
            file_put_contents($tempFile, $data);

            $success = false;
            $printer = '';

            if ($os === 'Windows') {
                // Windows: Intentar imprimir con diferentes métodos
                $commands = [
                    'copy "' . $tempFile . '" LPT1:', // Puerto paralelo
                    'copy "' . $tempFile . '" PRN',   // Impresora predeterminada
                    'print "' . $tempFile . '"'       // Comando print
                ];

                foreach ($commands as $cmd) {
                    $output = [];
                    $returnVar = 0;
                    exec($cmd . ' 2>&1', $output, $returnVar);

                    if ($returnVar === 0) {
                        $success = true;
                        $printer = 'Windows - ' . explode(' ', $cmd)[0];
                        break;
                    }
                }

            } elseif ($os === 'Linux' || $os === 'Darwin') {
                // Linux/Mac: Usar lp command
                $commands = [
                    'lp "' . $tempFile . '"',                    // Impresora predeterminada
                    'lp -d "POS-80" "' . $tempFile . '"',        // Impresora específica
                    'cat "' . $tempFile . '" > /dev/usb/lp0',   // Puerto USB directo
                ];

                foreach ($commands as $cmd) {
                    $output = [];
                    $returnVar = 0;
                    exec($cmd . ' 2>&1', $output, $returnVar);

                    if ($returnVar === 0) {
                        $success = true;
                        $printer = 'Unix - lp';
                        break;
                    }
                }
            }

            // Limpiar archivo temporal
            @unlink($tempFile);

            return [
                'success' => $success,
                'printer' => $printer,
                'os' => $os
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar ticket en formato HTML para impresión directa sin preview
     */
    public function printTicketDirect(Request $request, $id)
    {
        try {
            // Verificar que el ID sea válido
            if (!$id || !is_numeric($id)) {
                throw new \Exception("ID de venta inválido: $id");
            }

            // Buscar la venta
            $sale = Sale::with([
                'client',
                'company',
                'typedocument',
                'details',
                'details.product',
                'details.product.marca'
            ])->find($id);

            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Crear un HTML optimizado para impresión directa
            $html = view('sales.ticket-direct', compact('sale'))->render();

            return response($html)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->header('Cache-Control', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al generar ticket directo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function savefactemp($idsale, $clientid, $productid, $cantidad, $price, $pricenosujeta, $priceexenta, $pricegravada, $ivarete13, $renta, $ivarete, $acuenta, $fpago, $fee, $reserva, $ruta, $destino, $linea, $canal, $unitCode = null, $unitId = null, $conversionFactor = null)
    {
        //\Log::info("savefactemp llamado con idsale: " . $idsale);

        DB::beginTransaction();

        try {
            $id_user = auth()->user()->id;
            //\Log::info("savefactemp: Usuario ID: " . $id_user);

            // Validar que el idsale no esté vacío
            if (!$idsale || $idsale == 'null' || $idsale == '') {
                throw new \Exception("ID de venta no válido: " . $idsale);
            }

            //\Log::info("savefactemp: Buscando venta con ID: " . $idsale);

            // Verificar si la venta existe en la base de datos
            $sale = Sale::find($idsale);
            //\Log::info("savefactemp: Resultado de búsqueda de venta: " . ($sale ? 'ENCONTRADA' : 'NO ENCONTRADA'));

            // Si no se encuentra, hacer un query adicional para verificar
            if (!$sale) {
                //\Log::info("savefactemp: Verificando existencia de venta con query directo");
                $saleExists = DB::table('sales')->where('id', $idsale)->first();
                //\Log::info("savefactemp: Query directo resultado: " . ($saleExists ? 'EXISTE' : 'NO EXISTE'));

                if ($saleExists) {
                    //\Log::info("savefactemp: Venta existe en DB pero Eloquent no la encuentra. Datos: " . json_encode($saleExists));
                }
            }

            // Validar que la venta existe
            if (!$sale) {
                throw new \Exception("No se encontró la venta con ID: " . $idsale);
            }

            // Validar que el cliente no esté vacío
            if (!$clientid || $clientid == 'null' || $clientid == '0') {
                throw new \Exception("Debe seleccionar un cliente válido");
            }

            $sale->client_id = $clientid;
            $sale->acuenta = $acuenta;
            $sale->waytopay = $fpago;
            $sale->save();
            //$iva_calculado = round($price/1.13,2);
            //$preciogravado = round($iva_calculado*$cantidad,2);
            //$ivafac = round($pricegravada-($pricegravada/1.13),2);
            //precio unitario
            //iva fac
            $ivafac = round($pricegravada - ($pricegravada / 1.13), 8);
            //precio gravado
            $pricegravadafac = round($pricegravada / 1.13, 8);
            //precio unitario evaluar si es gravada sino solamente es el precio unitario
            if ($pricegravada != "0.00") {
                $priceunitariofac = round($pricegravadafac / $cantidad, 3);
            } else {
                $priceunitariofac = round($price, 3);
            }
            if ($sale->typedocument_id == '8') {
                // Para Factura de Sujeto Excluido: usar precios sin IVA
                $priceunitariofac = round($price / 1.13, 8);
                $pricegravadafac = round($pricegravada / 1.13, 8);
            }
            //$ivarete13 = round($pricegravada * 0.13, 2);
            //$ivafac = round($pricegravada - ($pricegravada / 1.13), 2);
            if ($sale->typedocument_id == '8') {
                $ivafac = 0.00;
            }
            //iva al fee
            $feesiniva = round($fee / 1.13, 8);
            $ivafee = round($fee - $feesiniva, 8);
            $saledetails = new Salesdetail();
            $saledetails->sale_id = $idsale;
            $saledetails->product_id = $productid;
            $saledetails->amountp = $cantidad;
            //$saledetails->priceunit = ($sale->typedocument_id==6) ? round($iva_calculado,2) : $price;
            $saledetails->priceunit = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8' || $sale->typedocument_id == '3') ? round($priceunitariofac, 8) : $price;
            //$saledetails->pricesale = ($sale->typedocument_id==6) ? round($preciogravado,2) : $pricegravada;
            $saledetails->pricesale = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8' || $sale->typedocument_id == '3') ? round($pricegravadafac, 8) : $pricegravada;
            $saledetails->nosujeta = $pricenosujeta;
            $saledetails->exempt = $priceexenta;
            $saledetails->detained13 = ($sale->typedocument_id == '6' || $sale->typedocument_id == '8' || $sale->typedocument_id == '3') ? round($ivafac, 8) : $ivarete13;
            $saledetails->detained = $ivarete;
            $saledetails->renta = ($sale->typedocument_id != '8') ? round(0.00, 8) : round($renta * $cantidad, 8);
            $saledetails->fee = $feesiniva;
            $saledetails->feeiva = $ivafee;
            $saledetails->reserva = $reserva;
            $saledetails->ruta = $ruta;
            $saledetails->destino = $destino;
            $saledetails->linea = $linea;
            $saledetails->canal = $canal;
            $saledetails->user_id = $id_user;

            // Normalizar parámetros desde URL (pueden venir como string 'null')
            $unitCode = ($unitCode === 'null' || $unitCode === '') ? null : $unitCode;
            $unitId = ($unitId === 'null' || $unitId === '') ? null : $unitId;
            $conversionFactor = ($conversionFactor === 'null' || $conversionFactor === '') ? null : $conversionFactor;

            // Resolver faltantes por catálogo
            if (!$unitId && $unitCode) {
                $unitModel = Unit::where('unit_code', $unitCode)->first();
                if ($unitModel) { $unitId = $unitModel->id; }
            }
            if (!$conversionFactor && $unitCode) {
                $puc = ProductUnitConversion::getConversionByUnitCode($productid, $unitCode);
                if ($puc) { $conversionFactor = $puc->conversion_factor; }
            }

            // Guardar unidad de venta si se resolvió correctamente
            if ($unitId && $conversionFactor) {
                $saledetails->unit_id = (int)$unitId;
                $saledetails->unit_name = $unitCode;
                $saledetails->conversion_factor = (float)$conversionFactor;
                $saledetails->base_quantity_used = round(((float)$cantidad) * ((float)$conversionFactor), 4);
            }
            $saledetails->save();

            // NO DESCONTAR DEL INVENTARIO AQUÍ - Se descontará cuando la venta se finalice
            // $this->deductFromInventory($productid, $cantidad, $unitCode, $unitId, $conversionFactor);

            // Recalcular y actualizar el totalamount de la venta
            $this->updateSaleTotalAmount($idsale);

            DB::commit();
            return response()->json(array(
                "res" => "1",
                "idsaledetail" => $saledetails['id']
            ), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo procesar la venta', 'message' => $e->getMessage()], 500);
        }
    }

    public function newcorrsale($iddoc): JsonResponse
    {
        DB::beginTransaction();

        try {
            $iduser = auth()->user()->id;

            // Obtener la primera empresa con permiso
            $companyId = DB::table('permission_company')
                ->where('user_id', $iduser)
                ->where('state', 1)
                ->orderBy('id')
                ->value('company_id');

            // Validar que se obtuvo un company_id válido
            if (!$companyId) {
                throw new \Exception("No se encontró empresa válida para el usuario ID: " . $iduser);
            }

            // Crear la nueva venta como borrador (SIEMPRE nuevo)
            $corr = new Sale();
            $corr->company_id = $companyId;
            $corr->typedocument_id = $iddoc;
            $corr->user_id = $iduser;
            $corr->date = now();
            $corr->state = 1;
            $corr->typesale = 2; // Borrador de venta
            $corr->totalamount = 0.00; // Inicializar totalamount en 0 para borradores
            $corr->waytopay = 1; // Forma de pago por defecto
            $corr->state_credit = 0; // Estado de crédito por defecto


            $corr->save();


            // Verificar que la venta realmente se guardó
            $saleExists = Sale::find($corr->id);
            if (!$saleExists) {
                throw new \Exception("La venta se creó con ID " . $corr->id . " pero no se puede encontrar en la base de datos");
            }

            DB::commit();

            return response()->json([
                'sale_id' => $corr->id,
                'is_draft' => true,
                'message' => 'Nueva venta draft creada'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo crear la venta', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Obtener drafts de ventas del usuario actual
     */
    public function getUserDrafts(): JsonResponse
    {
        try {
            $userId = auth()->user()->id;

            $drafts = Sale::where('user_id', $userId)
                ->where('typesale', 2) // Borrador
                ->where('state', 1) // Activo
                ->with(['typedocument:id,name', 'company:id,razonsocial'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($draft) {
                    return [
                        'id' => $draft->id,
                        'corr' => $draft->corr,
                        'typedocument_name' => $draft->typedocument->name ?? 'N/A',
                        'company_name' => $draft->company->razonsocial ?? 'N/A',
                        'date' => $draft->date,
                        'created_at' => $draft->created_at,
                        'has_products' => $draft->salesdetails()->exists()
                    ];
                });

            return response()->json([
                'success' => true,
                'drafts' => $drafts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener drafts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos de un draft específico
     */
    public function getDraftProducts($draftId): JsonResponse
    {
        try {
            $decodedDraftId = base64_decode($draftId);
            $userId = auth()->user()->id;


            // Verificar que el draft pertenece al usuario actual
            $draft = Sale::where('id', $decodedDraftId)
                ->where('typesale', 2) // Borrador
                ->where('state', 1) // Activo
                ->first();


            if (!$draft) {
                // Verificar por qué no se encontró
                $sale = Sale::where('id', $decodedDraftId)->first();
                if ($sale) {

                }

                return response()->json([
                    'success' => false,
                    'message' => 'Draft no encontrado o no autorizado'
                ], 404);
            }

                        // Obtener productos del draft


            // Probar la relación directamente
            $detailsCount = $draft->details()->count();


            // Probar consulta directa a la tabla
            $directCount = \App\Models\Salesdetail::where('sale_id', $draft->id)->count();

            $salesDetails = $draft->details()
                ->with(['product:id,name,description,marca_id', 'unit:id,unit_name,unit_code'])
                ->get();


                        $products = $salesDetails->map(function($detail) {


                // Obtener la marca del producto
                $marcaName = 'N/A';
                if ($detail->product && $detail->product->marca_id) {
                    $marca = \App\Models\Marca::find($detail->product->marca_id);
                    $marcaName = $marca ? $marca->name : 'N/A';
                }

                    // Si hay un nombre personalizado en 'ruta' (usado para exámenes de laboratorio), usarlo
                    // De lo contrario, usar el nombre del producto
                    $productName = $detail->ruta && trim($detail->ruta) !== ''
                        ? $detail->ruta
                        : ($detail->product ? $detail->product->name : 'Producto no encontrado');

                    return [
                        'id' => $detail->id,
                        'product_id' => $detail->product_id,
                        'product_name' => $productName,
                        'product_description' => $detail->product ? $detail->product->description : '',
                        'marca_name' => $marcaName,
                        'unit_id' => $detail->unit_id,
                        'unit_name' => $detail->unit ? $detail->unit->unit_name : 'Unidad',
                        'unit_code' => $detail->unit ? $detail->unit->unit_code : '59',
                        'quantity' => number_format($detail->amountp, 2, '.', ''), // Formatear cantidad con 2 decimales
                        'unit_price' => $detail->priceunit, // Campo correcto del modelo
                        'subtotal' => $detail->pricesale, // Campo correcto del modelo
                        'iva_amount' => $detail->detained13, // Campo correcto del modelo (IVA Percibido)
                        'detained' => $detail->detained ?? 0, // IVA Retenido
                        'nosujeta' => $detail->nosujeta, // Campo correcto del modelo
                        'exempt' => $detail->exempt, // Campo correcto del modelo
                        'gravada' => $detail->nosujeta == 0 && $detail->exempt == 0 ? $detail->pricesale : 0, // Campo gravado (usando pricesale como base)
                        'total' => $detail->pricesale // Campo correcto del modelo
                    ];
                });

            return response()->json([
                'success' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos del draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroysaledetail($idsaledetail)
    {
        try {
            $decodedId = base64_decode($idsaledetail);

            // Buscar el detalle de venta
            $saledetails = Salesdetail::find($decodedId);

            if (!$saledetails) {
            }

            $saleId = $saledetails->sale_id;

            // Verificar que la venta existe y pertenece al usuario
            $sale = Sale::find($saleId);

            if (!$sale) {
            }

            // Validar que la venta pertenece al usuario
            /*if ($sale->user_id != auth()->user()->id) {
                return response()->json([
                    "res" => "0",
                    "message" => "No tiene autorización para modificar esta venta"
                ]);
            }*/

            // Permitir eliminación en borradores (typesale = 2) o ventas no finalizadas
            if ($sale->typesale != 2 && $sale->typesale != 0) {
                return response()->json([
                    "res" => "0",
                    "message" => "Solo se pueden eliminar productos de ventas en borrador"
                ]);
            }

            // Eliminar el detalle
            $saledetails->delete();

            // Recalcular el total de la venta después de eliminar el producto
            $this->updateSaleTotalAmount($saleId);

            return response()->json([
                "res" => "1",
                "message" => "Producto eliminado correctamente"
            ]);

        } catch (\Exception $e) {

            return response()->json([
                "res" => "0",
                "message" => "Error al eliminar producto: " . $e->getMessage()
            ]);
        }
    }

    public function getdatadocbycorr($corr)
    {
        $decodedCorr = base64_decode($corr);
        //Log::info("getdatadocbycorr: Correlativo recibido: " . $corr);
        //Log::info("getdatadocbycorr: ID decodificado: " . $decodedCorr);

        // Primero verificar si la venta existe
        $sale = Sale::find($decodedCorr);
        if (!$sale) {
            //Log::error("getdatadocbycorr: Venta no encontrada con ID: " . $decodedCorr);
            return response()->json([]);
        }

        //Log::info("getdatadocbycorr: Venta encontrada", [
        //    'sale_id' => $sale->id,
        //    'company_id' => $sale->company_id,
        //    'client_id' => $sale->client_id,
        //    'typedocument_id' => $sale->typedocument_id
        //]);

        // Verificar cada tabla por separado
        $company = DB::table('companies')->where('id', $sale->company_id)->first();
        //Log::info("getdatadocbycorr: Empresa encontrada: " . ($company ? 'SÍ' : 'NO'));

        $iva = DB::table('iva')->where('company_id', $sale->company_id)->first();
        //Log::info("getdatadocbycorr: IVA encontrado: " . ($iva ? 'SÍ' : 'NO'));

        $client = null;
        if ($sale->client_id) {
            $client = DB::table('clients')->where('id', $sale->client_id)->first();
            //Log::info("getdatadocbycorr: Cliente encontrado: " . ($client ? 'SÍ' : 'NO'));
        } else {
            //Log::info("getdatadocbycorr: Sale no tiene client_id asignado");
        }

        // Hacer consulta más robusta con LEFT JOINs
        $saledetails = Sale::leftJoin('companies', 'companies.id', '=', 'sales.company_id')
            ->leftJoin('iva', 'iva.company_id', '=', 'companies.id')
            ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
            ->select(
                'sales.*',
                'companies.*',
                'companies.id as company_id_override',
                'companies.name as company_name',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.comercial_name AS comercial_name',
                'clients.tipoContribuyente AS client_contribuyente',
                'iva.valor AS iva',
                'iva.valor_entre AS iva_entre'
            )
            ->where('sales.id', '=', $decodedCorr)
            ->get();

        //Log::info("getdatadocbycorr: Resultado de consulta", ['count' => $saledetails->count()]);

        if ($saledetails->isEmpty()) {
            //Log::error("getdatadocbycorr: Consulta devolvió array vacío para venta: " . $decodedCorr);
        } else {
            //Log::info("getdatadocbycorr: Datos encontrados", [
            //    'sale_id' => $saledetails[0]->id ?? 'NULL',
            //    'company_name' => $saledetails[0]->company_name ?? 'NULL',
            //    'client_firstname' => $saledetails[0]->client_firstname ?? 'NULL'
            //]);

            // Formatear la fecha para asegurar que esté en formato Y-m-d
            foreach ($saledetails as $sale) {
                if ($sale->date) {
                    // Asegurar que la fecha esté en formato Y-m-d
                    $sale->date = \Carbon\Carbon::parse($sale->date)->format('Y-m-d');
                }
            }
        }

        return response()->json($saledetails);
    }

    public function updateclient($idsale, $clientid)
    {
        try {
            // Validar que el idsale no esté vacío
            if (!$idsale || $idsale == 'null' || $idsale == '') {
                return response()->json(['error' => 'ID de venta no válido'], 400);
            }

            // Validar que el cliente no esté vacío
            if (!$clientid || $clientid == 'null' || $clientid == '0') {
                return response()->json(['error' => 'Debe seleccionar un cliente válido'], 400);
            }

            // Buscar la venta
            $sale = Sale::find($idsale);
            if (!$sale) {
                return response()->json(['error' => 'No se encontró la venta'], 404);
            }

            // Validación: si es Crédito Fiscal (3), el cliente debe ser Jurídico o Natural Contribuyente (con NRC)
            if ((string)($sale->typedocument ?? $sale->typedocument_id ?? '') === '3') {
                $client = Client::find($clientid);
                if (!$client) {
                    return response()->json(['error' => 'Cliente no encontrado para Crédito Fiscal'], 422);
                }

                $isJuridico = $client->tpersona === 'J';
                $isNaturalContribuyente = $client->tpersona === 'N' && (string)$client->contribuyente === '1';
                $hasNrc = !empty($client->ncr) && $client->ncr !== 'N/A';

                if ((!$isJuridico && !$isNaturalContribuyente) || !$hasNrc) {
                    return response()->json([
                        'error' => 'Para Crédito Fiscal debe seleccionar un cliente Jurídico o Natural Contribuyente con NRC válido.'
                    ], 422);
                }
            }

            // Actualizar el cliente
            $sale->client_id = $clientid;
            $sale->save();

            return response()->json(['success' => true, 'message' => 'Cliente actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar cliente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar forma de pago de una venta
     */
    public function updatePaymentMethod(Request $request)
    {
        try {
            $saleId = $request->input('sale_id');
            $paymentMethod = $request->input('payment_method');
            $authorizationNumber = $request->input('card_authorization_number');

            // Validar que el sale_id no esté vacío
            if (!$saleId || $saleId == 'null' || $saleId == '') {
                return response()->json(['error' => 'ID de venta no válido'], 400);
            }

            // Validar que la forma de pago sea válida
            if (!in_array($paymentMethod, [1, 2, 3])) {
                return response()->json(['error' => 'Forma de pago no válida'], 400);
            }

            // Buscar la venta
            $sale = Sale::find($saleId);
            if (!$sale) {
                return response()->json(['error' => 'No se encontró la venta'], 404);
            }

            // Actualizar la forma de pago
            $sale->waytopay = $paymentMethod;

            // Si es tarjeta, guardar el número de autorización
            if ($paymentMethod == 3 && $authorizationNumber) {
                $sale->card_authorization_number = $authorizationNumber;
            } elseif ($paymentMethod != 3) {
                // Si no es tarjeta, limpiar el número de autorización
                $sale->card_authorization_number = null;
            }

            $sale->save();

            return response()->json([
                'success' => true,
                'message' => 'Forma de pago actualizada correctamente',
                'payment_method' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar forma de pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar la retención del agente en la venta
     */
    public function updateRetencionAgente(Request $request)
    {
        try {
            $saleId = $request->input('sale_id');
            $retencionAgente = $request->input('retencion_agente', 0);

            // Validar que el sale_id no esté vacío
            if (!$saleId || $saleId == 'null' || $saleId == '') {
                return response()->json(['error' => 'ID de venta no válido'], 400);
            }

            // Buscar la venta
            $sale = Sale::find($saleId);
            if (!$sale) {
                return response()->json(['error' => 'No se encontró la venta'], 404);
            }

            // Actualizar la retención del agente
            $sale->retencion_agente = $retencionAgente;
            $sale->save();

            \Log::info('Retención del agente actualizada', [
                'sale_id' => $saleId,
                'retencion_agente' => $retencionAgente
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Retención del agente actualizada correctamente',
                'retencion_agente' => $retencionAgente
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar retención del agente: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar retención del agente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear registro de crédito cuando la forma de pago es crédito
     */
    private function createCreditRecord(Sale $sale)
    {
        try {
            // Verificar si ya existe un registro de crédito para esta venta
            $existingCredit = \App\Models\Credit::where('sale_id', $sale->id)->first();

            if ($existingCredit) {
                \Log::info("Ya existe un registro de crédito para la venta ID: {$sale->id}");
                return;
            }

            // Crear el registro de crédito inicial
            $credit = new \App\Models\Credit();
            $credit->sale_id = $sale->id;
            $credit->date_pay = null; // No hay pago inicial, se establecerá cuando se haga el primer pago
            $credit->current = $sale->totalamount; // El saldo actual es el total de la venta
            $credit->initial = $sale->totalamount; // El monto inicial es el total de la venta
            $credit->amountpay = 0.00; // No hay pago inicial
            $credit->user_id = $sale->user_id;
            $credit->save();

            \Log::info("Registro de crédito creado para la venta ID: {$sale->id}", [
                'sale_id' => $sale->id,
                'initial_amount' => $sale->totalamount,
                'current_amount' => $sale->totalamount,
                'credit_id' => $credit->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Error al crear registro de crédito para la venta ID: {$sale->id}", [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
                'total_amount' => $sale->totalamount
            ]);
            throw $e; // Re-lanzar la excepción para que se maneje en el método padre
        }
    }

    public function getdatadocbycorr2($corr)
    {
        $saledetails = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('phones', 'phones.id', '=', 'companies.phone_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->select(
                'sales.*',
                'companies.*',
                'companies.ncr AS NCR',
                'companies.nit AS NIT',
                'countries.name AS country_name',
                'departments.name AS department_name',
                'municipalities.name AS municipality_name',
                'addresses.reference AS address',
                'phones.*',
                'typedocuments.description AS document_name',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.tipoContribuyente AS client_contribuyente',
                'sales.id AS corr',
                'clients.tpersona',
                'clients.name_contribuyente'
            )
            ->where('sales.id', '=', base64_decode($corr))
            ->get();
        return response()->json($saledetails);
    }
//creacion de factura
    public function createdocument($corr, $amount)
    {
        DB::beginTransaction();
        try {
            $amount = substr($amount, 1);
            $salesave = Sale::find(base64_decode($corr));
            // Usar el total recibido (que debe estar correcto)
            $salesave->totalamount = $amount;
            $salesave->typesale = 1; // Cambiar a venta finalizada
            //dd($amount);
            //buscar el correlativo actual

            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $salesave->typedocument_id)
                ->where('docs.id_empresa', '=', $salesave->company_id)
                ->select(
                    'docs.actual',
                    'docs.id',
                    'docs.final'
                )
                ->get();

            if (!$newCorr) {
                throw new \Exception('No se encontró correlativo para el tipo de documento ' . $salesave->typedocument_id . ' y empresa ' . $salesave->company_id);
            }
            // Verificar si el correlativo está disponible
            if ($newCorr[0]->actual > $newCorr[0]->final) {
                throw new \Exception('Correlativo agotado. Número actual: ' . $newCorr[0]->actual . ', Final: ' . $newCorr[0]->final);
            }
            $salesave->nu_doc = $newCorr[0]->actual;
            $salesave->save();

            // DESCONTAR INVENTARIO CUANDO LA VENTA SE FINALIZA
            $this->deductInventoryFromFinalizedSale($salesave->id);
            //dd($salesave); // Debug comentado

            $idempresa = $salesave->company_id;
            $createdby = $salesave->user_id;
            //$company = Company::find($idempresa);
            //$config = Config::where('company_id', $idempresa)->first();
            //detalle factura
            $detailsbd = Salesdetail::where('sale_id', '=', base64_decode($corr))
                ->select(
                    DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE pricesale END) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            SUM(renta) rentarete,
            NULL pagos,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE detained13 END) iva')
                )
                ->get();
                //dd($detailsbd);
            //detalle de montos de la factura - CON VALIDACIÓN SEGURA
            try {
                // Validar que detailsbd tenga datos antes de acceder al índice 0
                $detailsbdFirst = $this->validateDocumentArray($detailsbd, 'detailsbd', 'construcción de totales');
                $gravadas = ($detailsbdFirst->gravadas);

                // Obtener la retención del agente desde la venta
                $retencionAgente = (float)($salesave->retencion_agente ?? 0);

                // Calcular totalPagar incluyendo la retención del agente
                $totalPagar = ($detailsbdFirst->nosujeta + $detailsbdFirst->exentas + $detailsbdFirst->gravadas + $detailsbdFirst->iva - ($detailsbdFirst->rentarete + $detailsbdFirst->ivarete + $retencionAgente));
                $subtotal = ($detailsbdFirst->nosujeta + $detailsbdFirst->exentas + $gravadas);

                $totales = [
                    "totalNoSuj" => (float)$detailsbdFirst->nosujeta,
                    "totalExenta" => (float)$detailsbdFirst->exentas,
                    "totalGravada" => (float)$detailsbdFirst->gravadas,
                    "subTotalVentas" => round((float)($subtotal), 8),
                    "descuNoSuj" => $detailsbdFirst->descnosujeta,
                    "descuExenta" => $detailsbdFirst->descexenta,
                    "descuGravada" => $detailsbdFirst->desgravada,
                    "porcentajeDescuento" => 0.00,
                    "totalDescu" => $detailsbdFirst->totaldesc,
                    "tributos" =>  null,
                    "subTotal" => round((float)($subtotal), 8),
                    "ivaPerci1" => 0.00,
                    "ivaRete1" => round((float)$retencionAgente, 8), // Retención del agente 1%
                    "reteRenta" => round((float)$detailsbdFirst->rentarete, 8),
                    "montoTotalOperacion" => round((float)($subtotal), 8),
                    //(float)$encabezado["montoTotalOperacion"],
                    "totalNoGravado" => (float)0,
                    "totalPagar" => (float)$totalPagar,
                    "totalLetras" => numtoletras(round((float)$totalPagar, 2)),
                    "saldoFavor" => 0.00,
                    "condicionOperacion" => $salesave->waytopay,
                    "creditcardautorization" => $salesave->card_authorization_number,
                    "pagos" => null,
                    "totalIva" => (float)$detailsbdFirst->iva
                ];
            } catch (\Exception $e) {
                return $this->handleError($e, 'construcción_totales', [
                    'sale_id' => base64_decode($corr),
                    'step' => 'construcción de totales',
                    'detailsbd_count' => $detailsbd->count(),
                    'detailsbd_empty' => $detailsbd->isEmpty()
                ]);
            }
            //detalle del comprobante como url de firmad∂or y mh etc
            $querydocumento = "SELECT
        a.id id_doc,
        b.`type` id_tipo_doc,
        docs.serie serie,
        docs.inicial inicial,
        docs.final final,
        docs.actual actual,
        docs.estado estado,
        a.company_id id_empresa,
        a.user_id hechopor,
        a.created_at fechacreacion,
        b.description NombreDocumento,
        c.name NombreUsuario,
        c.nit docUser,
        b.codemh tipodocumento,
        b.versionjson versionJson,
        e.url_credencial,
        e.url_envio,
        e.url_invalidacion,
        e.url_contingencia,
        e.url_firmador,
        d.typeTransmission tipogeneracion,
        e.cod ambiente,
        a.updated_at,
        1 aparece_ventas
        FROM sales a
        INNER JOIN typedocuments b ON a.typedocument_id = b.id
        INNER JOIN docs ON b.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
        INNER JOIN users c ON a.user_id = c.id
        LEFT JOIN config d ON a.company_id = d.company_id
        LEFT JOIN ambientes e ON d.ambiente = e.id
        WHERE a.id = " . base64_decode($corr) . "";
            $documento = DB::select(DB::raw($querydocumento));
            //dd($documento);

            $queryproducto = "SELECT
        c.id id_producto,
        CASE
        WHEN b.description IS NOT NULL AND b.description != '' THEN
            CONCAT(b.description, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
        WHEN c.code = 'LAB' AND b.ruta IS NOT NULL AND b.ruta != '' THEN
            CONCAT(b.ruta, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
        WHEN c.id = 9 THEN
            CONCAT(c.name, ' ', b.reserva, ' ', b.ruta, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
        ELSE
            CONCAT(c.name, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
        END AS descripcion,
        b.amountp cantidad,
        b.priceunit precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        b.renta renta_retenida,
        1 tipo_item,
        '59' AS uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        LEFT JOIN units u ON b.unit_id = u.id
        WHERE a.id=" . base64_decode($corr) . "";
            $producto = DB::select(DB::raw($queryproducto));
            $detalle = $producto;
            //data del emisor
            $queryemisor = "SELECT
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        /*a.name nombre,*/
        'MOISES EDGARDO ARANA ZOMETA' AS nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        LEFT JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$idempresa";
            $emisor = DB::select(DB::raw($queryemisor));

            $querycliente = "SELECT
        a.id idcliente,
        a.nit,
        a.ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN a.name_contribuyente
        END AS nombre,
        b.code codActividad,
        b.name descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN a.comercial_name
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        36 tipoDocumento,
        a.nit numDocumento,
        36 tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    INNER JOIN departments f ON c.department_id=f.id
    INNER JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $salesave->client_id";

            // Validar que client_id no esté vacío
            if (empty($salesave->client_id) || $salesave->client_id === null) {
                throw new \Exception('Error: La venta no tiene un cliente asignado. client_id: ' . $salesave->client_id);
            }

            $cliente = DB::select(DB::raw($querycliente));
            //dd($cliente);
            $comprobante = [
                "emisor"    => $emisor,
                "documento" => $documento,
                "detalle"   => $detalle,
                "totales"   => $totales,
                "cliente"   => $cliente
            ];
            // Guardar a archivo legible para depuración
            /*try {
                Storage::makeDirectory('debug'); // crea si no existe
                Storage::put('debug/comprobante.json', json_encode($comprobante, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Log::warning('No se pudo escribir debug/comprobante.json', ['error' => $e->getMessage()]);
            }*/
            // Verificar si la emisión DTE está habilitada para esta empresa
            if (Config::isDteEmissionEnabled($idempresa)) {

                $contingencia = [];
                $respuesta_hacienda = [];

                if ($documento[0]->tipogeneracion == 1) {
                    $contingencia = 1;
                    if ($contingencia) {
                        $respuesta_hacienda = $this->Enviar_Hacienda($comprobante, "01");
                        //dd($respuesta_hacienda); // Debug deshabilitado para producción
                        if ($respuesta_hacienda["codEstado"] == "03") {
                            // CREAR DTE CON ESTADO RECHAZADO Y REGISTRAR ERROR
                            $dtecreate = $this->crearDteConError($documento, $emisor, $respuesta_hacienda, $comprobante, $salesave, $documento[0]->NombreUsuario);

                            // REGISTRAR ERROR EN LA TABLA dte_errors
                            $this->registrarErrorDte($dtecreate, 'hacienda', 'HACIENDA_REJECTED', $respuesta_hacienda["descripcionMsg"] ?? 'Documento rechazado por Hacienda', [
                                'codigoMsg' => $respuesta_hacienda["codigoMsg"] ?? null,
                                'observacionesMsg' => $respuesta_hacienda["observacionesMsg"] ?? null,
                                'sale_id' => base64_decode($corr)
                            ]);

                            // Devolver respuesta JSON estructurada para el frontend
                            return response()->json([
                                'res' => 0,
                                'dte_response' => $respuesta_hacienda,
                                'error_type' => 'hacienda_rejected',
                                'message' => $respuesta_hacienda["descripcionMsg"] ?? 'Documento rechazado por Hacienda',
                                'codigo' => $respuesta_hacienda["codigoMsg"] ?? null,
                                'observaciones' => $respuesta_hacienda["observacionesMsg"] ?? null,
                                'sale_id' => base64_decode($corr)
                            ], 400);
                        }
                        $comprobante["json"] = $respuesta_hacienda;
                    }
                }
                // Crear respuesta de MH
                $dtecreate = new Dte();
                $dtecreate->versionJson = $documento[0]->versionJson;
                $dtecreate->ambiente_id = $documento[0]->ambiente;
                $dtecreate->tipoDte = $documento[0]->tipodocumento;
                $dtecreate->tipoModelo = $documento[0]->tipogeneracion;
                $dtecreate->tipoTransmision = 1;
                $dtecreate->tipoContingencia = "null";
                $dtecreate->idContingencia = "null";
                $dtecreate->nameTable = 'Sales';
                $dtecreate->company_id = $idempresa;
                $dtecreate->company_name = $emisor[0]->nombreComercial;
                $dtecreate->id_doc = $respuesta_hacienda["identificacion"]["numeroControl"];
                $dtecreate->codTransaction = "01";
                $dtecreate->desTransaction = "Emision";
                $dtecreate->type_document = $documento[0]->tipodocumento;
                $dtecreate->id_doc_Ref1 = "null";
                $dtecreate->id_doc_Ref2 = "null";
                $dtecreate->type_invalidacion = "null";
                $dtecreate->codEstado = $respuesta_hacienda["codEstado"];
                $dtecreate->Estado = $respuesta_hacienda["estado"];
                $dtecreate->codigoGeneracion = $respuesta_hacienda["codigoGeneracion"];
                $dtecreate->selloRecibido = $respuesta_hacienda["selloRecibido"];
                $dtecreate->fhRecibido = $respuesta_hacienda["fhRecibido"];
                $dtecreate->estadoHacienda = $respuesta_hacienda["estadoHacienda"];
                $dtecreate->json = json_encode($comprobante);
                $dtecreate->nSends = $respuesta_hacienda["nuEnvios"];
                $dtecreate->codeMessage = $respuesta_hacienda["codigoMsg"];
                $dtecreate->claMessage = $respuesta_hacienda["clasificaMsg"];
                $dtecreate->descriptionMessage = $respuesta_hacienda["descripcionMsg"];
                $dtecreate->detailsMessage = $respuesta_hacienda["observacionesMsg"];
                $dtecreate->sale_id = base64_decode($corr);
                $dtecreate->created_by = $documento[0]->NombreUsuario;
                $dtecreate->save();

                // Envío automático de correo después de crear el DTE
                $this->enviarCorreoAutomatico(base64_decode($corr), $dtecreate);
            } else {
                // Envío automático de correo para ventas sin DTE
                $this->enviarCorreoAutomatico(base64_decode($corr), null);
            }
            //dd($comprobante);
            //update correlativo - incrementar después de usar exitosamente
            $updateCorr = Correlativo::find($newCorr[0]->id);
            if ($updateCorr) {
            $updateCorr->actual = ($updateCorr->actual + 1);
            $updateCorr->save();
            }
            //if ($dtecreate) $exit = 1;
            $salesave = Sale::find(base64_decode($corr));
            $salesave->json = json_encode($comprobante);

            // El número de autorización ya debería estar guardado desde updatePaymentMethod
            // pero lo actualizamos aquí por si acaso no se guardó antes
            if ($salesave->waytopay == 3 && !$salesave->card_authorization_number) {
                // Si es tarjeta y no tiene número de autorización, intentar obtenerlo del request si está disponible
                // (Nota: createdocument usa GET, así que esto no aplicará aquí, pero se mantiene para consistencia)
            }

            $salesave->save();

            // Crear registro de crédito si la forma de pago es crédito (waytopay = 2)
            if ($salesave->waytopay == 2) {
                $this->createCreditRecord($salesave);
            }

            $exit = 1;
            DB::commit();

            // Generar URLs del ticket para impresión automática
            $saleId = base64_decode($corr);
            $ticketPrintUrl = route('sale.ticket-print', $saleId);
            $ticketDirectUrl = route('sale.ticket-direct', $saleId);
            // Unificar también la URL de vista estándar del ticket (misma que usa sale.index)
            $ticketUrl = route('sale.ticket', $saleId) . '?autoprint=true&auto_close=true';

            return response()->json([
                'res' => $exit,
                'sale_id' => $saleId,
                'ticket_print_url' => $ticketPrintUrl,
                'ticket_direct_url' => $ticketDirectUrl,
                'ticket_url' => $ticketUrl
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, 'createdocument', [
                'sale_id' => base64_decode($corr),
                'amount' => $amount
            ]);
        }
     // Cerrar el for loop
    }

    public function getdetailsdoc($corr)
    {
        $saledetails = Salesdetail::leftJoin('products', 'products.id', '=', 'salesdetails.product_id')
        ->leftjoin('marcas', 'marcas.id', '=', 'products.marca_id')
            ->select(
                'salesdetails.*',
                DB::raw('CONCAT(products.name, " ", marcas.name  ) as product_name')
            )
            ->where('sale_id', '=', base64_decode($corr))
            ->get();
            //dd($saledetails);
        return response()->json($saledetails);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $typedocument = request()->get('typedocument', 6); // Por defecto Factura
        $draftId = request()->get('draft_id');
        $corr = request()->get('corr', '');
        $operation = request()->get('operation', '');
        $draft = request()->get('draft', false);

        // Redirigir a create-dynamic con todos los parámetros
        $params = [
            'typedocument' => $typedocument,
            'new' => true // Indicar que es una nueva venta
        ];

        if ($draftId) {
            $params['draft_id'] = $draftId;
        }
        if ($corr) {
            $params['corr'] = $corr;
        }
        if ($operation) {
            $params['operation'] = $operation;
        }
        if ($draft) {
            $params['draft'] = $draft;
        }

        return redirect()->route('sale.create-dynamic', $params);
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
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $Sale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $Sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $Sale)
    {
        //
    }

    public function ncr($id_sale)
    {
        // La nota de crédito SOLO puede venir del formulario
        if (!request()->isMethod('post') || !request()->has('productos')) {
            return redirect()->back()
                ->with('error', 'Acceso no autorizado. La nota de crédito debe crearse desde el formulario.');
        }

        // Validar que el ID de venta sea válido
        if (!$id_sale || !is_numeric($id_sale)) {
            return redirect()->back()
                ->with('error', 'ID de venta inválido.');
        }
        DB::beginTransaction();
        try {
            $request = request();

            // Obtener la venta original
            $saleOriginal = Sale::where('id', $id_sale)
                ->where('typesale', 1)
                ->where('state', 1)
                ->first();

            if (!$saleOriginal) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'La venta original no existe o no está disponible para crear una nota de crédito.');
            }

            $idempresa = $saleOriginal->company_id;
            $createdby = $saleOriginal->user_id;

            // Verificar modificaciones, calcular total y crear detalles
            $hayModificaciones = false;
            $totalAmount = 0;
            $productosOriginales = $saleOriginal->details->keyBy('product_id');
            $detallesModificados = [];


            foreach ($request->productos as $index => $productoData) {

                if (!isset($productoData['incluir']) || !$productoData['incluir']) {
                    continue;
                }

                // Validar datos del producto
                if (!isset($productoData['product_id']) || !isset($productoData['cantidad']) || !isset($productoData['precio'])) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Datos de producto incompletos. Se requiere producto, cantidad y precio.');
                }

                $cantidad = (float)$productoData['cantidad'];
                $precio = (float)$productoData['precio'];
                $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

                // Validar entradas
                if (!is_numeric($cantidad) || $cantidad <= 0) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Cantidad inválida para el producto.');
                }

                if (!is_numeric($precio) || $precio < 0) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Precio inválido para el producto.');
                }

                $hayModificaciones = true;
                $subtotal = $cantidad * $precio;

                // Calcular total según el tipo de venta
                if ($tipoVenta === 'gravada') {
                    $totalAmount += $subtotal + ($subtotal * 0.13);
                } else {
                    // Para exenta y no_sujeta, solo se suma el subtotal sin IVA
                    $totalAmount += $subtotal;
                }

                // Preparar datos del detalle para crear después
                $detallesModificados[] = [
                    'productoData' => $productoData,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                    'tipoVenta' => $tipoVenta
                ];
            }

            if (!$hayModificaciones) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se detectaron modificaciones en los productos. No se puede crear una nota de crédito sin cambios.');
            }
            // Crear la nota de crédito con solo las modificaciones
            $nfactura = new Sale();
            $nfactura->client_id = $saleOriginal->client_id;
            $nfactura->company_id = $saleOriginal->company_id;
            $nfactura->doc_related = $id_sale; // ID de la venta original
            $nfactura->typesale = 1; // Venta confirmada
            $nfactura->date = now();
            $nfactura->user_id = Auth::id();
            $nfactura->waytopay = $saleOriginal->waytopay ?? 1;
            $nfactura->state = 1; // Activa/Confirmada
            $nfactura->state_credit = 0;

            // Asignar motivo solo si la columna existe
            if (Schema::hasColumn('sales', 'motivo')) {
                $nfactura->motivo = $request->motivo ?? 'Modificación de productos';
            }

            $nfactura->acuenta = $saleOriginal->acuenta ?? 0;

            // Obtener el typedocument_id para notas de crédito (tipo NCR)
            $typedocumentNCR = \App\Models\Typedocument::where('type', 'NCR')
                ->where('company_id', $saleOriginal->company_id)
                ->first();

            if (!$typedocumentNCR) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró configuración de tipo de documento NCR para esta empresa.');
            }

            $nfactura->typedocument_id = $typedocumentNCR->id;

            // Obtener y asignar el número de documento del correlativo
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $typedocumentNCR->id)
                ->where('docs.id_empresa', '=', $nfactura->company_id)
                ->where('docs.estado', '=', 1) // Solo correlativos activos
                ->select('docs.actual', 'docs.id', 'docs.final')
                ->first();

            if (!$newCorr) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró correlativo para el tipo de documento NCR.');
            }

            // Verificar si el correlativo está disponible
            if ($newCorr->actual > $newCorr->final) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Correlativo agotado para NCR. Número actual: ' . $newCorr->actual . ', Final: ' . $newCorr->final);
            }

            $nfactura->nu_doc = $newCorr->actual;
            $nfactura->totalamount = $totalAmount;
            $nfactura->save();

            // Actualizar correlativo después de guardar la nota de crédito
            DB::table('docs')->where('id', $newCorr->id)->increment('actual');

            // Crear detalles usando los datos del formulario
            foreach ($detallesModificados as $detalleData) {
                $productoData = $detalleData['productoData'];
                $cantidad = $detalleData['cantidad'];
                $precio = $detalleData['precio'];
                $subtotal = $detalleData['subtotal'];
                $tipoVenta = $detalleData['tipoVenta'];

                $detalle = new Salesdetail();
                $detalle->sale_id = $nfactura->id;
                $detalle->product_id = $productoData['product_id'];
                $detalle->amountp = $cantidad;
                $detalle->priceunit = $precio;
                $detalle->renta = 0; // Campo requerido
                $detalle->fee = 0; // Campo requerido
                $detalle->feeiva = 0; // Campo requerido
                $detalle->reserva = 0; // Campo requerido
                $detalle->ruta = null;
                $detalle->destino = null;
                $detalle->linea = null;
                $detalle->canal = null;
                $detalle->user_id = Auth::id();

                if ($tipoVenta === 'gravada') {
                    $detalle->pricesale = $subtotal;
                    $detalle->detained13 = $subtotal * 0.13;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = 0;
                } elseif ($tipoVenta === 'exenta') {
                    $detalle->pricesale = 0;
                    $detalle->detained13 = 0;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = $subtotal;
                    $detalle->nosujeta = 0;
                } elseif ($tipoVenta === 'no_sujeta') {
                    $detalle->pricesale = 0;
                    $detalle->detained13 = 0;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = $subtotal;
                } else {
                    // Por defecto, tratar como gravada
                    $detalle->pricesale = $subtotal;
                    $detalle->detained13 = $subtotal * 0.13;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = 0;
                }
                $detalle->save();
            }
            // Verificar si DTE está habilitado para esta empresa

            if (!Config::isDteEmissionEnabled($idempresa)) {
                DB::commit();
                if (request()->ajax()) {
                    return response('1');
                }
                return redirect()->route('credit-notes.index')
                    ->with('success', 'Nota de crédito creada exitosamente. DTE deshabilitado para esta empresa.');
            }

            // Obtener información básica de la venta original
        $qfactura = "SELECT
                        s.id id_factura,
                        s.totalamount total_venta,
                        s.company_id id_empresa,
                        s.client_id id_cliente,
                        s.user_id id_usuario,
                        clie.nit,
                        clie.email email_cliente,
                        clie.tpersona tipo_personeria,
                        CASE
                                WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                                WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
                            END AS nombre_cliente,
                        dte.json,
                        dte.tipoModelo,
                        dte.fhRecibido,
                        dte.codigoGeneracion,
                        dte.selloRecibido,
                        dte.tipoDte
                        FROM sales s
                        INNER JOIN clients clie ON s.client_id=clie.id
                        LEFT JOIN dte ON dte.sale_id=s.id
                        WHERE s.id = $id_sale";
        $factura = DB::select(DB::raw($qfactura));

        if (empty($factura) || !isset($factura[0])) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'No se pudo obtener información de la venta original.');
        }

        // Obtener información del tipo de documento NCR
        $qdoc = "SELECT
                a.id id_doc,
                a.`type` id_tipo_doc,
                docs.serie serie,
                docs.inicial inicial,
                docs.final final,
                docs.actual actual,
                docs.estado estado,
                a.company_id id_empresa,
                    " . Auth::id() . " hechopor,
                    NOW() fechacreacion,
                a.description NombreDocumento,
                    '" . Auth::user()->name . "' NombreUsuario,
                    '" . (Auth::user()->nit ?? '00000000-0') . "' docUser,
                a.codemh tipodocumento,
                a.versionjson versionJson,
                e.url_credencial,
                e.url_envio,
                e.url_invalidacion,
                e.url_contingencia,
                e.url_firmador,
                d.typeTransmission tipogeneracion,
                e.cod ambiente,
                    NOW() updated_at,
                1 aparece_ventas
                FROM typedocuments a
                INNER JOIN docs ON a.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
                INNER JOIN config d ON a.company_id=d.company_id
                INNER JOIN ambientes e ON d.ambiente=e.id
                    WHERE a.`type`= 'NCR' AND a.company_id = $idempresa";
        $doc = DB::select(DB::raw($qdoc));

        if (empty($doc) || !isset($doc[0])) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'No se encontró configuración de documento NCR para esta empresa.');
        }

        // Obtener detalles de la nota de crédito (solo las modificaciones)
        $detalle = $this->construirDetalleNotaCredito($nfactura->id);
        $versionJson = $doc[0]->versionJson;
        $ambiente = $doc[0]->ambiente;
        $tipoDte = $doc[0]->tipodocumento;
        $numero = $doc[0]->actual;

            // Obtener totales de la nota de crédito
            $totalesNC = $this->calcularTotalesNotaCredito($nfactura->id);

            // Construir array $totales con la estructura correcta
            $totales = [
                "totalNoSuj" => $totalesNC['nosujetas'],
                "totalExenta" => $totalesNC['exentas'],
                "totalGravada" => $totalesNC['gravadas'],
                "subTotalVentas" => $totalesNC['subtotal'],
                "totalIva" => $totalesNC['iva'],
                "totalPagar" => $totalesNC['total'],
                "totalLetras" => numtoletras(round((float)$totalesNC['total'], 2)),
                "condicionOperacion" => $nfactura->waytopay ?? '01',
                "descuNoSuj" => 0,
                "descuExenta" => 0,
                "descuGravada" => 0,
                "totalDescu" => 0,
                "ivaRete1" => 0,
                "reteRenta" => 0,
                "saldoFavor" => 0
            ];
            log::info('totalesNC', $totales);
            // Construir documento fiscal para nota de crédito
            $dteOriginal = $saleOriginal->dte;

        $documento[0] = [
                "tipodocumento"             => $doc[0]->tipodocumento,
                "nu_doc"                    => $numero,
                "tipo_establecimiento"      => "1",
                "version"                   => $doc[0]->versionJson,
                "ambiente"                  => $doc[0]->ambiente,
                "tipoDteOriginal"           => $dteOriginal->tipoDte ?? '01',
                "tipoGeneracionOriginal"    => $dteOriginal->tipoModelo ?? 1,
                "codigoGeneracionOriginal"  => $dteOriginal->codigoGeneracion ?? '',
                "selloRecibidoOriginal"     => $dteOriginal->selloRecibido ?? '',
                "numeroOriginal"            => $dteOriginal->codigoGeneracion ?? '',
                "fecEmiOriginal"            => $dteOriginal ? date('Y-m-d', strtotime($dteOriginal->fhRecibido)) : date('Y-m-d'),
                "total_iva"                 => $totalesNC['iva'],
            "tipoDocumento"             => "",
                "numDocumento"              => $factura[0]->nit ?? '',
                "nombre"                    => $factura[0]->nombre_cliente ?? '',
            "versionjson"               => $doc[0]->versionJson,
                "id_empresa"                => $saleOriginal->company_id,
                "url_credencial"            => $doc[0]->url_credencial,
                "url_envio"                 => $doc[0]->url_envio,
                "url_firmador"              => $doc[0]->url_firmador,
            "nuEnvio"                   => 1,
                "condiciones"               => "1",
                "total_venta"               => $totalesNC['total'],
                "tot_gravado"               => $totalesNC['gravadas'],
                "tot_nosujeto"              => $totalesNC['nosujetas'],
                "tot_exento"                => $totalesNC['exentas'],
                "subTotalVentas"            => $totalesNC['subtotal'],
            "descuNoSuj"                => 0.00,
            "descuExenta"               => 0.00,
            "descuGravada"              => 0.00,
            "totalDescu"                => 0.00,
                "subTotal"                  => $totalesNC['subtotal'],
            "ivaPerci1"                 => 0.00,
                "ivaRete1"                  => 0.00,
            "reteRenta"                 => 0.00,
                "total_letras"              => numtoletras(round((float)$totalesNC['total'], 2)),
                "totalPagar"                => $totalesNC['total'],
                "NombreUsuario"             => Auth::user()->name,
                "docUser"                   => Auth::user()->nit ?? ''
            ];
            // Obtener datos del cliente
        $qcliente = "SELECT
                                a.id id_cliente,
                            CASE
                                WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
                                WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
                            END AS nombre_cliente,
                                p.phone telefono_cliente,
                                a.email email_cliente,
                                c.reference direccion_cliente,
                                1 status_cliente,
                                a.created_at date_added,
                                CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                            a.nit,
                            a.tpersona tipo_personeria,
                            g.code municipio,
                            f.code departamento,
                            a.company_id id_empresa,
                            NULL hechopor,
                            a.tipoContribuyente id_clasificacion_tributaria,
                            CASE
                                WHEN a.tipoContribuyente = 'GRA' THEN 'GRANDES CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'MED' THEN 'MEDIANOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'PEQU'  THEN 'PEQUEÑOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'OTR'  THEN 'OTROS CONTRIBUYENTES'
                            END AS descripcion,
                            0 siempre_retiene,
                            1 id_tipo_contribuyente,
                            b.id giro,
                            b.code codActividad,
                            b.name descActividad,
                            a.comercial_name nombre_comercial
                        FROM clients a
                        INNER JOIN economicactivities b ON a.economicactivity_id=b.id
                        INNER JOIN addresses c ON a.address_id=c.id
                        INNER JOIN phones p ON a.phone_id=p.id
                        INNER JOIN countries d ON c.country_id=d.id
                        INNER JOIN departments f ON c.department_id=f.id
                        INNER JOIN municipalities g ON c.municipality_id=g.id
                        WHERE a.id = " . $factura[0]->id_cliente . "";
        $cliente = DB::select(DB::raw($qcliente));

        if (empty($cliente) || !isset($cliente[0])) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'No se pudo obtener información del cliente.');
        }

            // Obtener datos del emisor (empresa)
        $queryemisor = "SELECT
                        a.nit,
                        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                        a.name nombre,
                        c.code codActividad,
                        c.name descActividad,
                        a.name nombreComercial,
                        a.tipoEstablecimiento,
                        f.code departamento,
                        g.code municipio,
                        d.reference direccion,
                        e.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        a.email correo,
                        b.passkeyPublic clavePublicaMH,
                        b.passPrivateKey clavePrivadaMH,
                        b.passMH claveApiMH
                        FROM companies a
                        INNER JOIN config b ON a.id=b.company_id
                        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
                        INNER JOIN addresses d ON a.address_id=d.id
                        INNER JOIN phones e ON a.phone_id=e.id
                        INNER JOIN departments f ON d.department_id=f.id
                        INNER JOIN municipalities g ON d.municipality_id=g.id
                            WHERE a.id=" . $saleOriginal->company_id . "";
        $emisor = DB::select(DB::raw($queryemisor));

            // Construir comprobante para envío a Hacienda
        $comprobante = [
            "emisor" => $emisor,
            "documento" => $documento,
            "detalle" => $detalle,
            "totales" => $totales,
            "cliente" => $cliente
        ];

        Log::info('Comprobante construido para envío a Hacienda', [
            'sale_id' => $nfactura->id,
            'comprobante' => $comprobante
        ]);

            // Enviar a Hacienda
        $respuesta = $this->Enviar_Hacienda($comprobante, "05");

        Log::info('Respuesta de Hacienda recibida', [
            'sale_id' => $nfactura->id,
            'codEstado' => $respuesta["codEstado"] ?? 'NO_DEFINIDO',
            'respuesta_completa' => $respuesta
        ]);

        if ($respuesta["codEstado"] == "03") {
                // CREAR DTE CON ESTADO RECHAZADO Y REGISTRAR ERROR
                $dtecreate = $this->crearDteConError($doc, $emisor, $respuesta, $comprobante, $nfactura, $createdby);
                // REGISTRAR ERROR EN LA TABLA dte_errors
                $this->registrarErrorDte($dtecreate, 'hacienda', 'HACIENDA_REJECTED', $respuesta["descripcionMsg"] ?? 'Documento rechazado por Hacienda', [
                    'codigoMsg' => $respuesta["codigoMsg"] ?? null,
                    'observacionesMsg' => $respuesta["observacionesMsg"] ?? null,
                    'sale_id' => $nfactura->id
                ]);

                // Guardar JSON con información de rechazo en la tabla sales
                $comprobante["json"] = $respuesta;
                $nfactura->json = json_encode($comprobante);
                $nfactura->save();

                DB::rollBack();

                // Construir mensaje de error detallado
                $errorMessage = 'Error al enviar a Hacienda: ';
                $errorMessage .= $respuesta["descripcionMsg"] ?? 'Documento rechazado';

                if (isset($respuesta["codigoMsg"])) {
                    $errorMessage .= ' (Código: ' . $respuesta["codigoMsg"] . ')';
                }

                if (isset($respuesta["observacionesMsg"])) {
                    $errorMessage .= ' - Observaciones: ' . $respuesta["observacionesMsg"];
                }

                Log::error('Error Hacienda en NCR', [
                    'sale_id' => $nfactura->id,
                    'respuesta' => $respuesta,
                    'error_message' => $errorMessage
                ]);

                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => $errorMessage,
                        'hacienda_error' => true,
                        'codigo' => $respuesta["codigoMsg"] ?? null,
                        'observaciones' => $respuesta["observacionesMsg"] ?? null
                    ], 500);
                }

                return redirect()->back()
                    ->with('error', $errorMessage);
            }
            //dd($respuesta);
        $comprobante["json"] = $respuesta;
            // Crear registro DTE
            $dte = new \App\Models\Dte();
            $dte->versionJson = $doc[0]->versionJson;
            $dte->ambiente_id = $doc[0]->ambiente;
            $dte->tipoDte = $doc[0]->tipodocumento;
            $dte->tipoModelo = $doc[0]->tipogeneracion;
            $dte->tipoTransmision = 1;
            $dte->tipoContingencia = "null";
            $dte->idContingencia = "null";
            $dte->nameTable = 'Sales';
            $dte->company_id = $nfactura->company_id;
            $dte->company_name = $emisor[0]->nombreComercial;
            $dte->id_doc = $respuesta["identificacion"]["numeroControl"];
            $dte->codTransaction = "01";
            $dte->desTransaction = "Emision";
            $dte->type_document = $doc[0]->tipodocumento;
            $dte->id_doc_Ref1 = "null";
            $dte->id_doc_Ref2 = "null";
            $dte->type_invalidacion = "null";
            $dte->codEstado = $respuesta["codEstado"];
            $dte->Estado = $respuesta["estado"];
            $dte->codigoGeneracion = $respuesta["codigoGeneracion"];
            $dte->selloRecibido = $respuesta["selloRecibido"];
            $dte->fhRecibido = $respuesta["fhRecibido"];
            $dte->estadoHacienda = $respuesta["estadoHacienda"];
            $dte->json = json_encode($comprobante);
            $dte->nSends = $respuesta["nuEnvios"];
            $dte->codeMessage = $respuesta["codigoMsg"];
            $dte->claMessage = $respuesta["clasificaMsg"];
            $dte->descriptionMessage = $respuesta["descripcionMsg"];
            $dte->detailsMessage = $respuesta["observacionesMsg"];
            $dte->sale_id = $nfactura->id;
            $dte->created_by = $doc[0]->NombreUsuario;
            $dte->save();

            $nfactura->codigoGeneracion = $respuesta["codigoGeneracion"];

            // Agregar el codigoGeneracion al JSON antes de guardarlo
            //$comprobante["json"] = $respuesta;
            $nfactura->json = json_encode($comprobante);
            $nfactura->save();

            // Actualizar correlativo después de usar exitosamente
            $updateCorr = Correlativo::find($newCorr->id);
            if ($updateCorr) {
                $updateCorr->actual = ($updateCorr->actual + 1);
                $updateCorr->save();
            }

            DB::commit();
            if (request()->ajax()) {
                return response('1');
            }
            return redirect()->route('sale.index')
              >with('success', 'Nota de crédito creada y enviada a Hacienda exitosamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // Error SQL - mostrar detalles completos
            $errorMessage = 'Error de base de datos al crear la nota de crédito: ' . $e->getMessage();
            $errorDetails = [
                'sql_state' => $e->errorInfo[0] ?? null,
                'sql_code' => $e->errorInfo[1] ?? null,
                'sql_message' => $e->errorInfo[2] ?? null,
                'query' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            Log::error('Error SQL al crear NCR: ' . $errorMessage, $errorDetails);

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_details' => $errorDetails
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('error_details', $errorDetails);
        } catch (\Exception $e) {
            DB::rollBack();

            // Determinar el tipo de error y mostrar mensaje específico
            $errorMessage = 'Error al procesar la nota de crédito: ';
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
                $errorMessage .= 'Error de base de datos. Verifique que todos los datos requeridos estén completos.';
            } elseif (strpos($e->getMessage(), 'not found') !== false) {
                $errorMessage .= 'No se encontró un recurso requerido. Verifique que la venta original existe y está disponible.';
            } elseif (strpos($e->getMessage(), 'validation') !== false) {
                $errorMessage .= 'Error de validación. Verifique que todos los campos requeridos estén completos.';
            } elseif (strpos($e->getMessage(), 'permission') !== false) {
                $errorMessage .= 'Error de permisos. No tiene autorización para realizar esta acción.';
            } else {
                $errorMessage .= $e->getMessage();
            }

            // Agregar información adicional si está en modo debug
            if (config('app.debug')) {
                $errorMessage .= ' (Archivo: ' . basename($e->getFile()) . ', Línea: ' . $e->getLine() . ')';
            }

            Log::error('Error al crear NCR: ' . $errorMessage, $errorDetails);

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'exception' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Calcular totales de la nota de crédito
     */
    private function calcularTotalesNotaCredito($notaCreditoId): array
    {
        $totales = Salesdetail::where('sale_id', $notaCreditoId)
            ->selectRaw('
                SUM(pricesale) as gravadas,
                SUM(exempt) as exentas,
                SUM(nosujeta) as nosujetas,
                SUM(detained13) as iva,
                SUM(pricesale + exempt + nosujeta) as subtotal
            ')
            ->first();

        $total = $totales->subtotal + $totales->iva;

        return [
            'gravadas' => (float)$totales->gravadas,
            'exentas' => (float)$totales->exentas,
            'nosujetas' => (float)$totales->nosujetas,
            'iva' => (float)$totales->iva,
            'subtotal' => (float)$totales->subtotal,
            'total' => (float)$total
        ];
    }

    /**
     * Construir detalle fiscal para la nota de crédito
     */
    private function construirDetalleNotaCredito($notaCreditoId): array
    {
        $queryDetalle = "SELECT
                        det.id id_factura_det,
                        det.sale_id id_factura,
                        det.product_id id_producto,
                        CASE
                            WHEN det.description IS NOT NULL AND det.description != '' THEN det.description
                            WHEN pro.code = 'LAB' AND det.ruta IS NOT NULL AND det.ruta != '' THEN det.ruta
                            ELSE pro.description
                        END AS descripcion,
                        det.amountp cantidad,
                        det.priceunit precio_unitario,
                        det.nosujeta no_sujetas,
                        det.exempt exentas,
                        det.pricesale gravadas,
                        det.detained13 iva,
                        0.00 no_imponible,
                        sa.company_id id_empresa,
                        CASE
                                WHEN pro.`type` = 'tercero' THEN 'T'
                                WHEN pro.`type` = 'directo' THEN 'D'
                            END AS tipo_producto,
                        0.00 porcentaje_descuento,
                        0.00 descuento,
                        det.created_at,
                        det.updated_at
                        FROM salesdetails det
                        INNER JOIN sales sa ON det.sale_id=sa.id
                        INNER JOIN products pro ON det.product_id=pro.id
                        WHERE det.sale_id = $notaCreditoId";

        return DB::select(DB::raw($queryDetalle));
    }


    public function destroy($id)
    {
        try {
            $idFactura = base64_decode($id);
            $anular = Sale::find($idFactura);

            if (!$anular) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada',
                    'res' => 0
                ], 404);
            }

            $anular->state = 0;
            $anular->typesale = 0;

        $queryinvalidacion = "SELECT
        b.tipoModelo,
        b.type_document,
        b.sale_id numero_factura,
        b.id_doc,
        b.tipoDte,
        am.cod ambiente,
        comp.tipoEstablecimiento,
        b.codigoGeneracion,
        b.selloRecibido,
        b.fhRecibido,
        (SELECT SUM(det.detained13) FROM salesdetails det WHERE det.sale_id=a.id) iva,
        clie.nit,
        CASE
                WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
            END AS anombrede,
        a.company_id id_empresa,
        a.client_id id_cliente,
        am.url_credencial,
        am.url_invalidacion,
        am.url_firmador
        FROM sales a
        INNER JOIN clients clie ON a.client_id=clie.id
        INNER JOIN companies comp ON a.company_id=comp.id
        INNER JOIN dte b ON b.sale_id=a.id
        LEFT JOIN ambientes am ON CONCAT('0',b.ambiente_id)=am.cod
        WHERE a.id = $idFactura";
        $invalidacion = DB::select(DB::raw($queryinvalidacion));

        if (empty($invalidacion)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información del DTE para esta venta',
                'res' => 0
            ], 404);
        }

        //data del emisor
        $queryemisor = "SELECT
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        a.name nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        INNER JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$anular->company_id";
        $emisor = DB::select(DB::raw($queryemisor));

        if (empty($emisor)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información de la empresa',
                'res' => 0
            ], 404);
        }

        $queryproducto = "SELECT
        c.id id_producto,
        CASE
            WHEN b.description IS NOT NULL AND b.description != '' THEN
                CONCAT(b.description, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
            ELSE
                CONCAT(c.name, IF(u.unit_name IS NOT NULL AND u.unit_name != '', CONCAT(' - ', u.unit_name), ''))
        END AS descripcion,
        b.amountp cantidad,
        b.priceunit precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        0 renta_retenida,
        1 tipo_item,
        '59' AS uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        LEFT JOIN units u ON b.unit_id = u.id
        WHERE a.id=$idFactura";
        $producto = DB::select(DB::raw($queryproducto));
        $detalle = $producto;

        $detailsbd = Salesdetail::where('sale_id', '=', $idFactura)
            ->select(
                DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE pricesale END) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            0 rentarete,
            NULL pagos,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE detained13 END) iva')
            )
            ->get();
        //detalle de montos de la factura
        $totalPagar = ($detailsbd[0]->nosujeta + $detailsbd[0]->exentas + $detailsbd[0]->gravadas + $detailsbd[0]->iva - $detailsbd[0]->ivarete);
        $totales = [
            "totalNoSuj" => (float)$detailsbd[0]->nosujeta,
            "totalExenta" => (float)$detailsbd[0]->exentas,
            "totalGravada" => (float)$detailsbd[0]->gravadas,
            "subTotalVentas" => round((float)($detailsbd[0]->subtotalventas), 8),
            "descuNoSuj" => $detailsbd[0]->descnosujeta,
            "descuExenta" => $detailsbd[0]->descexenta,
            "descuGravada" => $detailsbd[0]->desgravada,
            "porcentajeDescuento" => 0.00,
            "totalDescu" => $detailsbd[0]->totaldesc,
            "tributos" =>  null,
            "subTotal" => round((float)($detailsbd[0]->subtotal), 8),
            "ivaPerci1" => 0.00,
            "ivaRete1" => 0.00,
            "reteRenta" => round((float)$detailsbd[0]->rentarete, 8),
            "montoTotalOperacion" => round((float)($detailsbd[0]->subtotal), 8),
            //(float)$encabezado["montoTotalOperacion"],
            "totalNoGravado" => (float)0,
            "totalPagar" => (float)$totalPagar,
            "totalLetras" => numtoletras(round((float)$totalPagar, 2)),
            "saldoFavor" => 0.00,
            "condicionOperacion" => $anular->waytopay,
            "pagos" => null,
            "totalIva" => (float)$detailsbd[0]->iva
        ];
        $querycliente = "SELECT
        a.id idcliente,
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
        END AS nombre,
        b.code codActividad,
        b.name descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.comercial_name, '')
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        36 tipoDocumento,
        a.nit numDocumento,
        36 tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    INNER JOIN departments f ON c.department_id=f.id
    INNER JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $anular->client_id";
        $cliente = DB::select(DB::raw($querycliente));

        if (empty($cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información del cliente',
                'res' => 0
            ], 404);
        }

        $documento[0] = [
            "tipodocumento"         => 99,
            "nu_doc"                => $invalidacion[0]->numero_factura,
            "tipoDteOriginal"       => $invalidacion[0]->tipoDte,
            "tipo_establecimiento"  => $invalidacion[0]->tipoEstablecimiento,  //Cambiar,
            "version"               => 2,
            "ambiente"              => $invalidacion[0]->ambiente,
            "id_doc"                => $invalidacion[0]->id_doc,
            "fecAnulado"            => date('Y-m-d'), //"2022-07-20", // $encabezado["fecEmi"],    //Cambiar
            "horAnulado"            => date("H:i:s"),
            "codigoGeneracionOriginal" => $invalidacion[0]->codigoGeneracion,
            "selloRecibidoOriginal"     => $invalidacion[0]->selloRecibido,
            "fecEmiOriginal"            => date('Y-m-d', strtotime($invalidacion[0]->fhRecibido)),
            "total_iva"                 => $invalidacion[0]->iva,
            "tipoDocumento"             => $invalidacion[0]->type_document,
            "numDocumento"              => $invalidacion[0]->nit,
            "nombre"                    => $invalidacion[0]->anombrede,
            "versionjson"               => 2,
            "id_empresa"                => $invalidacion[0]->id_empresa,
            "url_credencial"            => $invalidacion[0]->url_credencial,
            "url_envio"                 => $invalidacion[0]->url_invalidacion,
            "url_firmador"              => $invalidacion[0]->url_firmador,
            "nuEnvio"                   => 1
        ];
        $comprobante = [
            "emisor"    => $emisor,
            "documento" => $documento,
            "detalle"   => $detalle,
            "totales"   => $totales,
            "cliente"   => $cliente
        ];
        //$cliente = Client::where('id', $invalidacion[0]->id_cliente)->get();
        //dd($documento);
        $respuesta = $this->Enviar_Hacienda($comprobante, "02");
        //dd($respuesta);
        if ($respuesta["codEstado"] == "03") {
            return response()->json([
                'success' => false,
                'message' => $respuesta['descripcionMsg'],
                'code' => $respuesta["codEstado"]
            ], 400);
        }
        $comprobante["json"] = $respuesta;


        //dd($respuesta);
        $dtecreate = new Dte();
        $dtecreate->versionJson = $documento[0]["versionjson"];
        $dtecreate->ambiente_id = $documento[0]["ambiente"];
        $dtecreate->tipoDte = $documento[0]["tipoDocumento"];
        $dtecreate->tipoModelo = 2;
        $dtecreate->tipoTransmision = $documento[0]["tipoDocumento"];
        $dtecreate->tipoContingencia = "null";
        $dtecreate->idContingencia = "null";
        $dtecreate->nameTable = 'Sales';
        $dtecreate->company_id = $anular->company_id;
        $dtecreate->company_name = $emisor[0]->nombreComercial;
        $dtecreate->id_doc = $documento[0]["id_doc"];
        $dtecreate->codTransaction = "02";
        $dtecreate->desTransaction = "Invalidacion";
        $dtecreate->type_document = $documento[0]["tipoDocumento"];
        $dtecreate->id_doc_Ref1 = $documento[0]["id_doc"];
        $dtecreate->id_doc_Ref2 = "null";
        $dtecreate->type_invalidacion = "1";
        $dtecreate->codEstado = $respuesta["codEstado"];
        $dtecreate->Estado = $respuesta["estado"];
        $dtecreate->codigoGeneracion = $respuesta["codigoGeneracion"];
        $dtecreate->selloRecibido = $respuesta["selloRecibido"];
        $dtecreate->fhRecibido = $respuesta["fhRecibido"];
        $dtecreate->estadoHacienda = $respuesta["estadoHacienda"];
        $dtecreate->json = json_encode($comprobante);;
        $dtecreate->nSends = $respuesta["nuEnvios"];
        $dtecreate->codeMessage = $respuesta["codigoMsg"];
        $dtecreate->claMessage = $respuesta["clasificaMsg"];
        $dtecreate->descriptionMessage = $respuesta["descripcionMsg"];
        $dtecreate->detailsMessage = $respuesta["observacionesMsg"];
        $dtecreate->sale_id = $idFactura;
        $dtecreate->created_by = $documento[0]["nombre"];
        $dtecreate->save();
        $anular->save();

        if ($dtecreate && $anular) {
            return response()->json([
                'success' => true,
                'message' => 'Documento invalidado correctamente',
                'res' => 1
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al invalidar el documento',
                'res' => 0
            ], 500);
        }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'res' => 0
            ], 500);
        }
    }

    public function Enviar_Hacienda($comprobante, $codTransaccion = "01")
    {
        //$codTransaccion ='01';
        date_default_timezone_set('America/El_Salvador');
        ini_set('max_execution_time', '300');
        $respuesta = [];
        $comprobante_electronico = [];
        //return $comprobante_electronico;
        $comprobante_electronico = convertir_json($comprobante, $codTransaccion);
        //dd($comprobante_electronico);
        //return $comprobante_electronico;
        if ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06") {
            $tipo_documento = $comprobante["documento"][0]["tipodocumento"];
        } else {
            $tipo_documento = $comprobante["documento"][0]->tipodocumento;
        }
        //$tipo_documento = $comprobante["documento"][0]->tipodocumento;
        if ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06") {
            $version = $comprobante["documento"][0]["version"];
        } else {
            $version = $comprobante["documento"][0]->versionJson;
        }
        //$version = $comprobante["documento"][0]->versionJson;
        if ($codTransaccion == '01' || $codTransaccion == "05" || $codTransaccion == "06") {
            $numero_control = $comprobante_electronico["identificacion"]["numeroControl"];
        } else {
            $numero_control = 'Anulacion o Contingencia';
        }
        $empresa = $comprobante["documento"][0];
        $id_empresa = ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06" ? $comprobante["documento"][0]["id_empresa"] : $empresa->id_empresa);
        $ambiente = ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06" ? $comprobante["documento"][0]["ambiente"] : $empresa->ambiente);
        $emisor = $comprobante["emisor"];
        $url_credencial = ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06" ? $comprobante["documento"][0]["url_credencial"] : $empresa->url_credencial);
        $url_envio = ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06" ? $comprobante["documento"][0]["url_envio"] : $empresa->url_envio);
        $url_firmador = ($codTransaccion == "02" || $codTransaccion == "05" || $codTransaccion == "06" ? $comprobante["documento"][0]["url_firmador"] : $empresa->url_firmador);
        //dd(str_replace('-','',$emisor[0]->nit));
        $firma_electronica = [
            "nit" => str_replace('-', '', $emisor[0]->nit),
            "activo" => true,
            "passwordPri" => $emisor[0]->clavePrivadaMH,
            "dteJson" => $comprobante_electronico
        ];
        //dd($firma_electronica);
        //return json_encode($firma_electronica);
        //dd(json_encode($firma_electronica));
        //dd($url_firmador);
        try {
            $response = Http::accept('application/json')->post($url_firmador, $firma_electronica);
        } catch (\Throwable $th) {
            $error = [
                "codEstado" => "03",
                "estado" => "Error en Firma",
                "descripcionMsg" => "Error al firmar documento: " . $th->getMessage(),
                "observacionesMsg" => "Verificar conectividad con el servicio de firma Roma Copies",
                "nuEnvios" => 1
            ];
            return $error;
        }
        //return "aqui llego";
        //return $response;
        $objResponse = json_decode($response, true);

        // Validar respuesta del firmador como en Roma Copies
        if (!$objResponse || !isset($objResponse["body"])) {
            $error = [
                "codEstado" => "03",
                "estado" => "Error en Firma",
                "descripcionMsg" => "Respuesta inválida del servicio de firma",
                "observacionesMsg" => "El firmador no retornó un documento válido",
                "nuEnvios" => 1
            ];
            return $error;
        }

        // Verificar si hay error en la respuesta del firmador
        if (isset($objResponse["status"]) && $objResponse["status"] == "ERROR") {
            $error = [
                "codEstado" => "03",
                "estado" => "Error en Firma",
                "descripcionMsg" => $objResponse["body"]["mensaje"] ?? "Error en el servicio de firma",
                "observacionesMsg" => "Código: " . ($objResponse["body"]["codigo"] ?? "N/A"),
                "nuEnvios" => 1
            ];
            return $error;
        }

        $objResponse = (array)$objResponse;
        $comprobante_encriptado = $objResponse["body"];
        $validacion_usuario = [
            "user"  => str_replace('-', '', $emisor[0]->nit),
            "pwd"   => $emisor[0]->claveApiMH
        ];

        //dd($validacion_usuario);
        //dd($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial));
        if ($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial) == "OK") {
            // return 'paso validacion';
            $token = Session::get($id_empresa);
            //dd("token if",$token);
            //$ambiente = $comprobante["documento"][0]->ambiente;
            //dd($documento[0]);
            //return ["token" => $token];
            //dd($codTransaccion);
            if ($codTransaccion == "01" || $codTransaccion == "05" || $codTransaccion == "06") {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => 1, //intval($comprobante["nuEnvio"]),
                    "version"       => intval($version),
                    "tipoDte"       => $tipo_documento,
                    "documento"     => $comprobante_encriptado
                ];
            } else {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => intval($empresa["nuEnvio"]),
                    "version"       => intval($version),
                    "documento"     => $comprobante_encriptado
                ];
            }
            /*try {
                Storage::makeDirectory('debug'); // crea si no existe
                Storage::put('debug/comprobanteHacienda.json', json_encode($comprobante, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Log::warning('No se pudo escribir debug/comprobante.json', ['error' => $e->getMessage()]);
            }*/

            //dd($comprobante_enviar);
            //dd($url_envio);
            try {
                $response_enviado = Http::withToken($token)->post($url_envio, $comprobante_enviar);
            } catch (\Throwable $th) {
                $error = [
                    "codEstado" => "03",
                    "estado" => "Error de Conexión",
                    "descripcionMsg" => "Error al enviar documento a Hacienda: " . $th->getMessage(),
                    "observacionesMsg" => "Verificar conectividad con los servicios de Hacienda",
                    "nuEnvios" => 1
                ];
                return $error;
            }
        } else {
            $tokenError = $this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial);
            //dd("tokenError else",$tokenError);
            if ($tokenError != "OK") {
                $error = [
                    "codEstado" => "03",
                    "estado" => "Error de Autenticación",
                    "descripcionMsg" => "Error al obtener token de Hacienda",
                    "observacionesMsg" => "Verificar credenciales de Hacienda",
                    "nuEnvios" => 1
                ];
                return $error;
            }
            $response_enviado = $tokenError;
        }

        //dd($comprobante);

        //return json_encode($comprobante);
        //dd($response_enviado);
        $objEnviado = json_decode($response_enviado);

        // Validar respuesta de Hacienda como en Roma Copies
        if (!$objEnviado) {
            $error = [
                "codEstado" => "03",
                "estado" => "Error de Respuesta",
                "descripcionMsg" => "Respuesta inválida de Hacienda",
                "observacionesMsg" => "No se pudo procesar la respuesta del servidor",
                "nuEnvios" => 1
            ];
            return $error;
        }

        if (isset($objEnviado->estado)) {
            $estado_envio = $objEnviado->estado;
            $dateString = $objEnviado->fhProcesamiento;
            $myDateTime = DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            //$prueba = gettype($objEnviado->observaciones);
            //dd($objEnviado->observaciones);
            $observaciones = implode("<br>", $objEnviado->observaciones);
            if ($estado_envio == "PROCESADO") {
                $respuesta = [
                    "codEstado"         => "02",
                    "estado"            => "Enviado",
                    "codigoGeneracion"  => $objEnviado->codigoGeneracion,
                    "fhRecibido"        => $newDateString,
                    "selloRecibido"     => $objEnviado->selloRecibido,
                    "estadoHacienda"    => $objEnviado->estado,
                    "nuEnvios"          => 1,
                    "clasificaMsg"      => $objEnviado->clasificaMsg,
                    "codigoMsg"         =>  $objEnviado->codigoMsg,
                    "descripcionMsg"    => $objEnviado->descripcionMsg,
                    "observacionesMsg"  => $observaciones,

                ];
                $comprobante_electronico["selloRecibido"] = $objEnviado->selloRecibido;
                if ($codTransaccion == '01' || $codTransaccion == '05') {
                    if ($tipo_documento == '14') {
                        $respuesta["receptor"] = $comprobante_electronico["sujetoExcluido"];
                    } else {
                        $respuesta["receptor"] = $comprobante_electronico["receptor"];
                    }

                    $respuesta["identificacion"]    = $comprobante_electronico["identificacion"];
                    $respuesta["json_enviado"]      = $comprobante_electronico;
                }

                // $this->envia_correo($comprobante);

            } else {
                $respuesta = [
                    "codEstado" =>  "03",
                    "estado" =>  "Rechazado",
                    "descripcionMsg" =>  $objEnviado->descripcionMsg,
                    "observacionesMsg" =>  $observaciones,
                    "nuEnvios" =>  1
                ];
            }
        } else {
            $error = [
                "codEstado" => "03",
                "estado" => "Error de Estado",
                "descripcionMsg" => "Respuesta de Hacienda sin estado definido",
                "observacionesMsg" => "La respuesta no contiene información de estado válida",
                "nuEnvios" => 1
            ];
            return $error;
        }
        return $respuesta;
    }

    public function getTokenMH($id_empresa, $credenciales, $url_seguridad)
    {
        //dd('entra a gettoken');
        if (!Session::has($id_empresa)) {

            //dd('No encuentra la variable');
            //return ["mensaje" => "llama  getnewtokemh"];
            $respuesta =  $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
        } else {
            $now = new Datetime('now');
            $expira = DateTime::createFromFormat('Y-m-d H:i:s', Session::get($id_empresa . '_fecha'));
            $respuesta = 'OK';
            if ($now > $expira) {
                // dd($expira);
                $respuesta = $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
            }
        }
        //dd(Session::get($id_empresa));
        // return ["mensaje" => "pasa la autorizacion OK estoy en get"];
        if ($respuesta == 'OK') {
            return 'OK';
        } else {
            return $respuesta;
        }
    }

    public function getNewTokenMH($id_empresa, $credenciales, $url_seguridad)
    {
        try {
            $response_usuario = Http::asForm()->post($url_seguridad, $credenciales);
        } catch (\Throwable $th) {
            return [
                "codEstado" => "03",
                "estado" => "Error de Conexión",
                "descripcionMsg" => "Error al conectar con Hacienda: " . $th->getMessage(),
                "observacionesMsg" => "Verificar conectividad con los servicios de Hacienda",
                "nuEnvios" => 1
            ];
        }

        $objValidacion = json_decode($response_usuario, true);

        // Validar respuesta de autenticación
        if (!$objValidacion) {
            return [
                "codEstado" => "03",
                "estado" => "Error de Autenticación",
                "descripcionMsg" => "Respuesta inválida del servicio de autenticación",
                "observacionesMsg" => "No se pudo procesar la respuesta de autenticación",
                "nuEnvios" => 1
            ];
        }

        if ($objValidacion["status"] != 'OK') {
            return [
                "codEstado" => "03",
                "estado" => "Error de Autenticación",
                "descripcionMsg" => "Credenciales inválidas o error en autenticación",
                "observacionesMsg" => "Verificar usuario y contraseña de Hacienda",
                "nuEnvios" => 1
            ];
        } else {
            Session::put($id_empresa, str_replace('Bearer ', '', $objValidacion["body"]["token"]));
            $fecha_expira = date("Y-m-d H:i:S", strtotime('+24 hours'));
            Session::put($id_empresa . '_fecha', $fecha_expira);
            return 'OK';
        }
    }

    public function envia_correo(Request $request)
    {
        $id_factura = $request->id_factura;
        $nombre = $request->nombre;
        $numero = $request->numero;
        $comprobante = Sale::join('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->select(
                'sales.*',
                'dte.json as JsonDTE',
                'dte.codigoGeneracion',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR'
            )
            ->where('sales.id', '=', $id_factura)
            ->get();
        //dd($comprobante);
        $email = $request->email;
        //$email ="briandagoberto20@hotmail.com";
        $pdf = $this->genera_pdf($id_factura);
        $json_root = json_decode($comprobante[0]->JsonDTE);
        $json_enviado = $json_root->json->json_enviado;
        $json = json_encode($json_enviado, JSON_PRETTY_PRINT);
        $archivos = [
            $comprobante[0]->codigoGeneracion . '.pdf' => $pdf->output(),
            $comprobante[0]->codigoGeneracion . '.json' => $json
        ];
        $data = ["nombre" => $json_enviado->receptor->nombre, "numero" => $numero,  "json" => $json_enviado];
        $asunto = "Comprobante de Venta No." . $data["json"]->identificacion->numeroControl . ' de Proveedor: ' . $data["json"]->emisor->nombre;
        $correo = new EnviarCorreo($data);
        $correo->subject($asunto);
        foreach ($archivos as $nombreArchivo => $rutaArchivo) {
            $correo->attachData($rutaArchivo, $nombreArchivo);
        }

        Mail::to($email)->send($correo);
    }
/**
     * Envía correo electrónico con factura PDF (para uso offline - sin JSON de Hacienda)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviar_correo_offline(Request $request)
    {
        try {
            // Validar datos requeridos
            $request->validate([
                'id_factura' => 'required|integer|exists:sales,id',
                'email' => 'required|email',
                'nombre_cliente' => 'nullable|string',
            ]);

            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Obtener datos de la venta y empresa (usando el mismo patrón de la función index)
            $venta = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->join('clients', 'clients.id', '=', 'sales.client_id')
                ->select(
                    'sales.*',
                    'companies.name as company_name',
                    'companies.giro as company_giro',
                    'clients.firstname',
                    'clients.secondname',
                    'clients.firstlastname',
                    'clients.secondlastname',
                    'clients.comercial_name as client_comercial_name',
                    'clients.name_contribuyente as client_name_contribuyente',
                    'clients.email as client_email',
                    'clients.tpersona'
                )
                ->where('sales.id', $id_factura)
                ->first();

            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }

            // Generar PDF usando la función local existente
            $pdf = $this->genera_pdflocal($id_factura);

            if (!$pdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el PDF de la factura'
                ], 500);
            }

            // Preparar datos para el correo
            $nombreEmpresa = $venta->company_name;
            $numeroFactura = $venta->numero_control ?: "#{$venta->id}";

            // Datos del cliente (construir nombre según el tipo de persona)
            $nombreCompleto = '';
            if ($venta->tpersona === 'N') { // Persona Natural
                $nombreCompleto = trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''));
            } else { // Persona Jurídica
                $nombreCompleto = $venta->client_comercial_name ?: $venta->client_name_contribuyente;
            }

            $clienteInfo = [
                'nombre' => $nombre_cliente ?: $nombreCompleto,
                'email' => $venta->client_email,
                'telefono' => '', // No incluimos teléfono por ahora para evitar errores
                'direccion' => '' // No incluimos dirección por ahora para evitar errores
            ];

            // Datos para la plantilla del correo
            $dataCorreo = [
                'factura' => $venta,
                'cliente' => $clienteInfo,
                'fecha_emision' => $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                'total' => $venta->total ?? 0,
                'subtotal' => $venta->subtotal ?? 0,
                'iva' => $venta->iva ?? 0
            ];

            // Crear instancia del correo
            $correo = new EnviarFacturaOffline($dataCorreo, $numeroFactura, $nombreEmpresa);

            // Adjuntar PDF
            $nombreArchivoPdf = "Comprobante_{$numeroFactura}.pdf";
            $correo->attachData($pdf->output(), $nombreArchivoPdf, [
                'mime' => 'application/pdf',
            ]);

            // Usar la configuración existente del .env (sin modificaciones)
            // La configuración se toma automáticamente de las variables de entorno

            // Enviar correo
            Mail::to($email)->send($correo);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente',
                'data' => [
                    'email' => $email,
                    'numero_factura' => $numeroFactura,
                    'empresa' => $nombreEmpresa,
                    'cliente' => $clienteInfo['nombre']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al enviar correo offline: ' . $e->getMessage(), [
                'id_factura' => $request->id_factura ?? null,
                'email' => $request->email ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al enviar el correo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function genera_pdf($id)
    {
        $factura = Sale::leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->leftjoin('departments as dep', 'dep.id', '=', 'add.department_id')
            ->leftjoin('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->join('typedocuments as typedoc', 'typedoc.id', '=', 'sales.typedocument_id')
            ->select(
                DB::raw('dte.json as dte_json'),
                DB::raw('sales.json as sale_json'),
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR',
                'typedoc.codemh'
            )
            ->where('sales.id', '=', $id)
            ->get();
        $comprobante = json_decode($factura, true);
        // Usar como referencia el JSON de sales (como en el proyecto de referencia);
        // si no está, caer al JSON de DTE.
        $rawJson = $comprobante[0]['sale_json'] ?? ($comprobante[0]['dte_json'] ?? null);
        if (!$rawJson) {
            throw new \Exception('No se encontró JSON de la venta/DTE para la venta ' . $id);
        }
        $data = json_decode($rawJson, true);
        //print_r($data);
        //dd($data);
        $tipo_comprobante = $data["documento"][0]["tipodocumento"];
        //dd($tipo_comprobante);
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crf';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.fac';
                break;
            case '14': //FSE
                $rptComprobante = 'pdf.fse';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05': //NCR
                $rptComprobante = 'pdf.ncr';
                break;

            default:
                throw new \Exception("Tipo de comprobante no soportado: $tipo_comprobante");
                break;
        }
        @$fecha = $data["json"]["fhRecibido"] ?? ($data["documento"][0]["fechacreacion"] ?? date('Y-m-d'));
        // Generar QR en el formato que espera cada vista:
        // - FAC (01) y FSE (14): la vista usa <img src="data:image/png;base64,{{$qr}}"> → PNG base64
        // - CRF (03)/FEX (11)/NCR (05): vistas usan {!! $qr !!} → SVG
        @$qr = '';
        $ambiente = $data["documento"][0]["ambiente"] ?? null;
        $codigoGeneracion = $data["json"]["codigoGeneracion"] ?? null;


        if ($ambiente && $codigoGeneracion) {
            // Usar PNG base64 para todos los tipos (más compatible con PDF)
            $url = urlCodigoQR($ambiente, $codigoGeneracion, $fecha);
            try {
                $qrPng = \QrCode::format('png')->size(170)->generate($url);
                @$qr = base64_encode($qrPng);
            } catch (\Exception $e) {
                @$qr = '';
            }
        } else {
            \Log::warning("No se puede generar QR: ambiente=$ambiente, codigoGeneracion=$codigoGeneracion");
        }
        //return  '<img src="data:image/png;base64,'.$qr .'">';
        $data["codTransaccion"] = "01";
        $data["PaisE"] = $factura[0]['PaisE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["PaisR"] = $factura[0]['PaisR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["qr"] = $qr;

        // Variable $dte para NCR y FEX
        $identificacion = $data["json"]["identificacion"] ?? null;
        $data["dte"] = $identificacion["numeroControl"] ?? '';

        // Toda la información del PDF debe venir de json_enviado cuando exista
        $je = $data["json"]["json_enviado"] ?? null;
        if (!empty($je) && is_array($je)) {
            if (!empty($je["emisor"]) && is_array($je["emisor"])) {
                $emisorJson = $je["emisor"];
                $direccionEmisor = $emisorJson["direccion"] ?? ["complemento" => ($emisorJson["direccion"] ?? '')];
                $data["emisor"] = [[
                    "nrc" => $emisorJson["nrc"] ?? ($emisorJson["ncr"] ?? ''),
                    "ncr" => $emisorJson["nrc"] ?? ($emisorJson["ncr"] ?? ''),
                    "nit" => $emisorJson["nit"] ?? '',
                    "nombre" => $emisorJson["nombre"] ?? ($emisorJson["nombreComercial"] ?? ''),
                    "descActividad" => $emisorJson["descActividad"] ?? '',
                    "nombreComercial" => $emisorJson["nombreComercial"] ?? '',
                    "telefono" => $emisorJson["telefono"] ?? '',
                    "correo" => $emisorJson["correo"] ?? '',
                    "direccion" => $direccionEmisor
                ]];
            }
            if (!empty($je["receptor"]) && is_array($je["receptor"])) {
                $data["json"]["receptor"] = $je["receptor"];
                $receptorJson = $je["receptor"];
                $dirRec = $receptorJson["direccion"] ?? [];
                $complementoRec = is_array($dirRec) ? ($dirRec["complemento"] ?? '') : $dirRec;
                // Formato para fac (asociativo) y para NCR/FEX (cliente[0] con nombre_cliente, etc.)
                $data["cliente"] = [[
                    "tipoDocumento" => $receptorJson["tipoDocumento"] ?? null,
                    "numDocumento" => $receptorJson["numDocumento"] ?? ($receptorJson["nit"] ?? null),
                    "correo" => $receptorJson["correo"] ?? null,
                    "nombre" => $receptorJson["nombre"] ?? null,
                    "nombre_cliente" => $receptorJson["nombre"] ?? '',
                    "id_cliente" => $receptorJson["id_cliente"] ?? '',
                    "nit" => $receptorJson["nit"] ?? '',
                    "email_cliente" => $receptorJson["correo"] ?? '',
                    "ncr" => $receptorJson["nrc"] ?? ($receptorJson["ncr"] ?? ''),
                    "direccion_cliente" => $complementoRec,
                    "telefono_cliente" => $receptorJson["telefono"] ?? '',
                    "descActividad" => $receptorJson["descActividad"] ?? ''
                ]];
            }
        }

        // Alinear variables con la vista pdf.fac cuando no hay json_enviado
        if (!isset($data["emisor"])) {
            $emisorJson = $data["json"]["emisor"] ?? null;
            if ($emisorJson) {
                $direccionEmisor = $emisorJson["direccion"] ?? ["complemento" => ($emisorJson["direccion"] ?? '')];
                $nrc = $emisorJson["nrc"] ?? ($emisorJson["ncr"] ?? '');
                $nit = $emisorJson["nit"] ?? '';
                $nombre = $emisorJson["nombre"] ?? ($emisorJson["nombreComercial"] ?? '');
                $descActividad = $emisorJson["descActividad"] ?? '';
                $nombreComercial = $emisorJson["nombreComercial"] ?? '';
                $telefono = $emisorJson["telefono"] ?? '';
                $correo = $emisorJson["correo"] ?? '';

                $data["emisor"] = [[
                    "nrc" => $nrc,
                    "ncr" => $nrc,
                    "nit" => $nit,
                    "nombre" => $nombre,
                    "descActividad" => $descActividad,
                    "nombreComercial" => $nombreComercial,
                    "telefono" => $telefono,
                    "correo" => $correo,
                    "direccion" => $direccionEmisor
                ]];
            }
        }

        if (!isset($data["cliente"])) {
            $receptorJson = $data["json"]["receptor"] ?? null;
            if ($receptorJson) {
                $data["cliente"] = [
                    "tipoDocumento" => $receptorJson["tipoDocumento"] ?? null,
                    "numDocumento" => $receptorJson["numDocumento"] ?? null,
                    "correo" => $receptorJson["correo"] ?? null,
                    "nombre" => $receptorJson["nombre"] ?? null
                ];
            }
        }

        // Mapear COMPROBANTE para NCR y FEX usando la estructura real de datos
        \Log::info("Verificando mapeo de comprobante", [
            'tipo_comprobante' => $tipo_comprobante,
            'es_ncr_fex' => in_array($tipo_comprobante, ['05', '11']),
            'comprobante_exists' => isset($data["comprobante"]),
            'data_keys' => array_keys($data)
        ]);

        if (in_array($tipo_comprobante, ['05', '11'])) {
            // Usar la estructura real de datos que está llegando
            $emisor = $data["emisor"][0] ?? [];
            $receptor = $data["cliente"][0] ?? []; // Cliente es un array con índice 0
            $documento = $data["documento"][0] ?? [];
            $totales = $data["totales"] ?? [];
            $detalle = $data["detalle"][0] ?? []; // Detalle es un array con índice 0
            $identificacion = $data["json"]["identificacion"] ?? [];
            $resumen = $data["json"]["json_enviado"]["resumen"] ?? [];
            $extension = $data["json"]["json_enviado"]["extension"] ?? [];
            $cuerpoDocumento = $data["json"]["json_enviado"]["cuerpoDocumento"] ?? [];

            $data["comprobante"] = [
                [0 => [ // Estructura que espera NCR/FEX
                    "nombre_empresa" => $emisor["nombre"] ?? ($emisor["nombreComercial"] ?? ''),
                    "nit_emisor" => $emisor["nit"] ?? '',
                    "nrc_emisor" => $emisor["nrc"] ?? ($emisor["ncr"] ?? ''),
                    "descActividad" => $emisor["descActividad"] ?? '',
                    "complemento_emisor" => is_array($emisor["direccion"] ?? null) ? ($emisor["direccion"]["complemento"] ?? '') : ($emisor["direccion"] ?? ''),
                    "municipio_emisor" => $data["MunicipioE"] ?? '',
                    "departamento_emisor" => $data["DepartamentoE"] ?? '',
                    "telefono" => $emisor["telefono"] ?? '',
                    "correo" => $emisor["correo"] ?? '',
                    "nombreComercial" => $emisor["nombreComercial"] ?? ($emisor["nombre"] ?? ''),
                    "tipo_establecimiento" => $emisor["tipoEstablecimiento"] ?? '02',
                    "nombre_tienda" => $emisor["nombreComercial"] ?? ($emisor["nombre"] ?? ''),

                    // Datos del documento
                    "codigoGeneracion" => $identificacion["codigoGeneracion"] ?? '',
                    "selloRecibido" => $data["json"]["selloRecibido"] ?? '',
                    "version" => $identificacion["version"] ?? '',
                    "fecEmi" => $identificacion["fecEmi"] ?? '',
                    "horEmi" => $identificacion["horEmi"] ?? '',
                    "nu_doc" => $documento["actual"] ?? '',
                    "id_doc" => $documento["id_doc"] ?? '',

                    // Datos del receptor
                    "id_cliente" => $receptor["id_cliente"] ?? '',
                    "nombre" => $receptor["nombre_cliente"] ?? '',
                    "descActividad_receptor" => $receptor["descActividad"] ?? '',
                    "nit_receptor" => $receptor["nit"] ?? '',
                    "correo_receptor" => $receptor["email_cliente"] ?? '',
                    "nrc_receptor" => $receptor["ncr"] ?? '',
                    "complemento_receptor" => $receptor["direccion_cliente"] ?? '',
                    "telefono_receptor" => $receptor["telefono_cliente"] ?? '',
                    "municipio_receptor" => $data["MunicipioR"] ?? '',
                    "departamento_receptor" => $data["DepartamentoR"] ?? '',
                    "condicionOperacion" => $resumen["condicionOperacion"] ?? '1',
                    "Pais" => $data["PaisR"] ?? '',

                    // Totales
                    "total_letras" => $resumen["totalLetras"] ?? '',
                    "tot_nosujeto" => $totales["totalNoSuj"] ?? 0,
                    "tot_exento" => $totales["totalExenta"] ?? 0,
                    "tot_gravado" => $totales["totalGravada"] ?? 0,
                    "subTotalVentas" => $totales["subTotalVentas"] ?? 0,
                    "iva" => $totales["totalIva"] ?? 0,
                    "subTotal" => $totales["subTotalVentas"] ?? 0,
                    "ivaPerci1" => $totales["ivaPerci1"] ?? 0,
                    "ivaRete1" => $totales["ivaRete1"] ?? 0,
                    "totalNoGravado" => 0, // No hay este campo en los totales
                    "totalPagar" => $totales["totalPagar"] ?? 0,

                    // Extension
                    "nombEntrega" => $extension["nombEntrega"] ?? null,
                    "docuEntrega" => $extension["docuEntrega"] ?? null,
                    "nombRecibe" => $extension["nombRecibe"] ?? null,
                    "docuRecibe" => $extension["docuRecibe"] ?? null,

                    // Formas de pago
                    "credito" => ($resumen["condicionOperacion"] == "02") ? ($totales["totalPagar"] ?? 0) : 0,
                    "contado" => ($resumen["condicionOperacion"] == "01") ? ($totales["totalPagar"] ?? 0) : 0,
                    "tarjeta" => ($resumen["condicionOperacion"] == "03") ? ($totales["totalPagar"] ?? 0) : 0,
                ]],
                [1 => array_map(function($item) {
                    return [
                        "descripcion" => $item["descripcion"] ?? '',
                        "pre_unitario" => $item["precioUni"] ?? 0,
                        "imp_int_det" => $item["ventaNoSuj"] ?? 0,
                        "no_sujetas" => $item["ventaNoSuj"] ?? 0,
                        "excento" => $item["ventaExenta"] ?? 0,
                        "gravado" => $item["ventaGravada"] ?? 0,
                    ];
                }, $cuerpoDocumento)] // Array para detalles del documento
            ];

            \Log::info("Datos para mapeo NCR", [
                'cuerpoDocumento_count' => count($cuerpoDocumento),
                'cuerpoDocumento_data' => $cuerpoDocumento,
                'detalle_count' => count($detalle),
                'detalle_data' => $detalle,
                'emisor_nombre' => $emisor["nombre"] ?? 'NO_FOUND',
                'receptor_nombre' => $receptor["nombre_cliente"] ?? 'NO_FOUND'
            ]);

            \Log::info("Comprobante mapeado exitosamente", [
                'tipo' => $tipo_comprobante,
                'comprobante_keys' => array_keys($data["comprobante"]),
                'comprobante_1_count' => count($data["comprobante"][1] ?? []),
                'cuerpoDocumento_count' => count($cuerpoDocumento),
                'emisor_nombre' => $emisor["nombre"] ?? 'NO_FOUND',
                'receptor_nombre' => $receptor["nombre_cliente"] ?? 'NO_FOUND',
                'total_pagar' => $totales["totalPagar"] ?? 'NO_FOUND'
            ]);

            //dd($data["comprobante"]);
        }
        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
        $pdf->loadHtml(ob_get_clean());
        $pdf->setPaper($tamaño, $orientacion);
        $pdf->getDomPDF()->set_option("enable_php", true);

        // Agregar usuario logueado para fallbacks en las vistas
        $usuario = auth()->user();
        $data['usuario'] = $usuario;

        // Fallback para comprobante si no está definido
        if (!isset($data['comprobante'])) {
            $data['comprobante'] = [[0 => []], [1 => []]];
        }

        // Asegurar que comprobante[1] siempre esté definido
        if (!isset($data['comprobante'][1])) {
            $data['comprobante'][1] = [];
        }
        $pdf->loadView($rptComprobante, $data);
        return $pdf;
    }
    public function genera_pdflocal($id)
    {
        try {
                        // Consulta optimizada para reducir uso de memoria
            $factura = Sale::select(
                    'sales.id',
                    'sales.json',
                    'sales.company_id',
                    'sales.client_id',
                    'sales.typedocument_id',
                    'sales.totalamount',
                    'sales.date',
                    'sales.created_at'
                )
                ->where('sales.id', $id)
                ->first();

            if (!$factura) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            // Cargar solo los datos necesarios de forma separada para evitar joins pesados
            $company = \App\Models\Company::select('ncr', 'name', 'nit', 'giro', 'economicactivity_id')
                ->where('id', $factura->company_id)
                ->first();

            $client = \App\Models\Client::select('id', 'firstname', 'secondname', 'firstlastname', 'secondlastname', 'nit', 'email', 'tpersona', 'name_contribuyente', 'comercial_name')
                ->where('id', $factura->client_id)
                ->first();

            $typedocument = \App\Models\Typedocument::select('codemh')
                ->where('id', $factura->typedocument_id)
                ->first();

            if (!$factura) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            if (!$factura->json) {
                throw new \Exception("Datos de factura incompletos para ID $id");
            }
            //dd($factura);
            $data = json_decode($factura->json, true);
            //dd($data);

            if (!$data) {
                throw new \Exception("No se pudo decodificar los datos JSON de la factura");
            }

            //print_r($data);
            //dd($data);
            //$tipo_comprobante = $data["documento"][0]["tipodocumento"];
            $tipo_comprobante = $typedocument->codemh ?? '01';

            // Verificar si tiene DTE (considerar múltiples estados válidos)
            $saleForDte = Sale::with('dte')->find($id);
            $hasDte = false;
            if ($saleForDte && method_exists($saleForDte, 'hasDte')) {
                if ($saleForDte->hasDte() && $saleForDte->dte) {
                    $dte = $saleForDte->dte;
                    // Estados válidos para considerar que tiene DTE listo para PDF
                    $estadosValidos = ['PROCESADO', 'Enviado', 'Enviado'];
                    $estadoHaciendaValido = !empty($dte->estadoHacienda) && in_array($dte->estadoHacienda, ['PROCESADO', 'Enviado']);
                    $estadoInternoValido = !empty($dte->Estado) && in_array($dte->Estado, $estadosValidos);

                    $hasDte = $estadoHaciendaValido || $estadoInternoValido;

                    // Log para debugging
                    \Log::info("Validación DTE para venta $id: Estado='{$dte->Estado}', EstadoHacienda='{$dte->estadoHacienda}', HasDte=$hasDte");
                }
            } else {
                $hasDte = !empty($factura->json);
            }

            // Determinar qué vista usar
            $rptComprobante = $this->getPdfViewByType($tipo_comprobante, $hasDte);

        // Verificar que la vista existe
        if (!view()->exists($rptComprobante)) {
            throw new \Exception("Vista PDF no encontrada: $rptComprobante");
        }
            //$fecha = $data["json"]["fhRecibido"];
            //dd($data);
            $fecha = $data['documento'][0]['fechacreacion'] ?? $factura->date ?? date('Y-m-d');

            // Manejo seguro del QR (solo si hay DTE)
            $qr = '';
            if ($hasDte && $saleForDte && $saleForDte->dte) {
                try {
                    if (function_exists('codigoQR')) {
                        $ambiente = $saleForDte->dte->ambiente_id ?? '00';
                        $codigoGeneracion = $saleForDte->dte->codigoGeneracion ?? '';
                        $fechaQr = $saleForDte->dte->fhRecibido ? $saleForDte->dte->fhRecibido->format('Y-m-d') : $fecha;

                        if ($codigoGeneracion) {
                            $qr = codigoQR($ambiente, $codigoGeneracion, $fechaQr);
                            \Log::info("QR generado para venta $id: ambiente=$ambiente, codigo=$codigoGeneracion, fecha=$fechaQr, QR=$qr");
                        } else {
                            \Log::warning("No se pudo generar QR para venta $id: código de generación vacío");
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error generando código QR para venta $id: " . $e->getMessage());
                    $qr = '';
                }
            }
            //return  '<img src="data:image/png;base64,'.$qr .'">';
            $data["codTransaccion"] = "01";
            $data["PaisE"] = '';
            $data["DepartamentoE"] = '';
            $data["MunicipioE"] = '';
            $data["PaisR"] = '';
            $data["DepartamentoR"] = '';
            $data["MunicipioR"] = '';

            // Asegurar que los datos estén en el formato correcto para la vista
            $data["emisor"] = $data["emisor"] ?? [];
            $data["cliente"] = $data["cliente"] ?? [];
            $data["documento"] = $data["documento"] ?? [];
            $data["detalle"] = $data["detalle"] ?? [];
            $data["totales"] = $data["totales"] ?? [];

            // Agregar QR y datos del DTE. Preservar json_enviado cuando exista (toda la info del PDF debe salir de ahí).
            $data["qr"] = $qr;
            $jsonFromDecode = $data["json"] ?? [];
            $tieneJsonEnviado = !empty($jsonFromDecode["json_enviado"]) && is_array($jsonFromDecode["json_enviado"]);

            if ($tieneJsonEnviado) {
                // No sobrescribir: mantener el json completo (incl. json_enviado) para que los PDF usen esos datos
                $data["json"] = $jsonFromDecode;
                if ($hasDte && $saleForDte && $saleForDte->dte) {
                    $data["json"]["selloRecibido"] = $data["json"]["selloRecibido"] ?? ($saleForDte->dte->selloRecibido ?? '');
                    $data["json"]["codigoGeneracion"] = $data["json"]["codigoGeneracion"] ?? ($saleForDte->dte->codigoGeneracion ?? '');
                    $data["json"]["identificacion"] = $data["json"]["identificacion"] ?? [];
                }
            } elseif ($hasDte && $saleForDte && $saleForDte->dte) {
                $data["json"] = [
                    "selloRecibido" => $saleForDte->dte->selloRecibido ?? '',
                    "codigoGeneracion" => $saleForDte->dte->codigoGeneracion ?? '',
                    "numeroControl" => $saleForDte->dte->id_doc ?? '',
                    "fhRecibido" => $saleForDte->dte->fhRecibido ? $saleForDte->dte->fhRecibido->format('Y-m-d H:i:s') : '',
                    "Estado" => $saleForDte->dte->Estado ?? '',
                    "estadoHacienda" => $saleForDte->dte->estadoHacienda ?? ''
                ];
            } else {
                $data["json"] = [
                    "selloRecibido" => '',
                    "codigoGeneracion" => '',
                    "numeroControl" => '',
                    "fhRecibido" => '',
                    "Estado" => '',
                    "estadoHacienda" => ''
                ];
            }

            // Toda la información del PDF debe venir de json_enviado cuando exista
            if ($tieneJsonEnviado) {
                $je = $data["json"]["json_enviado"];
                // Emisor desde json_enviado
                if (!empty($je["emisor"]) && is_array($je["emisor"])) {
                    $emisorJe = $je["emisor"];
                    $dirEmisor = $emisorJe["direccion"] ?? [];
                    $data["emisor"] = [[
                        'ncr' => $emisorJe["nrc"] ?? ($emisorJe["ncr"] ?? ''),
                        'nombreComercial' => $emisorJe["nombreComercial"] ?? ($emisorJe["nombre"] ?? ''),
                        'nit' => $emisorJe["nit"] ?? '',
                        'descActividad' => $emisorJe["descActividad"] ?? '',
                        'direccion' => is_array($dirEmisor) ? ($dirEmisor["complemento"] ?? '') : $dirEmisor,
                        'municipio' => $dirEmisor["municipio"] ?? '',
                        'departamento' => $dirEmisor["departamento"] ?? '',
                        'telefono' => $emisorJe["telefono"] ?? '',
                        'correo' => $emisorJe["correo"] ?? ''
                    ]];
                }
                // Cliente/receptor desde json_enviado
                if (!empty($je["receptor"]) && is_array($je["receptor"])) {
                    $rec = $je["receptor"];
                    $dirRec = $rec["direccion"] ?? [];
                    $data["cliente"] = [[
                        'idcliente' => $client ? ($client->id ?? '') : ($data["cliente"][0]["idcliente"] ?? ''),
                        'nombre' => $rec["nombre"] ?? '',
                        'nit' => $rec["nit"] ?? ($rec["numDocumento"] ?? ''),
                        'correo' => $rec["correo"] ?? '',
                        'ncr' => $rec["nrc"] ?? ($rec["ncr"] ?? ''),
                        'direccion' => is_array($dirRec) ? ($dirRec["complemento"] ?? '') : $dirRec,
                        'telefono' => $rec["telefono"] ?? '',
                        'descActividad' => $rec["descActividad"] ?? '',
                        'municipio' => $dirRec["municipio"] ?? '',
                        'departamento' => $dirRec["departamento"] ?? ''
                    ]];
                    $data["json"]["receptor"] = $rec;
                }
            } else {
                // Sin json_enviado: fallback a empresa/cliente del modelo
                if (empty($data["emisor"]) && $company) {
                    $data["emisor"] = [[
                        'ncr' => $company->ncr,
                        'nombreComercial' => $company->name,
                        'nit' => $company->nit,
                        'descActividad' => $company->giro ?? 'Sin especificar',
                        'direccion' => 'Dirección de la empresa',
                        'municipio' => 'Municipio',
                        'departamento' => 'Departamento',
                        'telefono' => 'Sin especificar',
                        'correo' => 'Sin especificar'
                    ]];
                }
                $clientDisplayName = '';
                if ($client) {
                    if (($client->tpersona ?? '') === 'J') {
                        $clientDisplayName = trim($client->name_contribuyente ?? '') ?: trim($client->comercial_name ?? '') ?: 'Cliente';
                    } else {
                        $clientDisplayName = trim(
                            ($client->firstname ?? '') . ' ' .
                            ($client->secondname ?? '') . ' ' .
                            ($client->firstlastname ?? '') . ' ' .
                            ($client->secondlastname ?? '')
                        ) ?: 'Cliente General';
                    }
                }
                if (empty($data["cliente"]) && $client) {
                    $data["cliente"] = [[
                        'idcliente' => $client->id,
                        'nombre' => $clientDisplayName,
                        'nit' => $client->nit ?? '',
                        'correo' => $client->email ?? ''
                    ]];
                } elseif ($client && !empty($data["cliente"]) && isset($data["cliente"][0]) && is_array($data["cliente"][0]) && empty(trim($data["cliente"][0]["nombre"] ?? ''))) {
                    $data["cliente"][0]["nombre"] = $clientDisplayName;
                }
                if ($client && isset($data["json"]) && is_array($data["json"])) {
                    $data["json"]["receptor"] = $data["json"]["receptor"] ?? [];
                    if (empty(trim($data["json"]["receptor"]["nombre"] ?? ''))) {
                        $data["json"]["receptor"]["nombre"] = $clientDisplayName;
                    }
                }
            }

            // Log para debugging
            \Log::info("Datos pasados a la vista PDF:", [
                'emisor_count' => count($data["emisor"]),
                'cliente_count' => count($data["cliente"]),
                'detalle_count' => count($data["detalle"]),
                'totales_keys' => array_keys($data["totales"] ?? [])
            ]);

            // Pasar SVG a la vista
            $data["qr_svg"] = $qr;

            $tamaño = "Letter";
            $orientacion = "Portrait";
            $pdf = app('dompdf.wrapper');

            // Configuraciones optimizadas para rendimiento
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->set_option('defaultFont', 'Arial');
            $pdf->set_option('dpi', 72); // Reducir DPI para mejor rendimiento
            $pdf->set_option('fontHeightRatio', 1.0);
            $pdf->set_option('isPhpEnabled', false); // Deshabilitar PHP en PDF para seguridad y rendimiento
            $pdf->set_option('debugKeepTemp', false);
            $pdf->set_option('debugCss', false);
            $pdf->set_option('debugLayout', false);
            $pdf->set_option('debugLayoutLines', false);
            $pdf->set_option('debugLayoutBlocks', false);
            $pdf->set_option('debugLayoutInline', false);
            $pdf->set_option('debugLayoutPaddingBox', false);

            // Optimizar memoria
            ini_set('memory_limit', '128M'); // Reducir memoria
            ini_set('max_execution_time', 30); // Limitar tiempo de ejecución

            $pdf->setPaper($tamaño, $orientacion);
            $pdf->getDomPDF()->set_option("enable_php", true);

            // Agregar usuario logueado para fallbacks en las vistas
            $usuario = auth()->user();
            $data['usuario'] = $usuario;

            $pdf->loadView($rptComprobante, $data);
            //dd($pdf);
            return $pdf;

        } catch (\Exception $e) {
            \Log::error("Error en genera_pdflocal para ID $id: " . $e->getMessage());
            throw $e; // Re-lanzar la excepción para que la maneje el método print()
        }
    }
    public function print($id)
    {
        try {
            \Log::info("Iniciando generación de PDF para venta ID: $id");

            // Si la venta ya tiene DTE, generar PDF con datos del DTE (incluye JSON y QR oficiales)
            $sale = Sale::with('dte')->find($id);
            if (!$sale) {
                throw new \Exception("Venta con ID $id no encontrada");
            }

            $hasDte = $sale && $sale->dte()->exists();
            \Log::info("Venta ID: $id, tiene DTE: " . ($hasDte ? 'SÍ' : 'NO'));

            if ($hasDte) {
                $pdf = $this->genera_pdf($id);
            } else {
                // Caso contrario, generar PDF local
                $pdf = $this->genera_pdflocal($id);
            }

            \Log::info("PDF generado exitosamente para venta ID: $id");
            return $pdf->stream('comprobante.pdf');
        } catch (\Exception $e) {
            \Log::error("Error al generar PDF para venta ID $id: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());

            // Modo depuración: mostrar error en JSON si se solicita o si es AJAX
            if (request()->ajax() || request()->query('debug') === 'true') {
                return response()->json([
                    'error' => true,
                    'message' => 'Error al generar el PDF: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    public function destinos()
    {
        try {
            $destinos = DB::table('aeropuertos')->get();
            return response()->json($destinos, 200);
        } catch (\Exception $e) {
            // En caso de error de base de datos, retornar array vacío para evitar error 500
            Log::warning('Error al consultar tabla aeropuertos: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function linea()
    {
        try {
            $lineas = DB::table('lineas')->get();
            return response()->json($lineas);
        } catch (\Exception $e) {
            // En caso de error de base de datos, retornar array vacío para evitar error 500
            Log::warning('Error al consultar tabla lineas: ' . $e->getMessage());
            return response()->json([]);
        }
    }



    /**
     * Verificar si una venta tiene DTE
     */
    public function checkDte($id)
    {
        try {
            $sale = Sale::with('dte')->find($id);

            if (!$sale) {
                return response()->json(['error' => 'Venta no encontrada'], 404);
            }

            $hasDte = $sale->hasDte();
            $dteInfo = null;

            if ($hasDte && $sale->dte) {
                $dteInfo = [
                    'codigoGeneracion' => $sale->dte->codigoGeneracion,
                    'id_doc' => $sale->dte->id_doc,
                    'Estado' => $sale->dte->Estado,
                    'fhRecibido' => $sale->dte->fhRecibido ? $sale->dte->fhRecibido->format('Y-m-d H:i:s') : null,
                    'tipoDte' => $sale->dte->tipoDte
                ];
            }

            return response()->json([
                'hasDte' => $hasDte,
                'dteInfo' => $dteInfo,
                'saleId' => $id
            ]);

        } catch (\Exception $e) {
            Log::error("Error verificando DTE para venta $id: " . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Envía correo automáticamente después de completar una venta
     *
     * @param int $saleId ID de la venta
     * @param object|null $dte Objeto DTE si existe, null si no hay DTE
     */
    private function enviarCorreoAutomatico($saleId, $dte = null)
    {
        try {
            // Obtener datos de la venta y cliente
            $venta = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->join('clients', 'clients.id', '=', 'sales.client_id')
                ->select(
                    'sales.*',
                    'companies.name as company_name',
                    'companies.giro as company_giro',
                    'clients.firstname',
                    'clients.secondname',
                    'clients.firstlastname',
                    'clients.secondlastname',
                    'clients.comercial_name as client_comercial_name',
                    'clients.name_contribuyente as client_name_contribuyente',
                    'clients.email as client_email',
                    'clients.tpersona'
                )
                ->where('sales.id', $saleId)
                ->first();

            if (!$venta) {
                \Log::warning("No se encontró la venta ID: {$saleId} para envío automático de correo");
                return;
            }

            // Verificar si el cliente tiene email
            if (empty($venta->client_email)) {
                \Log::info("Cliente de venta ID: {$saleId} no tiene email configurado. No se enviará correo automático.");
                return;
            }

            // Construir nombre del cliente
            $nombreCompleto = '';
            if ($venta->tpersona === 'N') { // Persona Natural
                $nombreCompleto = trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''));
            } else { // Persona Jurídica
                $nombreCompleto = $venta->client_comercial_name ?: $venta->client_name_contribuyente;
            }

            $nombreCliente = $nombreCompleto ?: 'Cliente';
            $emailCliente = $venta->client_email;
            $numeroFactura = $venta->nu_doc ?: "#{$venta->id}";

            // Preparar datos para el correo
            $requestData = [
                'id_factura' => $saleId,
                'email' => $emailCliente,
                'nombre_cliente' => $nombreCliente,
                'numero_factura' => $numeroFactura
            ];

            // Crear request simulado
            $request = new \Illuminate\Http\Request();
            $request->merge($requestData);

            if ($dte && $dte->json) {
                // Envío con DTE (PDF + JSON)
                $this->enviarCorreoConDte($request);
            } else {
                // Envío sin DTE (solo PDF)
                $this->enviarCorreoSinDte($request);
            }

            \Log::info("Correo automático enviado exitosamente para venta ID: {$saleId} a: {$emailCliente}");

        } catch (\Exception $e) {
            \Log::error("Error enviando correo automático para venta ID: {$saleId} - " . $e->getMessage());
            // No lanzar excepción para no afectar el proceso de venta
        }
    }

    /**
     * Envía correo con DTE (PDF + JSON)
     */
    private function enviarCorreoConDte($request)
    {
        try {
            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;
            $numero_factura = $request->numero_factura;

            // Obtener datos de la venta y empresa (usando el mismo patrón del método existente)
            $venta = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->join('clients', 'clients.id', '=', 'sales.client_id')
                ->select(
                    'sales.*',
                    'companies.name as company_name',
                    'companies.giro as company_giro',
                    'clients.firstname',
                    'clients.secondname',
                    'clients.firstlastname',
                    'clients.secondlastname',
                    'clients.comercial_name as client_comercial_name',
                    'clients.name_contribuyente as client_name_contribuyente',
                    'clients.email as client_email',
                    'clients.tpersona'
                )
                ->where('sales.id', $id_factura)
                ->first();

            if (!$venta) {
                throw new \Exception("No se encontró la venta ID: {$id_factura}");
            }

            // Obtener datos del DTE
            $dte = \App\Models\Dte::where('sale_id', $id_factura)->first();
            if (!$dte || !$dte->json) {
                throw new \Exception("No se encontró DTE para la venta ID: {$id_factura}");
            }

            // Generar PDF usando el DTE (formato oficial)
            $pdf = $this->genera_pdf($id_factura);
            if (!$pdf) {
                throw new \Exception("Error al generar el PDF de la factura");
            }

            // Preparar JSON del DTE
            $json_root = json_decode($dte->json);
            $json_enviado = $json_root->json->json_enviado;
            $json = json_encode($json_enviado, JSON_PRETTY_PRINT);

            // Preparar datos para el correo
            $nombreEmpresa = $venta->company_name;
            $numeroFactura = $venta->nu_doc ?: "#{$venta->id}";

            // Datos del cliente (construir nombre según el tipo de persona)
            $nombreCompleto = '';
            if ($venta->tpersona === 'N') { // Persona Natural
                $nombreCompleto = trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''));
            } else { // Persona Jurídica
                $nombreCompleto = $venta->client_comercial_name ?: $venta->client_name_contribuyente;
            }

            $clienteInfo = [
                'nombre' => $nombre_cliente ?: $nombreCompleto,
                'email' => $venta->client_email,
                'telefono' => '',
                'direccion' => ''
            ];

            // Preparar datos para la plantilla de comprobante electrónico
            $dataCorreo = [
                'nombre' => $nombre_cliente ?: $nombreCompleto,
                'json' => $json_root->json, // Usar el JSON completo del DTE
                'numero_control' => $numeroFactura,
                'fecha_emision' => $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
            ];

            // Crear instancia del correo para comprobante electrónico
            $correo = new \App\Mail\EnviarComprobanteElectronico($dataCorreo, $numeroFactura, $nombreEmpresa);

            // Adjuntar PDF
            $nombreArchivoPdf = "Comprobante_{$numeroFactura}.pdf";
            $correo->attachData($pdf->output(), $nombreArchivoPdf, [
                'mime' => 'application/pdf',
            ]);

            // Adjuntar JSON del DTE
            $nombreArchivoJson = "DTE_{$dte->codigoGeneracion}.json";
            $correo->attachData($json, $nombreArchivoJson, [
                'mime' => 'application/json',
            ]);

            // Enviar correo usando la configuración existente del .env
            \Illuminate\Support\Facades\Mail::to($email)->send($correo);

            \Log::info("Correo con DTE enviado exitosamente para venta ID: {$id_factura} a: {$email}");

        } catch (\Exception $e) {
            \Log::error("Error enviando correo con DTE para venta ID: {$request->id_factura} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía correo sin DTE (solo PDF)
     */
    private function enviarCorreoSinDte($request)
    {
        try {
            // Usar el método existente para envío sin DTE
            $this->enviarFacturaPorCorreo($request);

        } catch (\Exception $e) {
            \Log::error("Error enviando correo sin DTE para venta ID: {$request->id_factura} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Determinar qué vista de PDF usar basado en el tipo de documento y si tiene DTE
     */
    private function getPdfViewByType($tipoComprobante, $hasDte = false)
    {
        switch ($tipoComprobante) {
            case '03': //CRF
                return $hasDte ? 'pdf.crf' : 'pdf.crflocal';
            case '01': //FAC
                return $hasDte ? 'pdf.fac' : 'pdf.faclocal';
            case '11':  //FEX
                return 'pdf.fex';
            case '05': //NCR
                return 'pdf.ncr';
            default:
                // Usar faclocal como vista por defecto
                Log::warning("Tipo de comprobante desconocido: $tipoComprobante. Usando vista por defecto.");
                return $hasDte ? 'pdf.fac' : 'pdf.faclocal';
        }
    }

    /**
     * Actualizar el totalamount de una venta basado en sus detalles
     */
    private function updateSaleTotalAmount($saleId)
    {
        try {
            $sale = Sale::find($saleId);
            if (!$sale) {
                return false;
            }

            // Calcular el total basado en los detalles de venta
            $totals = Salesdetail::where('sale_id', $saleId)
                ->selectRaw('
                    SUM(nosujeta) as nosujeta,
                    SUM(exempt) as exempt,
                    SUM(pricesale) as pricesale,
                    SUM(detained13) as iva,
                    SUM(detained) as ivarete,
                    SUM(renta) as renta
                ')
                ->first();

            // Calcular el total a pagar
            $totalAmount = ($totals->nosujeta + $totals->exempt + $totals->pricesale + $totals->iva) - ($totals->renta + $totals->ivarete);

            // Actualizar el totalamount en la venta
            $sale->totalamount = round($totalAmount, 8);
            $sale->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Error actualizando totalamount para venta ' . $saleId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos de un draft de preventa para cargar en el formulario
     * Si draftId = 0, retorna lista de todos los borradores
     * Si draftId > 0, retorna detalles específicos del borrador
     */
    public function getDraftPreventaData($draftId)
    {
        try {
            // Si draftId es 0, retornar lista de borradores
            if ($draftId == 0) {
                $drafts = Sale::with(['client', 'company', 'typedocument', 'user'])
                    ->where('typesale', '3') // Solo drafts de preventas
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'success' => true,
                    'drafts' => $drafts,
                    'count' => $drafts->count()
                ]);
            }

            // Si draftId > 0, retornar detalles específicos
            $draft = Sale::with([
                'client',
                'company',
                'typedocument',
                'user',
                'details.product'
            ])
            ->where('id', $draftId)
            ->where('typesale', '3') // Solo drafts de preventas
            ->first();

            if (!$draft) {
                return response()->json([
                    'success' => false,
                    'message' => 'Draft de preventa no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'draft' => $draft
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar draft de preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcular totales de todas las ventas que tengan problemas con el totalamount
     */
    public function recalculateSalesTotals()
    {
        try {
            // Obtener ventas que tienen detalles pero totalamount incorrecto
            $salesWithIssues = Sale::whereHas('details')
                ->where(function($query) {
                    $query->whereNull('totalamount')
                          ->orWhere('totalamount', 0);
                })
                ->get();

            $updated = 0;
            $errors = 0;

            foreach ($salesWithIssues as $sale) {
                if ($this->updateSaleTotalAmount($sale->id)) {
                    $updated++;
                } else {
                    $errors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. Ventas actualizadas: {$updated}, Errores: {$errors}",
                'updated' => $updated,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error recalculando totales de ventas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular totales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía factura por correo electrónico usando la configuración existente del .env
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarFacturaPorCorreo(Request $request)
    {
        try {
            // Validar datos requeridos
            $request->validate([
                'id_factura' => 'required|integer|exists:sales,id',
                'email' => 'required|email',
                'nombre_cliente' => 'nullable|string',
            ]);

            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Obtener datos de la venta y empresa
            $venta = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->join('clients', 'clients.id', '=', 'sales.client_id')
                ->select(
                    'sales.*',
                    'companies.name as company_name',
                    'companies.giro as company_giro',
                    'clients.firstname',
                    'clients.secondname',
                    'clients.firstlastname',
                    'clients.secondlastname',
                    'clients.comercial_name as client_comercial_name',
                    'clients.name_contribuyente as client_name_contribuyente',
                    'clients.email as client_email',
                    'clients.tpersona'
                )
                ->where('sales.id', $id_factura)
                ->first();

            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ], 404);
            }

            // Verificar si la venta tiene DTE
            $tieneDte = Sale::whereHas('dte')->where('id', $id_factura)->exists();

            // Generar PDF usando la función correcta según si tiene DTE
            if ($tieneDte) {
                $pdf = $this->genera_pdf($id_factura);
            } else {
                $pdf = $this->genera_pdflocal($id_factura);
            }

            if (!$pdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el PDF de la factura'
                ], 500);
            }

            // Preparar datos para el correo
            $nombreEmpresa = $venta->company_name;
            $numeroFactura = $venta->numero_control ?: "#{$venta->id}";

            // Datos del cliente (construir nombre según el tipo de persona)
            $nombreCompleto = '';
            if ($venta->tpersona === 'N') { // Persona Natural
                $nombreCompleto = trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''));
            } else { // Persona Jurídica
                $nombreCompleto = $venta->client_comercial_name ?: $venta->client_name_contribuyente;
            }

            $clienteInfo = [
                'nombre' => $nombre_cliente ?: $nombreCompleto,
                'email' => $venta->client_email,
                'telefono' => '',
                'direccion' => ''
            ];

            // Preparar datos según si tiene DTE o no
            if ($tieneDte) {
                // Para documentos con DTE, obtener datos del DTE
                $dte = Sale::with('dte')->where('id', $id_factura)->first()->dte;
                $jsonData = $dte ? json_decode($dte->json) : null;

                $dataCorreo = [
                    'nombre' => $nombre_cliente ?: $nombreCompleto,
                    'json' => $jsonData,
                    'numero_control' => $numeroFactura,
                    'fecha_emision' => $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                ];

                // Crear instancia del correo para DTE
                $correo = new \App\Mail\EnviarComprobanteElectronico($dataCorreo, $numeroFactura, $nombreEmpresa);
            } else {
                // Para documentos sin DTE
                $dataCorreo = [
                    'factura' => $venta,
                    'cliente' => $clienteInfo,
                    'fecha_emision' => $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                    'total' => $venta->total ?? 0,
                    'subtotal' => $venta->subtotal ?? 0,
                    'iva' => $venta->iva ?? 0
                ];

                // Crear instancia del correo para factura offline
                $correo = new \App\Mail\EnviarFacturaOffline($dataCorreo, $numeroFactura, $nombreEmpresa);
            }

            // Adjuntar PDF
            $nombreArchivoPdf = "Comprobante_{$numeroFactura}.pdf";
            $correo->attachData($pdf->output(), $nombreArchivoPdf, [
                'mime' => 'application/pdf',
            ]);

            // Para documentos con DTE, también adjuntar el JSON
            if ($tieneDte && isset($jsonData)) {
                $nombreArchivoJson = "DTE_{$numeroFactura}.json";
                $correo->attachData(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $nombreArchivoJson, [
                    'mime' => 'application/json',
                ]);
            }

            // Enviar correo usando la configuración existente del .env
            Mail::to($email)->send($correo);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente',
                'data' => [
                    'email' => $email,
                    'numero_factura' => $numeroFactura,
                    'empresa' => $nombreEmpresa,
                    'cliente' => $clienteInfo['nombre']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al enviar factura por correo: ' . $e->getMessage(), [
                'id_factura' => $request->id_factura ?? null,
                'email' => $request->email ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al enviar el correo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular precio y conversión para una unidad específica
     */
    public function calculateUnitConversion(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'nullable|numeric|min:0'
        ]);

        try {
            $productId = $request->product_id;
            $unitCode = $request->unit_code;
            $quantity = $request->quantity ?? 1;

            // Usar la nueva función de conversión para ventas
            $conversionData = $this->unitConversionService->calculateSaleConversion($productId, $quantity, $unitCode);

            return response()->json([
                'success' => true,
                'data' => $conversionData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular conversión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener producto con sus unidades de medida disponibles
     */
    public function getproductbyid($id): JsonResponse
    {
        try {


            $product = \App\Models\Product::with(['marca', 'provider', 'inventory.baseUnit'])->find($id);



            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Obtener unidades disponibles para este producto
            $units = $this->unitConversionService->getAvailableUnitsForProduct($id);

            // Obtener inventario actual usando el servicio de conversión
            $stockInfo = null;
            $inventory = $product->inventory;

            if ($inventory) {
                // Obtener información del stock según el tipo de venta del producto
                $availableQuantity = $inventory->base_quantity ?? $inventory->quantity ?? 0;
                $baseUnitName = $inventory->baseUnit->unit_name ?? 'Unidad';

                // Calcular medidas según el tipo de venta
                $measureInfo = [];

                if ($product->sale_type === 'weight') {
                    // Producto por peso (ej: sacos, libras)
                    $measureInfo = [
                        'available_quantity' => $availableQuantity,
                        'unit_name' => $baseUnitName,
                        'total_in_lbs' => $availableQuantity * ($product->weight_per_unit ?? 1),
                        'total_in_kg' => ($availableQuantity * ($product->weight_per_unit ?? 1)) * 0.453592,
                        'measure_type' => 'weight',
                        'measure_unit' => 'libras',
                        'measure_unit_alt' => 'kg'
                    ];
                } elseif ($product->sale_type === 'volume') {
                    // Producto por volumen (ej: depósitos, litros)
                    $measureInfo = [
                        'available_quantity' => $availableQuantity,
                        'unit_name' => $baseUnitName,
                        'total_in_lbs' => $availableQuantity * ($product->volume_per_unit ?? 1),
                        'total_in_liters' => $availableQuantity * ($product->volume_per_unit ?? 1),
                        'total_in_ml' => ($availableQuantity * ($product->volume_per_unit ?? 1)) * 1000,
                        'measure_type' => 'volume',
                        'measure_unit' => 'litros',
                        'measure_unit_alt' => 'ml'
                    ];
                } else {
                    // Producto por unidad (precio normal)
                    $measureInfo = [
                        'available_quantity' => $availableQuantity,
                        'unit_name' => $baseUnitName,
                        'total_in_lbs' => 0, // No aplica para productos por unidad
                        'measure_type' => 'unit',
                        'measure_unit' => $baseUnitName,
                        'measure_unit_alt' => null
                    ];
                }

                $stockInfo = array_merge([
                    'base_unit_id' => $inventory->base_unit_id ?? null,
                    'base_quantity' => $inventory->base_quantity ?? $inventory->quantity ?? 0
                ], $measureInfo);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'base_price' => $product->price, // Precio base del producto (siempre el mismo)
                        'sale_type' => $product->sale_type,
                        'weight_per_unit' => $product->weight_per_unit,
                        'volume_per_unit' => $product->volume_per_unit,
                        'pastillas_per_blister' => $product->pastillas_per_blister,
                        'blisters_per_caja' => $product->blisters_per_caja,
                        'marca_name' => $product->marca->name ?? '',
                        'provider_name' => $product->provider->razonsocial ?? ''
                    ],
                    'units' => $units,
                    'stock' => $stockInfo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descontar cantidad del inventario basado en la unidad de venta
     */
    private function deductFromInventory($productId, $quantity, $unitCode, $unitId, $conversionFactor)
    {
        try {
            // Obtener el producto y su inventario
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                throw new \Exception("Producto no encontrado");
            }

            $inventory = $product->inventory;
            if (!$inventory) {
                // Crear inventario automáticamente si no existe
                \Log::info("📦 Creando inventario automáticamente para producto {$product->name} (ID: {$productId})");
                $inventory = new \App\Models\Inventory();
                $inventory->product_id = $productId;
                $inventory->name = $product->name;
                $inventory->description = $product->description;
                $inventory->quantity = 0;
                $inventory->base_quantity = 0;

                // Determinar unidad base según el tipo de producto
                if ($product->pastillas_per_blister || $product->blisters_per_caja) {
                    // Para productos farmacéuticos, usar PASTILLA como unidad base
                    $pastillaUnit = \App\Models\Unit::where('unit_code', 'PASTILLA')->first();
                    $inventory->base_unit_id = $pastillaUnit ? $pastillaUnit->id : 28;
                } else {
                    $inventory->base_unit_id = 28; // Unidad por defecto
                }

                $inventory->base_unit_price = $product->price ?? 0;
                $inventory->price = $product->price ?? 0;
                $inventory->category = $product->category;
                $inventory->minimum_stock = 0;
                $inventory->user_id = $product->user_id ?? 1;
                $inventory->provider_id = $product->provider_id ?? 1;
                $inventory->active = 1;
                $inventory->save();

                \Log::info("✅ Inventario creado exitosamente para producto {$product->name} (ID: {$productId})");
            }

            // Calcular cantidad base a descontar
            // Preferimos usar base_quantity del inventario y la conversión del servicio
            $baseQuantityToDeduct = $this->unitConversionService->calculateBaseQuantityNeeded($productId, $quantity, $unitCode);

            // Determinar campo de stock base para control consistente
            $currentBaseQty = isset($inventory->base_quantity) ? (float)$inventory->base_quantity : (float)$inventory->quantity;

            // Log detallado para debugging
            \Log::info("Validación de stock en venta:", [
                'product_id' => $productId,
                'quantity_requested' => $quantity,
                'unit_code' => $unitCode,
                'base_quantity_needed' => $baseQuantityToDeduct,
                'current_stock' => $currentBaseQty,
                'is_sufficient' => $currentBaseQty >= $baseQuantityToDeduct
            ]);

            // Permitir inventarios negativos - solo registrar warning
            if ($currentBaseQty < $baseQuantityToDeduct) {
                \Log::warning("⚠️ Stock insuficiente para {$product->name}. Disponible: {$currentBaseQty}, Necesario: {$baseQuantityToDeduct}. Permitiendo inventario negativo.");
            }

            // Descontar del inventario (en base)
            $qtyBefore  = isset($inventory->quantity)      ? (float)$inventory->quantity      : 0;
            $baseBefore = isset($inventory->base_quantity) ? (float)$inventory->base_quantity : 0;

            if (isset($inventory->base_quantity)) {
                $inventory->base_quantity = (float)$inventory->base_quantity - $baseQuantityToDeduct;
            }
            // Mantener compatibilidad con quantity legado
            if (isset($inventory->quantity)) {
                $inventory->quantity = (float)$inventory->quantity - $baseQuantityToDeduct;
            }
            $inventory->save();

            // Registrar movimiento de venta
            try {
                InventoryMovement::record(
                    $inventory, 'venta',
                    $qtyBefore, -$baseQuantityToDeduct,
                    $baseBefore, -$baseQuantityToDeduct,
                    'Sale', null, null,
                    auth()->id(),
                    "Venta: {$quantity} {$unitCode}"
                );
            } catch (\Exception $movErr) {
                \Log::warning('No se pudo registrar movimiento de venta: ' . $movErr->getMessage());
            }

            // Log de la operación
            \Log::info("Inventario actualizado", [
                'product_id' => $productId,
                'product_name' => $product->name,
                'quantity_sold' => $quantity,
                'unit_code' => $unitCode,
                'base_quantity_deducted' => $baseQuantityToDeduct,
                'remaining_stock' => isset($inventory->base_quantity) ? $inventory->base_quantity : $inventory->quantity
            ]);

        } catch (\Exception $e) {
            \Log::error("Error al descontar inventario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida y obtiene datos seguros de arrays para la construcción de documentos
     *
     * @param mixed $array
     * @param string $variableName
     * @param string $context
     * @param int $expectedIndex
     * @return mixed
     * @throws \Exception
     */
    protected function validateDocumentArray($array, string $variableName, string $context, int $expectedIndex = 0)
    {
        try {
            // Validar que el array existe y tiene datos
            if (empty($array)) {
                throw new \Exception("Array \${$variableName} está vacío en contexto: {$context}. Esto puede indicar que no hay detalles de venta.");
            }

            // Validar que es una colección o array
            if (!is_array($array) && !is_object($array)) {
                throw new \Exception("Variable \${$variableName} no es un array en contexto: {$context}");
            }

            // Obtener el conteo
            $count = is_array($array) ? count($array) : (method_exists($array, 'count') ? $array->count() : 0);

            // Validar que tiene el índice esperado
            if ($count === 0) {
                throw new \Exception("Array \${$variableName} no tiene elementos en contexto: {$context}. Verificar que la venta tenga detalles.");
            }

            if (!isset($array[$expectedIndex])) {
                throw new \Exception("Índice {$expectedIndex} no existe en \${$variableName}. Array tiene {$count} elementos en contexto: {$context}");
            }

            return $array[$expectedIndex];

        } catch (\Exception $e) {
            // Log detallado del error
            Log::error("Error validando array en construcción de documento:", [
                'variable' => $variableName,
                'context' => $context,
                'expected_index' => $expectedIndex,
                'array_count' => is_array($array) ? count($array) : (is_object($array) && method_exists($array, 'count') ? $array->count() : 'N/A'),
                'array_type' => gettype($array),
                'error_message' => $e->getMessage(),
                'sale_id' => request()->route('corr') ?? 'unknown'
            ]);

            throw $e;
        }
    }

    /**
     * Construye el array de totales de forma segura
     *
     * @param mixed $detailsbd
     * @param object $salesave
     * @return array
     */
    protected function buildTotalesSafely($detailsbd, $salesave)
    {
        try {
            // Validar que detailsbd tenga datos
            $detailsbdFirst = $this->validateDocumentArray($detailsbd, 'detailsbd', 'construcción de totales');

            // Obtener la retención del agente desde la venta
            $retencionAgente = (float)($salesave->retencion_agente ?? 0);

            // Calcular totalPagar de forma segura
            // Para Factura de Sujeto Excluido (tipo 8), no se incluye IVA en el total
            if ($salesave->typedocument_id == '8') {
                $totalPagar = (
                    (float)($detailsbdFirst->nosujeta ?? 0) +
                    (float)($detailsbdFirst->exentas ?? 0) +
                    (float)($detailsbdFirst->gravadas ?? 0) -
                    ((float)($detailsbdFirst->rentarete ?? 0) + (float)($detailsbdFirst->ivarete ?? 0) + $retencionAgente)
                );
            } else {
            $totalPagar = (
                (float)($detailsbdFirst->nosujeta ?? 0) +
                (float)($detailsbdFirst->exentas ?? 0) +
                (float)($detailsbdFirst->gravadas ?? 0) +
                (float)($detailsbdFirst->iva ?? 0) -
                ((float)($detailsbdFirst->rentarete ?? 0) + (float)($detailsbdFirst->ivarete ?? 0) + $retencionAgente)
            );
            }

            return [
                "totalNoSuj" => (float)($detailsbdFirst->nosujeta ?? 0),
                "totalExenta" => (float)($detailsbdFirst->exentas ?? 0),
                "totalGravada" => (float)($detailsbdFirst->gravadas ?? 0),
                "subTotalVentas" => round((float)($detailsbdFirst->subtotalventas ?? 0), 8),
                "descuNoSuj" => $detailsbdFirst->descnosujeta ?? 0,
                "descuExenta" => $detailsbdFirst->descexenta ?? 0,
                "descuGravada" => $detailsbdFirst->desgravada ?? 0,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => $detailsbdFirst->totaldesc ?? 0,
                "tributos" => null,
                "subTotal" => round((float)($detailsbdFirst->subtotal ?? 0), 8),
                "ivaPerci1" => 0.00,
                "ivaRete1" => round((float)$retencionAgente, 8), // Retención del agente 1%
                "reteRenta" => round((float)($detailsbdFirst->rentarete ?? 0), 8),
                "montoTotalOperacion" => round((float)($detailsbdFirst->subtotal ?? 0), 8),
                "totalNoGravado" => (float)0,
                "totalPagar" => (float)$totalPagar,
                "totalLetras" => numtoletras(round((float)$totalPagar, 2)),
                "saldoFavor" => 0.00,
                "condicionOperacion" => $salesave->waytopay ?? '01',
                "pagos" => null,
                "totalIva" => (float)($detailsbdFirst->iva ?? 0)
            ];

        } catch (\Exception $e) {
            Log::error("Error construyendo totales:", [
                'error' => $e->getMessage(),
                'sale_id' => $salesave->id ?? 'unknown',
                'detailsbd_count' => is_array($detailsbd) ? count($detailsbd) : (is_object($detailsbd) && method_exists($detailsbd, 'count') ? $detailsbd->count() : 'N/A')
            ]);
            throw $e;
        }
    }

    /**
     * Descontar inventario cuando una venta se finaliza
     */
    protected function deductInventoryFromFinalizedSale($saleId)
    {
        try {
            \Log::info("🔄 INICIANDO DESCUENTO DE INVENTARIO para venta finalizada ID: {$saleId}");

            // Obtener todos los detalles de la venta
            $saleDetails = Salesdetail::where('sale_id', $saleId)->get();
            \Log::info("📋 Encontrados " . $saleDetails->count() . " detalles de venta para procesar");

            if ($saleDetails->count() == 0) {
                \Log::warning("⚠️ No se encontraron detalles de venta para la venta ID: {$saleId}");
                return;
            }

            foreach ($saleDetails as $index => $detail) {
                \Log::info("🔍 Procesando detalle " . ($index + 1) . " de " . $saleDetails->count());

                $product = $detail->product;
                if (!$product) {
                    \Log::error("❌ Producto no encontrado para detalle ID: {$detail->id}");
                    continue;
                }

                $quantity = $detail->amountp;
                $unitId = $detail->unit_id;
                $conversionFactor = $detail->conversion_factor;

                // Obtener el código de unidad desde el unit_id
                $unit = \App\Models\Unit::find($unitId);
                $unitCode = $unit ? $unit->unit_code : null;

                \Log::info("📦 Detalle: Producto '{$product->name}' (ID: {$product->id}), Cantidad: {$quantity}, Unidad ID: {$unitId}, Código: {$unitCode}, Factor: {$conversionFactor}");

                if (!$unitCode) {
                    \Log::error("❌ No se pudo obtener el código de unidad para unit_id: {$unitId}");
                    \Log::error("❌ Unidad encontrada: " . ($unit ? json_encode($unit->toArray()) : 'NULL'));
                    continue; // Continuar con el siguiente en lugar de fallar
                }

                // Verificar inventario antes del descuento
                $inventory = $product->inventory;
                $stockBefore = $inventory ? (isset($inventory->base_quantity) ? $inventory->base_quantity : $inventory->quantity) : 0;
                \Log::info("📊 Stock ANTES del descuento: {$stockBefore}");

                // Descontar del inventario usando la función existente
                try {
                    \Log::info("⚙️ Llamando a deductFromInventory...");
                    $this->deductFromInventory($product->id, $quantity, $unitCode, $unitId, $conversionFactor);
                    \Log::info("✅ Descuento completado para producto '{$product->name}'");
                } catch (\Exception $e) {
                    \Log::error("❌ Error al descontar inventario para producto '{$product->name}': " . $e->getMessage());
                    \Log::error("❌ Stack trace: " . $e->getTraceAsString());
                    // Continuar con el siguiente producto sin fallar toda la operación
                }
            }

            \Log::info("✅ PROCESO DE DESCUENTO COMPLETADO para venta ID: {$saleId}");

        } catch (\Exception $e) {
            \Log::error("💥 ERROR CRÍTICO descontando inventario para venta {$saleId}: " . $e->getMessage());
            \Log::error("💥 Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Mostrar el formulario de venta con diseño dinámico
     */
    public function createDynamic()
    {
        $typedocument = request()->get('typedocument', 6); // Por defecto Factura
        $draftId = request()->get('draft_id');
        $corr = request()->get('corr', '');
        $operation = request()->get('operation', '');
        $isNewSale = request()->get('new', false); // Nuevo parámetro para identificar nueva venta

        // Determinar el tipo de documento
        switch ($typedocument) {
            case 6:
                $document = 'Factura';
                break;
            case 8:
                $document = 'Factura de Sujeto Excluido';
                break;
            case 7:
                $document = 'Nota de Débito';
                break;
            case 3:
                $document = 'Crédito Fiscal';
                break;
            default:
                $document = 'Factura';
                break;
        }



        // Obtener empresas del usuario usando la misma lógica del módulo original
        $id_user = auth()->user()->id;
        $companies = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->select('companies.id', 'companies.name', 'companies.tipoContribuyente')
            ->where('permission_company.user_id', '=', $id_user)
            ->get();

        // Si no hay empresas del usuario, usar todas las empresas disponibles
        if ($companies->isEmpty()) {
            $companies = Company::select('id', 'name', 'tipoContribuyente')->get();
        }

        // Si se está cargando un draft (corr), verificar que existe y pertenece al usuario
        $draft = null;
        $draftClient = null;
        $draftDetails = null;

        if ($corr && $operation === 'edit') {

            $draft = Sale::where('id', $corr)
                ->where('user_id', $id_user)
                ->where('typesale', 2) // Borrador
                ->where('state', 1) // Activo
                ->with(['client', 'details.product', 'details.unit']) // Cargar relaciones
                ->first();

            if ($draft) {
                // Actualizar el tipo de documento si es diferente
                $typedocument = $draft->typedocument_id;
                $draftId = $draft->id;

                // Obtener información del cliente asociado
                $draftClient = $draft->client;

                // Obtener detalles de venta con productos y unidades
                $draftDetails = $draft->details;

            }
            //dd($draftDetails);
        }

        return view('sales.create-dynamic', compact(
            'document',
            'typedocument',
            'draftId',
            'corr',
            'operation',
            'companies',
            'id_user',
            'draft',
            'draftClient',
            'draftDetails',
            'isNewSale'
        ));
    }



    /**
     * Agregar producto a la venta
     */
    public function addProduct(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $quantity = $request->get('quantity', 1);

            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ]);
            }

            // Verificar stock
            if ($product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente'
                ]);
            }

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image' => $product->image
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover producto de la venta
     */
    public function removeProduct(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $saleId = $request->get('sale_id');

            // Primero verificar si la venta existe
            $sale = Sale::find($saleId);

            if (!$sale) {
            }

            // Validar que la venta pertenece al usuario y es un borrador
            /*if ($sale->user_id != auth()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene autorización para modificar esta venta'
                ], 403);
            }*/

            // Permitir eliminación en borradores (typesale = 2) o ventas no finalizadas
            if ($sale->typesale != 2 && $sale->typesale != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar productos de ventas en borrador'
                ], 400);
            }

            $saleDetail = Salesdetail::where('sale_id', $saleId)
                ->where('product_id', $productId)
                ->first();

            if (!$saleDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado en la venta'
                ], 404);
            }

            // Eliminar el detalle
            $saleDetail->delete();

            // Recalcular totales de la venta
            $this->updateSaleTotalAmount($saleId);

            return response()->json([
                'success' => true,
                'message' => 'Producto removido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar la venta completa
     */
    public function processSale(Request $request)
    {
        try {
            $documentType = $request->get('document_type', 6);
            $clientId = $request->get('client_id');
            $acuenta = $request->get('acuenta');
            $paymentMethod = $request->get('payment_method', 1);
            $items = $request->get('items', []);
            $totals = $request->get('totals', []);

            // Validar datos requeridos
            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos en la venta'
                ]);
            }

            if (empty($acuenta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Complete la información del cliente'
                ]);
            }

            // Validación: Crédito Fiscal solo para Natural Contribuyente o Jurídico
            // (y con NRC válido)
            if ((string)$documentType === '3') {
                $client = Client::find($clientId);
                if (!$client) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cliente no encontrado para Crédito Fiscal'
                    ], 422);
                }

                $isJuridico = $client->tpersona === 'J';
                $isNaturalContribuyente = $client->tpersona === 'N' && (string)$client->contribuyente === '1';
                $hasNrc = !empty($client->ncr) && $client->ncr !== 'N/A';

                if ((!$isJuridico && !$isNaturalContribuyente) || !$hasNrc) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para Crédito Fiscal debe seleccionar un cliente Jurídico o Natural Contribuyente con NRC válido.'
                    ], 422);
                }
            }

            // Crear la venta
            $sale = new Sale();
            $sale->client_id = $clientId;
            $sale->user_id = auth()->user()->id;
            $sale->company_id = auth()->user()->company_id;
            $sale->typedocument = $documentType;
            $sale->acuenta = $acuenta;
            $sale->waytopay = $paymentMethod;
            $sale->totalamount = $totals['total'] ?? 0;
            $sale->save();

            // Crear los detalles de venta
            foreach ($items as $item) {
                $saleDetail = new Salesdetail();
                $saleDetail->sale_id = $sale->id;
                $saleDetail->product_id = $item['id'];
                $saleDetail->amountp = $item['quantity'];
                $saleDetail->pricesale = $item['price'];
                $saleDetail->total = $item['total'];

                // Campos fiscales
                $saleDetail->nosujeta = $item['nosujeta'] ?? 0;
                $saleDetail->exempt = $item['exempt'] ?? 0;
                $saleDetail->detained13 = $item['iva_rete13'] ?? 0;
                $saleDetail->detained = $item['iva_rete'] ?? 0;

                // Calcular retención de renta según el tipo de documento (lógica de Roma Copies)
                if ($documentType != '8') {
                    $saleDetail->renta = 0; // Factura normal NO tiene retención de renta
                } else {
                    $saleDetail->renta = $item['renta'] ?? 0; // Sujeto Excluido SÍ tiene retención de renta
                }

                $saleDetail->save();

                // Descontar del inventario
                $product = Product::find($item['id']);
                if ($product) {
                    $product->stock -= $item['quantity'];
                    $product->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada correctamente',
                'sale_id' => $sale->id,
                'document_url' => route('sale.print', $sale->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar borrador de venta
     */
    public function saveDraft(Request $request)
    {
        try {
            $documentType = $request->get('document_type', 6);
            $clientId = $request->get('client_id');
            $acuenta = $request->get('acuenta');
            $paymentMethod = $request->get('payment_method', 1);
            $items = $request->get('items', []);
            $totals = $request->get('totals', []);

            // Guardar en sesión o base de datos temporal
            $draftData = [
                'document_type' => $documentType,
                'client_id' => $clientId,
                'acuenta' => $acuenta,
                'payment_method' => $paymentMethod,
                'items' => $items,
                'totals' => $totals,
                'created_at' => now()
            ];

            // Por ahora guardamos en sesión, pero se puede implementar en base de datos
            session(['sale_draft_' . auth()->user()->id => $draftData]);

            return response()->json([
                'success' => true,
                'message' => 'Borrador guardado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
* Obtener lista de clientes
     */
    public function getClients(Request $request)
    {
        try {
            $company_id = $request->get('company_id');
            $document_type = $request->get('document_type', 6); // Por defecto Factura


            // Validar que company_id existe
            if (!$company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró ID de empresa en el formulario'
                ], 400);
            }

            // Implementación como en Roma Copies - consulta SQL directa con parámetros preparados
            $query = "SELECT
                a.*,
                e.name as actividad_economica_descripcion,
                e.code as actividad_economica_codigo,
                (CASE a.tpersona WHEN 'N' THEN CONCAT(a.firstname , ' ', a.firstlastname) WHEN 'J' THEN a.comercial_name END) AS name_format_label
                FROM clients a
                LEFT JOIN economicactivities e ON a.economicactivity_id = e.id
                WHERE a.company_id = ?";

            $params = [$company_id];

            // Para Crédito Fiscal (tipo 3), solo mostrar contribuyentes
            if ($document_type == 3) {
                // Permitidos: Jurídico o Natural Contribuyente, y NRC válido
                $query .= " AND (
                    a.tpersona = 'J'
                    OR (a.tpersona = 'N' AND a.contribuyente = '1')
                )
                AND a.ncr IS NOT NULL AND a.ncr != 'N/A' AND a.ncr != ''";
            }

            $result = DB::select($query, $params);
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('❌ getClients - Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de productos
     */
    public function getProducts()
    {
        try {
            $products = Product::all();

            return response()->json($products);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener correlativo para el tipo de documento
     */
    public function getCorrelativo(Request $request)
    {
        try {
            $companyId = $request->get('company_id');
            $typedocument = $request->get('typedocument', 6);

            $correlativo = Correlativo::where('id_empresa', $companyId)
                ->where('id_tipo_doc', $typedocument)
                ->where('estado', 1) // Solo correlativos activos
                ->first();

            if ($correlativo) {
                return response()->json([
                    'success' => true,
                    'correlativo' => $correlativo->actual,
                    'serie' => $correlativo->serie,
                    'inicial' => $correlativo->inicial,
                    'final' => $correlativo->final,
                    'numeros_restantes' => $correlativo->numerosRestantes()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se encontró correlativo para esta empresa y tipo de documento'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información completa del cliente
     */
    public function getClientInfo(Request $request)
    {
        try {
            $clientId = $request->get('client_id');

            $client = Client::find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ]);
            }

            // Obtener dirección del cliente
            $address = DB::table('addresses')
                ->where('client_id', $clientId)
                ->first();

            return response()->json([
                'success' => true,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->tpersona === 'N'
                        ? $client->firstname . ' ' . $client->secondname . ' ' . $client->firstlastname . ' ' . $client->secondlastname
                        : $client->name_contribuyente,
                    'nit' => $client->nit,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'tpersona' => $client->tpersona,
                    'address' => $address ? $address->reference : '',
                    'typecontribuyente' => $client->typecontribuyente
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar producto por código o nombre
     */
    public function searchProduct(Request $request)
    {
        try {
            $code = $request->get('code');
            $companyId = $request->get('company_id');

            $product = Product::where(function($query) use ($code) {
                $query->where('code', 'LIKE', "%{$code}%")
                      ->orWhere('barcode', 'LIKE', "%{$code}%")
                      ->orWhere('name', 'LIKE', "%{$code}%");
            })
            ->where('company_id', $companyId)
            ->with(['marca', 'category'])
            ->first();

            if ($product) {
                return response()->json([
                    'success' => true,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'barcode' => $product->barcode,
                        'description' => $product->description,
                        'price' => $product->price,
                        'stock' => $product->stock,
                        'image' => $product->image ? asset('storage/' . $product->image) : asset('assets/img/products/default.png'),
                        'marca' => $product->marca ? $product->marca->name : '',
                        'category' => $product->category ? $product->category->name : '',
                        'typecontribuyente' => $product->typecontribuyente
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear DTE con estado de error
     *
     * @param array $documento
     * @param array $emisor
     * @param array $respuesta_hacienda
     * @param array $comprobante
     * @param object $salesave
     * @param string $createdby
     * @return Dte
     */
    private function crearDteConError($documento, $emisor, $respuesta_hacienda, $comprobante, $salesave, $createdby)
    {
        $dtecreate = new Dte();
        $dtecreate->versionJson = $documento[0]->versionJson;
        $dtecreate->ambiente_id = $documento[0]->ambiente;
        $dtecreate->tipoDte = $documento[0]->tipodocumento;
        $dtecreate->tipoModelo = $documento[0]->tipogeneracion;
        $dtecreate->tipoTransmision = 1;
        $dtecreate->tipoContingencia = "null";
        $dtecreate->idContingencia = "null";
        $dtecreate->nameTable = 'Sales';
        $dtecreate->company_id = $salesave->company_id;
        $dtecreate->company_name = $emisor[0]->nombreComercial;
        $dtecreate->id_doc = $respuesta_hacienda["identificacion"]["numeroControl"] ?? 'ERROR';
        $dtecreate->codTransaction = "01";
        $dtecreate->desTransaction = "Emision";
        $dtecreate->type_document = $documento[0]->tipodocumento;
        $dtecreate->id_doc_Ref1 = "null";
        $dtecreate->id_doc_Ref2 = "null";
        $dtecreate->type_invalidacion = "null";
        $dtecreate->codEstado = $respuesta_hacienda["codEstado"] ?? "03";
        $dtecreate->Estado = $respuesta_hacienda["estado"] ?? "RECHAZADO";
        $dtecreate->codigoGeneracion = $respuesta_hacienda["codigoGeneracion"] ?? null;
        $dtecreate->selloRecibido = $respuesta_hacienda["selloRecibido"] ?? null;
        $dtecreate->fhRecibido = $respuesta_hacienda["fhRecibido"] ?? null;
        $dtecreate->estadoHacienda = $respuesta_hacienda["estadoHacienda"] ?? "RECHAZADO";
        $dtecreate->json = json_encode($comprobante);
        $dtecreate->nSends = $respuesta_hacienda["nuEnvios"] ?? 0;
        $dtecreate->codeMessage = $respuesta_hacienda["codigoMsg"] ?? null;
        $dtecreate->claMessage = $respuesta_hacienda["clasificaMsg"] ?? null;
        $dtecreate->descriptionMessage = $respuesta_hacienda["descripcionMsg"] ?? 'Error en emisión DTE';
        $dtecreate->detailsMessage = $respuesta_hacienda["observacionesMsg"] ?? null;
        $dtecreate->sale_id = $salesave->id;
        $dtecreate->created_by = $createdby;
        $dtecreate->save();

        return $dtecreate;
    }

    /**
     * Registrar error DTE en la tabla de errores
     *
     * @param Dte $dte
     * @param string $tipo
     * @param string $codigo
     * @param string $descripcion
     * @param array $datos_adicionales
     * @return void
     */
    private function registrarErrorDte($dte, $tipo, $codigo, $descripcion, $datos_adicionales = [])
    {
        try {
            DB::table('dte_errors')->insert([
                'dte_id' => $dte->id,
                'sale_id' => $dte->sale_id,
                'company_id' => $dte->company_id,
                'tipo_error' => $tipo,
                'codigo_error' => $codigo,
                'descripcion' => $descripcion,
                'datos_adicionales' => json_encode($datos_adicionales),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::error("Error DTE registrado", [
                'dte_id' => $dte->id,
                'sale_id' => $dte->sale_id,
                'tipo_error' => $tipo,
                'codigo_error' => $codigo,
                'descripcion' => $descripcion
            ]);
        } catch (\Exception $e) {
            Log::error("Error al registrar error DTE: " . $e->getMessage());
        }
    }

    /**
     * Corregir datos incorrectos en salesdetails para Factura y Crédito Fiscal
     */
    public function corregirDatosCreditoFiscal(\Illuminate\Http\Request $request, $saleId)
    {
        try {

            $sale = Sale::find($saleId);
            if (!$sale) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada']);
            }
            // No tocar borradores (2) ni preventas (3)
            /*if (in_array((string) $sale->typesale, ['2','3'])) {
                return response()->json(['success' => true, 'message' => 'Saltado: borrador/preventa, no se corrige.']);
            }*/
            // Solo corregir Factura (6) y Crédito Fiscal (3)
            if (!in_array($sale->typedocument_id, ['3', '6'])) {
                return response()->json(['success' => true, 'message' => 'No requiere corrección']);
            }

            $salesDetails = Salesdetail::where('sale_id', $saleId)->get();
            $correcciones = 0;

            foreach ($salesDetails as $detail) {
                $usarCatalogo = (bool) $request->input('use_catalog', false);

                if ($usarCatalogo) {
                    // Corrección basada en catálogo SOLO si se solicita explícitamente
                    $product = Product::find($detail->product_id);
                    if (!$product) {
                        continue;
                    }
                    $precioOriginalProducto = $product->price; // con IVA
                    $precioUnitarioCorrect = round($precioOriginalProducto / 1.13, 8);
                    $precioTotalCorrect = round($precioUnitarioCorrect * (float)$detail->amountp, 8);
                    // IVA solo para líneas gravadas (sin exentas/no sujetas)
                    $ivaCorrect = ((float)$detail->exempt == 0.0 && (float)$detail->nosujeta == 0.0)
                        ? round($precioTotalCorrect * 0.13, 8)
                        : 0.00;

                    if (
                        abs($detail->priceunit - $precioUnitarioCorrect) > 0.0001 ||
                        abs($detail->pricesale - $precioTotalCorrect) > 0.0001 ||
                        abs($detail->detained13 - $ivaCorrect) > 0.0001
                    ) {
                        $detail->priceunit = $precioUnitarioCorrect;
                        $detail->pricesale = $precioTotalCorrect;
                        $detail->detained13 = $ivaCorrect;
                        $detail->save();
                        $correcciones++;
                    }
                } else {
                    // Preservar precios personalizados: si detained13 = 0, precio ya sin IVA; si > 0, precio con IVA
                    $precioUnitarioActual = round((float)$detail->priceunit, 8);
                    $ivaActual = round((float)$detail->detained13, 8);

                    if ($ivaActual != 0.0) {
                        // detained13 != 0: ya pasó por corrección previa, no requiere cambios
                        return response()->json(['success' => true, 'message' => 'No requiere corrección']);
                    } else if ($ivaActual == 0.0) {
                        // detained13 == 0: viene de preventa sin IVA calculado, calcular sin IVA e IVA
                        $precioUnitarioSinIva = round($precioUnitarioActual / 1.13, 8);
                        $precioTotalCorrect = round($precioUnitarioSinIva * round((float)$detail->amountp, 8), 8);
                        $ivaCorrect = ((float)$detail->exempt == 0.0 && (float)$detail->nosujeta == 0.0)
                            ? round($precioTotalCorrect * 0.13, 8)
                            : 0.00;
                    }

                    if (
                        abs($detail->priceunit - $precioUnitarioSinIva) > 0.0001 ||
                        abs($detail->pricesale - $precioTotalCorrect) > 0.0001 ||
                        abs($detail->detained13 - $ivaCorrect) > 0.0001
                    ) {
                        // Actualizar priceunit sin IVA, pricesale y detained13
                        $detail->priceunit = $precioUnitarioSinIva;
                        $detail->pricesale = $precioTotalCorrect;
                        $detail->detained13 = $ivaCorrect;
                        $detail->save();
                        $correcciones++;
                    }
                }
            }

            // Actualizar el total de la venta

            $this->updateSaleTotalAmount($saleId);

            return response()->json([
                'success' => true,
                'message' => "Correcciones realizadas: {$correcciones}",
                'correcciones' => $correcciones
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error interno']);
        }
    }

    /**
     * Crear Nota de Débito (NDR)
     *
     * @param int $id_sale ID de la venta original
     * @return \Illuminate\Http\RedirectResponse
     */
    public function ndr($id_sale)
    {
        // La nota de débito SOLO puede venir del formulario
        // Aceptar si vienen productos existentes y/o productos nuevos
        if (!request()->isMethod('post') || (!request()->has('productos') && !request()->has('productos_nuevos'))) {
            return redirect()->back()
                ->with('error', 'Acceso no autorizado. La nota de débito debe crearse desde el formulario.');
        }
        // Validar que el ID de venta sea válido
        if (!$id_sale || !is_numeric($id_sale)) {
            return redirect()->back()
                ->with('error', 'ID de venta inválido.');
        }

        DB::beginTransaction();
        try {
            $request = request();

            // Obtener la venta original
            $saleOriginal = Sale::where('id', $id_sale)
                ->where('typesale', 1)
                ->where('state', 1)
                ->firstOrFail();
            $idempresa = $saleOriginal->company_id;
            $createdby = $saleOriginal->user_id;
            // Verificar modificaciones, calcular total y crear detalles
            $hayModificaciones = false;
            $totalAmount = 0;
            $productosOriginales = $saleOriginal->salesdetails->keyBy('product_id');
            $detallesModificados = [];

            foreach (($request->productos ?? []) as $productoData) {
                if (!isset($productoData['incluir']) || !$productoData['incluir']) {
                    continue;
                }

                // Validar datos del producto
                if (!isset($productoData['product_id']) || !isset($productoData['cantidad']) || !isset($productoData['precio'])) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Datos de producto incompletos. Faltan campos requeridos.');
                }

                $productoOriginal = $productosOriginales->get($productoData['product_id']);
                if (!$productoOriginal) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Producto no encontrado en la venta original.');
                }
                $cantidadOriginal = $productoOriginal->amountp;
                $precioOriginal = $productoOriginal->priceunit;
                // ND: la cantidad enviada desde frontend es delta a aumentar
                $cantidadAumentar = (float)$productoData['cantidad'];
                $precioNuevo = (float)$productoData['precio'];

                // Validar que los valores sean numéricos válidos
                if (!is_numeric($cantidadAumentar) || !is_numeric($precioNuevo) || $cantidadAumentar < 0 || $precioNuevo < 0) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Valores de cantidad o precio inválidos para el producto.');
                }

                // Cálculo con el criterio solicitado:
                // - Para aumentos de cantidad, usar SIEMPRE el precio NUEVO enviado en el formulario
                // - Evitar crear una segunda línea por diferencia de precio cuando también hay aumento de cantidad
                // - Si SOLO cambia el precio (cantidadAumentar == 0), crear una única línea de ajuste
                $diferenciaCantidad = $cantidadAumentar; // delta
                $diferenciaPrecio = max(0, $precioNuevo - $precioOriginal); // solo incremento
                // Si no hay aumento en cantidad ni precio, continuar
                if ($diferenciaCantidad == 0 && $diferenciaPrecio == 0) {
                    continue;
                }

                $hayModificaciones = true;

                // Subtotales de ND según regla:
                // - Si hay aumento de cantidad, usar precio NUEVO para esas unidades
                // - Si solo hay cambio de precio (sin aumento de cantidad), usar diferencia de precio sobre cantidad original
                $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';
                $subtotalCantidad = 0;
                $subtotalAjuste = 0;

                if ($diferenciaCantidad > 0) {
                    $subtotalCantidad = $diferenciaCantidad * $precioNuevo;
                } elseif ($diferenciaPrecio > 0) {
                    $subtotalAjuste = $cantidadOriginal * $diferenciaPrecio;
                }

                $subtotalDiferencia = $subtotalCantidad + $subtotalAjuste;

                if ($tipoVenta === 'gravada') {
                    $totalAmount += $subtotalDiferencia + ($subtotalDiferencia * 0.13);
                } else {
                    $totalAmount += $subtotalDiferencia;
                }

                // Preparar datos del detalle para crear después
                $detallesModificados[] = [
                    'productoData' => $productoData,
                    'productoOriginal' => $productoOriginal,
                    'cantidadOriginal' => $cantidadOriginal,
                    'precioOriginal' => $precioOriginal,
                    'cantidadAumentar' => $diferenciaCantidad,
                    'diferenciaPrecio' => $diferenciaPrecio,
                    'tipoVenta' => $tipoVenta
                ];
            }

            // Procesar productos nuevos (aumentos por nuevos ítems)
            $productosNuevos = $request->get('productos_nuevos', []);
            if (is_array($productosNuevos) && count($productosNuevos) > 0) {
                foreach ($productosNuevos as $nuevo) {
                    if (!isset($nuevo['product_id']) || !isset($nuevo['cantidad']) || !isset($nuevo['precio'])) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Datos de producto nuevo incompletos.');
                    }
                    $cantidadNueva = (float)$nuevo['cantidad'];
                    $precioNuevo = (float)$nuevo['precio'];
                    if ($cantidadNueva <= 0 || $precioNuevo <= 0) {
                        DB::rollBack();
                        return redirect()->back()->with('error', 'Cantidad y precio de producto nuevo deben ser mayores a 0.');
                    }

                    $tipoVenta = $nuevo['tipo_venta'] ?? 'gravada';
                    $subtotal = $cantidadNueva * $precioNuevo;
                    if ($tipoVenta === 'gravada') {
                        $totalAmount += $subtotal + ($subtotal * 0.13);
                    } else {
                        $totalAmount += $subtotal;
                    }

                    $hayModificaciones = true;
                }
            }

            if (!$hayModificaciones) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se detectaron aumentos ni productos nuevos. No se puede crear una nota de débito sin cambios.');
            }

            // Crear la nota de débito con solo las modificaciones
        $nfactura = new Sale();
            $nfactura->client_id = $saleOriginal->client_id;
            $nfactura->company_id = $saleOriginal->company_id;
            $nfactura->doc_related = $id_sale; // ID de la venta original
            $nfactura->typesale = 1; // Venta confirmada
            $nfactura->date = now();
            $nfactura->user_id = Auth::id();
            $nfactura->waytopay = $saleOriginal->waytopay ?? 1;
            $nfactura->state = 1; // Activa/Confirmada
            $nfactura->state_credit = 0;

            // Asignar motivo solo si la columna existe
            if (Schema::hasColumn('sales', 'motivo')) {
                $nfactura->motivo = $request->motivo ?? 'Modificación de productos';
            }

            $nfactura->acuenta = $saleOriginal->acuenta ?? 0;

            // Obtener el typedocument_id para notas de débito (tipo NDB)
            $typedocumentNDB = \App\Models\Typedocument::where('type', 'NDB')
                ->where('company_id', $saleOriginal->company_id)
                ->first();

            if (!$typedocumentNDB) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró configuración de tipo de documento NDB para esta empresa.');
            }

            $nfactura->typedocument_id = $typedocumentNDB->id;

            // Obtener y asignar el número de documento del correlativo
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $typedocumentNDB->id)
                ->where('docs.id_empresa', '=', $nfactura->company_id)
                ->select('docs.actual', 'docs.id')
                ->first();

            if (!$newCorr) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró correlativo para el tipo de documento NDB.');
            }

            $nfactura->nu_doc = $newCorr->actual;
            $nfactura->totalamount = $totalAmount;
        $nfactura->save();

            // Actualizar correlativo después de guardar la nota de débito
            DB::table('docs')->where('id', $newCorr->id)->increment('actual');

            // Crear detalles usando los datos ya preparados
            foreach ($detallesModificados as $detalleData) {
                $productoData = $detalleData['productoData'];
                $productoOriginal = $detalleData['productoOriginal'];
                $cantidadOriginal = $detalleData['cantidadOriginal'];
                $precioOriginal = $detalleData['precioOriginal'];
                $cantidadAumentar = $detalleData['cantidadAumentar'];
                $diferenciaPrecio = $detalleData['diferenciaPrecio'];
                $tipoVenta = $detalleData['tipoVenta'];
                // 1) Línea por aumento de cantidad (usar PRECIO NUEVO)
                if ($cantidadAumentar > 0) {
                    $cantidadND = $cantidadAumentar;
                    $precioND = $precioNuevo;
                    $subtotal = $cantidadND * $precioND;
                    $detalle = new Salesdetail();
                    $detalle->sale_id = $nfactura->id;
                    $detalle->product_id = $productoData['product_id'];
                    $detalle->amountp = $cantidadND;
                    $detalle->priceunit = $precioND;
                    $detalle->description = $productoOriginal->description;
                    // Calcular retención de renta según el tipo de documento (lógica de Roma Copies)
                    if ($nfactura->typedocument_id != '8') {
                        $detalle->renta = 0; // Factura normal NO tiene retención de renta
                    } else {
                        $detalle->renta = $tipoVenta === 'gravada' ? $subtotal * 0.10 : 0; // Sujeto Excluido SÍ tiene retención de renta
                    }
                    $detalle->fee = 0; // Campo requerido
                    $detalle->feeiva = 0; // Campo requerido
                    $detalle->reserva = 0; // Campo requerido
                    $detalle->ruta = $productoOriginal->ruta ?? null;
                    $detalle->destino = $productoOriginal->destino ?? null;
                    $detalle->linea = $productoOriginal->linea ?? null;
                    $detalle->canal = $productoOriginal->canal ?? null;
                    $detalle->user_id = Auth::id();

                    if ($tipoVenta === 'gravada') {
                        $detalle->pricesale = $subtotal;
                        $detalle->detained13 = $subtotal * 0.13;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = 0;
                        $detalle->nosujeta = 0;
                    } elseif ($tipoVenta === 'exenta') {
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = $subtotal;
                        $detalle->nosujeta = 0;
                    } else { // no_sujeta
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = 0;
                        $detalle->nosujeta = $subtotal;
                    }
                    $detalle->save();
                }

                // 2) Línea por incremento de precio (solo cuando NO hay aumento de cantidad)
                if ($diferenciaCantidad == 0 && $diferenciaPrecio > 0) {
                    $cantidadND = $cantidadOriginal;
                    $precioND = $diferenciaPrecio;
                    $subtotal = $cantidadND * $precioND;
                    $detalle = new Salesdetail();
                    $detalle->sale_id = $nfactura->id;
                    $detalle->product_id = $productoData['product_id'];
                    $detalle->amountp = $cantidadND;
                    $detalle->priceunit = $precioND;
                    $detalle->description = $productoOriginal->description . ' (Ajuste precio)';
                    $detalle->renta = 0; // Campo requerido
                    $detalle->fee = 0; // Campo requerido
                    $detalle->feeiva = 0; // Campo requerido
                    $detalle->reserva = 0; // Campo requerido
                    $detalle->ruta = $productoOriginal->ruta ?? null;
                    $detalle->destino = $productoOriginal->destino ?? null;
                    $detalle->linea = $productoOriginal->linea ?? null;
                    $detalle->canal = $productoOriginal->canal ?? null;
                    $detalle->user_id = Auth::id();

                    if ($tipoVenta === 'gravada') {
                        $detalle->pricesale = $subtotal;
                        $detalle->detained13 = $subtotal * 0.13;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = 0;
                        $detalle->nosujeta = 0;
                    } elseif ($tipoVenta === 'exenta') {
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = $subtotal;
                        $detalle->nosujeta = 0;
                    } else { // no_sujeta
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null; // Campo nullable
                        $detalle->exempt = 0;
                        $detalle->nosujeta = $subtotal;
                    }
                    $detalle->save();
                }
            }

            // Crear detalles para productos nuevos
            if (is_array($productosNuevos) && count($productosNuevos) > 0) {
                foreach ($productosNuevos as $index => $nuevo) {
                    $cantidad = (float)$nuevo['cantidad'];
                    $precio = (float)$nuevo['precio'];
                    $tipoVenta = $nuevo['tipo_venta'] ?? 'gravada';

                    $subtotal = $cantidad * $precio;
                    $detalle = new Salesdetail();
                    $detalle->sale_id = $nfactura->id;
                    $detalle->product_id = $nuevo['product_id'];
                    $detalle->amountp = $cantidad;
                    $detalle->priceunit = $precio;
                    $detalle->description = $nuevo['description'] ?? 'Producto nuevo en ND';
                    $detalle->renta = 0;
                    $detalle->fee = 0;
                    $detalle->feeiva = 0;
                    $detalle->reserva = 0;
                    $detalle->ruta = null;
                    $detalle->destino = null;
                    $detalle->linea = null;
                    $detalle->canal = null;
                    $detalle->user_id = Auth::id();

                    if ($tipoVenta === 'gravada') {
                        $detalle->pricesale = $subtotal;
                        $detalle->detained13 = $subtotal * 0.13;
                        $detalle->detained = null;
                        $detalle->exempt = 0;
                        $detalle->nosujeta = 0;
                    } elseif ($tipoVenta === 'exenta') {
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null;
                        $detalle->exempt = $subtotal;
                        $detalle->nosujeta = 0;
                    } elseif ($tipoVenta === 'no_sujeta' || $tipoVenta === 'nosujeta') {
                        $detalle->pricesale = 0;
                        $detalle->detained13 = 0;
                        $detalle->detained = null;
                        $detalle->exempt = 0;
                        $detalle->nosujeta = $subtotal;
                    } else {
                        $detalle->pricesale = $subtotal;
                        $detalle->detained13 = $subtotal * 0.13;
                        $detalle->detained = null;
                        $detalle->exempt = 0;
                        $detalle->nosujeta = 0;
                    }
                    $detalle->save();
                }
            }

            // Verificar si DTE está habilitado para esta empresa
            if (!Config::isDteEmissionEnabled($idempresa)) {
                DB::commit();
                if (request()->ajax()) {
                    return response('0');
                }
                return redirect()->route('sale.index')
                    ->with('success', 'Nota de débito creada exitosamente. DTE deshabilitado para esta empresa.');
            }

            // Obtener información básica de la venta original
            $qfactura = "SELECT
                        s.id id_factura,
                        s.totalamount total_venta,
                        s.company_id id_empresa,
                        s.client_id id_cliente,
                        s.user_id id_usuario,
                        clie.nit,
                        clie.email email_cliente,
                        clie.tpersona tipo_personeria,
                        CASE
                            WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                            WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
                        END AS nombre_cliente,
                        dte.json,
                        dte.tipoModelo,
                        dte.fhRecibido,
                        dte.codigoGeneracion,
                        dte.selloRecibido,
                        dte.tipoDte
                        FROM sales s
                        INNER JOIN clients clie ON s.client_id=clie.id
                        LEFT JOIN dte ON dte.sale_id=s.id
                        WHERE s.id = $id_sale";
            $factura = DB::select(DB::raw($qfactura));
            // Obtener información del tipo de documento NDB
            $qdoc = "SELECT
                    a.id id_doc,
                    a.`type` id_tipo_doc,
                    docs.serie serie,
                    docs.inicial inicial,
                    docs.final final,
                    docs.actual actual,
                    docs.estado estado,
                    a.company_id id_empresa,
                    " . Auth::id() . " hechopor,
                    NOW() fechacreacion,
                    a.description NombreDocumento,
                    '" . Auth::user()->name . "' NombreUsuario,
                    '" . (Auth::user()->nit ?? '00000000-0') . "' docUser,
                    a.codemh tipodocumento,
                    a.versionjson versionJson,
                    e.url_credencial,
                    e.url_envio,
                    e.url_invalidacion,
                    e.url_contingencia,
                    e.url_firmador,
                    d.typeTransmission tipogeneracion,
                    e.cod ambiente,
                    NOW() updated_at,
                    1 aparece_ventas
                    FROM typedocuments a
                    INNER JOIN docs ON a.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
                    INNER JOIN config d ON a.company_id=d.company_id
                    INNER JOIN ambientes e ON d.ambiente=e.id
                    WHERE a.`type`= 'NDB' AND a.company_id = $idempresa";
            $doc = DB::select(DB::raw($qdoc));
            // Obtener detalles de la nota de débito (solo las modificaciones)
            $detalle = $this->construirDetalleNotaDebito($nfactura->id);
            $versionJson = $doc[0]->versionJson;
            $ambiente = $doc[0]->ambiente;
            $tipoDte = $doc[0]->tipodocumento;
            $numero = $doc[0]->actual;

            // Obtener totales de la nota de débito
            $totalesND = $this->calcularTotalesNotaDebito($nfactura->id);
            // Construir documento fiscal para nota de débito
            $documento[0] = [
                "tipodocumento" => $tipoDte,
                "nu_doc" => $numero,
                "tipo_establecimiento" => "1",
                "version" => $versionJson,
                "ambiente" => $ambiente,
                "tipoDteOriginal" => $factura[0]->tipoDte ?? '01',
                "tipoGeneracionOriginal" => $factura[0]->tipoModelo ?? 1,
                "codigoGeneracionOriginal" => $factura[0]->codigoGeneracion ?? '',
                "selloRecibidoOriginal" => $factura[0]->selloRecibido ?? '',
                "numeroOriginal" => $factura[0]->codigoGeneracion ?? '',
                "fecEmiOriginal" => $factura[0]->fhRecibido ? date('Y-m-d', strtotime($factura[0]->fhRecibido)) : date('Y-m-d'),
                "total_iva" => $totalesND->total_iva,
                "numDocumento" => $factura[0]->nit,
                "nombre" => $factura[0]->nombre_cliente,
                "versionjson" => $versionJson,
                "id_empresa" => $idempresa,
                "url_credencial" => $doc[0]->url_credencial,
                "url_envio" => $doc[0]->url_envio,
                "url_firmador" => $doc[0]->url_firmador,
                "nuEnvio" => 1,
                "condiciones" => "1",
                "total_venta" => $totalesND->total_venta,
                "tot_gravado" => $totalesND->tot_gravado,
                "tot_nosujeto" => $totalesND->tot_nosujeto,
                "tot_exento" => $totalesND->tot_exento,
                "subTotalVentas" => $totalesND->subTotalVentas,
                "descuNoSuj" => 0.00,
                "descuExenta" => 0.00,
                "descuGravada" => 0.00,
                "totalDescu" => 0.00,
                "subTotal" => $totalesND->subTotal,
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => round((float)($totalesND->totalRenta ?? 0), 8),
                "montoTotalOperacion" => $totalesND->montoTotalOperacion,
                "totalNoGravado" => 0.00,
                "totalPagar" => $totalesND->totalPagar,
                "totalLetras" => "",
                "totalIva" => $totalesND->total_iva,
                "saldoFavor" => 0.00,
                "condicionOperacion" => 1,
                "pagos" => null,
                "numPagoElectronico" => null,
            ];
            // Obtener datos del cliente (receptor) - igual que en NCR
            $qcliente = "SELECT
                                a.id id_cliente,
                            CASE
                                WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
                                WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
                            END AS nombre_cliente,
                                p.phone telefono_cliente,
                                a.email email_cliente,
                                c.reference direccion_cliente,
                                1 status_cliente,
                                a.created_at date_added,
                                CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                            a.nit,
                            a.tpersona tipo_personeria,
                            g.code municipio,
                            f.code departamento,
                            a.company_id id_empresa,
                            NULL hechopor,
                            a.tipoContribuyente id_clasificacion_tributaria,
                            CASE
                                WHEN a.tipoContribuyente = 'GRA' THEN 'GRANDES CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'MED' THEN 'MEDIANOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'PEQU'  THEN 'PEQUEÑOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'OTR'  THEN 'OTROS CONTRIBUYENTES'
                            END AS descripcion,
                            0 siempre_retiene,
                            1 id_tipo_contribuyente,
                            b.id giro,
                            b.code codActividad,
                            b.name descActividad,
                            a.comercial_name nombre_comercial
                        FROM clients a
                        INNER JOIN economicactivities b ON a.economicactivity_id=b.id
                        INNER JOIN addresses c ON a.address_id=c.id
                        INNER JOIN phones p ON a.phone_id=p.id
                        INNER JOIN countries d ON c.country_id=d.id
                        INNER JOIN departments f ON c.department_id=f.id
                        INNER JOIN municipalities g ON c.municipality_id=g.id
                        WHERE a.id = " . $factura[0]->id_cliente . "";
            $cliente = DB::select(DB::raw($qcliente));

            // Obtener datos del emisor (empresa) - igual que en NCR
            $queryemisor = "SELECT
                        a.nit,
                        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                        a.name nombre,
                        c.code codActividad,
                        c.name descActividad,
                        a.name nombreComercial,
                        a.tipoEstablecimiento,
                        f.code departamento,
                        g.code municipio,
                        d.reference direccion,
                        e.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        a.email correo,
                        b.passkeyPublic clavePublicaMH,
                        b.passPrivateKey clavePrivadaMH,
                        b.passMH claveApiMH
                        FROM companies a
                        INNER JOIN config b ON a.id=b.company_id
                        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
                        INNER JOIN addresses d ON a.address_id=d.id
                        INNER JOIN phones e ON a.phone_id=e.id
                        INNER JOIN departments f ON d.department_id=f.id
                        INNER JOIN municipalities g ON d.municipality_id=g.id
                            WHERE a.id=" . $saleOriginal->company_id . "";
            $emisor = DB::select(DB::raw($queryemisor));
            // Preparar datos para envío a Hacienda
            $comprobante = [
                "emisor" => $emisor,
                "documento" => $documento,
                "detalle" => $detalle,
                "totales" => $totalesND,
                "cliente" => $cliente
            ];
            //dd($comprobante);
            // Enviar a Hacienda
            $respuestaHacienda = $this->Enviar_Hacienda($comprobante, "06"); // 06 = Nota de Débito
            if ($respuestaHacienda["codEstado"] == "03") {
                // Captura de errores alineada con NCR
                // 1) Crear registro DTE con estado de error
                $dtecreate = $this->crearDteConError($doc, $emisor, $respuestaHacienda, $comprobante, $nfactura, $createdby);

                // 2) Registrar error en tabla dte_errors
                $this->registrarErrorDte(
                    $dtecreate,
                    'hacienda',
                    'HACIENDA_REJECTED',
                    $respuestaHacienda["descripcionMsg"] ?? 'Documento rechazado por Hacienda',
                    [
                        'codigoMsg' => $respuestaHacienda["codigoMsg"] ?? null,
                        'observacionesMsg' => $respuestaHacienda["observacionesMsg"] ?? null,
                        'sale_id' => $nfactura->id
                    ]
                );

                // 3) Guardar JSON de rechazo en la venta
                $comprobante["json"] = $respuestaHacienda;
                $nfactura->json = json_encode($comprobante);
                $nfactura->save();

                DB::rollBack();
                if (request()->ajax()) {
                    return response('0');
                }
                return redirect()->back()
                    ->with('error', 'Nota de débito rechazada por Hacienda: ' . ($respuestaHacienda["descripcionMsg"] ?? 'Error desconocido'));
            }
            $comprobante["json"] = $respuestaHacienda;
            // Crear DTE con respuesta de Hacienda
            //dd($respuestaHacienda);
            $dte = new Dte();
            $dte->versionJson = $versionJson;
            $dte->ambiente_id = $doc[0]->ambiente;
            $dte->tipoDte = '06'; // Nota de débito
            $dte->tipoModelo = $doc[0]->tipogeneracion;
            $dte->tipoTransmision = 1;
            $dte->tipoContingencia = "null";
            $dte->idContingencia = "null";
            $dte->nameTable = 'Sales';
            $dte->company_id = $nfactura->company_id;
            $dte->company_name = $emisor[0]->nombreComercial ?? ($emisor[0]->nombre ?? '');
            $dte->id_doc = $respuestaHacienda["identificacion"]["numeroControl"];
            // Para emisión, el código/transacción debe representar 'Emisión'
            $dte->codTransaction = '01';
            $dte->desTransaction = 'Emision';
            $dte->type_document = '06';
            $dte->id_doc_Ref1 = $factura[0]->id_factura;
            $dte->id_doc_Ref2 = null;
            $dte->type_invalidacion = null;
            $dte->codEstado = $respuestaHacienda["codEstado"] ?? '02';
            $dte->Estado = $respuestaHacienda["estado"] ?? 'Enviado';
            $dte->codigoGeneracion = $respuestaHacienda["codigoGeneracion"] ?? null;
            $dte->selloRecibido = $respuestaHacienda["selloRecibido"] ?? null;
            $dte->fhRecibido = $respuestaHacienda["fhRecibido"] ?? null;
            $dte->estadoHacienda = $respuestaHacienda["estadoHacienda"] ?? null;
            $dte->nSends = $respuestaHacienda["nuEnvios"] ?? 1;
            $dte->codeMessage = $respuestaHacienda["codigoMsg"] ?? null;
            $dte->claMessage = $respuestaHacienda["clasificaMsg"] ?? null;
            $dte->descriptionMessage = $respuestaHacienda["descripcionMsg"] ?? null;
            $dte->detailsMessage = $respuestaHacienda["observacionesMsg"] ?? null;
            $dte->created_by = $doc[0]->NombreUsuario;
            $dte->sale_id = $nfactura->id;
            // Guardar el comprobante completo para trazabilidad (igual que NCR)
            $dte->json = json_encode($comprobante);
            $dte->save();

            // Actualizar correlativo después de usar exitosamente
            $updateCorr = Correlativo::find($newCorr->id);
            if ($updateCorr) {
                $updateCorr->actual = ($updateCorr->actual + 1);
                $updateCorr->save();
                \Log::info('🔍 Correlativo NDB incrementado', [
                    'correlativo_id' => $updateCorr->id,
                    'nuevo_actual' => $updateCorr->actual
                ]);
            }

            DB::commit();

            if (request()->ajax()) {
                return response('1');
            }
            return redirect()->route('sale.index')
                ->with('success', 'Nota de débito creada y enviada a Hacienda exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando nota de débito: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al crear la nota de débito: ' . $e->getMessage());
        }
    }

    /**
     * Construir detalle de nota de débito
     */
    private function construirDetalleNotaDebito($notaDebitoId)
    {
        $queryDetalle = "SELECT
                        det.id id_factura_det,
                        det.sale_id id_factura,
                        det.product_id id_producto,
                        CASE
                            WHEN pro.code = 'LAB' AND det.ruta IS NOT NULL AND det.ruta != '' THEN det.ruta
                            ELSE pro.name
                        END AS descripcion,
                        det.amountp cantidad,
                        det.priceunit precio_unitario,
                        det.nosujeta no_sujetas,
                        det.exempt exentas,
                        det.pricesale gravadas,
                        det.detained13 iva,
                        0.00 no_imponible,
                        sa.company_id id_empresa,
                        'D' tipo_producto,
                        0.00 porcentaje_descuento,
                        0.00 descuento,
                        det.created_at,
                        det.updated_at
                        FROM salesdetails det
                        INNER JOIN sales sa ON det.sale_id=sa.id
                        INNER JOIN products pro ON det.product_id=pro.id
                        WHERE det.sale_id = $notaDebitoId";

        return DB::select(DB::raw($queryDetalle));
    }

    /**
     * Calcular totales de nota de débito
     */
    private function calcularTotalesNotaDebito($notaDebitoId)
    {
        $queryTotales = "SELECT
                        SUM(det.pricesale) as tot_gravado,
                        SUM(det.nosujeta) as tot_nosujeto,
                        SUM(det.exempt) as tot_exento,
                        SUM(det.pricesale + det.nosujeta + det.exempt) as subTotalVentas,
                        SUM(det.pricesale + det.nosujeta + det.exempt) as subTotal,
                        SUM(det.pricesale + det.nosujeta + det.exempt + det.detained13) as montoTotalOperacion,
                        SUM(det.pricesale + det.nosujeta + det.exempt + det.detained13 - det.renta - det.detained) as totalPagar,
                        SUM(det.pricesale + det.nosujeta + det.exempt + det.detained13 - det.renta - det.detained) as total_venta,
                        SUM(det.detained13) as totalIva,
                        SUM(det.renta) as totalRenta
                        FROM salesdetails det
                        INNER JOIN sales s ON det.sale_id = s.id
                        WHERE det.sale_id = $notaDebitoId";

        $resultado = DB::select(DB::raw($queryTotales));
        return $resultado[0] ?? [
            'tot_gravado' => 0,
            'tot_nosujeto' => 0,
            'tot_exento' => 0,
            'subTotalVentas' => 0,
            'subTotal' => 0,
            'montoTotalOperacion' => 0,
            'totalPagar' => 0,
            'total_venta' => 0,
            'totalIva' => 0,
            'totalRenta' => 0
        ];
    }

}
