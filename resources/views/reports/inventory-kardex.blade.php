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
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
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

    // Inicializar DataTable si hay resultados
    @if(isset($movements) && count($movements) > 0)
    $('#kardexTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bx bx-file me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'Kardex - {{ isset($product) ? $product->name : "" }}'
            },
            {
                extend: 'pdf',
                text: '<i class="bx bxs-file-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Kardex - {{ isset($product) ? $product->name : "" }}',
                orientation: 'landscape'
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i> Imprimir',
                className: 'btn btn-info btn-sm',
                title: 'Kardex - {{ isset($product) ? $product->name : "" }}'
            }
        ],
        order: [[0, 'asc']], // Ordenar por fecha
        pageLength: 50,
        responsive: false,
        scrollX: true
    });
    @endif

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

@section('title', 'Kardex de Inventario')

@section('content')
<style>
    .kardex-header {
        background: linear-gradient(135deg, #047857 0%, #065f46 100%);
        color: white;
        padding: 25px;
        border-radius: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .kardex-header h3 {
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        color: white !important;
        font-size: 1.75rem;
    }
    .kardex-header p {
        color: white !important;
        font-size: 1rem;
        opacity: 0.95;
    }
    .kardex-header h5 {
        color: rgba(255, 255, 255, 0.9) !important;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .kardex-table {
        font-size: 0.875rem;
        border-collapse: collapse;
    }
    .kardex-table th {
        background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #cbd5e1;
        padding: 12px 8px;
        color: #1e293b;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .kardex-table td {
        border: 1px solid #e2e8f0;
        padding: 10px 8px;
        vertical-align: middle;
    }
    .kardex-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .entry-cell {
        background-color: #d1fae5;
        font-weight: 600;
        color: #065f46;
    }
    .exit-cell {
        background-color: #fee2e2;
        font-weight: 600;
        color: #991b1b;
    }
    .balance-cell {
        background-color: #d1fae5;
        font-weight: 700;
        color: #047857;
    }
    .negative-balance {
        background-color: #fecaca !important;
        color: #991b1b !important;
        font-weight: 700;
    }
    .product-info-box {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-left: 5px solid #059669;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .product-info-box strong {
        color: #047857;
    }
    .stats-card {
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .kardex-table {
            font-size: 0.7rem;
        }
        .kardex-header {
            background: #047857 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Kardex de Inventario
</h4>

<!-- Filtros de búsqueda -->
<div class="card mb-4 no-print">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bx bx-filter-alt me-2"></i>Buscar Kardex de Producto
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.inventory-kardex.search') }}" id="kardexForm">
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
                    <label for="product_id" class="form-label">Producto <span class="text-danger">*</span></label>
                    <select class="form-select select2" name="product_id" id="product_id" required>
                        <option value="">Seleccionar producto</option>
                        @if(isset($products))
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" {{ (isset($filters['product_id']) && $filters['product_id'] == $prod->id) ? 'selected' : '' }}>
                                    {{ $prod->code ? '['.$prod->code.'] ' : '' }}{{ $prod->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_from" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="date_from" id="date_from" 
                           value="{{ isset($filters['date_from']) ? $filters['date_from'] : '' }}">
                </div>

                <div class="col-md-2">
                    <label for="date_to" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="date_to" id="date_to" 
                           value="{{ isset($filters['date_to']) ? $filters['date_to'] : '' }}">
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search-alt me-1"></i> Generar Kardex
                    </button>
                    <a href="{{ route('report.inventory-movements') }}" class="btn btn-label-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Volver a Resumen
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($product))
<!-- Información del Producto -->
<div class="card mb-4">
    <div class="kardex-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-0" style="color: white !important; font-weight: 700;">KARDEX DE INVENTARIO</h3>
                <p class="mb-0 mt-2" style="color: white !important; font-size: 1rem;">{{ $heading->name ?? 'Agroservicio Milagro de Dios' }}</p>
            </div>
            <div class="col-md-4 text-end">
                <h5 class="mb-0" style="color: rgba(255, 255, 255, 0.9) !important;">Fecha: {{ date('d/m/Y') }}</h5>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="product-info-box">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Producto:</strong> {{ $product->name }}</p>
                            <p class="mb-2"><strong>Código:</strong> {{ $product->code ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Proveedor:</strong> {{ $product->provider->razonsocial ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Categoría:</strong> {{ ucfirst($product->type ?? 'N/A') }}</p>
                            <p class="mb-2"><strong>Marca:</strong> {{ $product->marca->name ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Estado:</strong> 
                                @if($product->state)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    <div class="card-body text-center py-4">
                        <h6 class="text-white fw-bold mb-2" style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem;">Stock Actual</h6>
                        <h1 class="text-white mb-1 fw-bold" style="font-size: 2.5rem;">{{ number_format($stats['current_stock'], 2) }}</h1>
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                            <small class="text-white opacity-75" style="font-size: 0.8rem;">Mínimo: {{ number_format($stats['minimum_stock'], 2) }}</small>
                        </div>
                        @if($stats['current_stock'] < $stats['minimum_stock'] && $stats['current_stock'] >= 0)
                            <span class="badge mt-2" style="background-color: #f59e0b; padding: 6px 12px;">
                                <i class="bx bx-error-circle"></i> Bajo Stock
                            </span>
                        @endif
                        @if($stats['current_stock'] < 0)
                            <span class="badge mt-2" style="background-color: #dc2626; padding: 6px 12px;">
                                <i class="bx bx-x-circle"></i> Stock Negativo
                            </span>
                        @endif
                        @if($stats['current_stock'] >= $stats['minimum_stock'])
                            <span class="badge mt-2" style="background-color: #10b981; padding: 6px 12px;">
                                <i class="bx bx-check-circle"></i> Stock Normal
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card border-0" style="box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3" style="width: 48px; height: 48px;">
                        <span class="avatar-initial rounded" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                            <i class="bx bx-trending-up fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Entradas</small>
                        <h4 class="mb-0 fw-bold" style="color: #059669;">{{ number_format($stats['total_entries'], 2) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">{{ $stats['purchases_count'] }} compras</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card border-0" style="box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3" style="width: 48px; height: 48px;">
                        <span class="avatar-initial rounded" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                            <i class="bx bx-trending-down fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Salidas</small>
                        <h4 class="mb-0 fw-bold" style="color: #dc2626;">{{ number_format($stats['total_exits'], 2) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">{{ $stats['sales_count'] }} ventas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card border-0" style="box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3" style="width: 48px; height: 48px;">
                        <span class="avatar-initial rounded" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                            <i class="bx bx-calculator fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Balance Calculado</small>
                        <h4 class="mb-0 fw-bold" style="color: #2563eb;">{{ number_format($stats['calculated_balance'], 2) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">Entradas - Salidas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stats-card border-0" style="box-shadow: 0 2px 6px rgba(0,0,0,0.08); {{ abs($stats['difference']) > 0.01 ? 'border-left: 4px solid #f59e0b;' : 'border-left: 4px solid #10b981;' }}">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar flex-shrink-0 me-3" style="width: 48px; height: 48px;">
                        <span class="avatar-initial rounded" style="background: linear-gradient(135deg, {{ abs($stats['difference']) > 0.01 ? '#f59e0b' : '#10b981' }} 0%, {{ abs($stats['difference']) > 0.01 ? '#d97706' : '#059669' }} 100%); color: white;">
                            <i class="bx {{ abs($stats['difference']) > 0.01 ? 'bx-error-circle' : 'bx-check-circle' }} fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Diferencia</small>
                        <h4 class="mb-0 fw-bold" style="color: {{ abs($stats['difference']) > 0.01 ? '#d97706' : '#059669' }};">
                            {{ number_format($stats['difference'], 2) }}
                        </h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            @if(abs($stats['difference']) > 0.01)
                                ⚠️ Revisar inventario
                            @else
                                ✅ Correcto
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla Kardex -->
<div class="card border-0" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 2px solid #cbd5e1;">
        <h5 class="card-title mb-0 fw-bold" style="color: #1e293b;">
            <i class="bx bx-list-ul me-2" style="color: #059669;"></i>Movimientos Detallados 
            <span class="badge ms-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 4px 10px;">
                {{ $stats['total_movements'] }} registros
            </span>
        </h5>
        @if(isset($filters['date_from']) || isset($filters['date_to']))
        <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 6px 12px; font-size: 0.8rem;">
            <i class="bx bx-calendar me-1"></i>
            {{ isset($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Inicio' }}
            -
            {{ isset($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Hoy' }}
        </span>
        @endif
    </div>
    <div class="card-body">
        @if(count($movements) > 0)
        <div class="table-responsive">
            <table class="table table-bordered kardex-table" id="kardexTable">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 80px;">Fecha</th>
                        <th colspan="2" class="text-center" style="background-color: #e9ecef;">DETALLE</th>
                        <th colspan="1" class="text-center entry-cell">ENTRADAS</th>
                        <th colspan="1" class="text-center exit-cell">SALIDAS</th>
                        <th colspan="1" class="text-center balance-cell">SALDO</th>
                    </tr>
                    <tr>
                        <th style="width: 120px;">Tipo</th>
                        <th>Documento</th>
                        <th class="text-center entry-cell" style="width: 100px;">Cantidad</th>
                        <th class="text-center exit-cell" style="width: 100px;">Cantidad</th>
                        <th class="text-center balance-cell" style="width: 100px;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $movement)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                        <td>
                            @if($movement['type'] == 'COMPRA')
                                <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 6px 10px; font-size: 0.7rem; font-weight: 600;">
                                    <i class="bx bx-plus-circle"></i> {{ $movement['type'] }}
                                </span>
                            @else
                                <span class="badge" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 6px 10px; font-size: 0.7rem; font-weight: 600;">
                                    <i class="bx bx-minus-circle"></i> {{ $movement['type'] }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $movement['document'] }}</td>
                        <td class="text-end entry-cell">
                            @if($movement['entry_quantity'] > 0)
                                {{ number_format($movement['entry_quantity'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end exit-cell">
                            @if($movement['exit_quantity'] > 0)
                                {{ number_format($movement['exit_quantity'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end {{ $movement['balance'] < 0 ? 'negative-balance' : 'balance-cell' }}">
                            <strong>{{ number_format($movement['balance'], 2) }}</strong>
                            @if($movement['balance'] < 0)
                                <i class="bx bx-error-circle text-danger"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); font-weight: 700;">
                        <td colspan="3" class="text-end" style="color: #1e293b; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;">TOTALES:</td>
                        <td class="text-end entry-cell" style="font-size: 1rem;">{{ number_format($stats['total_entries'], 2) }}</td>
                        <td class="text-end exit-cell" style="font-size: 1rem;">{{ number_format($stats['total_exits'], 2) }}</td>
                        <td class="text-end balance-cell" style="font-size: 1rem;">{{ number_format($stats['calculated_balance'], 2) }}</td>
                    </tr>
                    <tr style="background: linear-gradient(180deg, #d1fae5 0%, #a7f3d0 100%); font-weight: 700;">
                        <td colspan="5" class="text-end" style="color: #047857; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;">STOCK ACTUAL EN SISTEMA:</td>
                        <td class="text-end {{ $stats['current_stock'] < 0 ? 'negative-balance' : '' }}" style="background-color: {{ $stats['current_stock'] < 0 ? '#fecaca' : '#a7f3d0' }} !important; color: {{ $stats['current_stock'] < 0 ? '#991b1b' : '#047857' }}; font-size: 1.1rem;">
                            {{ number_format($stats['current_stock'], 2) }}
                        </td>
                    </tr>
                    @if(abs($stats['difference']) > 0.01)
                    <tr style="background: linear-gradient(180deg, #fef3c7 0%, #fde68a 100%); font-weight: 700;">
                        <td colspan="5" class="text-end" style="color: #92400e; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="bx bx-error-circle me-1"></i>DIFERENCIA (ajustar):
                        </td>
                        <td class="text-end" style="background-color: #fbbf24 !important; color: #78350f; font-size: 1.1rem;">
                            {{ number_format($stats['difference'], 2) }}
                        </td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        @if(abs($stats['difference']) > 0.01)
        <div class="alert mt-3 border-0" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 5px solid #f59e0b; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <span class="avatar avatar-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                        <i class="bx bx-error-circle fs-5"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-bold" style="color: #92400e;">
                        <i class="bx bx-info-circle me-1"></i>Diferencia Detectada en el Inventario
                    </h6>
                    <p class="mb-0" style="color: #78350f; font-size: 0.9rem;">
                        Existe una diferencia de <strong class="fw-bold">{{ number_format(abs($stats['difference']), 2) }} unidades</strong> entre el balance calculado 
                        (entradas - salidas) y el stock actual en el sistema. Se recomienda revisar los movimientos y considerar un ajuste de inventario.
                    </p>
                </div>
            </div>
        </div>
        @endif

        @else
        <div class="text-center py-5">
            <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                    <i class="bx bx-package fs-1"></i>
                </span>
            </div>
            <h5 class="mb-1">No hay movimientos registrados</h5>
            <p class="text-muted">
                Este producto no tiene movimientos de compras o ventas en el periodo seleccionado.
            </p>
        </div>
        @endif
    </div>
</div>
@else
<!-- Estado inicial sin producto seleccionado -->
<div class="card border-0" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <div class="avatar avatar-xl mx-auto" style="width: 80px; height: 80px;">
                <span class="avatar-initial rounded-circle" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white;">
                    <i class="bx bx-bar-chart-alt-2 fs-1"></i>
                </span>
            </div>
        </div>
        <h4 class="mb-2 fw-bold" style="color: #1e293b;">Generar Kardex de Inventario</h4>
        <p class="text-muted mb-4" style="max-width: 500px; margin: 0 auto;">
            Selecciona una empresa y un producto para ver el detalle cronológico de movimientos.
            <br>El reporte mostrará todas las <strong>entradas</strong> (compras) y <strong>salidas</strong> (ventas) con sus saldos acumulados.
        </p>
        <div class="d-flex justify-content-center gap-3 mb-3">
            <div class="text-center">
                <div class="avatar avatar-sm mb-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <i class="bx bx-trending-up"></i>
                </div>
                <small class="text-muted d-block">Entradas</small>
            </div>
            <div class="text-center">
                <div class="avatar avatar-sm mb-2" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <i class="bx bx-trending-down"></i>
                </div>
                <small class="text-muted d-block">Salidas</small>
            </div>
            <div class="text-center">
                <div class="avatar avatar-sm mb-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white;">
                    <i class="bx bx-calculator"></i>
                </div>
                <small class="text-muted d-block">Saldos</small>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

