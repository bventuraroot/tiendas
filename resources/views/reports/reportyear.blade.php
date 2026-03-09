@php
$configData = Helper::appClasses();
$meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endsection

@section('title', 'Reporte Anual')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Reporte Anual Consolidado
</h4>
<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.yearsearch') }}" id="yearReportForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="company" class="form-label">Empresa <span class="text-danger">*</span></label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                        @foreach(App\Models\Company::select('id','name')->orderBy('name')->get() as $company)
                            <option value="{{ $company->id }}" {{ (isset($heading) && $heading->id == $company->id) ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Año</label>
                    <select class="form-select" name="year" id="year">
                        @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                            <option value="{{ $i }}" {{ (isset($yearB) && $yearB == $i) ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="button" class="btn btn-success" onclick="exportToExcel()" {{ !isset($heading) ? 'disabled' : '' }}>
                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Debug info (temporal) -->
@if(isset($heading))
<div class="alert alert-info">
    <h6>Debug Info:</h6>
    <p><strong>Total Ventas:</strong> {{ $totalventas ?? 0 }}</p>
    <p><strong>Total Compras:</strong> {{ $totalcompras ?? 0 }}</p>
    <p><strong>Registros de Ventas:</strong> {{ count($sales ?? []) }}</p>
    <p><strong>Registros de Compras:</strong> {{ count($purchases ?? []) }}</p>
    @if(isset($sales) && count($sales) > 0)
        <p><strong>Primera venta:</strong> {{ $sales->first()->toJson() }}</p>
    @endif
    @if(isset($purchases) && count($purchases) > 0)
        <p><strong>Primera compra:</strong> {{ $purchases->first()->toJson() }}</p>
    @endif
</div>
@endif

<!-- Resultados del reporte -->
@if(isset($heading))

<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Ventas (sin IVA)</h6>
                <h3 class="mb-0">${{ number_format($totalventas ?? 0, 2) }}</h3>
                <small>Año {{ $yearB ?? date('Y') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Compras Gravadas</h6>
                <h3 class="mb-0">${{ number_format($totalcompras ?? 0, 2) }}</h3>
                <small>Año {{ $yearB ?? date('Y') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Diferencia</h6>
                <h3 class="mb-0">${{ number_format($totaldiferencia ?? 0, 2) }}</h3>
                <small>Ventas Gravadas - Compras Gravadas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Margen</h6>
                <h3 class="mb-0">{{ $totalventas > 0 ? number_format((($totalventas - $totalcompras) / $totalventas) * 100, 1) : 0 }}%</h3>
                <small>Porcentaje de ganancia</small>
            </div>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Comparación Mensual</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="monthlyComparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Distribución Anual</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="annualDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de datos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-table me-2"></i>Consolidado Anual - {{ $heading->name }}
        </h5>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="printReport()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm" id="annualReportTable">
                <thead class="table-dark">
                    <tr>
                        <th>Mes</th>
                        <th class="text-end">Ventas Gravadas</th>
                        <th class="text-end">IVA Débito</th>
                        <th class="text-end">Total Ventas (sin IVA)</th>
                        <th class="text-end">Compras Gravadas</th>
                        <th class="text-end">IVA Crédito</th>
                        <th class="text-end">Total Compras Gravadas</th>
                        <th class="text-end">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalgravadas = 0;
                        $totaldebito = 0;
                        $totalventas = 0;
                        $totalinternas = 0;
                        $totalcredito = 0;
                        $totalcompras = 0;
                        $totaldiferencia = 0;
                        $monthlyData = [];
                    @endphp
                    @for ($i = 1; $i <= 12; $i++)
                        <tr>
                            <td><strong>{{ $meses[$i-1] }}</strong></td>
                            @php
                                $ventasMes = 0;
                                $comprasMes = 0;
                                $gravadasMes = 0;
                                $debitoMes = 0;
                                $internasMes = 0;
                                $creditoMes = 0;

                                $ventasEncontrados = $sales->filter(function($sale) use ($i) {
                                    return $sale->monthsale == $i;
                                });
                            @endphp

                            @if (!$ventasEncontrados->isEmpty())
                                @foreach ($ventasEncontrados as $sale)
                                    <td class="text-end">${{ number_format($sale->GRAVADAS, 2) }}</td>
                                    <td class="text-end">${{ number_format($sale->DEBITO, 2) }}</td>
                                    <td class="text-end"><strong>${{ number_format($sale->TOTALV, 2) }}</strong></td>
                                    @php
                                        $totalgravadas += $sale->GRAVADAS;
                                        $totaldebito += $sale->DEBITO;
                                        $totalventas += $sale->TOTALV;
                                        $ventasMes += $sale->TOTALV;
                                        $gravadasMes += $sale->GRAVADAS;
                                        $debitoMes += $sale->DEBITO;
                                    @endphp
                                @endforeach
                            @else
                                <td class="text-end">$0.00</td>
                                <td class="text-end">$0.00</td>
                                <td class="text-end"><strong>$0.00</strong></td>
                            @endif

                            @php
                                $comprasEncontradas = $purchases->filter(function($purchase) use ($i) {
                                    return $purchase->monthpurchase == $i;
                                });
                            @endphp

                            @if (!$comprasEncontradas->isEmpty())
                                @foreach ($comprasEncontradas as $purchase)
                                    <td class="text-end">${{ number_format($purchase->INTERNASPU, 2) }}</td>
                                    <td class="text-end">${{ number_format($purchase->CREDITOPU, 2) }}</td>
                                    <td class="text-end"><strong>${{ number_format($purchase->TOTALC, 2) }}</strong></td>
                                    @php
                                        $totalinternas += $purchase->INTERNASPU;
                                        $totalcredito += $purchase->CREDITOPU;
                                        $totalcompras += $purchase->TOTALC;
                                        $comprasMes += $purchase->TOTALC;
                                        $internasMes += $purchase->INTERNASPU;
                                        $creditoMes += $purchase->CREDITOPU;
                                    @endphp
                                @endforeach
                            @else
                                <td class="text-end">$0.00</td>
                                <td class="text-end">$0.00</td>
                                <td class="text-end"><strong>$0.00</strong></td>
                            @endif

                            <td class="text-end">
                                @php
                                    $diferenciaMes = $ventasMes - $comprasMes;
                                    $totaldiferencia += $diferenciaMes;
                                @endphp
                                @if($diferenciaMes > 0)
                                    <span class="text-success"><strong>${{ number_format($diferenciaMes, 2) }}</strong></span>
                                @elseif($diferenciaMes < 0)
                                    <span class="text-danger"><strong>${{ number_format($diferenciaMes, 2) }}</strong></span>
                                @else
                                    <span class="text-muted">$0.00</span>
                                @endif
                            </td>

                            @php
                                $monthlyData[] = [
                                    'month' => $meses[$i-1],
                                    'sales' => $ventasMes,
                                    'purchases' => $comprasMes,
                                    'difference' => $diferenciaMes
                                ];
                            @endphp
                        </tr>
                    @endfor
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th><strong>TOTALES</strong></th>
                        <th class="text-end"><strong>${{ number_format($totalgravadas, 2) }}</strong></th>
                        <th class="text-end"><strong>${{ number_format($totaldebito, 2) }}</strong></th>
                        <th class="text-end"><strong>${{ number_format($totalventas, 2) }}</strong></th>
                        <th class="text-end"><strong>${{ number_format($totalinternas, 2) }}</strong></th>
                        <th class="text-end"><strong>${{ number_format($totalcredito, 2) }}</strong></th>
                        <th class="text-end"><strong>${{ number_format($totalcompras, 2) }}</strong></th>
                        <th class="text-end">
                            @if($totaldiferencia > 0)
                                <span class="text-success"><strong>${{ number_format($totaldiferencia, 2) }}</strong></span>
                            @elseif($totaldiferencia < 0)
                                <span class="text-danger"><strong>${{ number_format($totaldiferencia, 2) }}</strong></span>
                            @else
                                <span class="text-muted"><strong>$0.00</strong></span>
                            @endif
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@elseif(Request::isMethod('post'))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay datos para los filtros seleccionados.</p>
    </div>
</div>
@endif

@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    if ($('#annualReportTable').length) {
        $('#annualReportTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            paging: false,
            searching: false,
            info: false,
            columnDefs: [
                { className: "text-center", targets: [0] },
                { className: "text-end", targets: [1, 2, 3, 4, 5, 6, 7] }
            ]
        });
    }

    // Crear gráficas si hay datos
    @if(isset($heading))
        createMonthlyComparisonChart();
        createAnnualDistributionChart();
    @endif
});

// Función para crear gráfica de comparación mensual
function createMonthlyComparisonChart() {
    var ctx = document.getElementById('monthlyComparisonChart');
    if (ctx) {
        var monthlyData = @json($monthlyData ?? []);

        if (monthlyData.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(function(m) { return m.month; }),
                    datasets: [{
                        label: 'Ventas',
                        data: monthlyData.map(function(m) { return m.sales; }),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Compras',
                        data: monthlyData.map(function(m) { return m.purchases; }),
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

// Función para crear gráfica de distribución anual
function createAnnualDistributionChart() {
    var ctx = document.getElementById('annualDistributionChart');
    if (ctx) {
        var totalSales = {{ $totalventas ?? 0 }};
        var totalPurchases = {{ $totalcompras ?? 0 }};

        if (totalSales > 0 || totalPurchases > 0) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Ventas', 'Compras'],
                    datasets: [{
                        data: [totalSales, totalPurchases],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
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
        }
    }
}

// Función para exportar a Excel
function exportToExcel() {
    // Implementar exportación a Excel
    alert('Función de exportación a Excel será implementada');
}

// Función para imprimir reporte
function printReport() {
    window.print();
}
</script>
@endsection
