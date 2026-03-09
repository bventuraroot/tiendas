@extends('layouts/layoutMaster')

@section('title', 'Dashboard Tienda - Ventas y Gastos')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endsection

@section('page-script')
<script>
    window.ventasDiarias = @json($ventasDiarias);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.ventasDiarias && window.ventasDiarias.length) {
        const categories = window.ventasDiarias.map(d => d.fecha_formateada);
        const data = window.ventasDiarias.map(d => d.total);

        const options = {
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false }
            },
            series: [{
                name: 'Ventas',
                data: data
            }],
            xaxis: {
                categories: categories
            },
            yaxis: {
                labels: {
                    formatter: val => '$' + val.toFixed(2)
                }
            },
            colors: ['#696cff'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        };

        const chart = new ApexCharts(document.querySelector('#ventasDiariasChart'), options);
        chart.render();
    }
});
</script>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1">Panel de Tienda</h4>
        <p class="text-muted mb-0">Resumen rápido de ventas, compras y utilidad del negocio</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Ventas Hoy</span>
                <h3 class="mb-2 text-primary">${{ number_format($totalVentasHoy, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Ventas Semana</span>
                <h3 class="mb-2">${{ number_format($totalVentasSemana, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Ventas Mes</span>
                <h3 class="mb-2">${{ number_format($totalVentasMes, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Compras (Gastos) Mes</span>
                <h3 class="mb-2 text-danger">${{ number_format($comprasMes, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Utilidad Aprox. del Mes</span>
                <h3 class="mb-2 text-success">${{ number_format($utilidadMes, 2) }}</h3>
                <small class="text-muted">Ventas mes - Compras mes</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Clientes</span>
                <h4 class="mb-0">{{ $tclientes }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <span class="text-muted d-block mb-1">Productos</span>
                <h4 class="mb-0">{{ $tproducts }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Ventas últimos 7 días</h5>
            </div>
            <div class="card-body">
                <div id="ventasDiariasChart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Top productos vendidos</h5>
            </div>
            <div class="card-body">
                @if($productosMasVendidos->count())
                    <ul class="list-unstyled mb-0">
                        @foreach($productosMasVendidos as $p)
                            <li class="mb-2 d-flex justify-content-between">
                                <span>{{ \Illuminate\Support\Str::limit($p->name, 30) }}</span>
                                <span class="text-muted">{{ $p->cantidad_vendida }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">Sin datos de ventas aún.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Accesos rápidos</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('sale.create-dynamic') }}" class="btn btn-primary">
                        Nueva venta
                    </a>
                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-outline-primary">
                        Presupuestos
                    </a>
                    <a href="{{ route('purchase.index') }}" class="btn btn-outline-primary">
                        Compras / gastos
                    </a>
                    <a href="{{ route('report.sales') }}" class="btn btn-outline-secondary">
                        Reportes de ventas
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Alertas de inventario</h5>
            </div>
            <div class="card-body">
                @if($productosStockBajo->count())
                    <p class="mb-1"><strong>Stock bajo:</strong></p>
                    <ul class="list-unstyled mb-3">
                        @foreach($productosStockBajo->take(5) as $i)
                            <li class="mb-1">
                                {{ $i->product->name ?? 'Producto' }} —
                                <span class="text-danger">{{ $i->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-3">No hay productos con stock bajo.</p>
                @endif

                @if($productosProximosVencer->count())
                    <p class="mb-1"><strong>Próximos a vencer:</strong></p>
                    <ul class="list-unstyled mb-0">
                        @foreach($productosProximosVencer->take(5) as $i)
                            <li class="mb-1">
                                {{ $i->product->name ?? 'Producto' }} —
                                vence {{ \Carbon\Carbon::parse($i->expiration_date)->format('d/m/Y') }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No hay productos próximos a vencer.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

