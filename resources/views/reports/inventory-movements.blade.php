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
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    // Inicializar DatePicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        language: 'es'
    });

    // Inicializar DataTable si hay resultados
    @if(isset($movements) && count($movements) > 0)
    $('#movementsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bx bx-file me-1"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="bx bxs-file-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i> Imprimir',
                className: 'btn btn-info btn-sm'
            }
        ],
        order: [[6, 'desc']], // Ordenar por diferencia descendente
        pageLength: 25,
        responsive: true,
        columnDefs: [
            { targets: -1, orderable: false } // Deshabilitar ordenamiento en columna de acciones
        ]
    });
    @endif

    // Expandir/colapsar detalles
    $('.btn-toggle-details').click(function() {
        const target = $(this).data('target');
        $(target).collapse('toggle');
        
        const icon = $(this).find('i');
        if (icon.hasClass('bx-chevron-down')) {
            icon.removeClass('bx-chevron-down').addClass('bx-chevron-up');
        } else {
            icon.removeClass('bx-chevron-up').addClass('bx-chevron-down');
        }
    });

    // Cargar empresas
    loadCompanies();

    function loadCompanies() {
        $.ajax({
            url: '/getcompanies',
            type: 'GET',
            success: function(data) {
                var select = $('#company');
                select.empty();
                select.append('<option value="">Seleccionar empresa</option>');
                data.forEach(function(company) {
                    var selected = "{{ isset($filters['company']) ? $filters['company'] : '' }}" == company.id ? 'selected' : '';
                    select.append('<option value="' + company.id + '" ' + selected + '>' + company.name + '</option>');
                });
            }
        });
    }
});
</script>
@endsection

