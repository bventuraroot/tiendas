@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Precios del Producto')

@section('vendor-style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <style>
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-tags me-2"></i>
                            Precios del Producto: {{ $product->name }}
                        </h4>
                        <div>
                            <a href="{{ route('product.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPriceModal">
                                <i class="fas fa-plus me-1"></i> Agregar Precio
                            </button>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkPriceModal">
                                <i class="fas fa-layer-group me-1"></i> Precios Masivos
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Información del Producto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Información del Producto</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Código:</strong></td>
                                    <td>{{ $product->code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Categoría:</strong></td>
                                    <td>{{ $product->category ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo de Venta:</strong></td>
                                    <td>
                                        @switch($product->sale_type)
                                            @case('weight')
                                                <span class="badge bg-info">Por Peso</span>
                                                @break
                                            @case('volume')
                                                <span class="badge bg-warning">Por Volumen</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">Por Unidad</span>
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Medidas del Producto</h6>
                            <table class="table table-sm">
                                @if($product->sale_type === 'weight')
                                    <tr>
                                        <td><strong>Peso por Unidad:</strong></td>
                                        <td>{{ $product->weight_per_unit ?? 'N/A' }} libras</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Contenido:</strong></td>
                                        <td>{{ $product->content_per_unit ?? 'N/A' }}</td>
                                    </tr>
                                @elseif($product->sale_type === 'volume')
                                    <tr>
                                        <td><strong>Volumen por Unidad:</strong></td>
                                        <td>{{ $product->volume_per_unit ?? 'N/A' }} litros</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Tabla de Precios -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="pricesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Unidad</th>
                                    <th>Precio Regular</th>
                                    <th>Precio de Costo</th>
                                    <th>Precio al Por Mayor</th>
                                    <th>Precio al Detalle</th>
                                    <th>Precio Especial</th>
                                    <th>Margen (%)</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->prices as $price)
                                    <tr>
                                        <td>
                                            <strong>{{ $price->unit->unit_name }}</strong>
                                            @if($price->is_default)
                                                <span class="badge bg-success ms-1">Por Defecto</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $price->unit->unit_code }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">${{ number_format($price->price, 2) }}</span>
                                        </td>
                                        <td>
                                            @if($price->cost_price)
                                                <span class="text-success">${{ number_format($price->cost_price, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->wholesale_price)
                                                <span class="text-info">${{ number_format($price->wholesale_price, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->retail_price)
                                                <span class="text-warning">${{ number_format($price->retail_price, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->special_price)
                                                <span class="text-danger">${{ number_format($price->special_price, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->cost_price && $price->price)
                                                @php
                                                    $margin = (($price->price - $price->cost_price) / $price->cost_price) * 100;
                                                @endphp
                                                <span class="badge {{ $margin > 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ number_format($margin, 1) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($price->is_active)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick="editPrice({{ $price->id }})"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="deletePrice({{ $price->id }})"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No hay precios configurados para este producto
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear Precio -->
@include('products.prices.partials.create-modal')

<!-- Modal para Precios Masivos -->
@include('products.prices.partials.bulk-modal')

<!-- Modal para Editar Precio -->
@include('products.prices.partials.edit-modal')

@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons-bootstrap5.js') }}"></script>
@endsection

@section('page-script')
<script>
// Variables globales
let currentPriceId = null;

// Función para crear precio
function createPrice() {
    const form = document.getElementById('createPriceForm');
    const formData = new FormData(form);

    fetch('{{ route("product.prices.store", $product->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('error', 'Error al crear el precio');
            console.error(data.errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
    });
}

// Función para editar precio
function editPrice(priceId) {
    currentPriceId = priceId;

    fetch(`{{ route("product.prices.edit", ["productId" => $product->id, "priceId" => ":priceId"]) }}`.replace(':priceId', priceId))
    .then(response => response.text())
    .then(html => {
        document.getElementById('editModalContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('editPriceModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error al cargar el precio');
    });
}

// Función para actualizar precio
function updatePrice() {
    const form = document.getElementById('editPriceForm');
    const formData = new FormData(form);

    fetch(`{{ route("product.prices.update", ["productId" => $product->id, "priceId" => ":priceId"]) }}`.replace(':priceId', currentPriceId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-HTTP-Method-Override': 'PUT'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('error', 'Error al actualizar el precio');
            console.error(data.errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
    });
}

// Función para eliminar precio
function deletePrice(priceId) {
    if (confirm('¿Está seguro de que desea eliminar este precio?')) {
        fetch(`{{ route("product.prices.destroy", ["productId" => $product->id, "priceId" => ":priceId"]) }}`.replace(':priceId', priceId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('error', 'Error al eliminar el precio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error de conexión');
        });
    }
}

// Función para crear precios masivos
function createBulkPrices() {
    const form = document.getElementById('bulkPriceForm');
    const formData = new FormData(form);

    fetch('{{ route("product.prices.bulk", $product->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('error', 'Error al crear los precios');
            console.error(data.errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
    });
}

// Función para mostrar alertas
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.table-responsive'));

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // DataTable para la tabla de precios
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#pricesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
});
</script>
@endsection
