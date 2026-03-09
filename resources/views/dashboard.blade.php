@extends('layouts/layoutMaster')

@section('title', 'Dashboard - Sistema Integral')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script>
    window.ventasUltimoAno = @json($ventasUltimoAno);
    window.ventasUltimoMes = @json($ventasUltimoMes);
    window.ventasUltimaSemana = @json($ventasUltimaSemana);
    window.ventasPorMes = @json($ventasPorMes);
    window.ventasPorDia = @json($ventasPorDia);
    window.ventasDiarias = @json($ventasDiarias);
    window.productosMasVendidos = @json($productosMasVendidos);
</script>
<script src="{{asset('assets/js/dashboards-crm.js')}}"></script>
@endsection

@section('content')

<!-- Alertas Importantes -->
@if($alertas['stockBajo'] > 0 || $alertas['proximosVencer'] > 0 || $alertas['citasPendientes'] > 0 || $alertas['ordenesPendientes'] > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible" role="alert">
            <h6 class="alert-heading mb-2"><i class="fa-solid fa-exclamation-triangle me-2"></i>Atención Requerida</h6>
            <div class="row">
                @if($alertas['stockBajo'] > 0)
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-box text-danger me-2"></i>
                        <span><strong>{{ $alertas['stockBajo'] }}</strong> productos con stock bajo</span>
                    </div>
                </div>
                @endif
                @if($alertas['proximosVencer'] > 0)
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-calendar-xmark text-warning me-2"></i>
                        <span><strong>{{ $alertas['proximosVencer'] }}</strong> productos próximos a vencer</span>
                    </div>
                </div>
                @endif
                @if($alertas['citasPendientes'] > 0)
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-calendar-check text-info me-2"></i>
                        <span><strong>{{ $alertas['citasPendientes'] }}</strong> citas pendientes hoy</span>
                    </div>
                </div>
                @endif
                @if($alertas['ordenesPendientes'] > 0)
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-flask text-primary me-2"></i>
                        <span><strong>{{ $alertas['ordenesPendientes'] }}</strong> órdenes de laboratorio pendientes</span>
                    </div>
                </div>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<!-- Título Principal -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Dashboard /</span> Vista General del Sistema
        </h4>
        <p class="text-muted">Bienvenido al sistema integral de Farmacia, Clínica y Laboratorio</p>
    </div>
</div>

<!-- Pestañas de Módulos -->
<div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
        <li class="nav-item">
            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-farmacia" aria-controls="navs-pills-farmacia" aria-selected="true">
                <i class="fa-solid fa-pills tf-icons me-2"></i>
                <span class="d-none d-sm-block">Farmacia</span>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-clinica" aria-controls="navs-pills-clinica" aria-selected="false">
                <i class="fa-solid fa-stethoscope tf-icons me-2"></i>
                <span class="d-none d-sm-block">Clínica</span>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-laboratorio" aria-controls="navs-pills-laboratorio" aria-selected="false">
                <i class="fa-solid fa-flask tf-icons me-2"></i>
                <span class="d-none d-sm-block">Laboratorio</span>
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-resumen" aria-controls="navs-pills-resumen" aria-selected="false">
                <i class="fa-solid fa-chart-line tf-icons me-2"></i>
                <span class="d-none d-sm-block">Resumen General</span>
            </button>
        </li>
    </ul>
    <div class="tab-content">
        
        <!-- ============================= TAB FARMACIA ============================= -->
        <div class="tab-pane fade show active" id="navs-pills-farmacia" role="tabpanel">
            <div class="row">
                <!-- Estadísticas de Farmacia -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-primary p-2 mb-2 rounded">
                                        <i class="fa-solid fa-shopping-cart fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">${{ number_format($totalVentasHoy, 2) }}</h5>
                                    <small class="text-muted">Ventas Hoy</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="fa-solid fa-dollar-sign fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-success p-2 mb-2 rounded">
                                        <i class="fa-solid fa-calendar-month fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">${{ number_format($totalVentasMes, 2) }}</h5>
                                    <small class="text-muted">Ventas del Mes</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="fa-solid fa-chart-line fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-warning p-2 mb-2 rounded">
                                        <i class="fa-solid fa-boxes-stacked fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $tproducts }}</h5>
                                    <small class="text-muted">Productos</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="fa-solid fa-box fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-info p-2 mb-2 rounded">
                                        <i class="fa-solid fa-users fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $tclientes }}</h5>
                                    <small class="text-muted">Clientes</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="fa-solid fa-user-group fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Ventas -->
                <div class="col-xl-8 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="card-title mb-0">Ventas Mensuales - Año {{ date('Y') }}</h5>
                            <span class="badge bg-label-{{ $crecimientoVentas >= 0 ? 'success' : 'danger' }}">
                                {{ $crecimientoVentas >= 0 ? '+' : '' }}{{ $crecimientoVentas }}%
                            </span>
                        </div>
                        <div class="card-body">
                            <div id="earningReportsTabsSales"></div>
                        </div>
                    </div>
                </div>

                <!-- Productos Más Vendidos -->
                <div class="col-xl-4 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top 5 Productos</h5>
                            <small class="text-muted">Más vendidos</small>
                        </div>
                        <div class="card-body">
                            <ul class="p-0 m-0">
                                @forelse($productosMasVendidos as $index => $producto)
                                <li class="pb-1 mb-3 d-flex align-items-center">
                                    <div class="badge bg-label-{{ ['primary', 'success', 'warning', 'info', 'secondary'][$index % 5] }} me-3 rounded p-2">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="d-flex w-100 align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">{{ Str::limit($producto->name, 25) }}</h6>
                                            <small class="text-muted">{{ $producto->cantidad_vendida }} unidades</small>
                                        </div>
                                    </div>
                                </li>
                                @empty
                                <li class="text-center text-muted py-4">
                                    <i class="fa-solid fa-box-open fa-3x mb-2 d-block"></i>
                                    <p class="mb-0">Sin datos disponibles</p>
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Alertas de Inventario -->
                @if(count($productosStockBajo) > 0 || count($productosProximosVencer) > 0)
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Alertas de Inventario</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(count($productosStockBajo) > 0)
                                <div class="col-md-6">
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading"><i class="fa-solid fa-circle-exclamation me-2"></i>Stock Bajo</h6>
                                        <ul class="mb-0">
                                            @foreach($productosStockBajo->take(5) as $inventory)
                                            <li>{{ $inventory->product->name ?? 'Producto' }} - <strong>{{ $inventory->quantity }}</strong> unidades (Mín: {{ $inventory->minimum_stock }})</li>
                                            @endforeach
                                        </ul>
                                        @if(count($productosStockBajo) > 5)
                                        <small class="text-muted">y {{ count($productosStockBajo) - 5 }} productos más...</small>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if(count($productosProximosVencer) > 0)
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading"><i class="fa-solid fa-clock me-2"></i>Próximos a Vencer</h6>
                                        <ul class="mb-0">
                                            @foreach($productosProximosVencer->take(5) as $inventory)
                                            <li>{{ $inventory->product->name ?? 'Producto' }} - Vence: <strong>{{ \Carbon\Carbon::parse($inventory->expiration_date)->format('d/m/Y') }}</strong></li>
                                            @endforeach
                                        </ul>
                                        @if(count($productosProximosVencer) > 5)
                                        <small class="text-muted">y {{ count($productosProximosVencer) - 5 }} productos más...</small>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- ============================= TAB CLÍNICA ============================= -->
        <div class="tab-pane fade" id="navs-pills-clinica" role="tabpanel">
            <div class="row">
                <!-- Estadísticas de Clínica -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-success p-2 mb-2 rounded">
                                        <i class="fa-solid fa-user-injured fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $tpacientes }}</h5>
                                    <small class="text-muted">Total Pacientes</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="fa-solid fa-hospital-user fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-primary p-2 mb-2 rounded">
                                        <i class="fa-solid fa-calendar-check fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $citasHoy }}</h5>
                                    <small class="text-muted">Citas Hoy</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="fa-solid fa-calendar-day fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                            @if($citasPendientesHoy > 0)
                            <div class="mt-2">
                                <span class="badge bg-label-warning">{{ $citasPendientesHoy }} pendientes</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-info p-2 mb-2 rounded">
                                        <i class="fa-solid fa-notes-medical fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $consultasHoy }}</h5>
                                    <small class="text-muted">Consultas Hoy</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="fa-solid fa-file-medical fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-warning p-2 mb-2 rounded">
                                        <i class="fa-solid fa-user-doctor fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $tmedicos }}</h5>
                                    <small class="text-muted">Médicos Activos</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="fa-solid fa-stethoscope fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Próximas Citas -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="card-title mb-0">Próximas Citas (24 horas)</h5>
                            <a href="/appointments" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                        </div>
                        <div class="card-body">
                            @if(count($proximasCitas) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Hora</th>
                                            <th>Paciente</th>
                                            <th>Médico</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($proximasCitas as $cita)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</td>
                                            <td>{{ $cita->patient->nombre_completo }}</td>
                                            <td>{{ $cita->doctor->nombre_completo }}</td>
                                            <td><span class="badge bg-label-info">{{ ucfirst($cita->tipo_cita) }}</span></td>
                                            <td><span class="badge bg-label-{{ $cita->estado == 'confirmada' ? 'success' : 'warning' }}">{{ ucfirst($cita->estado) }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center py-4">
                                <i class="fa-solid fa-calendar-xmark fa-3x text-muted mb-2 d-block"></i>
                                <p class="text-muted mb-0">No hay citas programadas para las próximas 24 horas</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Estadísticas del Mes -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Actividad del Mes</h5>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="fa-solid fa-user-plus"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="mb-0">{{ $pacientesNuevosMes }}</h4>
                                            <small class="text-muted">Pacientes Nuevos</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <span class="avatar-initial rounded bg-label-success">
                                                <i class="fa-solid fa-clipboard-list"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="mb-0">{{ $consultasMes }}</h4>
                                            <small class="text-muted">Consultas Realizadas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-label-{{ $crecimientoPacientes >= 0 ? 'success' : 'danger' }}">
                                    {{ $crecimientoPacientes >= 0 ? '+' : '' }}{{ $crecimientoPacientes }}% vs mes anterior
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Accesos Rápidos</h5>
                            <div class="d-grid gap-2">
                                <a href="/patients" class="btn btn-outline-primary">
                                    <i class="fa-solid fa-user-injured me-2"></i>Gestionar Pacientes
                                </a>
                                <a href="/appointments/create" class="btn btn-outline-success">
                                    <i class="fa-solid fa-calendar-plus me-2"></i>Nueva Cita
                                </a>
                                <a href="/consultations/create" class="btn btn-outline-info">
                                    <i class="fa-solid fa-notes-medical me-2"></i>Nueva Consulta
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================= TAB LABORATORIO ============================= -->
        <div class="tab-pane fade" id="navs-pills-laboratorio" role="tabpanel">
            <div class="row">
                <!-- Estadísticas de Laboratorio -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-warning p-2 mb-2 rounded">
                                        <i class="fa-solid fa-flask fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $ordenesLabHoy }}</h5>
                                    <small class="text-muted">Órdenes Hoy</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="fa-solid fa-vial fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-danger p-2 mb-2 rounded">
                                        <i class="fa-solid fa-hourglass-half fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $ordenesPendientes }}</h5>
                                    <small class="text-muted">Pendientes</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="fa-solid fa-clock fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-success p-2 mb-2 rounded">
                                        <i class="fa-solid fa-check-circle fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $ordenesCompletadasHoy }}</h5>
                                    <small class="text-muted">Completadas Hoy</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="fa-solid fa-check-double fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-label-info p-2 mb-2 rounded">
                                        <i class="fa-solid fa-calendar-month fa-lg"></i>
                                    </span>
                                    <h5 class="card-title mb-0 mt-2">{{ $ordenesMes }}</h5>
                                    <small class="text-muted">Órdenes del Mes</small>
                                </div>
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="fa-solid fa-chart-bar fa-2x"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Órdenes por Estado -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Órdenes por Estado</h5>
                        </div>
                        <div class="card-body">
                            @if(count($ordenesPorEstado) > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($ordenesPorEstado as $estado => $total)
                                <li class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-{{
                                                $estado == 'completada' ? 'success' :
                                                ($estado == 'en_proceso' ? 'info' :
                                                ($estado == 'pendiente' ? 'warning' : 'secondary'))
                                            }} me-3 rounded p-2">
                                                <i class="fa-solid fa-circle"></i>
                                            </span>
                                            <span>{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
                                        </div>
                                        <strong>{{ $total }}</strong>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted text-center mb-0">Sin datos disponibles</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Exámenes Más Solicitados -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Exámenes Más Solicitados</h5>
                        </div>
                        <div class="card-body">
                            @if(count($examenesMasSolicitados) > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($examenesMasSolicitados as $index => $examen)
                                <li class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-primary me-3 rounded p-2">{{ $index + 1 }}</span>
                                            <span>{{ Str::limit($examen->nombre, 30) }}</span>
                                        </div>
                                        <strong>{{ $examen->cantidad }}</strong>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted text-center mb-0">Sin datos disponibles</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Accesos Rápidos -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Accesos Rápidos - Laboratorio</h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="/lab-orders/create" class="btn btn-outline-primary w-100">
                                        <i class="fa-solid fa-plus me-2"></i>Nueva Orden
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="/lab-orders?estado=pendiente" class="btn btn-outline-warning w-100">
                                        <i class="fa-solid fa-hourglass me-2"></i>Pendientes
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="/lab-orders?estado=en_proceso" class="btn btn-outline-info w-100">
                                        <i class="fa-solid fa-spinner me-2"></i>En Proceso
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="/lab-orders?estado=completada" class="btn btn-outline-success w-100">
                                        <i class="fa-solid fa-check me-2"></i>Completadas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================= TAB RESUMEN GENERAL ============================= -->
        <div class="tab-pane fade" id="navs-pills-resumen" role="tabpanel">
            <div class="row">
                <div class="col-12 mb-4">
                    <h5 class="mb-3">Resumen Ejecutivo del Sistema</h5>
                </div>

                <!-- Módulo Farmacia -->
                <div class="col-md-4 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-label-primary">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-pills me-2"></i>Farmacia
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Ventas Hoy:</span>
                                <strong class="text-primary">${{ number_format($estadisticasGenerales['farmacia']['ventasHoy'], 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Ventas del Mes:</span>
                                <strong>${{ number_format($estadisticasGenerales['farmacia']['ventasMes'], 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total Productos:</span>
                                <strong>{{ $estadisticasGenerales['farmacia']['productos'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total Clientes:</span>
                                <strong>{{ $estadisticasGenerales['farmacia']['clientes'] }}</strong>
                            </div>
                            <hr>
                            <div class="text-center">
                                <span class="badge bg-label-{{ $estadisticasGenerales['farmacia']['crecimiento'] >= 0 ? 'success' : 'danger' }} mb-2">
                                    {{ $estadisticasGenerales['farmacia']['crecimiento'] >= 0 ? '↗' : '↘' }}
                                    {{ abs($estadisticasGenerales['farmacia']['crecimiento']) }}% vs mes anterior
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo Clínica -->
                <div class="col-md-4 mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-label-success">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-stethoscope me-2"></i>Clínica
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Pacientes:</span>
                                <strong class="text-success">{{ $estadisticasGenerales['clinica']['pacientes'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Médicos Activos:</span>
                                <strong>{{ $estadisticasGenerales['clinica']['medicos'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Citas Hoy:</span>
                                <strong>{{ $estadisticasGenerales['clinica']['citasHoy'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Consultas del Mes:</span>
                                <strong>{{ $estadisticasGenerales['clinica']['consultasMes'] }}</strong>
                            </div>
                            <hr>
                            <div class="text-center">
                                <span class="badge bg-label-{{ $estadisticasGenerales['clinica']['crecimiento'] >= 0 ? 'success' : 'danger' }} mb-2">
                                    {{ $estadisticasGenerales['clinica']['crecimiento'] >= 0 ? '↗' : '↘' }}
                                    {{ abs($estadisticasGenerales['clinica']['crecimiento']) }}% pacientes nuevos
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo Laboratorio -->
                <div class="col-md-4 mb-4">
                    <div class="card border-warning">
                        <div class="card-header bg-label-warning">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-flask me-2"></i>Laboratorio
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Órdenes Hoy:</span>
                                <strong class="text-warning">{{ $estadisticasGenerales['laboratorio']['ordenesHoy'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Pendientes:</span>
                                <strong>{{ $estadisticasGenerales['laboratorio']['pendientes'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Completadas Hoy:</span>
                                <strong>{{ $estadisticasGenerales['laboratorio']['completadasHoy'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Órdenes del Mes:</span>
                                <strong>{{ $estadisticasGenerales['laboratorio']['ordenesMes'] }}</strong>
                            </div>
                            <hr>
                            <div class="text-center">
                                @if($estadisticasGenerales['laboratorio']['pendientes'] > 0)
                                <span class="badge bg-label-danger mb-2">
                                    <i class="fa-solid fa-exclamation me-1"></i>
                                    {{ $estadisticasGenerales['laboratorio']['pendientes'] }} pendientes
                                </span>
                                @else
                                <span class="badge bg-label-success mb-2">
                                    <i class="fa-solid fa-check me-1"></i>
                                    Todo al día
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico Comparativo -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actividad Integrada del Sistema</h5>
                            <small class="text-muted">Comparativa de los tres módulos</small>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="avatar avatar-lg mb-2 mx-auto">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <i class="fa-solid fa-pills fa-2x"></i>
                                        </span>
                                    </div>
                                    <h4 class="mb-0">{{ $tsales }}</h4>
                                    <p class="text-muted">Transacciones Farmacia</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="avatar avatar-lg mb-2 mx-auto">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            <i class="fa-solid fa-notes-medical fa-2x"></i>
                                        </span>
                                    </div>
                                    <h4 class="mb-0">{{ $consultasMes }}</h4>
                                    <p class="text-muted">Consultas del Mes</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="avatar avatar-lg mb-2 mx-auto">
                                        <span class="avatar-initial rounded-circle bg-label-warning">
                                            <i class="fa-solid fa-vial fa-2x"></i>
                                        </span>
                                    </div>
                                    <h4 class="mb-0">{{ $ordenesMes }}</h4>
                                    <p class="text-muted">Exámenes del Mes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
