<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\MedicalConsultation;
use App\Models\LabOrder;
use App\Models\Inventory;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard Central - Hub principal del sistema
     */
    public function central()
    {
        $user = auth()->user();

        // Obtener información de la empresa del usuario
        $company = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->join('addresses', 'companies.address_id', '=', 'addresses.id')
            ->leftJoin('countries', 'addresses.country_id', '=', 'countries.id')
            ->leftJoin('departments', 'addresses.department_id', '=', 'departments.id')
            ->leftJoin('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
            ->leftJoin('phones', 'companies.phone_id', '=', 'phones.id')
            ->leftJoin('economicactivities', 'companies.economicactivity_id', '=', 'economicactivities.id')
            ->where('permission_company.user_id', $user->id)
            ->where('permission_company.state', 1)
            ->select(
                'companies.*',
                'addresses.reference as address',
                'countries.name as pais',
                'departments.name as departamento',
                'municipalities.name as municipio',
                'phones.phone',
                'economicactivities.name as actividad_economica'
            )
            ->first();

        // Si no hay empresa asignada, obtener la primera disponible
        if (!$company) {
            $company = Company::join('addresses', 'companies.address_id', '=', 'addresses.id')
                ->leftJoin('countries', 'addresses.country_id', '=', 'countries.id')
                ->leftJoin('departments', 'addresses.department_id', '=', 'departments.id')
                ->leftJoin('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
                ->leftJoin('phones', 'companies.phone_id', '=', 'phones.id')
                ->leftJoin('economicactivities', 'companies.economicactivity_id', '=', 'economicactivities.id')
                ->select(
                    'companies.*',
                    'addresses.reference as address',
                    'countries.name as pais',
                    'departments.name as departamento',
                    'municipalities.name as municipio',
                    'phones.phone',
                    'economicactivities.name as actividad_economica'
                )
                ->first();
        }

        // Estadísticas básicas de farmacia
        $tclientes = Client::count();
        $tproducts = Product::count();
        $tproviders = Provider::count();

        // Estadísticas de ventas
        $totalVentasHoy = $this->getTotalVentasHoy();
        $totalVentasSemana = $this->getTotalVentasSemana();
        $totalVentasMes = $this->getTotalVentasMes();
        $totalVentas = $this->getTotalVentas();
        $ventasDiarias = $this->getVentasDiarias();
        $cantidadVentasHoy = Sale::whereDate('date', Carbon::today())
            ->where('state', 1)
            ->count();
        $cantidadVentasMes = Sale::whereMonth('date', Carbon::now()->month)
            ->where('state', 1)
            ->count();

        // Crecimiento de ventas
        $crecimientoVentas = $this->calcularCrecimientoVentas();

        // Productos más vendidos
        $productosMasVendidos = $this->getProductosMasVendidos();

        // Estadísticas de clínica
        $tpacientes = Patient::count();
        $citasHoy = Appointment::whereDate('fecha_hora', Carbon::today())->count();
        $citasPendientesHoy = Appointment::whereDate('fecha_hora', Carbon::today())
            ->whereIn('estado', ['programada', 'confirmada'])
            ->count();
        $consultasHoy = MedicalConsultation::whereDate('fecha_hora', Carbon::today())->count();

        // Estadísticas de laboratorio
        $ordenesLabHoy = LabOrder::whereDate('fecha_orden', Carbon::today())->count();
        $ordenesPendientes = LabOrder::whereIn('estado', ['pendiente', 'muestra_tomada', 'en_proceso'])->count();

        // Órdenes de laboratorio pendientes de facturar (cualquier estado excepto cancelada, excluyendo ya facturadas)
        $productLabId = Product::where('code', 'LAB')->value('id');

        // Obtener IDs de órdenes que ya fueron facturadas
        $ordenesFacturadasIds = [];

        if ($productLabId) {
            $ordenesFacturadasIds = Sale::whereHas('details', function($query) use ($productLabId) {
                    $query->where('product_id', $productLabId);
                })
                ->whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
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
            $ordenesFacturadasIds = Sale::whereNotNull('acuenta')
                ->where('acuenta', 'like', '%LAB_ORDER_ID:%')
                ->get()
                ->map(function($sale) {
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

        $ordenesLabPorFacturar = LabOrder::where('estado', '!=', 'cancelada')
            ->whereNotIn('id', $ordenesFacturadasIds)
            ->count();

        // Alertas
        $productosStockBajo = $this->getProductosStockBajo();
        $productosProximosVencer = $this->getProductosProximosVencer();

        $alertas = [
            'stockBajo' => count($productosStockBajo),
            'proximosVencer' => count($productosProximosVencer),
            'citasPendientes' => $citasPendientesHoy,
            'ordenesPendientes' => $ordenesPendientes,
            'ordenesLabPorFacturar' => $ordenesLabPorFacturar,
        ];

        // Obtener lista de órdenes pendientes de facturar (detalles)
        $ordenesLabPorFacturarList = LabOrder::where('estado', '!=', 'cancelada')
            ->whereNotIn('id', $ordenesFacturadasIds)
            ->with(['patient', 'doctor'])
            ->orderBy('fecha_orden', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard-central', compact(
            'company',
            'tclientes', 'tproducts', 'tproviders',
            'totalVentasHoy', 'totalVentasSemana', 'totalVentasMes', 'totalVentas',
            'cantidadVentasHoy', 'cantidadVentasMes',
            'crecimientoVentas', 'ventasDiarias', 'productosMasVendidos',
            'tpacientes', 'citasHoy', 'citasPendientesHoy', 'consultasHoy',
            'ordenesLabHoy', 'ordenesPendientes', 'ordenesLabPorFacturar', 'ordenesLabPorFacturarList',
            'alertas', 'productosStockBajo'
        ));
    }

    /**
     * Dashboard Ejecutivo - Información crítica y detallada para la empresa
     * Requiere permiso: dashboard.analytics
     */
    public function analytics()
    {
        $user = auth()->user();

        // Obtener información de la empresa del usuario
        $company = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->join('addresses', 'companies.address_id', '=', 'addresses.id')
            ->leftJoin('countries', 'addresses.country_id', '=', 'countries.id')
            ->leftJoin('departments', 'addresses.department_id', '=', 'departments.id')
            ->leftJoin('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
            ->leftJoin('phones', 'companies.phone_id', '=', 'phones.id')
            ->leftJoin('economicactivities', 'companies.economicactivity_id', '=', 'economicactivities.id')
            ->where('permission_company.user_id', $user->id)
            ->where('permission_company.state', 1)
            ->select(
                'companies.*',
                'addresses.reference as address',
                'countries.name as pais',
                'departments.name as departamento',
                'municipalities.name as municipio',
                'phones.phone',
                'economicactivities.name as actividad_economica'
            )
            ->first();

        if (!$company) {
            $company = Company::join('addresses', 'companies.address_id', '=', 'addresses.id')
                ->leftJoin('countries', 'addresses.country_id', '=', 'countries.id')
                ->leftJoin('departments', 'addresses.department_id', '=', 'departments.id')
                ->leftJoin('municipalities', 'addresses.municipality_id', '=', 'municipalities.id')
                ->select('companies.*', 'addresses.reference as address')
                ->first();
        }

        // ========== INFORMACIÓN CRÍTICA Y DETALLADA ==========
        
        // Ventas críticas
        $totalVentasHoy = $this->getTotalVentasHoy();
        $totalVentasAyer = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereDate('sales.date', Carbon::yesterday())
            ->sum('salesdetails.pricesale') ?: 0;
        
        $crecimientoVentas = $totalVentasAyer > 0 
            ? (($totalVentasHoy - $totalVentasAyer) / $totalVentasAyer) * 100 
            : 0;

        // Análisis de rentabilidad y margen
        $ventasMes = $this->getTotalVentasMes();
        $comprasMes = DB::table('purchases')
            ->join('purchase_details', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->whereMonth('purchases.date', Carbon::now()->month)
            ->whereYear('purchases.date', Carbon::now()->year)
            ->sum('purchase_details.total_amount') ?: 0;
        
        $margenBruto = $ventasMes - $comprasMes;
        $margenPorcentaje = $ventasMes > 0 ? ($margenBruto / $ventasMes) * 100 : 0;

        // Productos críticos (bajo stock, próximos a vencer, más vendidos)
        $productosStockBajo = $this->getProductosStockBajo();
        $productosProximosVencer = $this->getProductosProximosVencer();
        $productosMasVendidos = $this->getProductosMasVendidos();

        // Análisis de clientes críticos
        $clientesMasCompran = DB::table('sales')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->whereYear('sales.date', Carbon::now()->year)
            ->select(
                'clients.id',
                DB::raw("CASE 
                    WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                    WHEN clients.tpersona = 'J' THEN COALESCE(clients.name_contribuyente, clients.comercial_name, '')
                    ELSE CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                END AS name"),
                DB::raw('SUM(salesdetails.pricesale) as total')
            )
            ->groupBy('clients.id', 'name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // Cuentas por cobrar críticas (créditos pendientes)
        // Los créditos están relacionados con ventas, y las ventas con clientes
        $creditosPendientes = DB::select(DB::raw("
            SELECT 
                CASE 
                    WHEN c.tpersona = 'N' THEN CONCAT_WS(' ', c.firstname, c.secondname, c.firstlastname, c.secondlastname)
                    WHEN c.tpersona = 'J' THEN COALESCE(c.name_contribuyente, c.comercial_name, '')
                    ELSE CONCAT_WS(' ', c.firstname, c.secondname, c.firstlastname, c.secondlastname)
                END AS name,
                COALESCE(cr.current, s.totalamount) as amount,
                s.date,
                s.id as sale_id
            FROM sales s
            INNER JOIN clients c ON s.client_id = c.id
            LEFT JOIN credits cr ON cr.sale_id = s.id 
                AND cr.id = (SELECT MAX(c2.id) FROM credits c2 WHERE c2.sale_id = s.id)
            WHERE s.waytopay = 2
                AND s.typesale = 1
                AND (s.state_credit != 1 OR s.state_credit IS NULL)
                AND COALESCE(cr.current, s.totalamount) > 0
            ORDER BY COALESCE(cr.current, s.totalamount) DESC
            LIMIT 10
        "));
        
        $totalCreditosPendientes = DB::select(DB::raw("
            SELECT SUM(COALESCE(cr.current, s.totalamount)) as total
            FROM sales s
            LEFT JOIN credits cr ON cr.sale_id = s.id 
                AND cr.id = (SELECT MAX(c2.id) FROM credits c2 WHERE c2.sale_id = s.id)
            WHERE s.waytopay = 2
                AND s.typesale = 1
                AND (s.state_credit != 1 OR s.state_credit IS NULL)
                AND COALESCE(cr.current, s.totalamount) > 0
        "));
        
        $totalCreditosPendientes = $totalCreditosPendientes[0]->total ?? 0;

        // Métricas de clínica y laboratorio críticas
        $citasPendientes = Appointment::whereIn('estado', ['programada', 'confirmada'])
            ->whereDate('fecha_hora', '>=', Carbon::now())
            ->count();
        
        $ordenesLabPendientes = LabOrder::whereIn('estado', ['pendiente', 'muestra_tomada', 'en_proceso'])->count();
        
        $consultasPendientes = MedicalConsultation::where('estado', 'pendiente')->count();

        // Alertas críticas combinadas
        $alertasCriticas = [];
        
        if ($productosStockBajo->count() > 0) {
            $alertasCriticas[] = [
                'tipo' => 'stock_bajo',
                'mensaje' => "{$productosStockBajo->count()} productos con stock crítico",
                'severidad' => 'danger',
                'icono' => 'fa-exclamation-triangle'
            ];
        }
        
        if ($productosProximosVencer->count() > 0) {
            $alertasCriticas[] = [
                'tipo' => 'vencimiento',
                'mensaje' => "{$productosProximosVencer->count()} productos próximos a vencer",
                'severidad' => 'warning',
                'icono' => 'fa-calendar-times'
            ];
        }
        
        if ($totalCreditosPendientes > 10000) {
            $alertasCriticas[] = [
                'tipo' => 'creditos',
                'mensaje' => "Créditos pendientes: $" . number_format($totalCreditosPendientes, 2),
                'severidad' => 'warning',
                'icono' => 'fa-money-bill-wave'
            ];
        }
        
        if ($citasPendientes > 20) {
            $alertasCriticas[] = [
                'tipo' => 'citas',
                'mensaje' => "{$citasPendientes} citas médicas pendientes",
                'severidad' => 'info',
                'icono' => 'fa-calendar-check'
            ];
        }

        // Gráficos y tendencias
        $ventasUltimos12Meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $total = DB::table('sales')
                ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
                ->where('sales.state', 1)
                ->whereYear('sales.date', $mes->year)
                ->whereMonth('sales.date', $mes->month)
                ->sum('salesdetails.pricesale') ?: 0;
            
            $ventasUltimos12Meses[] = [
                'mes' => $mes->format('M Y'),
                'total' => $total
            ];
        }

        return view('dashboard-analytics', compact(
            'company',
            'totalVentasHoy',
            'totalVentasAyer',
            'crecimientoVentas',
            'ventasMes',
            'comprasMes',
            'margenBruto',
            'margenPorcentaje',
            'productosStockBajo',
            'productosProximosVencer',
            'productosMasVendidos',
            'clientesMasCompran',
            'creditosPendientes',
            'totalCreditosPendientes',
            'citasPendientes',
            'ordenesLabPendientes',
            'consultasPendientes',
            'alertasCriticas',
            'ventasUltimos12Meses'
        ));
    }

    /**
     * Dashboard simplificado para tienda:
     * ventas, compras (gastos) y utilidad.
     */
    public function storeDashboard()
    {
        // Totales de ventas
        $totalVentasHoy = $this->getTotalVentasHoy();
        $totalVentasSemana = $this->getTotalVentasSemana();
        $totalVentasMes = $this->getTotalVentasMes();
        $totalVentas = $this->getTotalVentas();

        // Compras del mes (se usan como gastos principales de la tienda)
        $comprasMes = DB::table('purchases')
            ->join('purchase_details', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->whereMonth('purchases.date', Carbon::now()->month)
            ->whereYear('purchases.date', Carbon::now()->year)
            ->sum('purchase_details.total_amount') ?: 0;

        // Utilidad aproximada del mes
        $utilidadMes = $totalVentasMes - $comprasMes;

        // Información básica de clientes / productos
        $tclientes = Client::count();
        $tproducts = Product::count();
        $tproviders = Provider::count();

        // Productos más vendidos y alertas de inventario
        $productosMasVendidos = $this->getProductosMasVendidos();
        $productosStockBajo = $this->getProductosStockBajo();
        $productosProximosVencer = $this->getProductosProximosVencer();

        // Ventas de los últimos 7 días para gráfico simple
        $ventasDiarias = $this->getVentasDiarias();

        return view('dashboard-tienda', compact(
            'totalVentasHoy',
            'totalVentasSemana',
            'totalVentasMes',
            'totalVentas',
            'comprasMes',
            'utilidadMes',
            'tclientes',
            'tproducts',
            'tproviders',
            'productosMasVendidos',
            'productosStockBajo',
            'productosProximosVencer',
            'ventasDiarias'
        ));
    }

    public function home()
    {
        $user = auth()->user();

        // ========== ESTADÍSTICAS DE FARMACIA ==========
        $tclientes = Client::count();
        $tproviders = Provider::count();
        $tproducts = Product::count();
        $tsales = Sale::count();

        // Datos para gráficos de ventas
        $ventasUltimoAno = $this->getVentasUltimoAno();
        $ventasUltimoMes = $this->getVentasUltimoMes();
        $ventasUltimaSemana = $this->getVentasUltimaSemana();
        $productosMasVendidos = $this->getProductosMasVendidos();
        $ventasPorMes = $this->getVentasPorMes();
        $ventasPorDia = $this->getVentasPorDia();
        $ventasDiarias = $this->getVentasDiarias();

        // Totales de ventas
        $totalVentas = $this->getTotalVentas();
        $totalVentasMes = $this->getTotalVentasMes();
        $totalVentasSemana = $this->getTotalVentasSemana();
        $totalVentasHoy = $this->getTotalVentasHoy();

        // Crecimiento
        $crecimientoVentas = $this->calcularCrecimientoVentas();
        $crecimientoProductos = $this->calcularCrecimientoProductos();

        // Inventario y alertas
        $productosStockBajo = $this->getProductosStockBajo();
        $productosProximosVencer = $this->getProductosProximosVencer();

        // ========== ESTADÍSTICAS DE CLÍNICA ==========
        $tpacientes = Patient::count();
        $tmedicos = Doctor::where('estado', 'activo')->count();
        $citasHoy = $this->getCitasHoy();
        $citasPendientesHoy = $this->getCitasPendientesHoy();
        $consultasHoy = MedicalConsultation::whereDate('fecha_hora', Carbon::today())->count();
        $consultasMes = MedicalConsultation::whereMonth('fecha_hora', Carbon::now()->month)->count();

        // Próximas citas
        $proximasCitas = $this->getProximasCitas();

        // Estadísticas de pacientes
        $pacientesNuevosMes = Patient::whereMonth('created_at', Carbon::now()->month)->count();
        $crecimientoPacientes = $this->calcularCrecimientoPacientes();

        // ========== ESTADÍSTICAS DE LABORATORIO ==========
        $ordenesLabHoy = LabOrder::whereDate('fecha_orden', Carbon::today())->count();
        $ordenesPendientes = LabOrder::whereIn('estado', ['pendiente', 'muestra_tomada', 'en_proceso'])->count();
        $ordenesCompletadasHoy = LabOrder::whereDate('fecha_entrega_real', Carbon::today())->count();
        $ordenesMes = LabOrder::whereMonth('fecha_orden', Carbon::now()->month)->count();

        // Órdenes por estado
        $ordenesPorEstado = $this->getOrdenesPorEstado();

        // Exámenes más solicitados
        $examenesMasSolicitados = $this->getExamenesMasSolicitados();

        // ========== RESUMEN INTEGRADO ==========
        $estadisticasGenerales = [
            'farmacia' => [
                'clientes' => $tclientes,
                'proveedores' => $tproviders,
                'productos' => $tproducts,
                'ventas' => $tsales,
                'ventasHoy' => $totalVentasHoy,
                'ventasMes' => $totalVentasMes,
                'crecimiento' => $crecimientoVentas,
            ],
            'clinica' => [
                'pacientes' => $tpacientes,
                'medicos' => $tmedicos,
                'citasHoy' => $citasHoy,
                'consultasHoy' => $consultasHoy,
                'consultasMes' => $consultasMes,
                'pacientesNuevosMes' => $pacientesNuevosMes,
                'crecimiento' => $crecimientoPacientes,
            ],
            'laboratorio' => [
                'ordenesHoy' => $ordenesLabHoy,
                'pendientes' => $ordenesPendientes,
                'completadasHoy' => $ordenesCompletadasHoy,
                'ordenesMes' => $ordenesMes,
            ]
        ];

        // Alertas importantes
        $alertas = [
            'stockBajo' => $productosStockBajo->count(),
            'proximosVencer' => $productosProximosVencer->count(),
            'citasPendientes' => $citasPendientesHoy,
            'ordenesPendientes' => $ordenesPendientes,
        ];

        return view('dashboard', compact(
            // Farmacia
            'tclientes', 'tproviders', 'tproducts', 'tsales',
            'ventasUltimoAno', 'ventasUltimoMes', 'ventasUltimaSemana',
            'productosMasVendidos', 'ventasPorMes', 'ventasPorDia', 'ventasDiarias',
            'totalVentas', 'totalVentasMes', 'totalVentasSemana', 'totalVentasHoy',
            'crecimientoVentas', 'crecimientoProductos',
            'productosStockBajo', 'productosProximosVencer',

            // Clínica
            'tpacientes', 'tmedicos', 'citasHoy', 'citasPendientesHoy',
            'consultasHoy', 'consultasMes', 'proximasCitas',
            'pacientesNuevosMes', 'crecimientoPacientes',

            // Laboratorio
            'ordenesLabHoy', 'ordenesPendientes', 'ordenesCompletadasHoy',
            'ordenesMes', 'ordenesPorEstado', 'examenesMasSolicitados',

            // General
            'estadisticasGenerales', 'alertas'
        ));
    }

    private function getTotalVentas()
    {
        $total = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getTotalVentasMes()
    {
        $total = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getTotalVentasSemana()
    {
        $total = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereBetween('sales.date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getVentasUltimoAno()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereYear('sales.date', Carbon::now()->year)
            ->selectRaw('MONTH(sales.date) as mes, SUM(salesdetails.pricesale) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $resultado = [];
        foreach ($meses as $numero => $nombre) {
            $ventaMes = $ventas->where('mes', $numero)->first();
            $resultado[$nombre] = $ventaMes ? (float)$ventaMes->total : 0;
        }
        return array_values($resultado); // Para gráficos tipo array
    }

    private function getVentasUltimoMes()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $diasEnMes = Carbon::now()->daysInMonth;
        $resultado = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $ventaDia = $ventas->where('dia', $i)->first();
            $resultado[] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function getVentasUltimaSemana()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereBetween('sales.date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $resultado = [];
        for ($i = 0; $i < 7; $i++) {
            $dia = Carbon::now()->startOfWeek()->copy()->addDays($i)->day;
            $ventaDia = $ventas->where('dia', $dia)->first();
            $resultado[] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function getProductosMasVendidos()
    {
        $productos = Salesdetail::join('products', 'salesdetails.product_id', '=', 'products.id')
            ->join('sales', 'salesdetails.sale_id', '=', 'sales.id')
            ->where('sales.state', 1) // Solo ventas activas
            ->selectRaw('products.name, SUM(salesdetails.amountp) as cantidad_vendida')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('cantidad_vendida')
            ->limit(5)
            ->get();

        // Si no hay productos vendidos, retornar colección vacía
        return $productos->isEmpty() ? collect() : $productos;
    }

    private function getVentasPorMes()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereYear('sales.date', Carbon::now()->year)
            ->selectRaw('MONTH(sales.date) as mes, SUM(salesdetails.pricesale) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $resultado = [];
        foreach ($meses as $numero => $nombre) {
            $ventaMes = $ventas->where('mes', $numero)->first();
            $resultado[$nombre] = $ventaMes ? (float)$ventaMes->total : 0;
        }
        return $resultado;
    }

    private function getVentasPorDia()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $diasEnMes = Carbon::now()->daysInMonth;
        $resultado = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $ventaDia = $ventas->where('dia', $i)->first();
            $resultado[$i] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function calcularCrecimientoVentas()
    {
        $ventasMesActual = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->sum('salesdetails.pricesale') ?: 0;

        $ventasMesAnterior = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->subMonth()->month)
            ->sum('salesdetails.pricesale') ?: 0;

        if ($ventasMesAnterior > 0) {
            return round((($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1);
        }
        return 0;
    }

    private function calcularCrecimientoProductos()
    {
        $productosMesActual = Product::whereMonth('created_at', Carbon::now()->month)->count();
        $productosMesAnterior = Product::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

        if ($productosMesAnterior > 0) {
            return round((($productosMesActual - $productosMesAnterior) / $productosMesAnterior) * 100, 1);
        }

        return 0;
    }

    /**
     * Obtener ventas diarias de los últimos 7 días con información detallada
     */
    private function getVentasDiarias()
    {
        $ventas = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereBetween('sales.date', [Carbon::now()->subDays(6), Carbon::now()])
            ->selectRaw('DATE(sales.date) as fecha, SUM(salesdetails.pricesale) as total, COUNT(DISTINCT sales.id) as cantidad_ventas')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $resultado = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i)->format('Y-m-d');
            $ventaDia = $ventas->where('fecha', $fecha)->first();

            $resultado[] = [
                'fecha' => $fecha,
                'fecha_formateada' => Carbon::parse($fecha)->format('d/m'),
                'dia_semana' => Carbon::parse($fecha)->locale('es')->dayName,
                'total' => $ventaDia ? (float)$ventaDia->total : 0,
                'cantidad_ventas' => $ventaDia ? $ventaDia->cantidad_ventas : 0,
                'es_hoy' => $fecha === Carbon::now()->format('Y-m-d')
            ];
        }

        return $resultado;
    }

    /**
     * Obtener total de ventas del día actual
     */
    private function getTotalVentasHoy()
    {
        $total = DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereDate('sales.date', Carbon::now())
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    // ==================== MÉTODOS DE CLÍNICA ====================

    /**
     * Obtener citas del día actual
     */
    private function getCitasHoy()
    {
        return Appointment::whereDate('fecha_hora', Carbon::today())->count();
    }

    /**
     * Obtener citas pendientes del día
     */
    private function getCitasPendientesHoy()
    {
        return Appointment::whereDate('fecha_hora', Carbon::today())
            ->whereIn('estado', ['programada', 'confirmada'])
            ->count();
    }

    /**
     * Obtener próximas citas (próximas 24 horas)
     */
    private function getProximasCitas()
    {
        return Appointment::with(['patient', 'doctor'])
            ->where('fecha_hora', '>=', Carbon::now())
            ->where('fecha_hora', '<=', Carbon::now()->addHours(24))
            ->whereIn('estado', ['programada', 'confirmada'])
            ->orderBy('fecha_hora')
            ->limit(5)
            ->get();
    }

    /**
     * Calcular crecimiento de pacientes
     */
    private function calcularCrecimientoPacientes()
    {
        $pacientesMesActual = Patient::whereMonth('created_at', Carbon::now()->month)->count();
        $pacientesMesAnterior = Patient::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

        if ($pacientesMesAnterior > 0) {
            return round((($pacientesMesActual - $pacientesMesAnterior) / $pacientesMesAnterior) * 100, 1);
        }
        return 0;
    }

    // ==================== MÉTODOS DE LABORATORIO ====================

    /**
     * Obtener órdenes de laboratorio por estado
     */
    private function getOrdenesPorEstado()
    {
        return LabOrder::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();
    }

    /**
     * Obtener exámenes más solicitados
     */
    private function getExamenesMasSolicitados()
    {
        return DB::table('lab_order_exams')
            ->join('lab_exams', 'lab_order_exams.exam_id', '=', 'lab_exams.id')
            ->select('lab_exams.nombre', DB::raw('count(*) as cantidad'))
            ->groupBy('lab_exams.id', 'lab_exams.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();
    }

    // ==================== MÉTODOS DE INVENTARIO/FARMACIA ====================

    /**
     * Obtener productos con stock bajo
     */
    private function getProductosStockBajo()
    {
        return Inventory::with('product')
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->where('quantity', '>', 0)
            ->where('active', true)
            ->orderBy('quantity')
            ->limit(10)
            ->get();
    }

    /**
     * Obtener productos próximos a vencer (próximos 30 días)
     */
    private function getProductosProximosVencer()
    {
        $fechaLimite = Carbon::now()->addDays(30);

        return Inventory::with('product')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', $fechaLimite)
            ->where('expiration_date', '>=', Carbon::now())
            ->where('active', true)
            ->orderBy('expiration_date')
            ->limit(10)
            ->get();
    }
}
