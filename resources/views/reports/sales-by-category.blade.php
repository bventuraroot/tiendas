@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('title', 'Análisis de Ventas por Categoría')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Ventas por Categoría
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.sales-by-category-search') }}" id="searchForm">
            @csrf
            <div class="row">
                <div class="col-md-4">
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
                <div class="col-md-3">
                    <label for="year" class="form-label">Año</label>
                    <select class="form-select" name="year" id="year">
                        <option value="">Todos</option>
                        @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                            <option value="{{ $i }}" {{ (isset($filters['year']) && $filters['year'] == $i) || (!isset($filters['year']) && $i == date('Y')) ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultados del reporte -->
@if(isset($salesByCategory) && $salesByCategory->count() > 0)

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Categorías</h6>
                <h3 class="mb-0">{{ number_format($stats['total_categories']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Cantidad Total</h6>
                <h3 class="mb-0">{{ number_format($stats['total_quantity'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Monto Total</h6>
                <h3 class="mb-0">${{ number_format($stats['total_amount'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Promedio por Categoría</h6>
                <h3 class="mb-0">${{ number_format($stats['average_per_category'], 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de resultados -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Ventas por Categoría
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
            <table class="table table-striped table-hover" id="salesTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Categoría</th>
                        <th>Total Ventas</th>
                        <th>Total Productos</th>
                        <th>Cantidad Total</th>
                        <th>Monto Total</th>
                        <th>Precio Promedio</th>
                        <th>% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($salesByCategory as $item)
                    @php
                        $percentage = $stats['total_amount'] > 0 ? ($item->total_amount / $stats['total_amount']) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td><strong><span class="badge bg-primary">{{ ucfirst($item->category ?? 'Sin categoría') }}</span></strong></td>
                        <td><span class="badge bg-info">{{ number_format($item->total_sales) }}</span></td>
                        <td><span class="badge bg-secondary">{{ number_format($item->total_products) }}</span></td>
                        <td>{{ number_format($item->total_quantity, 2) }}</td>
                        <td><strong>${{ number_format($item->total_amount, 2) }}</strong></td>
                        <td>${{ number_format($item->average_price, 2) }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: {{ $percentage }}%"
                                         aria-valuenow="{{ $percentage }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <span class="text-nowrap"><strong>{{ number_format($percentage, 1) }}%</strong></span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="2" class="text-end"><strong>TOTALES:</strong></th>
                        <th><strong>{{ number_format($salesByCategory->sum('total_sales')) }}</strong></th>
                        <th><strong>{{ number_format($salesByCategory->sum('total_products')) }}</strong></th>
                        <th><strong>{{ number_format($salesByCategory->sum('total_quantity'), 2) }}</strong></th>
                        <th><strong>${{ number_format($salesByCategory->sum('total_amount'), 2) }}</strong></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Distribución de Ventas por Categoría (Monto)</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryPieChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Comparativa de Categorías (Cantidad vs Monto)</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryBarChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

@elseif(isset($salesByCategory))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay ventas por categoría para los filtros seleccionados.</p>
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
            order: [[5, 'desc']], // Ordenar por monto total descendente
            pageLength: 25,
            paging: false,
            searching: false,
            info: false
        });
    }

    // Gráfica de pastel - Distribución por categoría
    @if(isset($salesByCategory) && $salesByCategory->count() > 0)
    const ctxPie = document.getElementById('categoryPieChart');
    if (ctxPie) {
        const categories = @json($salesByCategory);

        // Colores variados para las categorías
        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(99, 255, 132, 0.8)'
        ];

        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: categories.map(c => c.category ? c.category.charAt(0).toUpperCase() + c.category.slice(1) : 'Sin categoría'),
                datasets: [{
                    data: categories.map(c => parseFloat(c.total_amount)),
                    backgroundColor: colors.slice(0, categories.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': $' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfica de barras - Comparativa
    const ctxBar = document.getElementById('categoryBarChart');
    if (ctxBar) {
        const categories = @json($salesByCategory);

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: categories.map(c => c.category ? c.category.charAt(0).toUpperCase() + c.category.slice(1) : 'Sin categoría'),
                datasets: [
                    {
                        label: 'Cantidad Vendida',
                        data: categories.map(c => parseFloat(c.total_quantity)),
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Monto Total ($)',
                        data: categories.map(c => parseFloat(c.total_amount)),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Monto ($)'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
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

    let csv = 'Categoría,Total Ventas,Total Productos,Cantidad Total,Monto Total,Precio Promedio,% del Total\n';

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
    link.download = 'ventas_por_categoria_' + Date.now() + '.csv';
    link.click();
}
</script>
@endsection

