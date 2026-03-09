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
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
@endsection

@section('title', 'Reporte de Cuentas por Pagar')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Cuentas por Pagar
</h4>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('report.accounts-payable-search') }}" method="POST" id="searchForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Empresa <span class="text-danger">*</span></label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccione una empresa</option>
                        @foreach($companies ?? [] as $comp)
                            <option value="{{ $comp->id }}" {{ (isset($filters['company']) && $filters['company'] == $comp->id) ? 'selected' : '' }}>
                                {{ $comp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Año</label>
                    <select class="form-select" name="year" id="year">
                        @php
                            $currentYear = date('Y');
                            $selectedYear = $yearB ?? $currentYear;
                        @endphp
                        @for($i = 0; $i < 5; $i++)
                            @php $year = $currentYear - $i; @endphp
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <select class="form-select" name="period" id="period">
                        @php
                            $months = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                            $selectedMonth = $period ?? date('m');
                        @endphp
                        @foreach($months as $num => $name)
                            <option value="{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}" {{ $selectedMonth == str_pad($num, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id" id="provider_id">
                        <option value="">Todos los proveedores</option>
                        @foreach($providers ?? [] as $provider)
                            <option value="{{ $provider->id }}" {{ (isset($filters['provider_id']) && $filters['provider_id'] == $provider->id) ? 'selected' : '' }}>
                                {{ $provider->razonsocial }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado de Pago</label>
                    <select class="form-select" name="payment_status" id="payment_status">
                        <option value="">Todos</option>
                        <option value="0" {{ (isset($filters['payment_status']) && $filters['payment_status'] == '0') ? 'selected' : '' }}>Pendiente</option>
                        <option value="1" {{ (isset($filters['payment_status']) && $filters['payment_status'] == '1') ? 'selected' : '' }}>Parcial</option>
                        <option value="2" {{ (isset($filters['payment_status']) && $filters['payment_status'] == '2') ? 'selected' : '' }}>Pagado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search me-1"></i>Buscar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($purchases))
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Reporte de Cuentas por Pagar
            @if(isset($company))
                - {{ $company->name }}
            @endif
        </h5>
        @if($purchases->count() > 0)
            <form action="{{ route('report.accounts-payable-pdf') }}" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="company" value="{{ $filters['company'] ?? '' }}">
                <input type="hidden" name="year" value="{{ $filters['year'] ?? '' }}">
                <input type="hidden" name="period" value="{{ $filters['period'] ?? '' }}">
                <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                <input type="hidden" name="provider_id" value="{{ $filters['provider_id'] ?? '' }}">
                <input type="hidden" name="payment_status" value="{{ $filters['payment_status'] ?? '' }}">
                <button type="submit" class="btn btn-danger">
                    <i class="ti ti-file-pdf me-1"></i>Exportar PDF
                </button>
            </form>
        @endif
    </div>
    <div class="card-body">
        @if($purchases->count() > 0)
            <!-- Resumen de totales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Total Facturado</h6>
                            <h3 class="mb-0">$ {{ number_format($totals['total_amount'] ?? 0, 2, '.', ',') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body">
                            <h6 class="card-title text-success">Total Pagado</h6>
                            <h3 class="mb-0">$ {{ number_format($totals['total_paid'] ?? 0, 2, '.', ',') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body">
                            <h6 class="card-title text-danger">Saldo Pendiente</h6>
                            <h3 class="mb-0">$ {{ number_format($totals['total_balance'] ?? 0, 2, '.', ',') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body">
                            <h6 class="card-title text-info">Resumen</h6>
                            <p class="mb-1">Pendientes: <strong>{{ $totals['pending_count'] ?? 0 }}</strong></p>
                            <p class="mb-1">Parciales: <strong>{{ $totals['partial_count'] ?? 0 }}</strong></p>
                            <p class="mb-0">Pagadas: <strong>{{ $totals['paid_count'] ?? 0 }}</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de resultados -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Tipo Doc</th>
                            <th>Proveedor</th>
                            <th>NIT</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Último Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->number }}</td>
                                <td>{{ $purchase->formatted_date ?? \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</td>
                                <td>{{ $purchase->document_type ?? 'N/A' }}</td>
                                <td>{{ $purchase->provider_name }}</td>
                                <td>{{ $purchase->provider_nit ?? 'N/A' }}</td>
                                <td>$ {{ number_format($purchase->total, 2, '.', ',') }}</td>
                                <td>$ {{ number_format($purchase->paid_amount ?? 0, 2, '.', ',') }}</td>
                                <td>
                                    <strong class="{{ ($purchase->current_balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        $ {{ number_format($purchase->current_balance ?? 0, 2, '.', ',') }}
                                    </strong>
                                </td>
                                <td>
                                    @if($purchase->payment_status_display == 'PAGADO')
                                        <span class="badge bg-success">{{ $purchase->payment_status_display }}</span>
                                    @elseif($purchase->payment_status_display == 'PARCIAL')
                                        <span class="badge bg-warning">{{ $purchase->payment_status_display }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $purchase->payment_status_display }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($purchase->last_payment_date)
                                        {{ \Carbon\Carbon::parse($purchase->last_payment_date)->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">TOTALES:</td>
                            <td>$ {{ number_format($totals['total_amount'] ?? 0, 2, '.', ',') }}</td>
                            <td>$ {{ number_format($totals['total_paid'] ?? 0, 2, '.', ',') }}</td>
                            <td class="text-danger">$ {{ number_format($totals['total_balance'] ?? 0, 2, '.', ',') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="ti ti-info-circle me-2"></i>
                No se encontraron compras con los filtros seleccionados.
            </div>
        @endif
    </div>
</div>
@endif

@endsection

