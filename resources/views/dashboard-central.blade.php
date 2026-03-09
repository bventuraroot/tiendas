@extends('layouts/layoutMaster')

@section('title', 'Centro de Control - Sistema Integral')

@section('vendor-style')
<style>
.module-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
    height: 100%;
    min-height: 280px;
}
.module-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.module-card.farmacia:hover {
    border-color: #696cff;
}
.module-card.clinica:hover {
    border-color: #71dd37;
}
.module-card.laboratorio:hover {
    border-color: #ffab00;
}
.module-card.facturacion:hover {
    border-color: #03c3ec;
}
.module-icon {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    margin: 0 auto 20px;
}
.stat-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    min-width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 18px;
    font-weight: bold;
}
.quick-action-btn {
    width: 100%;
    margin-bottom: 10px;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin: 15px -25px 30px -25px;
    border-radius: 0;
}
.metric-card {
    border-left: 4px solid;
    transition: all 0.3s ease;
}
.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.metric-card.primary { border-left-color: #696cff; }
.metric-card.success { border-left-color: #71dd37; }
.metric-card.warning { border-left-color: #ffab00; }
.metric-card.info { border-left-color: #03c3ec; }
.metric-card.danger { border-left-color: #ff3e1d; }
.company-info-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid #e0e0e0;
}
.sales-chart-container {
    height: 300px;
    position: relative;
}
.growth-badge {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection

@section('content')

<!-- Header Principal -->
<div class="dashboard-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="text-white mb-2">¡Bienvenido, {{ auth()->user()->name }}!</h2>
                <p class="text-white-50 mb-0">
                    <i class="fa-solid fa-calendar-day me-2"></i>{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex justify-content-end gap-2">
                    <div class="text-center">
                        <h4 class="text-white mb-0">{{ \Carbon\Carbon::now()->format('H:i') }}</h4>
                        <small class="text-white-50">Hora Actual</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas Importantes -->
@if(($alertas['stockBajo'] ?? 0) > 0 || ($alertas['proximosVencer'] ?? 0) > 0 || ($alertas['citasPendientes'] ?? 0) > 0 || ($alertas['ordenesPendientes'] ?? 0) > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible d-flex align-items-center" role="alert">
            <i class="fa-solid fa-bell fa-2x me-3"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2">Atención Requerida</h6>
                <div class="row">
                    @if(($alertas['stockBajo'] ?? 0) > 0)
                    <div class="col-md-3 mb-2">
                        <span class="badge bg-danger me-1">{{ $alertas['stockBajo'] }}</span>
                        <span>Productos con stock bajo</span>
                    </div>
                    @endif
                    @if(($alertas['proximosVencer'] ?? 0) > 0)
                    <div class="col-md-3 mb-2">
                        <span class="badge bg-warning me-1">{{ $alertas['proximosVencer'] }}</span>
                        <span>Productos por vencer</span>
                    </div>
                    @endif
                    @if(($alertas['citasPendientes'] ?? 0) > 0)
                    <div class="col-md-3 mb-2">
                        <span class="badge bg-info me-1">{{ $alertas['citasPendientes'] }}</span>
                        <span>Citas pendientes hoy</span>
                    </div>
                    @endif
                    @if(($alertas['ordenesPendientes'] ?? 0) > 0)
                    <div class="col-md-3 mb-2">
                        <span class="badge bg-primary me-1">{{ $alertas['ordenesPendientes'] }}</span>
                        <span>Órdenes de lab. pendientes</span>
                    </div>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

{{-- Información de la Empresa - Ocultada según solicitud del usuario --}}
{{-- 
@if(isset($company) && $company)
<div class="row mb-4">
    <div class="col-12">
        <div class="card company-info-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        @if($company->logo)
                            <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo" class="img-fluid" style="max-height: 100px;">
                        @else
                            <div class="bg-label-primary rounded p-4">
                                <i class="fa-solid fa-building fa-3x text-primary"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-10">
                        <h4 class="mb-2">{{ $company->name ?? 'Empresa' }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>NIT:</strong> {{ $company->nit ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>NCR:</strong> {{ $company->ncr ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Email:</strong> {{ $company->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Teléfono:</strong> {{ $company->phone ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Dirección:</strong> {{ $company->address ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Actividad:</strong> {{ $company->actividad_economica ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
--}}

{{-- Métricas de Ventas - Ocultadas en Centro de Control (solo visibles en Dashboard Ejecutivo) --}}
{{-- 
<!-- Métricas de Ventas -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-line me-2"></i>Métricas de Ventas</h5>
    </div>

    <!-- Ventas Hoy -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="card metric-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Ventas Hoy</h6>
                        <h3 class="mb-1">${{ number_format($totalVentasHoy ?? 0, 2) }}</h3>
                        <small class="text-muted">{{ $cantidadVentasHoy ?? 0 }} transacciones</small>
                    </div>
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="fa-solid fa-calendar-day fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ventas Semana -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="card metric-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Ventas Esta Semana</h6>
                        <h3 class="mb-1">${{ number_format($totalVentasSemana ?? 0, 2) }}</h3>
                        <small class="text-muted">Últimos 7 días</small>
                    </div>
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="fa-solid fa-calendar-week fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ventas Mes -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="card metric-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Ventas Este Mes</h6>
                        <h3 class="mb-1">${{ number_format($totalVentasMes ?? 0, 2) }}</h3>
                        <small class="text-muted">{{ $cantidadVentasMes ?? 0 }} ventas
                            @if(isset($crecimientoVentas) && $crecimientoVentas != 0)
                                <span class="badge growth-badge {{ $crecimientoVentas > 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $crecimientoVentas > 0 ? '+' : '' }}{{ $crecimientoVentas }}%
                                </span>
                            @endif
                        </small>
                    </div>
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="fa-solid fa-calendar fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ventas Acumuladas -->
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="card metric-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Ventas Acumuladas</h6>
                        <h3 class="mb-1">${{ number_format($totalVentas ?? 0, 2) }}</h3>
                        <small class="text-muted">Total histórico</small>
                    </div>
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="fa-solid fa-chart-line fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
--}}

<!-- Resumen de Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Resumen General del Negocio</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <i class="fa-solid fa-users fa-2x text-primary mb-2"></i>
                            <h4 class="mb-0">{{ $tclientes ?? 0 }}</h4>
                            <small class="text-muted">Clientes</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <i class="fa-solid fa-box fa-2x text-success mb-2"></i>
                            <h4 class="mb-0">{{ $tproducts ?? 0 }}</h4>
                            <small class="text-muted">Productos</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <i class="fa-solid fa-truck fa-2x text-warning mb-2"></i>
                            <h4 class="mb-0">{{ $tproviders ?? 0 }}</h4>
                            <small class="text-muted">Proveedores</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <i class="fa-solid fa-user-injured fa-2x text-info mb-2"></i>
                            <h4 class="mb-0">{{ $tpacientes ?? 0 }}</h4>
                            <small class="text-muted">Pacientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Título -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Selecciona el Módulo donde Deseas Trabajar</h4>
        <p class="text-muted">Cada módulo está diseñado para concentrarte en una actividad específica</p>
    </div>
</div>

<!-- Módulos Principales -->
<div class="row g-4 mb-4">

    <!-- MÓDULO FARMACIA -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card module-card farmacia" onclick="window.location.href='/dashboard-farmacia'">
            <div class="card-body text-center position-relative">
                @if(($alertas['stockBajo'] ?? 0) > 0 || ($alertas['proximosVencer'] ?? 0) > 0)
                <span class="stat-badge bg-label-danger">
                    <i class="fa-solid fa-exclamation"></i>
                </span>
                @endif

                <div class="module-icon bg-label-primary">
                    <i class="fa-solid fa-pills fa-4x text-primary"></i>
                </div>

                <h3 class="mb-2">Farmacia</h3>
                <p class="text-muted mb-4">Gestión de medicamentos, ventas e inventario</p>

                <div class="row text-start mb-3">
                    <div class="col-12">
                        <small class="text-muted d-block">Total Productos</small>
                        <h5 class="mb-0">{{ $tproducts ?? 0 }}</h5>
                    </div>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary">
                        <i class="fa-solid fa-arrow-right me-2"></i>Acceder a Farmacia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO CLÍNICA -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card module-card clinica" onclick="window.location.href='/dashboard-clinica'">
            <div class="card-body text-center position-relative">
                @if(($alertas['citasPendientes'] ?? 0) > 0)
                <span class="stat-badge bg-label-warning">
                    {{ $alertas['citasPendientes'] }}
                </span>
                @endif

                <div class="module-icon bg-label-success">
                    <i class="fa-solid fa-stethoscope fa-4x text-success"></i>
                </div>

                <h3 class="mb-2">Clínica Médica</h3>
                <p class="text-muted mb-4">Atención médica, citas y consultas</p>

                <div class="row text-start mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Citas Hoy</small>
                        <h5 class="mb-0 text-success">{{ $citasHoy ?? 0 }}</h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Pacientes</small>
                        <h5 class="mb-0">{{ $tpacientes ?? 0 }}</h5>
                    </div>
                </div>

                <div class="d-grid">
                    <button class="btn btn-success">
                        <i class="fa-solid fa-arrow-right me-2"></i>Acceder a Clínica
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO LABORATORIO -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card module-card laboratorio" onclick="window.location.href='/dashboard-laboratorio'">
            <div class="card-body text-center position-relative">
                @if(($alertas['ordenesPendientes'] ?? 0) > 0)
                <span class="stat-badge bg-label-warning">
                    {{ $alertas['ordenesPendientes'] }}
                </span>
                @endif

                <div class="module-icon bg-label-warning">
                    <i class="fa-solid fa-flask fa-4x text-warning"></i>
                </div>

                <h3 class="mb-2">Laboratorio</h3>
                <p class="text-muted mb-4">Exámenes clínicos y resultados</p>

                <div class="row text-start mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Órdenes Hoy</small>
                        <h5 class="mb-0 text-warning">{{ $ordenesLabHoy ?? 0 }}</h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Pendientes</small>
                        <h5 class="mb-0">{{ $ordenesPendientes ?? 0 }}</h5>
                    </div>
                </div>

                <div class="d-grid">
                    <button class="btn btn-warning">
                        <i class="fa-solid fa-arrow-right me-2"></i>Acceder a Laboratorio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO FACTURACIÓN -->
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card module-card facturacion" onclick="window.location.href='/facturacion-integral'">
            <div class="card-body text-center position-relative">
                @php
                    $ordenesLabPorFacturar = $ordenesLabPorFacturar ?? 0;
                    $pendientesFacturar = $ordenesLabPorFacturar;
                @endphp
                @if($pendientesFacturar > 0)
                <span class="stat-badge bg-label-info">
                    {{ $pendientesFacturar }}
                </span>
                @endif

                <div class="module-icon bg-label-info">
                    <i class="fa-solid fa-file-invoice-dollar fa-4x text-info"></i>
                </div>

                <h3 class="mb-2">Facturación</h3>
                <p class="text-muted mb-4">Factura servicios de todos los módulos</p>

                <div class="row text-start mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Lab. Por Facturar</small>
                        <h5 class="mb-0 text-info">{{ $ordenesLabPorFacturar }}</h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Hoy</small>
                        <h5 class="mb-0">${{ number_format($totalVentasHoy ?? 0, 2) }}</h5>
                    </div>
                </div>

                <div class="d-grid">
                    <button class="btn btn-info">
                        <i class="fa-solid fa-arrow-right me-2"></i>Acceder a Facturación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Bajo y Órdenes Pendientes de Facturar -->
<div class="row mb-4">
    <!-- Productos con Stock Bajo -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0 text-white">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    Productos con Stock Bajo
                </h5>
            </div>
            <div class="card-body">
                @if(isset($productosStockBajo) && $productosStockBajo->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock Actual</th>
                                    <th class="text-center">Stock Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productosStockBajo as $inventory)
                                <tr>
                                    <td>
                                        <strong>{{ $inventory->product->name ?? 'Producto sin nombre' }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ number_format($inventory->quantity, 0) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ number_format($inventory->minimum_stock, 0) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="/inventory/index" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-box me-2"></i>Ver Inventario Completo
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">No hay productos con stock bajo</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Órdenes Pendientes de Facturar -->
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                    Órdenes Pendientes de Facturar
                    @if(($ordenesLabPorFacturar ?? 0) > 0)
                        <span class="badge bg-danger ms-2">{{ $ordenesLabPorFacturar }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if(isset($ordenesLabPorFacturarList) && $ordenesLabPorFacturarList->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Paciente</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordenesLabPorFacturarList as $orden)
                                <tr>
                                    <td>
                                        <strong>{{ $orden->numero_orden }}</strong>
                                    </td>
                                    <td>
                                        {{ $orden->patient->nombre_completo ?? 'Paciente sin nombre' }}
                                    </td>
                                    <td class="text-center">
                                        <small>{{ \Carbon\Carbon::parse($orden->fecha_orden)->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <strong>${{ number_format($orden->total, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="/facturacion-integral?tipo=laboratorio" class="btn btn-sm btn-outline-warning">
                            <i class="fa-solid fa-file-invoice-dollar me-2"></i>Ir a Facturar
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">No hay órdenes pendientes de facturar</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-header bg-label-primary">
                <h6 class="mb-0"><i class="fa-solid fa-pills me-2"></i>Farmacia</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('sale.create-dynamic', ['typedocument' => 6, 'new' => true]) }}" class="quick-action-btn btn btn-sm btn-outline-primary">
                    <span><i class="fa-solid fa-cash-register me-2"></i>Nueva Venta</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/products" class="quick-action-btn btn btn-sm btn-outline-primary">
                    <span><i class="fa-solid fa-box me-2"></i>Inventario</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/purchase/index" class="quick-action-btn btn btn-sm btn-outline-primary">
                    <span><i class="fa-solid fa-truck me-2"></i>Compras</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-header bg-label-success">
                <h6 class="mb-0"><i class="fa-solid fa-stethoscope me-2"></i>Clínica</h6>
            </div>
            <div class="card-body">
                <a href="/appointments/create" class="quick-action-btn btn btn-sm btn-outline-success">
                    <span><i class="fa-solid fa-calendar-plus me-2"></i>Nueva Cita</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/consultations/create" class="quick-action-btn btn btn-sm btn-outline-success">
                    <span><i class="fa-solid fa-notes-medical me-2"></i>Nueva Consulta</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/patients" class="quick-action-btn btn btn-sm btn-outline-success">
                    <span><i class="fa-solid fa-user-injured me-2"></i>Pacientes</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-header bg-label-warning">
                <h6 class="mb-0"><i class="fa-solid fa-flask me-2"></i>Laboratorio</h6>
            </div>
            <div class="card-body">
                <a href="/lab-orders/create" class="quick-action-btn btn btn-sm btn-outline-warning">
                    <span><i class="fa-solid fa-plus me-2"></i>Nueva Orden</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/lab-orders?estado=pendiente" class="quick-action-btn btn btn-sm btn-outline-warning">
                    <span><i class="fa-solid fa-hourglass me-2"></i>Pendientes</span>
                    <span class="badge bg-danger">{{ $ordenesPendientes ?? 0 }}</span>
                </a>
                <a href="/lab-orders" class="quick-action-btn btn btn-sm btn-outline-warning">
                    <span><i class="fa-solid fa-list me-2"></i>Todas las Órdenes</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-header bg-label-info">
                <h6 class="mb-0"><i class="fa-solid fa-file-invoice me-2"></i>Facturación</h6>
            </div>
            <div class="card-body">
                <a href="/facturacion-integral?tipo=farmacia" class="quick-action-btn btn btn-sm btn-outline-info">
                    <span><i class="fa-solid fa-pills me-2"></i>Facturar Farmacia</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/facturacion-integral?tipo=clinica" class="quick-action-btn btn btn-sm btn-outline-info">
                    <span><i class="fa-solid fa-stethoscope me-2"></i>Facturar Clínica</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/facturacion-integral?tipo=laboratorio" class="quick-action-btn btn btn-sm btn-outline-info">
                    <span><i class="fa-solid fa-flask me-2"></i>Facturar Laboratorio</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection


