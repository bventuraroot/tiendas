@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('title', 'Análisis de Ventas por Producto')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Ventas por Producto
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.sales-by-product-search') }}" id="searchForm">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <label for="company" class="form-label">Empresa <span class="text-danger">*</span></label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                        @if(isset($companies))
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (isset($heading) && $heading->id == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-select" name="category" id="category">
                        <option value="">Todas</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ (isset($filters['category']) && $filters['category'] == $category) ? 'selected' : '' }}>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="provider_id" class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id" id="provider_id">
                        <option value="">Todos</option>
                        @if(isset($providers))
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ (isset($filters['provider_id']) && $filters['provider_id'] == $provider->id) ? 'selected' : '' }}>
                                    {{ $provider->razonsocial }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">Año</label>
                    <select class="form-select" name="year" id="year">
                        <option value="">Todos</option>
                        @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                            <option value="{{ $i }}" {{ (isset($filters['year']) && $filters['year'] == $i) ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="period" class="form-label">Mes</label>
                    <select class="form-select" name="period" id="period">
                        <option value="">Todos</option>
                        <option value="1" {{ (isset($filters['period']) && $filters['period'] == '1') ? 'selected' : '' }}>Enero</option>
                        <option value="2" {{ (isset($filters['period']) && $filters['period'] == '2') ? 'selected' : '' }}>Febrero</option>
                        <option value="3" {{ (isset($filters['period']) && $filters['period'] == '3') ? 'selected' : '' }}>Marzo</option>
                        <option value="4" {{ (isset($filters['period']) && $filters['period'] == '4') ? 'selected' : '' }}>Abril</option>
                        <option value="5" {{ (isset($filters['period']) && $filters['period'] == '5') ? 'selected' : '' }}>Mayo</option>
                        <option value="6" {{ (isset($filters['period']) && $filters['period'] == '6') ? 'selected' : '' }}>Junio</option>
                        <option value="7" {{ (isset($filters['period']) && $filters['period'] == '7') ? 'selected' : '' }}>Julio</option>
                        <option value="8" {{ (isset($filters['period']) && $filters['period'] == '8') ? 'selected' : '' }}>Agosto</option>
                        <option value="9" {{ (isset($filters['period']) && $filters['period'] == '9') ? 'selected' : '' }}>Septiembre</option>
                        <option value="10" {{ (isset($filters['period']) && $filters['period'] == '10') ? 'selected' : '' }}>Octubre</option>
                        <option value="11" {{ (isset($filters['period']) && $filters['period'] == '11') ? 'selected' : '' }}>Noviembre</option>
                        <option value="12" {{ (isset($filters['period']) && $filters['period'] == '12') ? 'selected' : '' }}>Diciembre</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultados del reporte -->
@if(isset($salesByProduct) && $salesByProduct->count() > 0)

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Productos</h6>
                <h3 class="mb-0">{{ number_format($stats['total_products']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Cantidad Total</h6>
                <h3 class="mb-0">{{ number_format($stats['total_quantity'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Monto Total</h6>
                <h3 class="mb-0">${{ number_format($stats['total_amount'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Ganancia Total</h6>
                <h3 class="mb-0">${{ number_format($salesByProduct->sum('total_profit'), 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <h6 class="card-title">Margen Promedio</h6>
                <h3 class="mb-0">{{ number_format($salesByProduct->avg('profit_margin_percentage'), 1) }}%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h6 class="card-title">Promedio por Producto</h6>
                <h3 class="mb-0">${{ number_format($stats['average_per_product'], 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de resultados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Ventas por Producto
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm" id="salesTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Marca</th>
                        <th>Ventas</th>
                        <th>Cantidad</th>
                        <th>Monto Total</th>
                        <th>Precio Prom.</th>
                        <th>Costo Última Compra</th>
                        <th>Ganancia Total</th>
                        <th>% Ganancia</th>
                        <th>Primera Venta</th>
                        <th>Última Venta</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($salesByProduct as $item)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td><code>{{ $item->product_code }}</code></td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td>{{ $item->provider_name ?? 'N/A' }}</td>
                        <td>{{ $item->marca_name ?? 'N/A' }}</td>
                        <td><span class="badge bg-info">{{ number_format($item->total_sales) }}</span></td>
                        <td>{{ number_format($item->total_quantity, 2) }}</td>
                        <td><strong>${{ number_format($item->total_amount, 2) }}</strong></td>
                        <td>${{ number_format($item->average_sale_price, 2) }}</td>
                        <td>
                            @if($item->last_cost_price > 0)
                                ${{ number_format($item->last_cost_price, 2) }}
                            @else
                                <span class="text-muted">Sin costo</span>
                            @endif
                        </td>
                        <td>
                            @if($item->total_profit > 0)
                                <span class="text-success"><strong>${{ number_format($item->total_profit, 2) }}</strong></span>
                            @elseif($item->total_profit < 0)
                                <span class="text-danger"><strong>${{ number_format($item->total_profit, 2) }}</strong></span>
                            @else
                                <span class="text-muted">$0.00</span>
                            @endif
                        </td>
                        <td>
                            @if($item->profit_margin_percentage > 0)
                                <span class="badge bg-success">{{ number_format($item->profit_margin_percentage, 1) }}%</span>
                            @elseif($item->profit_margin_percentage < 0)
                                <span class="badge bg-danger">{{ number_format($item->profit_margin_percentage, 1) }}%</span>
                            @else
                                <span class="badge bg-secondary">0%</span>
                            @endif
                        </td>
                        <td>{{ $item->first_sale_date ? \Carbon\Carbon::parse($item->first_sale_date)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $item->last_sale_date ? \Carbon\Carbon::parse($item->last_sale_date)->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="5" class="text-end"><strong>TOTALES:</strong></th>
                        <th><strong>{{ number_format($salesByProduct->sum('total_sales')) }}</strong></th>
                        <th><strong>{{ number_format($salesByProduct->sum('total_quantity'), 2) }}</strong></th>
                        <th><strong>${{ number_format($salesByProduct->sum('total_amount'), 2) }}</strong></th>
                        <th colspan="2"></th>
                        <th><strong>${{ number_format($salesByProduct->sum('total_profit'), 2) }}</strong></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 10 Productos más Vendidos (Monto)</h5>
            </div>
            <div class="card-body">
                <canvas id="topProductsChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 10 Productos más Vendidos (Cantidad)</h5>
            </div>
            <div class="card-body">
                <canvas id="topProductsQuantityChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

@elseif(isset($salesByProduct))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay ventas de productos para los filtros seleccionados.</p>
    </div>
</div>
@endif
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    if ($('#salesTable').length) {
        $('#salesTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[8, 'desc']], // Ordenar por monto total descendente
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: []
        });
    }

    // Gráfica de top productos por monto
    @if(isset($salesByProduct) && $salesByProduct->count() > 0)
    const ctxAmount = document.getElementById('topProductsChart');
    if (ctxAmount) {
        const topProducts = @json($salesByProduct->take(10)->values());

        new Chart(ctxAmount, {
            type: 'bar',
            data: {
                labels: topProducts.map(p => p.product_name.substring(0, 20) + (p.product_name.length > 20 ? '...' : '')),
                datasets: [{
                    label: 'Monto Total ($)',
                    data: topProducts.map(p => parseFloat(p.total_amount)),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Monto: $' + context.parsed.x.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfica de top productos por cantidad
    const ctxQuantity = document.getElementById('topProductsQuantityChart');
    if (ctxQuantity) {
        const topProducts = @json($salesByProduct->sortByDesc('total_quantity')->take(10)->values());

        new Chart(ctxQuantity, {
            type: 'bar',
            data: {
                labels: topProducts.map(p => p.product_name.substring(0, 20) + (p.product_name.length > 20 ? '...' : '')),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: topProducts.map(p => parseFloat(p.total_quantity)),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Cantidad: ' + context.parsed.x.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
});

function exportToExcel() {
    const table = $('#salesTable').DataTable();
    const data = table.rows().data().toArray();

    let csv = 'Código,Producto,Categoría,Proveedor,Marca,Ventas,Cantidad,Monto Total,Precio Promedio,Primera Venta,Última Venta\n';

    data.forEach(function(row) {
        const cleanRow = row.map(cell => {
            const temp = document.createElement('div');
            temp.innerHTML = cell;
            return temp.textContent || temp.innerText || '';
        });

        csv += cleanRow.slice(1).join(',') + '\n';
    });

    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'ventas_por_producto_' + Date.now() + '.csv';
    link.click();
}
</script>
@endsection

