@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard Ejecutivo - Información Crítica')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.js.min.css">
<style>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin: -25px -25px 30px -25px;
    border-radius: 0 0 15px 15px;
}
.metric-card {
    border-left: 4px solid;
    transition: all 0.3s ease;
    height: 100%;
}
.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}
.metric-card.primary { border-left-color: #696cff; }
.metric-card.success { border-left-color: #71dd37; }
.metric-card.warning { border-left-color: #ffab00; }
.metric-card.info { border-left-color: #03c3ec; }
.metric-card.danger { border-left-color: #ff3e1d; }
.critical-alert {
    border-left: 4px solid;
    animation: pulse 2s infinite;
}
.critical-alert.danger { border-left-color: #ff3e1d; }
.critical-alert.warning { border-left-color: #ffab00; }
.critical-alert.info { border-left-color: #03c3ec; }
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
.growth-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
}
.growth-indicator.positive { color: #71dd37; }
.growth-indicator.negative { color: #ff3e1d; }
.table-custom th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.chart-container {
    position: relative;
    height: 350px;
    margin-top: 20px;
}
.stat-badge-large {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}
</style>
@endsection

@section('content')

<!-- Header del Dashboard Ejecutivo -->
<div class="dashboard-header">
    <div class="container-xxl">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fa-solid fa-chart-line me-2"></i>
                    Dashboard Ejecutivo
                </h1>
                <p class="mb-0 opacity-75">Información crítica y análisis detallado para toma de decisiones</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="text-white">
                    <small class="d-block opacity-75">Última actualización</small>
                    <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Alertas Críticas -->
    @if(count($alertasCriticas ?? []) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="fw-bold mb-3">
                <i class="fa-solid fa-bell me-2 text-warning"></i>
                Alertas Críticas
            </h5>
            <div class="row">
                @foreach($alertasCriticas as $alerta)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card critical-alert alert-{{ $alerta['severidad'] }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-lg me-3">
                                    <span class="avatar-initial rounded bg-label-{{ $alerta['severidad'] }}">
                                        <i class="fa-solid {{ $alerta['icono'] }}"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $alerta['mensaje'] }}</h6>
                                    <small class="text-muted">{{ ucfirst($alerta['tipo']) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Métricas Principales de Ventas -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="fw-bold mb-3">
                <i class="fa-solid fa-dollar-sign me-2 text-success"></i>
                Análisis de Ventas
            </h5>
        </div>

        <!-- Ventas Hoy -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="card metric-card primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Ventas Hoy</h6>
                            <h3 class="stat-badge-large mb-1">${{ number_format($totalVentasHoy ?? 0, 2) }}</h3>
                            @if(isset($crecimientoVentas))
                            <div class="growth-indicator {{ $crecimientoVentas >= 0 ? 'positive' : 'negative' }}">
                                <i class="fa-solid {{ $crecimientoVentas >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                <span>{{ number_format(abs($crecimientoVentas), 2) }}%</span>
                                <small class="text-muted">vs ayer</small>
                            </div>
                            @endif
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-calendar-day fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas Mes Actual -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="card metric-card success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Ventas del Mes</h6>
                            <h3 class="stat-badge-large mb-1">${{ number_format($ventasMes ?? 0, 2) }}</h3>
                            <small class="text-muted">Mes actual</small>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-calendar-check fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Margen Bruto -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="card metric-card info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Margen Bruto</h6>
                            <h3 class="stat-badge-large mb-1">${{ number_format($margenBruto ?? 0, 2) }}</h3>
                            <div class="mt-2">
                                <span class="badge bg-label-info">{{ number_format($margenPorcentaje ?? 0, 2) }}%</span>
                                <small class="text-muted ms-2">Margen %</small>
                            </div>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="fa-solid fa-percent fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Créditos Pendientes -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="card metric-card warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Créditos Pendientes</h6>
                            <h3 class="stat-badge-large mb-1">${{ number_format($totalCreditosPendientes ?? 0, 2) }}</h3>
                            <small class="text-muted">{{ count($creditosPendientes ?? []) }} clientes</small>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="fa-solid fa-money-bill-wave fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Ventas Últimos 12 Meses -->
        <div class="col-xl-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-chart-area me-2"></i>
                        Tendencia de Ventas (Últimos 12 Meses)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="ventasChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métricas de Clínica y Laboratorio -->
        <div class="col-xl-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-hospital me-2"></i>
                        Estado Operacional
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Citas Pendientes</span>
                            <span class="badge bg-label-info">{{ $citasPendientes ?? 0 }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: {{ min(($citasPendientes ?? 0) * 2, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Consultas Pendientes</span>
                            <span class="badge bg-label-warning">{{ $consultasPendientes ?? 0 }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: {{ min(($consultasPendientes ?? 0) * 3, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Órdenes Lab Pendientes</span>
                            <span class="badge bg-label-danger">{{ $ordenesLabPendientes ?? 0 }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: {{ min(($ordenesLabPendientes ?? 0) * 5, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Clientes -->
        <div class="col-xl-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-users me-2"></i>
                        Top 10 Clientes del Mes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-end">Total Compras</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientesMasCompran ?? [] as $cliente)
                                <tr>
                                    <td>
                                        <i class="fa-solid fa-user me-2 text-primary"></i>
                                        {{ $cliente->name }}
                                    </td>
                                    <td class="text-end">
                                        <strong>${{ number_format($cliente->total, 2) }}</strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        No hay datos disponibles
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Críticos y Más Vendidos -->
    <div class="row">
        <!-- Productos Más Vendidos -->
        <div class="col-xl-6 mb-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fa-solid fa-trophy me-2"></i>
                        Top 5 Productos Más Vendidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th class="text-end">Cantidad Vendida</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productosMasVendidos ?? [] as $index => $producto)
                                <tr>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $producto->name }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success">{{ number_format($producto->cantidad_vendida, 0) }}</span>
                                        <small class="text-muted ms-1">unidades</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        No hay datos de productos vendidos
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Stock Bajo -->
        <div class="col-xl-6 mb-4">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        Productos con Stock Crítico
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-custom">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productosStockBajo ?? [] as $inventory)
                                <tr>
                                    <td>{{ $inventory->product->name ?? 'Producto' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-danger">{{ $inventory->quantity }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ $inventory->minimum_stock }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        No hay productos con stock bajo
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <!-- Productos Próximos a Vencer -->
        <div class="col-xl-6 mb-4">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fa-solid fa-calendar-times me-2"></i>
                        Productos Próximos a Vencer (30 días)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-custom">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Vence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productosProximosVencer ?? [] as $inventory)
                                <tr>
                                    <td>{{ $inventory->product->name ?? 'Producto' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-warning">{{ $inventory->quantity }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">
                                            {{ \Carbon\Carbon::parse($inventory->expiration_date)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        No hay productos próximos a vencer
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Créditos Pendientes -->
    @if(count($creditosPendientes ?? []) > 0)
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fa-solid fa-credit-card me-2"></i>
                        Top 10 Créditos Pendientes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($creditosPendientes as $credito)
                                <tr>
                                    <td>
                                        <i class="fa-solid fa-user me-2 text-warning"></i>
                                        {{ $credito->name }}
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-warning">${{ number_format($credito->amount, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">
                                            {{ \Carbon\Carbon::parse($credito->date)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Ventas Últimos 12 Meses
    const ventasData = @json($ventasUltimos12Meses ?? []);
    
    if (ventasData.length > 0) {
        const ctx = document.getElementById('ventasChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ventasData.map(item => item.mes),
                datasets: [{
                    label: 'Ventas ($)',
                    data: ventasData.map(item => item.total),
                    borderColor: '#696cff',
                    backgroundColor: 'rgba(105, 108, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#696cff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Ventas: $' + context.parsed.y.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('en-US');
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
});
</script>
@endsection