@section('title', 'Reporte de Movimientos de Inventario')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Movimientos de Inventario
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bx bx-filter-alt me-2"></i>Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.inventory-movements-search') }}" id="searchForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="company" class="form-label">Empresa <span class="text-danger">*</span></label>
                    <select class="form-select select2" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                        @if(isset($companies))
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (isset($filters['company']) && $filters['company'] == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="provider_id" class="form-label">Proveedor</label>
                    <select class="form-select select2" name="provider_id" id="provider_id">
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

                <div class="col-md-4">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-select select2" name="category" id="category">
                        <option value="">Todas las categorías</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ (isset($filters['category']) && $filters['category'] == $category) ? 'selected' : '' }}>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="product_id" class="form-label">Producto Específico</label>
                    <select class="form-select select2" name="product_id" id="product_id">
                        <option value="">Todos los productos</option>
                        @if(isset($allProducts))
                            @foreach($allProducts as $product)
                                <option value="{{ $product->id }}" {{ (isset($filters['product_id']) && $filters['product_id'] == $product->id) ? 'selected' : '' }}>
                                    {{ $product->code ? '['.$product->code.'] ' : '' }}{{ $product->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date_from" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="date_from" id="date_from" 
                           value="{{ isset($filters['date_from']) ? $filters['date_from'] : '' }}">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="date_to" id="date_to" 
                           value="{{ isset($filters['date_to']) ? $filters['date_to'] : '' }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_negative_first" id="show_negative_first" value="1"
                               {{ (isset($filters['show_negative_first']) && $filters['show_negative_first']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_negative_first">
                            Negativos Primero
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search-alt me-1"></i> Buscar
                    </button>
                    <button type="reset" class="btn btn-label-secondary">
                        <i class="bx bx-reset me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($movements))
<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-package fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Productos</small>
                        <h5 class="mb-0">{{ $stats['total_products'] }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="bx bx-error fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Stock Negativo</small>
                        <h5 class="mb-0 text-danger">{{ $stats['products_with_negative_stock'] }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="bx bx-cart fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Compras</small>
                        <h6 class="mb-0">{{ number_format($stats['total_purchases_value'], 2) }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="bx bx-trending-up fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Ventas</small>
                        <h6 class="mb-0">{{ number_format($stats['total_sales_value'], 2) }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de movimientos -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bx bx-list-ul me-2"></i>Movimientos de Inventario
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
    </div>
    <div class="card-body">
        @if(count($movements) > 0)
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="movementsTable">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Categoría</th>
                        <th class="text-center">Compras</th>
                        <th class="text-center">Ventas</th>
                        <th class="text-center">Balance</th>
                        <th class="text-center">Stock Actual</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $index => $movement)
                    <tr class="{{ $movement['is_negative'] ? 'table-danger' : '' }}">
                        <td>{{ $movement['product_code'] ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $movement['product_name'] }}</strong>
                        </td>
                        <td>{{ $movement['provider_name'] }}</td>
                        <td>
                            <span class="badge bg-label-secondary">{{ ucfirst($movement['category'] ?? 'N/A') }}</span>
                        </td>
                        <td class="text-center text-success">
                            <strong>{{ number_format($movement['total_purchases'], 2) }}</strong>
                            <br>
                            <small class="text-muted">({{ $movement['purchases_count'] }} movs.)</small>
                        </td>
                        <td class="text-center text-info">
                            <strong>{{ number_format($movement['total_sales'], 2) }}</strong>
                            <br>
                            <small class="text-muted">({{ $movement['sales_count'] }} movs.)</small>
                        </td>
                        <td class="text-center">
                            <strong>{{ number_format($movement['balance'], 2) }}</strong>
                        </td>
                        <td class="text-center {{ $movement['is_negative'] ? 'text-danger fw-bold' : '' }}">
                            {{ number_format($movement['current_stock'], 2) }}
                        </td>
                        <td class="text-center {{ abs($movement['difference']) > 0.01 ? 'text-warning fw-bold' : '' }}">
                            {{ number_format($movement['difference'], 2) }}
                        </td>
                        <td class="text-center">
                            @if($movement['is_negative'])
                                <span class="badge bg-danger">
                                    <i class="bx bx-error-circle"></i> Negativo
                                </span>
                            @elseif(abs($movement['difference']) > 0.01)
                                <span class="badge bg-warning">
                                    <i class="bx bx-error"></i> Diferencia
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="bx bx-check-circle"></i> OK
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-toggle-details" 
                                        data-target="#details-{{ $index }}"
                                        title="Ver detalles">
                                    <i class="bx bx-chevron-down"></i>
                                </button>
                                <form method="POST" action="{{ route('report.inventory-kardex.search') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="company" value="{{ isset($filters['company']) ? $filters['company'] : '' }}">
                                    <input type="hidden" name="product_id" value="{{ $movement['product_id'] }}">
                                    @if(isset($filters['date_from']))
                                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                                    @endif
                                    @if(isset($filters['date_to']))
                                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                                    @endif
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Ver Kardex">
                                        <i class="bx bx-clipboard"></i> Kardex
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="11" class="p-0">
                            <div class="collapse" id="details-{{ $index }}">
                                <div class="card card-body m-3">
                                    <div class="row">
                                        <!-- Detalles de Compras -->
                                        <div class="col-md-6">
                                            <h6 class="text-success">
                                                <i class="bx bx-cart me-1"></i>Compras ({{ $movement['purchases_count'] }})
                                            </h6>
                                            @if($movement['purchases_count'] > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Documento</th>
                                                            <th class="text-end">Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($movement['purchases'] as $purchase)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</td>
                                                            <td>{{ $purchase->purchase_doc }}</td>
                                                            <td class="text-end">{{ number_format($purchase->quantity, 2) }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <p class="text-muted small">No hay compras registradas</p>
                                            @endif
                                        </div>

                                        <!-- Detalles de Ventas -->
                                        <div class="col-md-6">
                                            <h6 class="text-info">
                                                <i class="bx bx-trending-up me-1"></i>Ventas ({{ $movement['sales_count'] }})
                                            </h6>
                                            @if($movement['sales_count'] > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Documento</th>
                                                            <th class="text-end">Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($movement['sales'] as $sale)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}</td>
                                                            <td>{{ $sale->sale_doc }}</td>
                                                            <td class="text-end">{{ number_format($sale->base_quantity_used ?? $sale->amountp, 2) }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                            <p class="text-muted small">No hay ventas registradas</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Resumen -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                <strong>Análisis:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Total de entradas (compras): <strong>{{ number_format($movement['total_purchases'], 2) }}</strong></li>
                                                    <li>Total de salidas (ventas): <strong>{{ number_format($movement['total_sales'], 2) }}</strong></li>
                                                    <li>Balance calculado: <strong>{{ number_format($movement['balance'], 2) }}</strong></li>
                                                    <li>Stock actual en sistema: <strong>{{ number_format($movement['current_stock'], 2) }}</strong></li>
                                                    <li>Diferencia: <strong class="{{ abs($movement['difference']) > 0.01 ? 'text-warning' : 'text-success' }}">{{ number_format($movement['difference'], 2) }}</strong>
                                                        @if(abs($movement['difference']) > 0.01)
                                                            <span class="text-warning">(Posible error de inventario)</span>
                                                        @else
                                                            <span class="text-success">(Inventario correcto)</span>
                                                        @endif
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                    <i class="bx bx-package fs-1"></i>
                </span>
            </div>
            <h5 class="mb-1">No se encontraron movimientos</h5>
            <p class="text-muted">
                Selecciona una empresa y configura los filtros para ver el reporte de movimientos de inventario.
            </p>
        </div>
        @endif
    </div>
</div>
@endif

@endsection



