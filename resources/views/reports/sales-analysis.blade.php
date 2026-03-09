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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endsection

@section('title', 'Análisis General de Ventas')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Análisis General de Ventas
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.sales-analysis-search') }}" id="searchForm">
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
@if(isset($stats))

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Ventas</h6>
                <h3 class="mb-0">{{ number_format($stats['total_sales']) }}</h3>
                <small>Ventas completadas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Monto Total</h6>
                <h3 class="mb-0">${{ number_format($stats['total_amount'], 2) }}</h3>
                <small>Ingresos totales</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Ticket Promedio</h6>
                <h3 class="mb-0">${{ number_format($stats['average_ticket'], 2) }}</h3>
                <small>Por venta</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6 class="card-title">Ventas Canceladas</h6>
                <h3 class="mb-0">{{ number_format($stats['cancelled_sales']) }}</h3>
                <small>Ventas anuladas</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ventas por período -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ventas por Período</h5>
            </div>
            <div class="card-body">
                @if(isset($salesByPeriod) && $salesByPeriod->count() > 0)
                    <div style="position: relative; height: 400px; width: 100%;">
                        <canvas id="salesByPeriodChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Ventas</th>
                                    <th>Monto</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByPeriod as $item)
                                <tr>
                                    <td>{{ $item->period }}</td>
                                    <td>{{ number_format($item->total_sales) }}</td>
                                    <td>${{ number_format($item->total_amount, 2) }}</td>
                                    <td>${{ number_format($item->average_ticket, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Ventas por tipo de documento -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ventas por Tipo de Documento</h5>
            </div>
            <div class="card-body">
                @if(isset($salesByDocument) && $salesByDocument->count() > 0)
                    <div style="position: relative; height: 400px; width: 100%;">
                        <canvas id="salesByDocumentChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Tipo Documento</th>
                                    <th>Cantidad</th>
                                    <th>Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByDocument as $item)
                                <tr>
                                    <td>{{ $item->document_type }}</td>
                                    <td>{{ number_format($item->total_sales) }}</td>
                                    <td>${{ number_format($item->total_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top 10 clientes -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Top 10 Clientes</h5>
    </div>
    <div class="card-body">
        @if(isset($topClients) && $topClients->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="topClientsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Total Ventas</th>
                            <th>Monto Total</th>
                            <th>% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $counter = 1; @endphp
                        @foreach($topClients as $client)
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td><strong>{{ $client->client_name }}</strong></td>
                            <td><span class="badge bg-info">{{ number_format($client->total_sales) }}</span></td>
                            <td><strong>${{ number_format($client->total_amount, 2) }}</strong></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    @php
                                        $percentage = $stats['total_amount'] > 0 ? ($client->total_amount / $stats['total_amount']) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: {{ $percentage }}%"
                                         aria-valuenow="{{ $percentage }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ number_format($percentage, 1) }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay datos disponibles</p>
        @endif
    </div>
</div>

@elseif(Request::isMethod('post'))
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
    // Variables para controlar las instancias de gráficas
    var salesByPeriodChart = null;
    var salesByDocumentChart = null;

    // Inicializar DataTable para top clientes
    if ($('#topClientsTable').length && !$.fn.DataTable.isDataTable('#topClientsTable')) {
        $('#topClientsTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[3, 'desc']],
            paging: false,
            searching: false,
            info: false
        });
    }

    // Función para crear gráfica de ventas por período
    function createSalesByPeriodChart() {
        var ctxPeriod = document.getElementById('salesByPeriodChart');
        if (ctxPeriod && salesByPeriodChart === null) {
            var salesData = @json($salesByPeriod ?? []);

            if (salesData && salesData.length > 0) {
                try {
                    salesByPeriodChart = new Chart(ctxPeriod, {
                        type: 'line',
                        data: {
                            labels: salesData.map(function(s) { return s.period; }),
                            datasets: [{
                                label: 'Monto Total ($)',
                                data: salesData.map(function(s) { return parseFloat(s.total_amount) || 0; }),
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000,
                                easing: 'easeInOutQuart'
                            },
                            layout: {
                                padding: {
                                    top: 20,
                                    bottom: 20,
                                    left: 20,
                                    right: 20
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    grid: {
                                        display: true,
                                        color: 'rgba(0,0,0,0.1)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Período',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    display: true,
                                    grid: {
                                        display: true,
                                        color: 'rgba(0,0,0,0.1)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Monto ($)',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        }
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value.toLocaleString();
                                        },
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0,0,0,0.8)',
                                    titleColor: 'white',
                                    bodyColor: 'white',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function(context) {
                                            return 'Monto: $' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creando gráfica de período:', error);
                }
            }
        }
    }

    // Función para crear gráfica de ventas por tipo de documento
    function createSalesByDocumentChart() {
        var ctxDoc = document.getElementById('salesByDocumentChart');
        if (ctxDoc && salesByDocumentChart === null) {
            var docData = @json($salesByDocument ?? []);

            if (docData && docData.length > 0) {
                try {
                    salesByDocumentChart = new Chart(ctxDoc, {
                        type: 'doughnut',
                        data: {
                            labels: docData.map(function(d) { return d.document_type; }),
                            datasets: [{
                                data: docData.map(function(d) { return parseFloat(d.total_amount) || 0; }),
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 206, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)',
                                    'rgba(255, 159, 64, 0.8)',
                                    'rgba(199, 199, 199, 0.8)',
                                    'rgba(83, 102, 255, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(199, 199, 199, 1)',
                                    'rgba(83, 102, 255, 1)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000,
                                easing: 'easeInOutQuart'
                            },
                            layout: {
                                padding: {
                                    top: 20,
                                    bottom: 20,
                                    left: 20,
                                    right: 20
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0,0,0,0.8)',
                                    titleColor: 'white',
                                    bodyColor: 'white',
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.label || '';
                                            var value = context.parsed || 0;
                                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                            var percentage = ((value / total) * 100).toFixed(1);
                                            return label + ': $' + value.toLocaleString() + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creando gráfica de documentos:', error);
                }
            }
        }
    }

    // Esperar a que Chart.js esté completamente cargado
    function waitForChartJS() {
        if (typeof Chart !== 'undefined') {
            // Crear las gráficas solo si hay datos
            @if(isset($salesByPeriod) && $salesByPeriod->count() > 0)
                setTimeout(function() {
                    createSalesByPeriodChart();
                }, 500);
            @endif

            @if(isset($salesByDocument) && $salesByDocument->count() > 0)
                setTimeout(function() {
                    createSalesByDocumentChart();
                }, 500);
            @endif
        } else {
            setTimeout(waitForChartJS, 100);
        }
    }

    // Iniciar la carga de gráficas cuando la página esté completamente cargada
    $(window).on('load', function() {
        setTimeout(waitForChartJS, 100);
    });

    // Limpiar gráficas cuando se cierre la página
    $(window).on('beforeunload', function() {
        if (salesByPeriodChart) {
            salesByPeriodChart.destroy();
            salesByPeriodChart = null;
        }
        if (salesByDocumentChart) {
            salesByDocumentChart.destroy();
            salesByDocumentChart = null;
        }
    });
});
</script>
@endsection

