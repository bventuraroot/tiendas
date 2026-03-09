@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
<link rel="stylesheet"
    href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/tables-datatables-advanced.js') }}"></script>
@endsection

@section('title', 'Reporte de Inventario por Categoría')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Inventario por Categoría
</h4>

<!-- Filtros de búsqueda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('report.inventory-by-category') }}" id="searchForm">
            <div class="row">
                <div class="col-md-4">
                    <label for="company" class="form-label">Empresa</label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="{{ route('report.inventory') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Reporte Principal
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultados del reporte -->
@if(isset($inventoryByCategory) && $inventoryByCategory->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Inventario Agrupado por Categoría
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
        <div>
            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Resumen estadístico -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Categorías</h6>
                        <h3 class="mb-0">{{ $inventoryByCategory->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Productos</h6>
                        <h3 class="mb-0">{{ number_format($inventoryByCategory->sum('total_products')) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Valor Total</h6>
                        <h3 class="mb-0">${{ number_format($inventoryByCategory->sum('total_value'), 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Stock Total</h6>
                        <h3 class="mb-0">{{ number_format($inventoryByCategory->sum('total_quantity')) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="inventoryByCategoryTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Categoría</th>
                        <th>Total Productos</th>
                        <th>Stock Total</th>
                        <th>Productos Stock Bajo</th>
                        <th>Productos Sin Stock</th>
                        <th>Valor Total</th>
                        <th>Valor Promedio</th>
                        <th>Porcentaje del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $counter = 1;
                        $totalValue = $inventoryByCategory->sum('total_value');
                    @endphp
                    @foreach($inventoryByCategory as $category)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>
                            <strong>{{ ucfirst($category->category) }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ number_format($category->total_products) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ number_format($category->total_quantity) }}</span>
                        </td>
                        <td>
                            @if($category->low_stock_products > 0)
                                <span class="badge bg-warning">{{ number_format($category->low_stock_products) }}</span>
                            @else
                                <span class="badge bg-success">0</span>
                            @endif
                        </td>
                        <td>
                            @if($category->out_of_stock_products > 0)
                                <span class="badge bg-danger">{{ number_format($category->out_of_stock_products) }}</span>
                            @else
                                <span class="badge bg-success">0</span>
                            @endif
                        </td>
                        <td>
                            <strong>${{ number_format($category->total_value, 2) }}</strong>
                        </td>
                        <td>
                            ${{ number_format($category->total_value / max($category->total_products, 1), 2) }}
                        </td>
                        <td>
                            @if($totalValue > 0)
                                <span class="badge bg-secondary">
                                    {{ number_format(($category->total_value / $totalValue) * 100, 1) }}%
                                </span>
                            @else
                                <span class="badge bg-secondary">0%</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th colspan="2">TOTALES:</th>
                        <th>{{ number_format($inventoryByCategory->sum('total_products')) }}</th>
                        <th>{{ number_format($inventoryByCategory->sum('total_quantity')) }}</th>
                        <th>{{ number_format($inventoryByCategory->sum('low_stock_products')) }}</th>
                        <th>{{ number_format($inventoryByCategory->sum('out_of_stock_products')) }}</th>
                        <th>${{ number_format($inventoryByCategory->sum('total_value'), 2) }}</th>
                        <th>${{ number_format($inventoryByCategory->sum('total_value') / max($inventoryByCategory->sum('total_products'), 1), 2) }}</th>
                        <th>100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Gráfico de distribución -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Distribución por Categoría (Productos)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="productsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Distribución por Categoría (Valor)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="valueChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@elseif(isset($inventoryByCategory))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay categorías con inventario para la empresa seleccionada.</p>
    </div>
</div>
@endif
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Cargar empresas
    loadCompanies();

    // Inicializar DataTable
    if ($('#inventoryByCategoryTable').length) {
        $('#inventoryByCategoryTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[6, 'desc']], // Ordenar por valor total descendente
            pageLength: 25
        });
    }

    // Crear gráficos si hay datos
    @if(isset($inventoryByCategory) && $inventoryByCategory->count() > 0)
        createCharts();
    @endif
});

function loadCompanies() {
    $.ajax({
        url: '/company/getcompanies',
        method: 'GET',
        success: function(response) {
            let options = '<option value="">Seleccionar empresa</option>';
            response.forEach(function(company) {
                let selected = '{{ isset($heading) ? $heading->id : "" }}' == company.id ? 'selected' : '';
                options += `<option value="${company.id}" ${selected}>${company.name}</option>`;
            });
            $('#company').html(options);
        },
        error: function() {
            console.error('Error al cargar empresas');
        }
    });
}

function createCharts() {
    // Datos para los gráficos
    const categories = @json($inventoryByCategory->pluck('category'));
    const products = @json($inventoryByCategory->pluck('total_products'));
    const values = @json($inventoryByCategory->pluck('total_value'));

    // Gráfico de productos por categoría
    const productsCtx = document.getElementById('productsChart').getContext('2d');
    new Chart(productsCtx, {
        type: 'doughnut',
        data: {
            labels: categories.map(cat => cat.charAt(0).toUpperCase() + cat.slice(1)),
            datasets: [{
                data: products,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de valor por categoría
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    new Chart(valueCtx, {
        type: 'bar',
        data: {
            labels: categories.map(cat => cat.charAt(0).toUpperCase() + cat.slice(1)),
            datasets: [{
                label: 'Valor Total ($)',
                data: values,
                backgroundColor: '#36A2EB',
                borderColor: '#2693E6',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
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
                }
            }
        }
    });
}

function exportToExcel() {
    let table = $('#inventoryByCategoryTable').DataTable();
    let data = table.data().toArray();

    // Crear CSV
    let csv = 'Categoría,Total Productos,Stock Total,Productos Stock Bajo,Productos Sin Stock,Valor Total,Valor Promedio,Porcentaje del Total\n';

    data.forEach(function(row) {
        csv += `"${row[1]}","${row[2]}","${row[3]}","${row[4]}","${row[5]}","${row[6]}","${row[7]}","${row[8]}"\n`;
    });

    // Descargar archivo
    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'reporte_inventario_por_categoria.csv';
    link.click();
}

function exportToPDF() {
    // Implementar exportación a PDF
    Swal.fire({
        icon: 'info',
        title: 'Funcionalidad en desarrollo',
        text: 'La exportación a PDF estará disponible próximamente'
    });
}
</script>
@endsection
