@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('title', 'Reporte de Ventas por Proveedor')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Ventas por Proveedor
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.sales-by-provider-search') }}" id="searchForm">
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
                <div class="col-md-3">
                    <label for="provider_id" class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id" id="provider_id">
                        <option value="">Todos los proveedores</option>
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
@if(isset($salesByProvider) && $salesByProvider->count() > 0)

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Proveedores</h6>
                <h3 class="mb-0">{{ number_format($stats['total_providers']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Ventas</h6>
                <h3 class="mb-0">{{ number_format($stats['total_sales']) }}</h3>
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
                <h6 class="card-title">Promedio por Proveedor</h6>
                <h3 class="mb-0">${{ number_format($stats['average_per_provider'], 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de resultados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Ventas por Proveedor
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="salesTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Proveedor</th>
                        <th>NIT</th>
                        <th>Email</th>
                        <th>Total Ventas</th>
                        <th>Productos Vendidos</th>
                        <th>Cantidad Total</th>
                        <th>Monto Total</th>
                        <th>Precio Promedio</th>
                        <th>Primera Venta</th>
                        <th>Última Venta</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($salesByProvider as $item)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td><strong>{{ $item->provider_name }}</strong></td>
                        <td>{{ $item->provider_nit ?? 'N/A' }}</td>
                        <td>{{ $item->provider_email ?? 'N/A' }}</td>
                        <td><span class="badge bg-info">{{ number_format($item->total_sales) }}</span></td>
                        <td><span class="badge bg-secondary">{{ number_format($item->products_sold) }}</span></td>
                        <td>{{ number_format($item->total_quantity, 2) }}</td>
                        <td><strong>${{ number_format($item->total_amount, 2) }}</strong></td>
                        <td>${{ number_format($item->average_price, 2) }}</td>
                        <td>{{ $item->first_sale_date ? \Carbon\Carbon::parse($item->first_sale_date)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $item->last_sale_date ? \Carbon\Carbon::parse($item->last_sale_date)->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="4" class="text-end"><strong>TOTALES:</strong></th>
                        <th><strong>{{ number_format($salesByProvider->sum('total_sales')) }}</strong></th>
                        <th><strong>{{ number_format($salesByProvider->sum('products_sold')) }}</strong></th>
                        <th><strong>{{ number_format($salesByProvider->sum('total_quantity'), 2) }}</strong></th>
                        <th><strong>${{ number_format($salesByProvider->sum('total_amount'), 2) }}</strong></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Gráfica de ventas por proveedor -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Gráfica de Ventas por Proveedor (Top 10)</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart" height="100"></canvas>
    </div>
</div>

@elseif(isset($salesByProvider))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay ventas para los filtros seleccionados.</p>
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
            order: [[7, 'desc']], // Ordenar por monto total descendente
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: []
        });
    }

    // Gráfica de ventas por proveedor
    @if(isset($salesByProvider) && $salesByProvider->count() > 0)
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        const topProviders = @json($salesByProvider->take(10)->values());

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topProviders.map(p => p.provider_name),
                datasets: [{
                    label: 'Monto Total ($)',
                    data: topProviders.map(p => parseFloat(p.total_amount)),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Monto: $' + context.parsed.y.toLocaleString();
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

    let csv = 'Proveedor,NIT,Email,Total Ventas,Productos Vendidos,Cantidad Total,Monto Total,Precio Promedio,Primera Venta,Última Venta\n';

    data.forEach(function(row) {
        // Extraer texto limpio de las celdas (sin HTML)
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
    link.download = 'ventas_por_proveedor_' + Date.now() + '.csv';
    link.click();
}

function exportToPDF() {
    Swal.fire({
        icon: 'info',
        title: 'Funcionalidad en desarrollo',
        text: 'La exportación a PDF estará disponible próximamente'
    });
}
</script>
@endsection

