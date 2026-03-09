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

@section('title', 'Reporte de Inventario por Proveedor')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Inventario por Proveedor
</h4>

<!-- Filtros de búsqueda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('report.inventory-by-provider') }}" id="searchForm">
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
@if(isset($inventoryByProvider) && $inventoryByProvider->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Inventario Agrupado por Proveedor
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
                        <h6 class="card-title">Total Proveedores</h6>
                        <h3 class="mb-0">{{ $inventoryByProvider->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Productos</h6>
                        <h3 class="mb-0">{{ number_format($inventoryByProvider->sum('total_products')) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Valor Total</h6>
                        <h3 class="mb-0">${{ number_format($inventoryByProvider->sum('total_value'), 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Stock Total</h6>
                        <h3 class="mb-0">{{ number_format($inventoryByProvider->sum('total_quantity')) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="inventoryByProviderTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Proveedor</th>
                        <th>NIT</th>
                        <th>Total Productos</th>
                        <th>Stock Total</th>
                        <th>Valor Total</th>
                        <th>Precio Promedio</th>
                        <th>Porcentaje del Total</th>
                        <th>Valor por Producto</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $counter = 1;
                        $totalValue = $inventoryByProvider->sum('total_value');
                    @endphp
                    @foreach($inventoryByProvider as $provider)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>
                            <strong>{{ $provider->provider_name }}</strong>
                        </td>
                        <td>{{ $provider->provider_nit ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ number_format($provider->total_products) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ number_format($provider->total_quantity) }}</span>
                        </td>
                        <td>
                            <strong>${{ number_format($provider->total_value, 2) }}</strong>
                        </td>
                        <td>
                            ${{ number_format($provider->average_price, 2) }}
                        </td>
                        <td>
                            @if($totalValue > 0)
                                <span class="badge bg-secondary">
                                    {{ number_format(($provider->total_value / $totalValue) * 100, 1) }}%
                                </span>
                            @else
                                <span class="badge bg-secondary">0%</span>
                            @endif
                        </td>
                        <td>
                            ${{ number_format($provider->total_value / max($provider->total_products, 1), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th colspan="3">TOTALES:</th>
                        <th>{{ number_format($inventoryByProvider->sum('total_products')) }}</th>
                        <th>{{ number_format($inventoryByProvider->sum('total_quantity')) }}</th>
                        <th>${{ number_format($inventoryByProvider->sum('total_value'), 2) }}</th>
                        <th>${{ number_format($inventoryByProvider->avg('average_price'), 2) }}</th>
                        <th>100%</th>
                        <th>${{ number_format($inventoryByProvider->sum('total_value') / max($inventoryByProvider->sum('total_products'), 1), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Gráfico de distribución -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Distribución por Proveedor (Productos)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="productsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Distribución por Proveedor (Valor)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="valueChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Análisis de proveedores -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Top 5 Proveedores por Valor</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($inventoryByProvider->take(5) as $index => $provider)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $provider->provider_name }}</h6>
                                    <small class="text-muted">{{ $provider->total_products }} productos</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary rounded-pill">${{ number_format($provider->total_value, 2) }}</span>
                                    <br><small class="text-muted">{{ number_format(($provider->total_value / $totalValue) * 100, 1) }}%</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Top 5 Proveedores por Productos</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($inventoryByProvider->sortByDesc('total_products')->take(5) as $index => $provider)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $provider->provider_name }}</h6>
                                    <small class="text-muted">${{ number_format($provider->total_value, 2) }} valor</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success rounded-pill">{{ number_format($provider->total_products) }}</span>
                                    <br><small class="text-muted">{{ number_format(($provider->total_products / $inventoryByProvider->sum('total_products')) * 100, 1) }}%</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@elseif(isset($inventoryByProvider))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay proveedores con inventario para la empresa seleccionada.</p>
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
    if ($('#inventoryByProviderTable').length) {
        $('#inventoryByProviderTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[5, 'desc']], // Ordenar por valor total descendente
            pageLength: 25
        });
    }

    // Crear gráficos si hay datos
    @if(isset($inventoryByProvider) && $inventoryByProvider->count() > 0)
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
    const providers = @json($inventoryByProvider->pluck('provider_name'));
    const products = @json($inventoryByProvider->pluck('total_products'));
    const values = @json($inventoryByProvider->pluck('total_value'));

    // Gráfico de productos por proveedor
    const productsCtx = document.getElementById('productsChart').getContext('2d');
    new Chart(productsCtx, {
        type: 'doughnut',
        data: {
            labels: providers.map(prov => prov.length > 20 ? prov.substring(0, 20) + '...' : prov),
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
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });

    // Gráfico de valor por proveedor
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    new Chart(valueCtx, {
        type: 'bar',
        data: {
            labels: providers.map(prov => prov.length > 15 ? prov.substring(0, 15) + '...' : prov),
            datasets: [{
                label: 'Valor Total ($)',
                data: values,
                backgroundColor: '#FF6384',
                borderColor: '#E64A6F',
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
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
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
    let table = $('#inventoryByProviderTable').DataTable();
    let data = table.data().toArray();

    // Crear CSV
    let csv = 'Proveedor,NIT,Total Productos,Stock Total,Valor Total,Precio Promedio,Porcentaje del Total,Valor por Producto\n';

    data.forEach(function(row) {
        csv += `"${row[1]}","${row[2]}","${row[3]}","${row[4]}","${row[5]}","${row[6]}","${row[7]}","${row[8]}"\n`;
    });

    // Descargar archivo
    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'reporte_inventario_por_proveedor.csv';
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
