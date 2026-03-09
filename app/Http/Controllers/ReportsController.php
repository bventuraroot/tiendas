<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasePayment;
use App\Models\Report;
use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Provider;
use App\Models\Marca;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function sales(){
        return view('reports.sales');
    }

    public function purchases(){
        return view('reports.purchases');
    }

    public function reportsales($company, $year, $period){
        $sales_r = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
        ->join('clients', 'clients.id', '=', 'sales.client_id')
        ->join('companies', 'companies.id', '=', 'sales.company_id')
        ->select('sales.*',
        'typedocuments.description AS document_name',
        'clients.firstname',
        'clients.firstlastname',
        'clients.comercial_name',
        'clients.tpersona',
        'companies.name AS company_name')
        ->where("companies.id", "=", $company)
        ->whereRaw('YEAR(sales.date) = ?', [$year])
        ->whereRaw('MONTH(sales.date) = ?', [$period])
        ->get();
        $sales_r1['data'] = $sales_r;
        return response()->json($sales_r1);
    }
    public function reportpurchases($company, $year, $period){
        $purchases_r = Purchase::join('typedocuments', 'typedocuments.id', '=', 'purchases.document_id')
        ->join('providers', 'providers.id', '=', 'purchases.provider_id')
        ->join('companies', 'companies.id', '=', 'purchases.company_id')
        ->select('purchases.*',
        'typedocuments.description AS document_name',
        'providers.razonsocial AS nameprovider',
        'companies.name AS company_name')
        ->where("companies.id", "=", $company)
        ->whereRaw('YEAR(purchases.datedoc) = ?', [$year])
        ->whereRaw('MONTH(purchases.datedoc) = ?', [$period])
        ->get();
        $purchases_r1['data'] = $purchases_r;
        return response()->json($purchases_r1);
    }

    public function contribuyentes(){
            return view('reports.contribuyentes');
    }
    public function reportyear(){
            return view('reports.reportyear');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function contribusearch(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->join('dte', 'dte.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte.id_doc AS numero_control',
        'dte.codigoGeneracion AS codigo_generacion',
        'dte.selloRecibido AS sello_recibido')
        ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS dateF ")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->where('sales.typedocument_id', "=", "3")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->orderBy('sales.id')
        ->get();

        // Si se solicita exportación a Excel
        if ($request->filled('export_excel')) {
            return $this->exportContribuyentesToExcel($sales, $Company, $request['year'], $request['period']);
        }

        return view('reports.contribuyentes', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "sales" => $sales
        ));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function yearsearch(Request $request){
        $Company = Company::find($request['company']);

        // Consulta corregida para ventas - comparar con compras sin IVA
        $sales = Sale::join('salesdetails','salesdetails.sale_id', '=','sales.id')
        ->selectRaw("SUM(CASE WHEN salesdetails.exempt > 0 THEN salesdetails.exempt ELSE salesdetails.pricesale / 1.13 END) as GRAVADAS") // Sin IVA
        ->selectRaw("SUM(CASE WHEN salesdetails.exempt > 0 THEN 0 ELSE salesdetails.pricesale - (salesdetails.pricesale / 1.13) END) as DEBITO") // IVA
        ->selectRaw("SUM(CASE WHEN salesdetails.exempt > 0 THEN salesdetails.exempt ELSE salesdetails.pricesale / 1.13 END) as TOTALV") // Total sin IVA para comparar con compras
        ->selectRaw("YEAR(sales.date) as yearsale")
        ->selectRaw("MONTH(sales.date) as monthsale")
        ->where('sales.company_id', '=', $request['company'])
        ->where('sales.state', '=', 1)
        ->whereRaw('YEAR(sales.date) = ?', [$request['year']])
        ->groupBy([
            'yearsale',
            'monthsale'
        ])
        ->orderBy('monthsale', 'asc')
        ->get();

        // Consulta corregida para compras - solo gravada (sin IVA ni exenta)
        $purchases = Purchase::selectRaw("SUM(purchases.gravada) as INTERNASPU")
        ->selectRaw("SUM(purchases.iva) as CREDITOPU")
        ->selectRaw("SUM(purchases.gravada) as TOTALC") // Solo gravada, sin IVA ni exenta
        ->selectRaw("YEAR(purchases.date) as yearpurchase")
        ->selectRaw("MONTH(purchases.date) as monthpurchase")
        ->where('purchases.company_id', '=', $request['company'])
        ->whereRaw('YEAR(purchases.date) = ?', [$request['year']])
        ->groupBy([
            'yearpurchase',
            'monthpurchase'
        ])
        ->orderBy('monthpurchase', 'asc')
        ->get();

        // Debug: Log para verificar datos
        Log::info('Sales data:', $sales->toArray());
        Log::info('Purchases data:', $purchases->toArray());

        // Calcular totales para estadísticas
        $totalgravadas = $sales->sum('GRAVADAS');
        $totaldebito = $sales->sum('DEBITO');
        $totalventas = $sales->sum('TOTALV');
        $totalinternas = $purchases->sum('INTERNASPU');
        $totalcredito = $purchases->sum('CREDITOPU');
        $totalcompras = $purchases->sum('TOTALC');
        $totaldiferencia = $totalventas - $totalcompras;

        return view('reports.reportyear', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "purchases" => $purchases,
            "sales" => $sales,
            "totalgravadas" => $totalgravadas,
            "totaldebito" => $totaldebito,
            "totalventas" => $totalventas,
            "totalinternas" => $totalinternas,
            "totalcredito" => $totalcredito,
            "totalcompras" => $totalcompras,
            "totaldiferencia" => $totaldiferencia
        ));
    }

    public function consumidor(){
        return view('reports.consumidor');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function consumidorsearch(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
        ->join('dte', 'dte.sale_id', '=', 'sales.id')
        ->select('sales.*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'clients.firstname',
        'clients.firstlastname',
        'clients.comercial_name',
        'clients.tpersona',
        'dte.id_doc AS numero_control',
        'dte.codigoGeneracion AS codigo_generacion',
        'dte.selloRecibido AS sello_recibido')
        ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS dateF ")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("COALESCE((SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id), 0) / 1.13 AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("COALESCE((SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id), 0) - (COALESCE((SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id), 0) / 1.13) AS iva")
        ->where('sales.typedocument_id', "=", "6")
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->orderBy('sales.id')
        ->get();

        // Si se solicita exportación a Excel
        if ($request->filled('export_excel')) {
            return $this->exportConsumidorToExcel($sales, $Company, $request['year'], $request['period']);
        }

        return view('reports.consumidor', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "sales" => $sales
        ));
    }

    public function bookpurchases(){
        return view('reports.bookpurchases');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function comprassearch(Request $request){
        $Company = Company::find($request['company']);

        $purchases = Purchase::join('providers AS pro', 'pro.id', '=', 'purchases.provider_id')
        ->select('*')
        ->selectRaw("DATE_FORMAT(purchases.date, '%d/%m/%Y') AS dateF")
        ->whereRaw('YEAR(purchases.date)=?', $request['year'])
        ->whereRaw('MONTH(purchases.date)=?', $request['period'])
        ->WhereRaw('DAY(purchases.date) BETWEEN "01" AND "31"')
        ->where('purchases.company_id', '=', $request['company'])
        ->orderByRaw('MONTH(purchases.date)')
        ->orderBy('purchases.date')
        ->get();
        return view('reports.bookpurchases', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "purchases" => $purchases
        ));
    }
    public function directas(){

        return view('reports.directas');

    }

    /**
     * Reporte de ventas por clientes
     */
    public function salesByClient(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        // Selección por defecto: año y mes actuales
        $defaultYear = date('Y');
        $defaultMonth = date('m');
        return view('reports.sales-by-client', [
            'companies' => $companies,
            'yearB' => $defaultYear,
            'period' => $defaultMonth
        ]);
    }

    /**
     * Buscar reporte de ventas por clientes
     */
    public function salesByClientSearch(Request $request){
        $Company = Company::find($request['company']);
        // Cargar listado de empresas para que el select siga funcionando tras la búsqueda
        $companies = Company::select('id','name')->orderBy('name')->get();

        // Consulta para obtener ventas agrupadas por cliente
        $salesByClient = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->select(
                'clients.id as client_id',
                'clients.firstname',
                'clients.firstlastname',
                'clients.comercial_name',
                'clients.tpersona',
                'clients.nit',
                'clients.email',
                'typedocuments.description as document_type'
            )
            ->selectRaw('COUNT(sales.id) as total_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN 1 ELSE 0 END) as completed_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 0 THEN 1 ELSE 0 END) as cancelled_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            ->selectRaw('AVG(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE NULL END) as average_amount')
            ->selectRaw('MIN(sales.date) as first_sale_date')
            ->selectRaw('MAX(sales.date) as last_sale_date')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1) // Solo ventas completadas (no anuladas)
            ->when($request->filled('date_range'), function($query) use ($request) {
                $dateRange = explode(' to ', $request['date_range']);
                if (count($dateRange) === 2) {
                    $query->whereBetween('sales.date', [$dateRange[0], $dateRange[1]]);
                }
            })
            ->when(!$request->filled('date_range') && $request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when(!$request->filled('date_range') && $request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->when($request->filled('client_id'), function($query) use ($request) {
                $query->where('clients.id', $request['client_id']);
            })
            ->groupBy('clients.id', 'clients.firstname', 'clients.firstlastname', 'clients.comercial_name', 'clients.tpersona', 'clients.nit', 'clients.email', 'typedocuments.description')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Obtener detalles de ventas por cliente si se solicita
        $salesDetails = null;
        if ($request->filled('client_id') && $request->filled('show_details')) {
            $salesDetails = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
                ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
                ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
                ->join('products', 'salesdetails.product_id', '=', 'products.id')
                ->select(
                    'sales.id as sale_id',
                    'sales.date',
                    'sales.totalamount',
                    'sales.state',
                    'typedocuments.description as document_type',
                    'products.name as product_name',
                    'salesdetails.amountp as quantity',
                    'salesdetails.pricesale',
                    'salesdetails.exempt',
                    'salesdetails.detained13'
                )
                ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS formatted_date")
                ->selectRaw("CASE
                    WHEN clients.tpersona = 'J' THEN clients.comercial_name
                    ELSE CONCAT(clients.firstname, ' ', clients.firstlastname)
                END as client_name")
                ->where('clients.id', $request['client_id'])
                ->where('sales.company_id', $request['company'])
                ->where('sales.state', 1) // Solo ventas completadas (no anuladas)
                ->when($request->filled('date_range'), function($query) use ($request) {
                    $dateRange = explode(' to ', $request['date_range']);
                    if (count($dateRange) === 2) {
                        $query->whereBetween('sales.date', [$dateRange[0], $dateRange[1]]);
                    }
                })
                ->when(!$request->filled('date_range') && $request->filled('year'), function($query) use ($request) {
                    $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
                })
                ->when(!$request->filled('date_range') && $request->filled('period'), function($query) use ($request) {
                    $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
                })
                ->orderBy('sales.date', 'desc')
                ->get();
        }

        // Si se solicita exportación a Excel
        if ($request->filled('export_excel')) {
            if ($request->filled('show_details') && $request->filled('client_id')) {
                return $this->exportDetailsToExcel($salesDetails, $Company);
            } else {
                return $this->exportToExcel($salesByClient, $Company);
            }
        }

        // Si se solicitan detalles, retornar la vista de detalles (sin target="_blank")
        if ($request->filled('client_id') && $request->filled('show_details')) {
            return view('reports.sales-by-client-details', array(
                "heading" => $Company,
                "yearB" => $request['year'] ?? null,
                "period" => $request['period'] ?? null,
                "client_id" => $request['client_id'] ?? null,
                "companies" => $companies,
                "salesDetails" => $salesDetails
            ));
        }

        // Generar PDF automáticamente al hacer búsqueda (como facturas)
        try {
            $pdf = app('dompdf.wrapper');
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->set_option('defaultFont', 'Arial');
            $pdf->set_option('dpi', 96);

            $pdf->loadView('reports.sales-by-client-pdf', [
                'heading' => $Company,
                'yearB' => $request['year'] ?? null,
                'period' => $request['period'] ?? null,
                'dateRange' => $request['date_range'] ?? null,
                'salesByClient' => $salesByClient
            ]);
            $pdf->setPaper('Letter', 'portrait');

            // Mostrar el PDF en el navegador (preview como facturas)
            $fileName = 'reporte_ventas_clientes_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            Log::error('Error generando PDF: ' . $e->getMessage());

            // Si falla el PDF, mostrar vista HTML
            return view('reports.sales-by-client', array(
                "heading" => $Company,
                "yearB" => $request['year'] ?? null,
                "period" => $request['period'] ?? null,
                "client_id" => $request['client_id'] ?? null,
                "companies" => $companies,
                "salesByClient" => $salesByClient,
                "salesDetails" => $salesDetails
            ));
        }
    }

    /**
     * Exportar reporte de ventas por clientes a Excel
     */
    public function exportToExcel($salesByClient, $company)
    {
        $filename = 'reporte_ventas_por_clientes_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($salesByClient, $company) {
            $file = fopen('php://output', 'w');

            // BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Encabezados
            fputcsv($file, [
                'Cliente',
                'Tipo',
                'NIT',
                'Total Ventas',
                'Ventas Completadas',
                'Ventas Canceladas',
                'Monto Total',
                'Promedio por Venta',
                'Primera Venta',
                'Última Venta'
            ]);

            // Datos
            foreach ($salesByClient as $sale) {
                $clientName = $sale->tpersona == 'J' ? $sale->comercial_name : $sale->firstname . ' ' . $sale->firstlastname;

                fputcsv($file, [
                    $clientName,
                    $sale->tpersona == 'J' ? 'Jurídica' : 'Natural',
                    $sale->nit,
                    $sale->total_sales,
                    $sale->completed_sales,
                    $sale->cancelled_sales,
                    number_format($sale->total_amount, 2),
                    number_format($sale->average_amount, 2),
                    $sale->first_sale_date ? date('d/m/Y', strtotime($sale->first_sale_date)) : '',
                    $sale->last_sale_date ? date('d/m/Y', strtotime($sale->last_sale_date)) : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar detalles de ventas por cliente a Excel
     */
    public function exportDetailsToExcel($salesDetails, $company)
    {
        $filename = 'detalles_ventas_cliente_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($salesDetails, $company) {
            $file = fopen('php://output', 'w');

            // BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Encabezados
            fputcsv($file, [
                'ID Venta',
                'Fecha',
                'Tipo Documento',
                'Cliente',
                'Producto',
                'Cantidad',
                'Precio Unitario',
                'Exento',
                'Retenido 13%',
                'Total',
                'Estado'
            ]);

            // Datos
            foreach ($salesDetails as $detail) {
                fputcsv($file, [
                    $detail->sale_id,
                    $detail->formatted_date,
                    $detail->document_type,
                    $detail->client_name,
                    $detail->product_name,
                    number_format($detail->quantity, 2),
                    number_format($detail->pricesale, 2),
                    $detail->exempt ? 'Sí' : 'No',
                    $detail->detained13 ? 'Sí' : 'No',
                    number_format($detail->quantity * $detail->pricesale, 2),
                    $detail->state == 1 ? 'Completada' : 'Cancelada'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar reporte de contribuyentes a Excel (HTML)
     */
    public function exportContribuyentesToExcel($sales, $company, $year, $period)
    {
        $mesesDelAno = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );

        $filename = 'ventas_contribuyentes_' . $company->name . '_' . $year . '_' . $period . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // Variables para totales
        $total_ex = 0;
        $total_gv = 0;
        $total_gv2 = 0;
        $total_iva = 0;
        $total_iva2 = 0;
        $total_ns = 0;
        $total_iva2P = 0;
        $vto = 0;
        $i = 1;

        // Generar HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Libro de Ventas a Contribuyentes</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin: 0; padding: 0; }
        th, td { border: 1px solid #000; padding: 3px; text-align: left; font-size: 9px; margin: 0; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { margin: 0 0 10px 0; padding: 0; text-align: center; }
        .subheader { font-size: 10px; margin: 0 0 5px 0; padding: 0; }
        .totals { font-weight: bold; background-color: #e0e0e0; }
        h2 { margin: 0 0 5px 0; padding: 0; font-size: 14px; }
        p { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LIBRO DE VENTAS CONTRIBUYENTES (Valores expresados en USD)</h2>
        <div class="subheader">
            <strong>Nombre del Contribuyente:</strong> ' . $company->name . ' &nbsp;&nbsp;
            <strong>N.R.C.:</strong> ' . $company->nrc . ' &nbsp;&nbsp;
            <strong>NIT:</strong> ' . $company->nit . ' &nbsp;&nbsp;
            <strong>MES:</strong> ' . strtoupper($mesesDelAno[(int)$period-1]) . ' &nbsp;&nbsp;
            <strong>AÑO:</strong> ' . $year . '
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="4"></th>
                <th colspan="3" class="text-center">VENTAS PROPIAS</th>
                <th colspan="3" class="text-center">A CUENTA DE TERCEROS</th>
                <th colspan="3" class="text-center">INFORMACIÓN DTE</th>
            </tr>
            <tr>
                <th style="width: 50px;">NUM. CORR.</th>
                <th style="width: 120px;">Fecha Recibido</th>
                <th style="width: 200px;">Nombre del Cliente</th>
                <th style="width: 80px;">NRC</th>
                <th>Exentas</th>
                <th>Internas Gravadas</th>
                <th>Debito Fiscal</th>
                <th>No Sujetas</th>
                <th>Exentas</th>
                <th>Internas Gravadas</th>
                <th>Debito Fiscal</th>
                <th>IVA Percibido</th>
                <th>TOTAL</th>
                <th style="width: 180px;">ID Doc</th>
                <th style="width: 200px;">Sello Recibido</th>
                <th style="width: 200px;">Código Generación</th>
            </tr>
        </thead>
        <tbody>';

        // Procesar datos de las ventas
        foreach ($sales as $sale) {
            $clientName = '';
            if ($sale['typesale'] == '0') {
                $clientName = 'ANULADO';
            } else {
                if ($sale['tpersona'] == 'J') {
                    $clientName = $sale['comercial_name'];
                } elseif ($sale['tpersona'] == 'N') {
                    $clientName = $sale['firstname'] . ' ' . $sale['firstlastname'];
                }
            }

            $exenta = $sale['typesale'] == '0' ? 0 : $sale['exenta'];
            $gravada = $sale['typesale'] == '0' ? 0 : $sale['gravada'];
            $iva = $sale['typesale'] == '0' ? 0 : $sale['iva'];
            $nosujeta = $sale['typesale'] == '0' ? 0 : $sale['nosujeta'];
            $ivaP = $sale['typesale'] == '0' ? 0 : ($sale['ivaP'] ?? 0);
            $totalamount = $sale['typesale'] == '0' ? 0 : $sale['totalamount'];

            $html .= '
                <tr>
                    <td>' . $i . '</td>
                    <td>' . $sale['dateF'] . '</td>
                    <td>' . $clientName . '</td>
                    <td class="text-right">' . $sale['ncrC'] . '</td>
                    <td class="text-right">' . number_format($exenta, 2) . '</td>
                    <td class="text-right">' . number_format($gravada, 2) . '</td>
                    <td class="text-right">' . number_format($iva, 2) . '</td>
                    <td class="text-right">' . number_format($nosujeta, 2) . '</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">' . number_format($ivaP, 2) . '</td>
                    <td class="text-right">' . number_format($totalamount, 2) . '</td>
                    <td>' . ($sale['numero_control'] ?? '-') . '</td>
                    <td>' . ($sale['sello_recibido'] ?? '-') . '</td>
                    <td>' . ($sale['codigo_generacion'] ?? '-') . '</td>
                </tr>';

            // Acumular totales
            $total_ex += $exenta;
            $total_gv += $gravada;
            $total_gv2 += $gravada;
            $total_iva += $iva;
            $total_iva2 += $iva;
            $total_ns += $nosujeta;
            $total_iva2P += $ivaP;
            $vto += $totalamount;
            $i++;
        }

        // Agregar fila de totales
        $html .= '
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td colspan="4" class="text-right">TOTALES DEL MES</td>
                    <td class="text-right">' . number_format($total_ex, 2) . '</td>
                    <td class="text-right">' . number_format($total_gv, 2) . '</td>
                    <td class="text-right">' . number_format($total_iva, 2) . '</td>
                    <td class="text-right">' . number_format($total_ns, 2) . '</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">0.00</td>
                    <td class="text-right">' . number_format($total_iva2P, 2) . '</td>
                    <td class="text-right">' . number_format($vto, 2) . '</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        </table>

        <table style="text-align: center; font-size: 9px; margin-top: 10px;" border="1">
            <tr>
                <td rowspan="2"><b>RESUMEN OPERACIONES</b></td>
                <td colspan="2"><b>PROPIAS</b></td>
                <td colspan="2"><b>A CUENTA DE TERCEROS</b></td>
            </tr>
            <tr>
                <td style="width: 100px;"><b>VALOR NETO</b></td>
                <td style="width: 100px;"><b>DEBITO FISCAL</b></td>
                <td style="width: 100px;"><b>VALOR NETO</b></td>
                <td style="width: 100px;"><b>DEBITO FISCAL</b></td>
                <td style="width: 100px;"><b>IVA PERCIBIDO</b></td>
            </tr>
            <tr style="text-align: left;">
                <td style="width: 400px;">&nbsp;&nbsp;VENTAS NETAS INTERNAS GRAVADAS A CONTRIBUYENTES</td>
                <td style="text-align: right;">$' . number_format($total_gv, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$' . number_format($total_iva, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
            <tr style="text-align: left;">
                <td>&nbsp;&nbsp;VENTAS NETAS INTERNAS EXENTAS A CONTRIBUYENTES</td>
                <td style="text-align: right;">$' . number_format($total_ex, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
            <tr style="text-align: left;">
                <td>&nbsp;&nbsp;VENTAS NETAS INTERNAS NO SUJETAS A CONTRIBUYENTES</td>
                <td style="text-align: right;">$' . number_format($total_ns, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
        </table>

        <div style="text-align: center; font-size: 9px; color: #666; margin-top: 10px;">
            <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
        </div>
    </body>
</html>';

        return response($html, 200, $headers);
    }

    /**
     * Exportar reporte de consumidor a Excel (HTML)
     */
    public function exportConsumidorToExcel($sales, $company, $year, $period)
    {
        $mesesDelAno = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );

        $filename = 'ventas_consumidor_' . $company->name . '_' . $year . '_' . $period . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // Variables para totales
        $total_ex = 0;
        $total_gv = 0;
        $total_gv2 = 0;
        $total_iva = 0;
        $total_iva2 = 0;
        $total_ns = 0;
        $total_iva2P = 0;
        $vto = 0;
        $i = 1;

        // Generar HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Libro de Ventas a Consumidor Final</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin: 0; padding: 0; }
        th, td { border: 1px solid #000; padding: 3px; text-align: left; font-size: 9px; margin: 0; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { margin: 0 0 10px 0; padding: 0; text-align: center; }
        .subheader { font-size: 10px; margin: 0 0 5px 0; padding: 0; }
        .totals { font-weight: bold; background-color: #e0e0e0; }
        h2 { margin: 0 0 5px 0; padding: 0; font-size: 14px; }
        p { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LIBRO DE VENTAS CONSUMIDOR FINAL (Valores expresados en USD)</h2>
        <div class="subheader">
            <strong>Nombre del Contribuyente:</strong> ' . $company->name . ' &nbsp;&nbsp;
            <strong>N.R.C.:</strong> ' . $company->ncr . ' &nbsp;&nbsp;
            <strong>NIT:</strong> ' . $company->nit . ' &nbsp;&nbsp;
            <strong>MES:</strong> ' . strtoupper($mesesDelAno[(int)$period-1]) . ' &nbsp;&nbsp;
            <strong>AÑO:</strong> ' . $year . '
        </div>
    </div>

        <table>
        <thead>
            <tr>
                <th colspan="11" class="text-center">LIBRO DE VENTAS CONSUMIDOR FINAL</th>
            </tr>
            <tr>
                <th style="width: 50px;">NUM. CORR.</th>
                <th style="width: 120px;">Fecha</th>
                <th style="width: 250px;">Nombre del Cliente</th>
                <th>Exentas</th>
                <th>Internas Gravadas</th>
                <th>No Sujetas</th>
                <th>Debito Fiscal</th>
                <th>TOTAL</th>
                <th style="width: 180px;">Número Control</th>
                <th style="width: 200px;">Código Generación</th>
                <th style="width: 200px;">Sello Recibido</th>
            </tr>
        </thead>
        <tbody>';

        // Procesar datos de las ventas
        foreach ($sales as $sale) {
            $clientName = '';
            if ($sale['typesale'] == '0') {
                $clientName = 'ANULADO';
            } else {
                if ($sale['tpersona'] == 'J') {
                    $clientName = $sale['comercial_name'];
                } elseif ($sale['tpersona'] == 'N') {
                    $clientName = $sale['firstname'] . ' ' . $sale['firstlastname'];
                }
            }

            $exenta = $sale['typesale'] == '0' ? 0 : $sale['exenta'];
            $gravada = $sale['typesale'] == '0' ? 0 : $sale['gravada'];
            $iva = $sale['typesale'] == '0' ? 0 : $sale['iva'];
            $nosujeta = $sale['typesale'] == '0' ? 0 : $sale['nosujeta'];
            $ivaP = $sale['typesale'] == '0' ? 0 : ($sale['ivaP'] ?? 0);
            $totalamount = $sale['typesale'] == '0' ? 0 : $sale['totalamount'];

            $html .= '
                <tr>
                    <td>' . $i . '</td>
                    <td>' . $sale['dateF'] . '</td>
                    <td>' . $clientName . '</td>
                    <td class="text-right">' . number_format($exenta, 2) . '</td>
                    <td class="text-right">' . number_format($gravada, 2) . '</td>
                    <td class="text-right">' . number_format($nosujeta, 2) . '</td>
                    <td class="text-right">' . number_format($iva, 2) . '</td>
                    <td class="text-right">' . number_format($totalamount, 2) . '</td>
                    <td>' . ($sale['numero_control'] ?? '-') . '</td>
                    <td>' . ($sale['codigo_generacion'] ?? $sale['correlativo']) . '</td>
                    <td>' . ($sale['sello_recibido'] ?? '-') . '</td>
                </tr>';

            // Acumular totales
            $total_ex += $exenta;
            $total_gv += $gravada;
            $total_gv2 += $gravada;
            $total_iva += $iva;
            $total_iva2 += $iva;
            $total_ns += $nosujeta;
            $total_iva2P += $ivaP;
            $vto += $totalamount;
            $i++;
        }

        // Agregar fila de totales
        $html .= '
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td colspan="4" class="text-right">TOTALES DEL MES</td>
                    <td class="text-right">' . number_format($total_ex, 2) . '</td>
                    <td class="text-right">' . number_format($total_gv, 2) . '</td>
                    <td class="text-right">' . number_format($total_ns, 2) . '</td>
                    <td class="text-right">' . number_format($total_iva, 2) . '</td>
                    <td class="text-right">' . number_format($vto, 2) . '</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        </table>

        <table style="text-align: center; font-size: 9px; margin-top: 10px;" border="1">
            <tr>
                <td rowspan="2"><b>RESUMEN OPERACIONES</b></td>
                <td colspan="2"><b>PROPIAS</b></td>
                <td colspan="2"><b>A CUENTA DE TERCEROS</b></td>
            </tr>
            <tr>
                <td style="width: 100px;"><b>VALOR NETO</b></td>
                <td style="width: 100px;"><b>DEBITO FISCAL</b></td>
                <td style="width: 100px;"><b>VALOR NETO</b></td>
                <td style="width: 100px;"><b>DEBITO FISCAL</b></td>
                <td style="width: 100px;"><b>IVA PERCIBIDO</b></td>
            </tr>
            <tr style="text-align: left;">
                <td style="width: 400px;">&nbsp;&nbsp;VENTAS NETAS INTERNAS GRAVADAS A CONSUMIDOR FINAL</td>
                <td style="text-align: right;">$' . number_format($total_gv, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$' . number_format($total_iva, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
            <tr style="text-align: left;">
                <td>&nbsp;&nbsp;VENTAS NETAS INTERNAS EXENTAS A CONSUMIDOR FINAL</td>
                <td style="text-align: right;">$' . number_format($total_ex, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
            <tr style="text-align: left;">
                <td>&nbsp;&nbsp;VENTAS NETAS INTERNAS NO SUJETAS A CONSUMIDOR FINAL</td>
                <td style="text-align: right;">$' . number_format($total_ns, 2) . '&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
                <td style="text-align: right;">$0.00&nbsp;&nbsp;</td>
            </tr>
        </table>

        <div style="text-align: center; font-size: 9px; color: #666; margin-top: 10px;">
            <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
        </div>
    </body>
</html>';

        return response($html, 200, $headers);
    }

    /**
     * Exportar reporte de ventas por clientes a PDF
     */
    public function salesByClientPdf(Request $request)
    {
        $request->validate([
            'company' => 'required|integer'
        ]);

        $Company = Company::findOrFail($request->input('company'));

        $salesByClient = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'clients.id as client_id',
                'clients.firstname',
                'clients.firstlastname',
                'clients.comercial_name',
                'clients.tpersona',
                'clients.nit',
                'clients.email'
            )
            ->selectRaw('COUNT(sales.id) as total_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN 1 ELSE 0 END) as completed_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 0 THEN 1 ELSE 0 END) as cancelled_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            ->selectRaw('AVG(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE NULL END) as average_amount')
            ->selectRaw('MIN(sales.date) as first_sale_date')
            ->selectRaw('MAX(sales.date) as last_sale_date')
            ->where('sales.company_id', $request->input('company'))
            ->where('sales.state', 1) // Solo ventas completadas (no anuladas)
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request->input('year')]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request->input('period')]);
            })
            ->when($request->filled('client_id'), function($query) use ($request) {
                $query->where('clients.id', $request->input('client_id'));
            })
            ->groupBy('clients.id', 'clients.firstname', 'clients.firstlastname', 'clients.comercial_name', 'clients.tpersona', 'clients.nit', 'clients.email')
            ->orderBy('total_amount', 'desc')
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);

        $pdf->loadView('reports.sales-by-client-pdf', [
            'heading' => $Company,
            'yearB' => $request->input('year'),
            'period' => $request->input('period'),
            'salesByClient' => $salesByClient
        ]);
        $pdf->setPaper('Letter', 'portrait');

        // Verificar si se solicita descarga o preview
        if ($request->has('download') && $request->input('download') == '1') {
            // Descargar el PDF
            $fileName = 'reporte_ventas_por_clientes_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($fileName);
        } else {
            // Mostrar el PDF en el navegador (preview)
            $fileName = 'reporte_ventas_por_clientes_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->stream($fileName);
        }
    }

    /**
     * Exportar detalles de ventas por cliente a PDF
     */
    public function salesByClientDetailsPdf(Request $request)
    {
        $request->validate([
            'company' => 'required|integer',
            'client_id' => 'required|integer'
        ]);

        $Company = Company::findOrFail($request->input('company'));

        $salesDetails = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->select(
                'sales.id as sale_id',
                'sales.date',
                'sales.totalamount',
                'sales.state',
                'typedocuments.description as document_type',
                'products.name as product_name',
                'salesdetails.amountp as quantity',
                'salesdetails.pricesale',
                'salesdetails.exempt',
                'salesdetails.detained13'
            )
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS formatted_date")
            ->selectRaw("CASE
                WHEN clients.tpersona = 'J' THEN clients.comercial_name
                ELSE CONCAT(clients.firstname, ' ', clients.firstlastname)
            END as client_name")
            ->where('clients.id', $request->input('client_id'))
            ->where('sales.company_id', $request->input('company'))
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request->input('year')]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request->input('period')]);
            })
            ->orderBy('sales.date', 'desc')
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);

        $pdf->loadView('reports.sales-by-client-details-pdf', [
            'heading' => $Company,
            'yearB' => $request->input('year'),
            'period' => $request->input('period'),
            'client_id' => $request->input('client_id'),
            'salesDetails' => $salesDetails
        ]);
        $pdf->setPaper('Letter', 'portrait');

        // Verificar si se solicita descarga o preview
        if ($request->has('download') && $request->input('download') == '1') {
            // Descargar el PDF
            $fileName = 'detalles_cliente_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->download($fileName);
        } else {
            // Mostrar el PDF en el navegador (preview)
            $fileName = 'detalles_cliente_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->stream($fileName);
        }
    }

    /**
     * Reporte de inventario
     */
    public function inventory(){
        return view('reports.inventory');
    }

    /**
     * Buscar reporte de inventario
     */
    public function inventorySearch(Request $request){
        $Company = Company::find($request['company']);

        // Consulta base para el inventario
        $inventoryQuery = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->leftJoin('providers', 'products.provider_id', '=', 'providers.id')
            ->leftJoin('marcas', 'products.marca_id', '=', 'marcas.id')
            ->leftJoin('units', 'inventory.base_unit_id', '=', 'units.id')
            ->select(
                'inventory.id as inventory_id',
                'products.id as product_id',
                'products.code as product_code',
                'products.name as product_name',
                'products.description as product_description',
                'products.type as product_type',
                'products.cfiscal as fiscal_type',
                'products.price as product_price',
                'products.state as product_state',
                'providers.razonsocial as provider_name',
                'marcas.name as marca_name',
                'units.unit_name as base_unit_name',
                'units.unit_code as base_unit_code',
                'inventory.quantity',
                'inventory.base_quantity',
                'inventory.base_unit_price',
                'inventory.minimum_stock',
                'inventory.location',
                'inventory.expiration_date',
                'inventory.batch_number',
                'inventory.expiring_quantity'
            );

        // Aplicar filtros
        if ($request->filled('category')) {
            $inventoryQuery->where('products.type', $request['category']);
        }

        if ($request->filled('provider_id')) {
            $inventoryQuery->where('products.provider_id', $request['provider_id']);
        }

        if ($request->filled('marca_id')) {
            $inventoryQuery->where('products.marca_id', $request['marca_id']);
        }

        if ($request->filled('stock_status')) {
            switch ($request['stock_status']) {
                case 'low_stock':
                    $inventoryQuery->whereRaw('inventory.quantity <= inventory.minimum_stock');
                    break;
                case 'out_of_stock':
                    $inventoryQuery->where('inventory.quantity', 0);
                    break;
                case 'expiring_soon':
                    $inventoryQuery->whereNotNull('inventory.expiration_date')
                                 ->where('inventory.expiration_date', '<=', now()->addDays(30));
                    break;
                case 'active':
                    $inventoryQuery->where('products.state', 1);
                    break;
            }
        }

        if ($request->filled('location')) {
            $inventoryQuery->where('inventory.location', 'LIKE', '%' . $request['location'] . '%');
        }

        // Ordenar resultados
        $orderBy = $request->get('order_by', 'product_name');
        $orderDirection = $request->get('order_direction', 'asc');

        switch ($orderBy) {
            case 'quantity':
                $inventoryQuery->orderBy('inventory.quantity', $orderDirection);
                break;
            case 'price':
                $inventoryQuery->orderBy('products.price', $orderDirection);
                break;
            case 'expiration':
                $inventoryQuery->orderBy('inventory.expiration_date', $orderDirection);
                break;
            default:
                $inventoryQuery->orderBy('products.name', $orderDirection);
        }

        $inventory = $inventoryQuery->get();

        // Calcular estadísticas
        $stats = [
            'total_products' => $inventory->count(),
            'total_value' => $inventory->sum(function($item) {
                return ($item->base_quantity ?? $item->quantity) * ($item->base_unit_price ?? $item->product_price ?? 0);
            }),
            'low_stock_count' => $inventory->filter(function($item) {
                return ($item->quantity ?? 0) <= ($item->minimum_stock ?? 0);
            })->count(),
            'out_of_stock_count' => $inventory->filter(function($item) {
                return ($item->quantity ?? 0) == 0;
            })->count(),
            'expiring_soon_count' => $inventory->filter(function($item) {
                return $item->expiration_date && $item->expiration_date <= now()->addDays(30);
            })->count(),
            'active_products' => $inventory->filter(function($item) {
                return $item->product_state == 1;
            })->count()
        ];

        // Obtener datos para filtros - categorías de productos en inventario
        $categories = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->distinct()
            ->pluck('products.type')
            ->filter()
            ->values();

        $providers = Provider::orderBy('razonsocial')
            ->get(['id', 'razonsocial']);

        $marcas = Marca::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = Inventory::whereNotNull('location')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        return view('reports.inventory', array(
            "heading" => $Company,
            "inventory" => $inventory,
            "stats" => $stats,
            "categories" => $categories,
            "providers" => $providers,
            "marcas" => $marcas,
            "locations" => $locations,
            "filters" => $request->all()
        ));
    }

    /**
     * Reporte de inventario por categoría
     */
    public function inventoryByCategory(Request $request){
        $Company = Company::find($request['company']);

        $inventoryByCategory = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->select(
                'products.type as category',
                DB::raw('COUNT(*) as total_products'),
                DB::raw('SUM(inventory.quantity) as total_quantity'),
                DB::raw('SUM(CASE WHEN inventory.quantity <= inventory.minimum_stock THEN 1 ELSE 0 END) as low_stock_products'),
                DB::raw('SUM(CASE WHEN inventory.quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_products'),
                DB::raw('SUM(inventory.quantity * COALESCE(inventory.base_unit_price, products.price)) as total_value')
            )
            ->groupBy('products.type')
            ->orderBy('total_value', 'desc')
            ->get();

        return view('reports.inventory-by-category', array(
            "heading" => $Company,
            "inventoryByCategory" => $inventoryByCategory
        ));
    }

    /**
     * Reporte de inventario por proveedor
     */
    public function inventoryByProvider(Request $request){
        $Company = Company::find($request['company']);

        $inventoryByProvider = Inventory::join('products', 'inventory.product_id', '=', 'products.id')
            ->join('providers', 'products.provider_id', '=', 'providers.id')
            ->select(
                'providers.id as provider_id',
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit',
                DB::raw('COUNT(*) as total_products'),
                DB::raw('SUM(inventory.quantity) as total_quantity'),
                DB::raw('SUM(inventory.quantity * COALESCE(inventory.base_unit_price, products.price)) as total_value'),
                DB::raw('AVG(COALESCE(inventory.base_unit_price, products.price)) as average_price')
            )
            ->groupBy('providers.id', 'providers.razonsocial', 'providers.nit')
            ->orderBy('total_value', 'desc')
            ->get();

        return view('reports.inventory-by-provider', array(
            "heading" => $Company,
            "inventoryByProvider" => $inventoryByProvider
        ));
    }

    /**
     * Reporte de ventas por proveedor
     */
    public function salesByProvider(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        $providers = Provider::orderBy('razonsocial')->get();
        return view('reports.sales-by-provider', [
            'companies' => $companies,
            'providers' => $providers
        ]);
    }

    /**
     * Buscar reporte de ventas por proveedor
     */
    public function salesByProviderSearch(Request $request){
        $Company = Company::find($request['company']);
        $companies = Company::select('id','name')->orderBy('name')->get();
        $providers = Provider::orderBy('razonsocial')->get();

        // Consulta para obtener ventas agrupadas por proveedor
        $salesByProvider = Salesdetail::join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->join('providers', 'products.provider_id', '=', 'providers.id')
            ->select(
                'providers.id as provider_id',
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit',
                'providers.email as provider_email'
            )
            ->selectRaw('COUNT(DISTINCT sales.id) as total_sales')
            ->selectRaw('COUNT(DISTINCT products.id) as products_sold')
            ->selectRaw('SUM(salesdetails.amountp) as total_quantity')
            ->selectRaw('SUM(salesdetails.pricesale) as total_amount')
            ->selectRaw('AVG(salesdetails.pricesale / salesdetails.amountp) as average_price')
            ->selectRaw('MIN(sales.date) as first_sale_date')
            ->selectRaw('MAX(sales.date) as last_sale_date')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1)
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->when($request->filled('provider_id'), function($query) use ($request) {
                $query->where('providers.id', $request['provider_id']);
            })
            ->when($request->filled('date_range'), function($query) use ($request) {
                $dateRange = explode(' to ', $request['date_range']);
                if (count($dateRange) === 2) {
                    $query->whereBetween('sales.date', [$dateRange[0], $dateRange[1]]);
                }
            })
            ->groupBy('providers.id', 'providers.razonsocial', 'providers.nit', 'providers.email')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Calcular estadísticas
        $stats = [
            'total_providers' => $salesByProvider->count(),
            'total_sales' => $salesByProvider->sum('total_sales'),
            'total_amount' => $salesByProvider->sum('total_amount'),
            'average_per_provider' => $salesByProvider->count() > 0 ? $salesByProvider->sum('total_amount') / $salesByProvider->count() : 0
        ];

        return view('reports.sales-by-provider', array(
            "heading" => $Company,
            "companies" => $companies,
            "providers" => $providers,
            "salesByProvider" => $salesByProvider,
            "stats" => $stats,
            "filters" => $request->all()
        ));
    }

    /**
     * Análisis general de ventas
     */
    public function salesAnalysis(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        return view('reports.sales-analysis', [
            'companies' => $companies
        ]);
    }

    /**
     * Buscar análisis general de ventas
     */
    public function salesAnalysisSearch(Request $request){
        $Company = Company::find($request['company']);
        $companies = Company::select('id','name')->orderBy('name')->get();

        // Análisis por período
        $salesByPeriod = Sale::select(
                DB::raw('DATE_FORMAT(date, "%Y-%m") as period'),
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(CASE WHEN state = 1 THEN totalamount ELSE 0 END) as total_amount'),
                DB::raw('AVG(CASE WHEN state = 1 THEN totalamount ELSE NULL END) as average_ticket'),
                DB::raw('SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END) as cancelled_sales')
            )
            ->where('company_id', $request['company'])
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(date) = ?', [$request['year']]);
            })
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        // Análisis por tipo de documento
        $salesByDocument = Sale::join('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
            ->select(
                'typedocuments.description as document_type',
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            )
            ->where('sales.company_id', $request['company'])
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->groupBy('typedocuments.id', 'typedocuments.description')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Top 10 clientes
        $topClients = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'clients.id',
                DB::raw('CASE WHEN clients.tpersona = "J" THEN clients.comercial_name ELSE CONCAT(clients.firstname, " ", clients.firstlastname) END as client_name'),
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            )
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1)
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->groupBy('clients.id', 'client_name')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        // Estadísticas generales
        $stats = [
            'total_sales' => Sale::where('company_id', $request['company'])
                ->where('state', 1)
                ->when($request->filled('year'), function($query) use ($request) {
                    $query->whereRaw('YEAR(date) = ?', [$request['year']]);
                })
                ->when($request->filled('period'), function($query) use ($request) {
                    $query->whereRaw('MONTH(date) = ?', [$request['period']]);
                })
                ->count(),
            'total_amount' => Sale::where('company_id', $request['company'])
                ->where('state', 1)
                ->when($request->filled('year'), function($query) use ($request) {
                    $query->whereRaw('YEAR(date) = ?', [$request['year']]);
                })
                ->when($request->filled('period'), function($query) use ($request) {
                    $query->whereRaw('MONTH(date) = ?', [$request['period']]);
                })
                ->sum('totalamount'),
            'average_ticket' => Sale::where('company_id', $request['company'])
                ->where('state', 1)
                ->when($request->filled('year'), function($query) use ($request) {
                    $query->whereRaw('YEAR(date) = ?', [$request['year']]);
                })
                ->when($request->filled('period'), function($query) use ($request) {
                    $query->whereRaw('MONTH(date) = ?', [$request['period']]);
                })
                ->avg('totalamount'),
            'cancelled_sales' => Sale::where('company_id', $request['company'])
                ->where('state', 0)
                ->when($request->filled('year'), function($query) use ($request) {
                    $query->whereRaw('YEAR(date) = ?', [$request['year']]);
                })
                ->when($request->filled('period'), function($query) use ($request) {
                    $query->whereRaw('MONTH(date) = ?', [$request['period']]);
                })
                ->count()
        ];

        return view('reports.sales-analysis', array(
            "heading" => $Company,
            "companies" => $companies,
            "salesByPeriod" => $salesByPeriod,
            "salesByDocument" => $salesByDocument,
            "topClients" => $topClients,
            "stats" => $stats,
            "filters" => $request->all()
        ));
    }

    /**
     * Análisis de ventas por producto
     */
    public function salesByProduct(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        return view('reports.sales-by-product', [
            'companies' => $companies
        ]);
    }

    /**
     * Buscar análisis de ventas por producto
     */
    public function salesByProductSearch(Request $request){
        $Company = Company::find($request['company']);
        $companies = Company::select('id','name')->orderBy('name')->get();

        // Consulta para obtener ventas agrupadas por producto con cálculo de ganancia
        $salesByProduct = Salesdetail::join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->leftJoin('providers', 'products.provider_id', '=', 'providers.id')
            ->leftJoin('marcas', 'products.marca_id', '=', 'marcas.id')
            ->select(
                'products.id as product_id',
                'products.code as product_code',
                'products.name as product_name',
                'products.type as product_type',
                'providers.razonsocial as provider_name',
                'marcas.name as marca_name'
            )
            ->selectRaw('COUNT(DISTINCT sales.id) as total_sales')
            ->selectRaw('SUM(salesdetails.amountp) as total_quantity')
            ->selectRaw('SUM(salesdetails.pricesale) as total_amount')
            ->selectRaw('AVG(salesdetails.pricesale / salesdetails.amountp) as average_sale_price')
            ->selectRaw('(SELECT unit_price FROM purchase_details pd
                         JOIN purchases p ON pd.purchase_id = p.id
                         WHERE pd.product_id = products.id
                         AND p.company_id = ' . $request['company'] . '
                         ' . ($request->filled('year') ? 'AND YEAR(p.date) = ' . $request['year'] : '') . '
                         ' . ($request->filled('period') ? 'AND MONTH(p.date) = ' . $request['period'] : '') . '
                         ' . ($request->filled('date_range') ? 'AND p.date BETWEEN "' . explode(' to ', $request['date_range'])[0] . '" AND "' . explode(' to ', $request['date_range'])[1] . '"' : '') . '
                         ORDER BY p.date DESC LIMIT 1) as last_cost_price')
            ->selectRaw('SUM(salesdetails.amountp * ((salesdetails.pricesale / salesdetails.amountp) - COALESCE((SELECT unit_price FROM purchase_details pd
                         JOIN purchases p ON pd.purchase_id = p.id
                         WHERE pd.product_id = products.id
                         AND p.company_id = ' . $request['company'] . '
                         ' . ($request->filled('year') ? 'AND YEAR(p.date) = ' . $request['year'] : '') . '
                         ' . ($request->filled('period') ? 'AND MONTH(p.date) = ' . $request['period'] : '') . '
                         ' . ($request->filled('date_range') ? 'AND p.date BETWEEN "' . explode(' to ', $request['date_range'])[0] . '" AND "' . explode(' to ', $request['date_range'])[1] . '"' : '') . '
                         ORDER BY p.date DESC LIMIT 1), 0))) as total_profit')
            ->selectRaw('CASE
                WHEN (SELECT unit_price FROM purchase_details pd
                      JOIN purchases p ON pd.purchase_id = p.id
                      WHERE pd.product_id = products.id
                      AND p.company_id = ' . $request['company'] . '
                      ' . ($request->filled('year') ? 'AND YEAR(p.date) = ' . $request['year'] : '') . '
                      ' . ($request->filled('period') ? 'AND MONTH(p.date) = ' . $request['period'] : '') . '
                      ' . ($request->filled('date_range') ? 'AND p.date BETWEEN "' . explode(' to ', $request['date_range'])[0] . '" AND "' . explode(' to ', $request['date_range'])[1] . '"' : '') . '
                      ORDER BY p.date DESC LIMIT 1) > 0
                THEN ((AVG(salesdetails.pricesale / salesdetails.amountp) - (SELECT unit_price FROM purchase_details pd
                      JOIN purchases p ON pd.purchase_id = p.id
                      WHERE pd.product_id = products.id
                      AND p.company_id = ' . $request['company'] . '
                      ' . ($request->filled('year') ? 'AND YEAR(p.date) = ' . $request['year'] : '') . '
                      ' . ($request->filled('period') ? 'AND MONTH(p.date) = ' . $request['period'] : '') . '
                      ' . ($request->filled('date_range') ? 'AND p.date BETWEEN "' . explode(' to ', $request['date_range'])[0] . '" AND "' . explode(' to ', $request['date_range'])[1] . '"' : '') . '
                      ORDER BY p.date DESC LIMIT 1)) / (SELECT unit_price FROM purchase_details pd
                      JOIN purchases p ON pd.purchase_id = p.id
                      WHERE pd.product_id = products.id
                      AND p.company_id = ' . $request['company'] . '
                      ' . ($request->filled('year') ? 'AND YEAR(p.date) = ' . $request['year'] : '') . '
                      ' . ($request->filled('period') ? 'AND MONTH(p.date) = ' . $request['period'] : '') . '
                      ' . ($request->filled('date_range') ? 'AND p.date BETWEEN "' . explode(' to ', $request['date_range'])[0] . '" AND "' . explode(' to ', $request['date_range'])[1] . '"' : '') . '
                      ORDER BY p.date DESC LIMIT 1)) * 100
                ELSE 0
                END as profit_margin_percentage')
            ->selectRaw('MIN(sales.date) as first_sale_date')
            ->selectRaw('MAX(sales.date) as last_sale_date')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1)
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->when($request->filled('category'), function($query) use ($request) {
                $query->where('products.type', $request['category']);
            })
            ->when($request->filled('provider_id'), function($query) use ($request) {
                $query->where('products.provider_id', $request['provider_id']);
            })
            ->when($request->filled('date_range'), function($query) use ($request) {
                $dateRange = explode(' to ', $request['date_range']);
                if (count($dateRange) === 2) {
                    $query->whereBetween('sales.date', [$dateRange[0], $dateRange[1]]);
                }
            })
            ->groupBy('products.id', 'products.code', 'products.name', 'products.type', 'providers.razonsocial', 'marcas.name')
            ->orderBy('total_profit', 'desc')
            ->get();

        // Obtener filtros - productos que han sido vendidos en esta empresa
        $categories = Salesdetail::join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1)
            ->distinct()
            ->pluck('products.type')
            ->filter()
            ->values();

        $providers = Provider::orderBy('razonsocial')
            ->get(['id', 'razonsocial']);

        // Calcular estadísticas
        $stats = [
            'total_products' => $salesByProduct->count(),
            'total_quantity' => $salesByProduct->sum('total_quantity'),
            'total_amount' => $salesByProduct->sum('total_amount'),
            'average_per_product' => $salesByProduct->count() > 0 ? $salesByProduct->sum('total_amount') / $salesByProduct->count() : 0
        ];

        return view('reports.sales-by-product', array(
            "heading" => $Company,
            "companies" => $companies,
            "salesByProduct" => $salesByProduct,
            "stats" => $stats,
            "categories" => $categories,
            "providers" => $providers,
            "filters" => $request->all()
        ));
    }

    /**
     * Análisis de ventas por categoría
     */
    public function salesByCategory(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        return view('reports.sales-by-category', [
            'companies' => $companies
        ]);
    }

    /**
     * Buscar análisis de ventas por categoría
     */
    public function salesByCategorySearch(Request $request){
        $Company = Company::find($request['company']);
        $companies = Company::select('id','name')->orderBy('name')->get();

        // Consulta para obtener ventas agrupadas por categoría
        $salesByCategory = Salesdetail::join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->select(
                'products.type as category'
            )
            ->selectRaw('COUNT(DISTINCT sales.id) as total_sales')
            ->selectRaw('COUNT(DISTINCT products.id) as total_products')
            ->selectRaw('SUM(salesdetails.amountp) as total_quantity')
            ->selectRaw('SUM(salesdetails.pricesale) as total_amount')
            ->selectRaw('AVG(salesdetails.pricesale / salesdetails.amountp) as average_price')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1)
            ->when($request->filled('year'), function($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when($request->filled('period'), function($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->when($request->filled('date_range'), function($query) use ($request) {
                $dateRange = explode(' to ', $request['date_range']);
                if (count($dateRange) === 2) {
                    $query->whereBetween('sales.date', [$dateRange[0], $dateRange[1]]);
                }
            })
            ->groupBy('products.type')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Calcular estadísticas
        $stats = [
            'total_categories' => $salesByCategory->count(),
            'total_amount' => $salesByCategory->sum('total_amount'),
            'total_quantity' => $salesByCategory->sum('total_quantity'),
            'average_per_category' => $salesByCategory->count() > 0 ? $salesByCategory->sum('total_amount') / $salesByCategory->count() : 0
        ];

        return view('reports.sales-by-category', array(
            "heading" => $Company,
            "companies" => $companies,
            "salesByCategory" => $salesByCategory,
            "stats" => $stats,
            "filters" => $request->all()
        ));
    }


    /**
     * Reporte de movimientos de inventario
     */
    public function inventoryMovements(){
        $companies = Company::select('id','name')->orderBy('name')->get();
        $providers = Provider::orderBy('razonsocial')->get(['id', 'razonsocial']);
        $products = Product::whereHas('inventory')->orderBy('name')->get(['id', 'name', 'code']);

        return view('reports.inventory-movements', [
            'companies' => $companies,
            'providers' => $providers,
            'products' => $products
        ]);
    }

    /**
     * Reporte Kardex detallado de un producto
     */
    public function inventoryKardex(Request $request){
        $companies = Company::select('id','name')->orderBy('name')->get();
        $products = Product::whereHas('inventory')->orderBy('name')->get(['id', 'name', 'code']);

        // Si no hay datos en el request, mostrar solo el formulario
        if (!$request->filled('company') || !$request->filled('product_id')) {
            return view('reports.inventory-kardex', [
                'companies' => $companies,
                'products' => $products
            ]);
        }

        // Validar datos del formulario
        $request->validate([
            'company' => 'required|integer',
            'product_id' => 'required|integer'
        ]);

        $Company = Company::findOrFail($request['company']);
        $product = Product::with(['inventory', 'provider', 'marca'])->findOrFail($request['product_id']);

        // Preparar fechas de filtrado
        $dateFrom = $request->filled('date_from') ? $request['date_from'] : null;
        $dateTo = $request->filled('date_to') ? $request['date_to'] : null;

        // Obtener compras (entradas)
        $purchasesQuery = PurchaseDetail::where('product_id', $product->id)
            ->where('added_to_inventory', true)
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('purchases.company_id', $request['company']);

        if ($dateFrom) {
            $purchasesQuery->where('purchases.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $purchasesQuery->where('purchases.date', '<=', $dateTo);
        }

        $purchases = $purchasesQuery->select(
            'purchase_details.*',
            'purchases.date as movement_date',
            'purchases.number as document',
            'purchases.id as purchase_id'
        )->get();

        // Obtener ventas (salidas)
        $salesQuery = Salesdetail::where('product_id', $product->id)
            ->join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->leftJoin('dte', 'sales.id', '=', 'dte.sale_id')
            ->where('sales.company_id', $request['company'])
            ->where('sales.state', 1);

        if ($dateFrom) {
            $salesQuery->where('sales.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $salesQuery->where('sales.date', '<=', $dateTo);
        }

        $sales = $salesQuery->select(
            'salesdetails.*',
            'sales.date as movement_date',
            DB::raw('COALESCE(dte.id_doc, sales.nu_doc) as document'),
            'sales.id as sale_id'
        )->get();

        // Construir movimientos tipo Kardex
        $movements = [];
        $unitConversion = new \App\Services\UnitConversionService();

        // Agregar compras como entradas
        foreach ($purchases as $purchase) {
            $baseQuantity = $unitConversion->calculateBaseQuantityNeeded(
                $product->id,
                $purchase->quantity,
                $purchase->unit_code ?? '59'
            );

            $movements[] = [
                'date' => $purchase->movement_date,
                'type' => 'COMPRA',
                'concept' => 'Compra',
                'document' => $purchase->document,
                'entry_quantity' => $baseQuantity,
                'exit_quantity' => 0,
                'reference_id' => $purchase->purchase_id
            ];
        }

        // Agregar ventas como salidas
        foreach ($sales as $sale) {
            $baseQuantity = $sale->base_quantity_used ?? $sale->amountp;

            $movements[] = [
                'date' => $sale->movement_date,
                'type' => 'VENTA',
                'concept' => 'Venta',
                'document' => $sale->document,
                'entry_quantity' => 0,
                'exit_quantity' => $baseQuantity,
                'reference_id' => $sale->sale_id
            ];
        }

        // Ordenar movimientos por fecha
        usort($movements, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Calcular saldos acumulados
        $balance = 0;
        foreach ($movements as &$movement) {
            $balance += $movement['entry_quantity'] - $movement['exit_quantity'];
            $movement['balance'] = $balance;
        }

        // Información del inventario actual
        $currentStock = $product->inventory ? $product->inventory->base_quantity : 0;
        $minimumStock = $product->inventory ? $product->inventory->minimum_stock : 0;

        // Estadísticas
        $stats = [
            'total_entries' => collect($movements)->sum('entry_quantity'),
            'total_exits' => collect($movements)->sum('exit_quantity'),
            'current_stock' => $currentStock,
            'minimum_stock' => $minimumStock,
            'calculated_balance' => $balance,
            'difference' => $currentStock - $balance,
            'total_movements' => count($movements),
            'purchases_count' => $purchases->count(),
            'sales_count' => $sales->count()
        ];

        $companies = Company::select('id','name')->orderBy('name')->get();
        $products = Product::whereHas('inventory')->orderBy('name')->get(['id', 'name', 'code']);

        return view('reports.inventory-kardex', array(
            "heading" => $Company,
            "companies" => $companies,
            "products" => $products,
            "product" => $product,
            "movements" => $movements,
            "stats" => $stats,
            "filters" => $request->all()
        ));
    }

    /**
     * Buscar reporte de movimientos de inventario
     */
    public function inventoryMovementsSearch(Request $request){
        $Company = Company::find($request['company']);

        // Obtener todos los productos con inventario
        $productsQuery = Product::with(['inventory', 'provider', 'marca'])
            ->whereHas('inventory');

        // Aplicar filtros
        if ($request->filled('provider_id')) {
            $productsQuery->where('provider_id', $request['provider_id']);
        }

        if ($request->filled('category')) {
            $productsQuery->where('type', $request['category']);
        }

        if ($request->filled('product_id')) {
            $productsQuery->where('id', $request['product_id']);
        }

        $products = $productsQuery->get();

        // Preparar fechas de filtrado
        $dateFrom = $request->filled('date_from') ? $request['date_from'] : null;
        $dateTo = $request->filled('date_to') ? $request['date_to'] : null;

        // Construir datos de movimientos para cada producto
        $movements = [];

        foreach ($products as $product) {
            // Obtener compras (entradas)
            $purchasesQuery = PurchaseDetail::where('product_id', $product->id)
                ->where('added_to_inventory', true)
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('purchases.company_id', $request['company']);

            if ($dateFrom) {
                $purchasesQuery->where('purchases.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $purchasesQuery->where('purchases.date', '<=', $dateTo);
            }

            $purchases = $purchasesQuery->select(
                'purchase_details.*',
                'purchases.date as purchase_date',
                'purchases.number as purchase_doc'
            )->get();

            // Obtener ventas (salidas)
            $salesQuery = Salesdetail::where('product_id', $product->id)
                ->join('sales', 'salesdetails.sale_id', '=', 'sales.id')
                ->leftJoin('dte', 'sales.id', '=', 'dte.sale_id')
                ->where('sales.company_id', $request['company'])
                ->where('sales.state', 1); // Solo ventas activas

            if ($dateFrom) {
                $salesQuery->where('sales.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $salesQuery->where('sales.date', '<=', $dateTo);
            }

            $sales = $salesQuery->select(
                'salesdetails.*',
                'sales.date as sale_date',
                DB::raw('COALESCE(dte.id_doc, sales.nu_doc) as sale_doc')
            )->get();

            // Calcular totales
            $totalPurchases = $purchases->sum(function($purchase) use ($product) {
                // Si el producto usa base_quantity, usarlo; si no, usar quantity
                $unitConversion = new \App\Services\UnitConversionService();
                return $unitConversion->calculateBaseQuantityNeeded(
                    $product->id,
                    $purchase->quantity,
                    $purchase->unit_code ?? '59'
                );
            });

            $totalSales = $sales->sum(function($sale) {
                return $sale->base_quantity_used ?? $sale->amountp;
            });

            $currentStock = $product->inventory ? $product->inventory->base_quantity : 0;
            $balance = $totalPurchases - $totalSales;

            // Solo incluir productos con movimientos o si se solicitan todos
            if ($totalPurchases > 0 || $totalSales > 0 || $request->filled('show_all')) {
                $movements[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'provider_name' => $product->provider ? $product->provider->razonsocial : 'N/A',
                    'category' => $product->type,
                    'current_stock' => $currentStock,
                    'total_purchases' => $totalPurchases,
                    'total_sales' => $totalSales,
                    'balance' => $balance,
                    'difference' => $currentStock - $balance, // Diferencia entre balance calculado y stock real
                    'is_negative' => $currentStock < 0,
                    'purchases_count' => $purchases->count(),
                    'sales_count' => $sales->count(),
                    'purchases' => $purchases,
                    'sales' => $sales
                ];
            }
        }

        // Ordenar por stock negativo primero, luego por diferencia
        if ($request->get('show_negative_first', false)) {
            usort($movements, function($a, $b) {
                if ($a['is_negative'] && !$b['is_negative']) return -1;
                if (!$a['is_negative'] && $b['is_negative']) return 1;
                return abs($b['difference']) <=> abs($a['difference']);
            });
        }

        // Calcular estadísticas
        $stats = [
            'total_products' => count($movements),
            'products_with_negative_stock' => collect($movements)->where('is_negative', true)->count(),
            'total_purchases_value' => collect($movements)->sum('total_purchases'),
            'total_sales_value' => collect($movements)->sum('total_sales'),
            'products_with_differences' => collect($movements)->where('difference', '!=', 0)->count()
        ];

        // Obtener categorías y productos para filtros
        $categories = Product::whereHas('inventory')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        $allProducts = Product::whereHas('inventory')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $providers = Provider::orderBy('razonsocial')
            ->get(['id', 'razonsocial']);

        $companies = Company::select('id','name')->orderBy('name')->get();

        return view('reports.inventory-movements', array(
            "heading" => $Company,
            "companies" => $companies,
            "movements" => $movements,
            "stats" => $stats,
            "categories" => $categories,
            "providers" => $providers,
            "allProducts" => $allProducts,
            "filters" => $request->all()
        ));
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
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        //
    }

    /**
     * Mostrar vista del reporte de cuentas por pagar
     *
     * @return \Illuminate\Http\Response
     */
    public function accountsPayable()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $providers = Provider::select('id', 'razonsocial')->orderBy('razonsocial')->get();
        
        // Selección por defecto: año y mes actuales
        $defaultYear = date('Y');
        $defaultMonth = date('m');
        
        return view('reports.accounts-payable', [
            'companies' => $companies,
            'providers' => $providers,
            'yearB' => $defaultYear,
            'period' => $defaultMonth
        ]);
    }

    /**
     * Buscar reporte de cuentas por pagar con filtros
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function accountsPayableSearch(Request $request)
    {
        $request->validate([
            'company' => 'required|exists:companies,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'period' => 'nullable|integer|min:1|max:12',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'provider_id' => 'nullable|exists:providers,id',
            'payment_status' => 'nullable|in:0,1,2',
        ]);

        $company = Company::findOrFail($request->company);

        // Construir la consulta base
        $query = Purchase::join('companies', 'companies.id', '=', 'purchases.company_id')
            ->join('providers', 'providers.id', '=', 'purchases.provider_id')
            ->leftJoin('typedocuments', 'typedocuments.id', '=', 'purchases.document_id')
            ->leftJoin('purchase_payments', function($join) {
                $join->on('purchase_payments.purchase_id', '=', 'purchases.id')
                     ->whereRaw('purchase_payments.id = (SELECT MAX(p2.id) FROM purchase_payments p2 WHERE p2.purchase_id = purchases.id)');
            })
            ->select(
                'purchases.id as purchase_id',
                'purchases.number',
                'purchases.date',
                'purchases.total',
                'purchases.paid_amount',
                'purchases.payment_status',
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit',
                'providers.ncr as provider_ncr',
                'companies.name as company_name',
                'typedocuments.description as document_type',
                DB::raw('COALESCE(purchase_payments.current, (purchases.total - COALESCE(purchases.paid_amount, 0))) as current_balance'),
                DB::raw('COALESCE(purchase_payments.date_pay, NULL) as last_payment_date'),
                DB::raw('(CASE
                    WHEN purchases.payment_status = 2 THEN "PAGADO"
                    WHEN purchases.payment_status = 1 THEN "PARCIAL"
                    WHEN (purchases.total - COALESCE(purchases.paid_amount, 0)) <= 0 THEN "PAGADO"
                    ELSE "PENDIENTE"
                END) AS payment_status_display'),
                DB::raw("DATE_FORMAT(purchases.date, '%d/%m/%Y') AS formatted_date")
            )
            ->where('purchases.company_id', $request->company);

        // Filtros opcionales
        if ($request->filled('year')) {
            $query->whereRaw('YEAR(purchases.date) = ?', [$request->year]);
        }

        if ($request->filled('period')) {
            $query->whereRaw('MONTH(purchases.date) = ?', [$request->period]);
        }

        if ($request->filled('date_from')) {
            $query->where('purchases.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('purchases.date', '<=', $request->date_to);
        }

        if ($request->filled('provider_id')) {
            $query->where('purchases.provider_id', $request->provider_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('purchases.payment_status', $request->payment_status);
        }

        $purchases = $query->orderBy('purchases.date', 'desc')
            ->orderBy('purchases.number', 'desc')
            ->get();

        // Calcular totales
        $totals = [
            'total_amount' => $purchases->sum('total'),
            'total_paid' => $purchases->sum('paid_amount'),
            'total_balance' => $purchases->sum('current_balance'),
            'pending_count' => $purchases->where('payment_status_display', 'PENDIENTE')->count(),
            'partial_count' => $purchases->where('payment_status_display', 'PARCIAL')->count(),
            'paid_count' => $purchases->where('payment_status_display', 'PAGADO')->count(),
        ];

        return view('reports.accounts-payable', [
            'company' => $company,
            'purchases' => $purchases,
            'totals' => $totals,
            'companies' => Company::select('id', 'name')->orderBy('name')->get(),
            'providers' => Provider::select('id', 'razonsocial')->orderBy('razonsocial')->get(),
            'filters' => $request->only(['year', 'period', 'date_from', 'date_to', 'provider_id', 'payment_status', 'company']),
            'yearB' => $request->year ?? date('Y'),
            'period' => $request->period ?? date('m')
        ]);
    }

    /**
     * Exportar reporte de cuentas por pagar a PDF
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function accountsPayablePdf(Request $request)
    {
        $request->validate([
            'company' => 'required|exists:companies,id',
            'year' => 'nullable|integer|min:2000|max:2100',
            'period' => 'nullable|integer|min:1|max:12',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'provider_id' => 'nullable|exists:providers,id',
            'payment_status' => 'nullable|in:0,1,2',
        ]);

        $company = Company::findOrFail($request->company);

        // Construir la consulta base (misma que en search)
        $query = Purchase::join('companies', 'companies.id', '=', 'purchases.company_id')
            ->join('providers', 'providers.id', '=', 'purchases.provider_id')
            ->leftJoin('typedocuments', 'typedocuments.id', '=', 'purchases.document_id')
            ->leftJoin('purchase_payments', function($join) {
                $join->on('purchase_payments.purchase_id', '=', 'purchases.id')
                     ->whereRaw('purchase_payments.id = (SELECT MAX(p2.id) FROM purchase_payments p2 WHERE p2.purchase_id = purchases.id)');
            })
            ->select(
                'purchases.id as purchase_id',
                'purchases.number',
                'purchases.date',
                'purchases.total',
                'purchases.paid_amount',
                'purchases.payment_status',
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit',
                'providers.ncr as provider_ncr',
                'companies.name as company_name',
                'typedocuments.description as document_type',
                DB::raw('COALESCE(purchase_payments.current, (purchases.total - COALESCE(purchases.paid_amount, 0))) as current_balance'),
                DB::raw('COALESCE(purchase_payments.date_pay, NULL) as last_payment_date'),
                DB::raw('(CASE
                    WHEN purchases.payment_status = 2 THEN "PAGADO"
                    WHEN purchases.payment_status = 1 THEN "PARCIAL"
                    WHEN (purchases.total - COALESCE(purchases.paid_amount, 0)) <= 0 THEN "PAGADO"
                    ELSE "PENDIENTE"
                END) AS payment_status_display'),
                DB::raw("DATE_FORMAT(purchases.date, '%d/%m/%Y') AS formatted_date")
            )
            ->where('purchases.company_id', $request->company);

        // Aplicar mismos filtros
        if ($request->filled('year')) {
            $query->whereRaw('YEAR(purchases.date) = ?', [$request->year]);
        }

        if ($request->filled('period')) {
            $query->whereRaw('MONTH(purchases.date) = ?', [$request->period]);
        }

        if ($request->filled('date_from')) {
            $query->where('purchases.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('purchases.date', '<=', $request->date_to);
        }

        if ($request->filled('provider_id')) {
            $query->where('purchases.provider_id', $request->provider_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('purchases.payment_status', $request->payment_status);
        }

        $purchases = $query->orderBy('purchases.date', 'desc')
            ->orderBy('purchases.number', 'desc')
            ->get();

        // Calcular totales
        $totals = [
            'total_amount' => $purchases->sum('total'),
            'total_paid' => $purchases->sum('paid_amount'),
            'total_balance' => $purchases->sum('current_balance'),
            'pending_count' => $purchases->where('payment_status_display', 'PENDIENTE')->count(),
            'partial_count' => $purchases->where('payment_status_display', 'PARCIAL')->count(),
            'paid_count' => $purchases->where('payment_status_display', 'PAGADO')->count(),
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);

        $pdf->loadView('reports.accounts-payable-pdf', [
            'company' => $company,
            'purchases' => $purchases,
            'totals' => $totals,
            'filters' => $request->only(['year', 'period', 'date_from', 'date_to', 'provider_id', 'payment_status']),
            'generated_at' => now()->format('d/m/Y H:i:s')
        ])->setPaper('letter', 'landscape');

        return $pdf->download('reporte-cuentas-por-pagar-' . now()->format('Y-m-d') . '.pdf');
    }
}
