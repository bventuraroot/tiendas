@extends('layouts.app')

@section('title', 'Nueva Compra')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Nueva Compra</h4>
                </div>
                <div class="card-body">
                    <form id="purchaseForm" method="POST" action="{{ route('purchase.store') }}">
                        @csrf

                        <!-- Información General -->
                        <div class="mb-4 row">
                            <div class="col-md-3">
                                <label for="number" class="form-label">Número de Comprobante *</label>
                                <input type="text" class="form-control" id="number" name="number" required>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label">Fecha de Compra *</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="document" class="form-label">Tipo de Documento *</label>
                                <select class="form-select" id="document" name="document" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="6">FACTURA</option>
                                    <option value="3">COMPROBANTE DE CREDITO FISCAL</option>
                                    <option value="9">NOTA DE CREDITO</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="period" class="form-label">Período *</label>
                                <select class="form-select" id="period" name="period" required>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $i == date('n') ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 row">
                            <div class="col-md-6">
                                <label for="provider" class="form-label">Proveedor *</label>
                                <select class="form-select" id="provider" name="provider" required>
                                    <option value="">Seleccionar proveedor...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="company" class="form-label">Empresa *</label>
                                <select class="form-select" id="company" name="company" required>
                                    <option value="">Seleccionar empresa...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Productos -->
                        <div class="mb-4 row">
                            <div class="col-12">
                                <h5>Productos</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="productsTable">
                                        <thead>
                                            <tr>
                                                <th>Producto *</th>
                                                <th>Unidad de Medida *</th>
                                                <th>Cantidad *</th>
                                                <th>Precio Unitario *</th>
                                                <th>Subtotal</th>
                                                <th>IVA (13%)</th>
                                                <th>Total</th>
                                                <th>Fecha Caducidad</th>
                                                <th>Lote</th>
                                                <th>Notas</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productsTableBody">
                                            <!-- Los productos se agregarán dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-primary" id="addProductBtn">
                                    <i class="ti ti-plus"></i> Agregar Producto
                                </button>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="mb-4 row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-end"><span id="subtotal">$0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>IVA (13%):</strong></td>
                                        <td class="text-end"><span id="totalIva">$0.00</span></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td><strong>TOTAL:</strong></td>
                                        <td class="text-end"><span id="totalAmount">$0.00</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-save"></i> Guardar Compra
                                </button>
                                <a href="{{ route('purchase.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-x"></i> Cancelar
                                </a>
                            </div>
                        </div>

                        <!-- Campos ocultos -->
                        <input type="hidden" name="iduser" value="{{ auth()->id() }}">
                        <input type="hidden" name="details" id="detailsInput">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleccionar producto -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="productSearch" placeholder="Buscar producto...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="productSelectionTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Proveedor</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Productos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script src="{{ asset('assets/js/app-purchase-list.js') }}"></script> 
<script src="{{ asset('assets/js/forms-purchase.js') }}"></script>
<script>
// Código específico para la vista de crear compra
$(document).ready(function() {
    // Cargar datos iniciales
    loadProviders();
    loadCompanies();

    // Configurar formulario específico para crear compra
    $('#purchaseForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        console.log('Formulario de crear compra enviado');

        if (selectedProducts.length === 0) {
            Swal.fire({
                title: 'Error',
                text: 'Debe agregar al menos un producto',
                icon: 'warning',
                confirmButtonText: 'Ok'
            });
            return;
        }

        // Preparar datos para envío
        const details = selectedProducts.map(product => ({
            product_id: product.product_id,
            quantity: parseInt(product.quantity),
            unit_price: parseFloat(product.unit_price),
            expiration_date: product.expiration_date || null,
            batch_number: product.batch_number || null,
            notes: product.notes || null
        }));

        $('#detailsInput').val(JSON.stringify(details));

        // Enviar formulario
        submitForm();
    });
});

function loadProviders() {
    $.get('/providers', function(data) {
        const select = $('#provider');
        select.empty().append('<option value="">Seleccionar proveedor...</option>');
        data.forEach(provider => {
            select.append(`<option value="${provider.id}">${provider.razonsocial}</option>`);
        });
    });
}

function loadCompanies() {
    $.get('/companies', function(data) {
        const select = $('#company');
        select.empty().append('<option value="">Seleccionar empresa...</option>');
        data.forEach(company => {
            select.append(`<option value="${company.id}">${company.name}</option>`);
        });
    });
}

// Funciones específicas para la vista de crear compra
function submitForm() {
    const formData = new FormData($('#purchaseForm')[0]);

    $.ajax({
        url: $('#purchaseForm').attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Compra creada correctamente',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route("purchase.index") }}';
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Error al crear la compra',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire({
                title: 'Error',
                text: response?.message || 'Error desconocido',
                icon: 'error',
                confirmButtonText: 'Ok'
            });
        }
    });
}
</script>
@endsection
