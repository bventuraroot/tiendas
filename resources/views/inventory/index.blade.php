@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Control de Inventario')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script>
        // Definir la URL base para las peticiones AJAX (soporta subdirectorios)
        window.baseUrl = window.baseUrl || (document.querySelector('[data-base-url]')?.getAttribute('data-base-url') || (window.location.origin + '/'));
        if (window.baseUrl && !window.baseUrl.endsWith('/')) window.baseUrl += '/';
    </script>
    <script src="{{ asset('assets/js/app-inventory-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-inventory.js') }}"></script>
    <script src="{{ asset('assets/js/inventory-units.js') }}"></script>
@endsection

@section('content')
<style>
    .imagen-producto-select2 {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        vertical-align: middle;
    }

    .expiration-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        display: inline-block;
        min-width: 80px;
    }

    .expiration-expired {
        background-color: #dc3545;
        color: white;
    }

    .expiration-critical {
        background-color: #fd7e14;
        color: white;
    }

    .expiration-warning {
        background-color: #ffc107;
        color: #212529;
    }

    .expiration-ok {
        background-color: #198754;
        color: white;
    }

    .expiration-none {
        background-color: #6c757d;
        color: white;
    }
</style>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">Inventarios de Productos</h5>
                <div>
                    <button type="button" class="btn btn-warning me-2" onclick="showExpirationReport()">
                        <i class="ti ti-alert-triangle me-1"></i>Reporte de Vencimiento
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addinventoryModal">
                        <i class="ti ti-plus me-1"></i>Agregar Producto
                    </button>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-inventory border-top">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Tipo</th>
                        <th>Proveedor</th>
                        <th>Cantidad</th>
                        <th>Stock Mínimo</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Estado Vencimiento</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Modal de Agregar Producto -->
    <div class="modal fade" id="addinventoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Producto al Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addinventoryForm" class="row">
                        @csrf
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="codesearch">Código de búsqueda</label>
                            <input type="text" id="codesearch" name="codesearch" class="form-control" placeholder="Escanee con la pistola o escriba el código">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="psearch">Buscar Producto</label>
                            <select class="select2psearch form-select" id="psearch" name="psearch" onchange="searchproduct(this.value)">
                                <option value="">Seleccionar producto</option>
                            </select>
                            <input type="hidden" id="productid" name="productid">
                            <input type="hidden" id="productname" name="productname">
                            <input type="hidden" id="productdescription" name="productdescription">
                            <input type="hidden" id="productprice" name="productprice">
                            <input type="hidden" id="conversion-factor" name="conversion-factor">
                            <input type="hidden" id="selected-unit-id" name="selected-unit-id">
                        </div>

                        <!-- Información del producto seleccionado -->
                        <div class="mb-3 col-12" style="display: none;" id="add-information-products">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Detalles del Producto</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="text-center col-md-4">
                                            <img id="product-image" src="{{ asset('assets/img/products/none.jpg') }}" alt="Imagen del producto" class="mb-3 img-fluid" style="max-height: 200px;">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="table-responsive">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <th style="width: 35%">Nombre:</th>
                                                            <td id="product-name">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Descripción:</th>
                                                            <td id="product-description">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Proveedor:</th>
                                                            <td id="product-provider">-</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Precio:</th>
                                                            <td id="product-price">$ 0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos de inventario -->
                        <div class="mb-3 col-md-4">
                            <label class="form-label" for="unit-select">Unidad de medida</label>
                            <select class="form-select" id="unit-select" name="unit-select">
                                <option value="">Seleccionar unidad...</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label" for="quantity">Cantidad Inicial</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="0" required/>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label" for="minimum_stock">Stock Mínimo</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" class="form-control" min="0" value="0" required/>
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label" for="location">Ubicación</label>
                            <input type="text" id="location" name="location" class="form-control" placeholder="Ej: Estante A1"/>
                        </div>

                        <div class="mb-3 col-12">
                            <div class="alert alert-secondary" id="inventory-conversion-info" style="display:none;">
                                <div>
                                    <strong>Equivalente en base:</strong> <span id="base-add-display">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-center col-12">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Guardar Inventario</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Inventario -->
    <div class="modal fade" id="editinventoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editinventoryForm" class="row">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_inventoryid" name="edit_inventoryid">
                        <input type="hidden" id="edit_productid" name="edit_productid">
                        <input type="hidden" id="edit_conversion-factor" name="edit_conversion-factor">
                        <input type="hidden" id="edit_selected-unit-id" name="edit_selected-unit-id">

                        <!-- Información del producto -->
                        <div class="mb-3 col-12">
                            <div class="alert alert-info">
                                <strong>Producto:</strong> <span id="edit_product_name">-</span><br>
                                <strong>Stock actual:</strong> <span id="edit_current_stock">0</span>
                            </div>
                        </div>

                        <!-- Campos de unidad de medida -->
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="edit_unit-select">Unidad de medida</label>
                            <select class="form-select" id="edit_unit-select" name="edit_unit-select">
                                <option value="">Seleccionar unidad...</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="edit_quantity">Cantidad a agregar/ajustar</label>
                            <input type="number" id="edit_quantity" name="edit_quantity" class="form-control" min="0" required/>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="edit_minimum_stock">Stock Mínimo</label>
                            <input type="number" id="edit_minimum_stock" name="edit_minimum_stock" class="form-control" min="0" required/>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="edit_location">Ubicación</label>
                            <input type="text" id="edit_location" name="edit_location" class="form-control"/>
                        </div>

                        <!-- Información de conversión -->
                        <div class="mb-3 col-12">
                            <div class="alert alert-secondary" id="edit-inventory-conversion-info" style="display:none;">
                                <div>
                                    <strong>Equivalente en base:</strong> <span id="edit-base-add-display">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-center col-12">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Guardar</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Seguimiento de Vencimiento -->
    <div class="modal fade" id="expirationTrackingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seguimiento de Vencimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="expiration-content">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Reporte de Vencimiento -->
    <div class="modal fade" id="expirationReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reporte de Productos por Vencimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="expiration-report-content">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
