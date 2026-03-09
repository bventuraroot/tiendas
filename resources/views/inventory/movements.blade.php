@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Historial de Movimientos - ' . ($inventory->name ?: $inventory->product->name ?? 'Producto'))

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}">
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function () {
    const productId = {{ $inventory->product_id }};

    const table = $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/inve/movements-data/' + productId,
            data: function (d) {
                d.type      = $('#filterType').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to   = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'fecha',        title: 'Fecha', width: '130px' },
            { data: 'tipo_label',   title: 'Tipo', orderable: false },
            { data: 'cambio_base',  title: 'Cambio (base)', className: 'text-end', orderable: false },
            { data: 'stock_despues',title: 'Stock tras mov.', className: 'text-end', orderable: false },
            { data: 'referencia',   title: 'Referencia', orderable: false },
            { data: 'usuario',      title: 'Usuario', orderable: false },
            { data: 'notas',        title: 'Notas', orderable: false },
        ],
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bx bx-file me-1"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i> Imprimir',
                className: 'btn btn-info btn-sm'
            }
        ],
        pageLength: 25,
        responsive: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }
    });

    // Filtros
    $('#btnFilter').on('click', function () {
        table.ajax.reload();
    });

    $('#btnClearFilter').on('click', function () {
        $('#filterType').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        table.ajax.reload();
    });

    // Flatpickr para fechas
    flatpickr('#filterDateFrom', { dateFormat: 'Y-m-d', locale: 'es' });
    flatpickr('#filterDateTo',   { dateFormat: 'Y-m-d', locale: 'es' });
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Inventario /</span> Historial de Movimientos
</h4>

<!-- Info del producto -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar flex-shrink-0">
                <span class="avatar-initial rounded bg-label-primary">
                    <i class="bx bx-package fs-4"></i>
                </span>
            </div>
            <div>
                <h5 class="mb-0">{{ $inventory->name ?: ($inventory->product->name ?? 'N/A') }}</h5>
                <small class="text-muted">
                    Código: <strong>{{ $inventory->product->code ?? 'N/A' }}</strong>
                    &nbsp;|&nbsp;
                    Stock actual:
                    <strong class="{{ (float)$inventory->base_quantity < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format((float)$inventory->base_quantity, 4) }}
                        {{ $inventory->baseUnit->unit_name ?? 'uds' }}
                    </strong>
                    &nbsp;|&nbsp;
                    Stock mínimo: <strong>{{ $inventory->minimum_stock }}</strong>
                </small>
            </div>
            <div class="ms-auto">
                <a href="{{ url('/inventory') }}" class="btn btn-label-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Volver al inventario
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bx bx-filter-alt me-2"></i>Filtros</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tipo de movimiento</label>
                <select id="filterType" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="entrada_inicial">Entrada Inicial</option>
                    <option value="compra">Compra</option>
                    <option value="ajuste_manual">Ajuste Manual</option>
                    <option value="venta">Venta</option>
                    <option value="anulacion_compra">Anulación Compra</option>
                    <option value="anulacion_venta">Anulación Venta</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha desde</label>
                <input type="text" id="filterDateFrom" class="form-control form-control-sm" placeholder="aaaa-mm-dd">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha hasta</label>
                <input type="text" id="filterDateTo" class="form-control form-control-sm" placeholder="aaaa-mm-dd">
            </div>
            <div class="col-md-3">
                <button id="btnFilter" class="btn btn-primary btn-sm me-2">
                    <i class="bx bx-search me-1"></i>Filtrar
                </button>
                <button id="btnClearFilter" class="btn btn-label-secondary btn-sm">
                    <i class="bx bx-reset me-1"></i>Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de movimientos -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="bx bx-list-ul me-2"></i>Historial de Movimientos
        </h5>
        <span class="badge bg-label-secondary" id="totalMovements"></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="movementsTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="text-end">Cambio (base)</th>
                        <th class="text-end">Stock tras mov.</th>
                        <th>Referencia</th>
                        <th>Usuario</th>
                        <th>Notas</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
