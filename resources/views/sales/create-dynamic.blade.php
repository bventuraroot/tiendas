@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nueva Venta Dinámica')

@section('content')
<div class="container-fluid">
    <!-- Header con título y navegación -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Nueva {{ $document }}</h2>
                </div>
                <div class="gap-2 d-flex">
                    <!-- <button type="button" class="btn btn-primary" onclick="clearDraftAndCreateNew()">
                        <i class="ti ti-plus me-1"></i>Nueva Venta
                    </button> -->
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                        <i class="ti ti-arrow-left me-1"></i>Salir (Borrador guardado)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form id="sale-form" method="POST" action="{{ route('sale.process-sale') }}">
        @csrf

        <!-- Hidden inputs para mantener la lógica original -->
        <input type="hidden" name="iduser" id="iduser" value="{{ $id_user }}">
        <input type="hidden" name="typedocument" id="typedocument" value="{{ $typedocument }}">
        <input type="hidden" name="typecontribuyente" id="typecontribuyente" value="">
        <input type="hidden" name="iva" id="iva" value="13">
        <input type="hidden" name="iva_entre" id="iva_entre" value="13">
        <input type="hidden" name="valcorr" id="valcorr" value="{{ $corr }}">
        <input type="hidden" name="valdraft" id="valdraft" value="{{ $draftId ? 'true' : 'false' }}">
        <input type="hidden" name="operation" id="operation" value="{{ $operation }}">
        <input type="hidden" name="draft_id" id="draft_id" value="{{ $draftId }}">
        <input type="hidden" name="isnewsale" id="isnewsale">

        <!-- Campo de empresa oculto pero funcional -->
        <input type="hidden" name="company" id="company" value="{{ $companies->first()->id ?? '1' }}">


        <!-- Campos de producto (ocultos) -->
        <input type="hidden" name="productname" id="productname" value="">
        <input type="hidden" name="marca" id="marca" value="">
        <input type="hidden" name="productid" id="productid" value="">
        <input type="hidden" name="productdescription" id="productdescription" value="">
        <input type="hidden" name="productunitario" id="productunitario" value="">

        <!-- Totales (ocultos) -->
        <input type="hidden" name="sumas" id="sumas" value="0">
        <input type="hidden" name="13iva" id="13iva" value="0">
        <input type="hidden" name="ivaretenido" id="ivaretenido" value="0">
        <input type="hidden" name="rentaretenido" id="rentaretenido" value="0">
        <input type="hidden" name="ventasnosujetas" id="ventasnosujetas" value="0">
        <input type="hidden" name="ventasexentas" id="ventasexentas" value="0">
        <input type="hidden" name="ventatotal" id="ventatotal" value="0">
        <input type="hidden" name="ventatotallhidden" id="ventatotallhidden" value="0">

        <!-- Campos adicionales -->
        <input type="hidden" name="reserva" id="reserva" value="0">
        <input type="hidden" name="ruta" id="ruta" value="0">
        <input type="hidden" name="destino" id="destino" value="0">
        <input type="hidden" name="linea" id="linea" value="0">
        <input type="hidden" name="Canal" id="Canal" value="0">
        <input type="hidden" name="fee" id="fee" value="0">
        <input type="hidden" name="fee2" id="fee2" value="0">

        <div class="row">
            <!-- Panel izquierdo - Información de la venta -->
            <div class="col-lg-9">
                <div class="shadow-sm card">
                    <!--<div class="card-header">
                        <h5 class="mb-0 card-title">
                            <i class="ti ti-file-invoice me-2"></i>
                            Detalles de la Venta
                        </h5>
                    </div>-->
                    <div class="card-body">
                        <div class="mb-4 row">
                            <div class="col-md-2">
                                <label class="form-label" for="corr">Correlativo</label>
                                <input type="text" id="corr" name="corr" class="form-control" readonly />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date">Fecha</label>
                                <input type="date" id="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" />
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="client">Cliente <span class="text-danger">*</span></label>
                                <select class="form-select select2client" id="client" name="client" onchange="valtrypecontri(this.value)">
                                    <option value="0">Seleccione un cliente</option>
                                </select>
                                <input type="hidden" name="typecontribuyenteclient" id="typecontribuyenteclient">
                                <input type="hidden" name="cliente_agente_retencion" id="cliente_agente_retencion" value="0">
                                <input type="hidden" name="retencion_agente" id="retencion_agente" value="0">
                                <input type="hidden" id="acuenta" name="acuenta" value="">
                                <div id="client-validation-message" class="mt-1" style="display:none;">
                                    <small class="text-warning">
                                        <i class="ti ti-alert-triangle me-1"></i>
                                        <span id="validation-text"></span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Información compacta del cliente -->
                        <div id="client-info" class="mb-3" style="display:none;">
                            <div class="card border-primary">
                                <div class="py-2 text-white card-header bg-primary">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-users me-2"></i>
                                        <h6 class="mb-0 fw-semibold">Información del Cliente</h6>
                                    </div>
                                </div>
                                <div class="py-3 card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2 d-flex align-items-center">
                                                <i class="ti ti-user-circle me-2 text-primary"></i>
                                                <strong>Información Personal</strong>
                                            </div>
                                            <div class="ps-3">
                                                <div class="mb-1">
                                                    <small class="text-muted">Nombre:</small>
                                                    <span id="client-name" class="fw-semibold text-dark ms-1">-</span>
                                                </div>
                                                <div class="mb-1">
                                                    <small class="text-muted">Tipo:</small>
                                                    <span id="client-type" class="fw-semibold text-dark ms-1">-</span>
                                                </div>
                                                <div class="mb-1">
                                                    <small class="text-muted">NIT/DUI:</small>
                                                    <span id="client-nit" class="fw-semibold text-dark ms-1">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2 d-flex align-items-center">
                                                <i class="ti ti-building me-2 text-success"></i>
                                                <strong>Información Fiscal</strong>
                                            </div>
                                            <div class="ps-3">
                                                <div class="mb-1">
                                                    <small class="text-muted">Contribuyente:</small>
                                                    <span id="client-contribuyente" class="fw-semibold text-dark ms-1">-</span>
                                                </div>
                                                <div class="mb-1">
                                                    <small class="text-muted">Estado:</small>
                                                    <span id="client-status" class="badge bg-success ms-1">Activo</span>
                                                </div>
                                                <div class="mb-1">
                                                    <small class="text-muted">Email:</small>
                                                    <span id="client-email" class="fw-semibold text-dark ms-1">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Productos -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3 fw-semibold">
                                    <i class="ti ti-package me-2"></i>Agregar Productos
                                </h6>
                            </div>
                        </div>

                        <!-- Búsqueda de Productos -->
                        <div class="mb-3 row">
                            <div class="col-md-4">
                                <label class="form-label" for="codesearch">Código de Producto</label>
                                <div class="input-group">
                                    <input type="text" id="codesearch" name="codesearch" class="form-control" placeholder="Ingrese el código de producto" />
                                    <button type="button" class="btn btn-outline-secondary" id="clear-codesearch" title="Limpiar código">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="psearch">Buscar Producto</label>
                                <select class="form-select select2psearch" id="psearch" name="psearch">
                                    <option value="0">Seleccione un producto</option>
                                </select>
                                <input type="hidden" id="productname" name="productname">
                                <input type="hidden" id="marca" name="marca">
                                <input type="hidden" id="productid" name="productid">
                                <input type="hidden" id="productdescription" name="productdescription">
                                <input type="hidden" id="productunitario" name="productunitario">

                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="cantidad">Cantidad</label>
                                <input type="number" id="cantidad" name="cantidad" min="1" step="1" value="1" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="unit-select">Unidad descontar del inventario</label>
                                <select class="form-select unit-select" id="unit-select" name="unit-select">
                                    <option value="">Seleccionar...</option>
                                </select>
                                <input type="hidden" id="selected-unit-id" name="selected-unit-id">
                                <input type="hidden" id="conversion-factor" name="conversion-factor">
                                <!-- Campos ocultos para el stock -->
                                <input type="hidden" id="stock_quantity" name="stock_quantity">
                                <input type="hidden" id="total_in_lbs" name="total_in_lbs">
                                <input type="hidden" id="total_in_liters" name="total_in_liters">
                                <input type="hidden" id="total_in_ml" name="total_in_ml">
                                <input type="hidden" id="measure_type" name="measure_type">
                                <input type="hidden" id="pastillas_per_blister" name="pastillas_per_blister">
                                <input type="hidden" id="blisters_per_caja" name="blisters_per_caja">
                                <!-- Preview de conversión farmacéutica -->
                                <small id="unit-conversion-preview" class="text-muted" style="display: none;"></small>
                            </div>

                            <!-- El selector de precios múltiples se insertará aquí dinámicamente -->
                            <div class="col-md-4" id="price-type-container" style="">
                                <label class="form-label" for="price-type-select">Tipo de Precio</label>
                                <select class="form-select" id="price-type-select" name="price-type-select">
                                    <option value="">Seleccionar tipo...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Precios y Totales -->
                        <div class="mb-4 row">
                            <div class="col-md-2">
                                <label class="form-label" for="precio">Precio Unitario</label>
                                <input type="number" id="precio" name="precio" step="0.00000001" min="0" max="10000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                            </div>
                            @if($typedocument == 3)
                            <div class="col-md-2">
                                <label class="form-label" for="precio_sin_iva">Precio sin IVA</label>
                                <input type="number" id="precio_sin_iva" name="precio_sin_iva" step="0.00000001" min="0" max="10000" placeholder="0.00000000" class="form-control">
                            </div>
                            @endif
                            <div class="col-md-2">
                                <label class="form-label" for="typesale">Tipo de venta</label>
                                <select class="form-select" id="typesale" name="typesale" onchange="changetypesale(this.value)">
                                    <option value="gravada">Gravadas</option>
                                    <option value="exenta">Exenta</option>
                                    <option value="nosujeta">No Sujeta</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="ivarete13">Iva 13%</label>
                                <input type="number" id="ivarete13" name="ivarete13" step="0.00000001" max="10000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-md-2" id="ivaPercibidoContainer" style="display: none;">
                                <label class="form-label" for="ivapercibido">Iva Percibido</label>
                                <input type="number" id="ivapercibido" name="ivapercibido" step="0.00000001" max="10000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-md-2" id="ivaRetenidoFieldContainer" style="display: none;">
                                <label class="form-label" for="ivaretencion">IVA Retenido</label>
                                <input type="number" id="ivaretencion" name="ivaretencion" step="0.00000001" max="10000" placeholder="0.00000000" class="form-control" readonly>
                            </div>
                            <!-- Campo oculto para IVA Retenido (detained) que se calcula automáticamente cuando cliente es agente de retención -->
                            <input type="hidden" id="ivarete" name="ivarete" value="0">
                            @if($typedocument == 8)
                            <div class="col-md-2">
                                <label class="form-label" for="rentarete">Renta 10%</label>
                                <input type="number" id="rentarete" name="rentarete" step="0.00000001" max="10000" placeholder="0.00000000" class="form-control">
                            </div>
                            @endif
                            <div class="col-md-4">
                                <label class="form-label" for="total">Total</label>
                                <input type="number" id="total" name="total" step="0.00000001" max="10000" placeholder="0.00000000" class="form-control" readonly>
                            </div>
                        </div>

                        <!-- Botón Agregar Producto -->
                        <div class="mb-2">
                            <button type="button" class="btn btn-primary btn-lg" onclick="agregarp()">
                                <i class="ti ti-plus me-2"></i>Agregar
                            </button>

                        </div>

                        <!-- Información del Producto -->
                        <div class="mb-3" id="add-information-products" style="display: none;">
                            <div class="card border-primary">
                                <div class="p-3 card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img id="product-image" src="{{ asset('assets/img/products/default.png') }}" alt="Producto" class="rounded img-fluid" style="max-height: 60px;">
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Nombre</small>
                                            <span id="product-name" class="fw-semibold">-</span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Marca</small>
                                            <span id="product-marca" class="fw-semibold">-</span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Proveedor</small>
                                            <span id="product-provider" class="fw-semibold">-</span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Precio</small>
                                            <span id="product-price" class="fw-semibold text-primary">$0.00</span>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearProductData()">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Productos -->
                        <!--<div class="row">
                            <div class="col-12">
                                <h6 class="mb-3 fw-semibold">
                                    <i class="ti ti-list me-2"></i>Lista de Productos
                                </h6>
                            </div>
                        </div>-->

                        <div class="card-datatable table-responsive" id="resultados">
                            <div class="panel">
                                <table class="table table-sm animated table-hover table-striped table-bordered fadeIn" id="tblproduct">
                                    <thead class="bg-secondary">
                                        <tr>
                                            <th class="text-center text-white">CANT.</th>
                                            <th class="text-white">DESCRIPCION</th>
                                            <th class="text-right text-white">PRECIO UNIT.</th>
                                            <th class="text-right text-white">NO SUJETAS</th>
                                            <th class="text-right text-white">EXENTAS</th>
                                            <th class="text-right text-white">GRAVADAS</th>
                                            <th class="text-right text-white">TOTAL</th>
                                            <th class="text-center text-white">ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td rowspan="8" colspan="5"></td>
                                            <td class="text-right">SUMAS</td>
                                            <td class="text-center" id="sumasl">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @if($typedocument == 3 || $typedocument == 8)
                                        <tr>
                                            <td class="text-right">IVA 13%</td>
                                            <td class="text-center" id="13ival">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @endif
                                        @if($typedocument == 8)
                                        <tr>
                                            <td class="text-right">(-) Renta 10%</td>
                                            <td class="text-center" id="10rental">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @endif
                                        <tr id="ivaRetenidoRow" style="display: none;">
                                            <td class="text-right">(-) IVA Retenido</td>
                                            <td class="text-center" id="ivaretenidol">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Ventas No Sujetas</td>
                                            <td class="text-center" id="ventasnosujetasl">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Ventas Exentas</td>
                                            <td class="text-center" id="ventasexentasl">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Venta Total</td>
                                            <td class="text-center" id="ventatotall">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho - Información del Catálogo -->
            <div class="col-lg-3">
                <div class="sticky-top" style="top: 20px;">
                    <!-- Stock Disponible -->
                    <div class="mb-3 card border-success">
                        <div class="text-white card-header bg-success">
                            <h6 class="mb-0"><i class="fas fa-warehouse me-2"></i>Stock Disponible</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 row">
                                <div class="col-6"><strong>Stock disponible:</strong></div>
                                <div class="col-6 text-end" id="catalog-stock-available">0 unidades</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-6"><strong>Estado:</strong></div>
                                <div class="col-6 text-end" id="catalog-stock-status">
                                    <span class="badge bg-secondary">Sin datos</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validaciones -->
                    <div class="mb-3 card border-warning">
                        <div class="text-white card-header bg-warning">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Validaciones</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 row">
                                <div class="col-6"><strong>Stock actual:</strong></div>
                                <div class="col-6 text-end" id="catalog-validation-current">0 unidades</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-6"><strong>A vender:</strong></div>
                                <div class="col-6 text-end" id="catalog-validation-sell">0</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-6"><strong>Stock después:</strong></div>
                                <div class="col-6 text-end" id="catalog-validation-after">0 unidades</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-6"><strong id="catalog-validation-lbs-label">En libras:</strong></div>
                                <div class="col-6 text-end" id="catalog-validation-lbs">0 libras</div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="shadow-sm card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-settings me-2"></i>Acciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="fpago">Seleccionar forma de pago <span class="text-danger">*</span></label>
                                <select class="form-select" id="fpago" name="fpago" onchange="valfpago(this.value)">
                                    <option value="0">Seleccione una opción</option>
                                    <option selected value="1">Contado</option>
                                    <option value="2">A crédito</option>
                                    <option value="3">Tarjeta</option>
                                </select>
                            </div>
                            <div class="mb-3" id="card-authorization-container" style="display: none;">
                                <label class="form-label" for="card_authorization_number">Número de Autorización POS <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="card_authorization_number" name="card_authorization_number" placeholder="Ingrese el número de autorización" maxlength="100">
                            </div>
                            <div class="gap-2 d-grid">
                                <button type="button" class="btn btn-success" onclick="finalizeSale()" id="finalize-btn" disabled>
                                    <i class="ti ti-check me-1"></i>Finalizar Venta
                                </button>
                                <!--<button type="button" class="btn btn-outline-info" onclick="printTicket()" id="print-btn" disabled>
                                    <i class="ti ti-printer me-1"></i>Imprimir Ticket
                                </button>-->
                                <!--<button type="button" class="btn btn-outline-warning" onclick="resetSale()">
                                    <i class="ti ti-refresh me-1"></i>Nueva Venta
                                </button>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal para confirmaciones -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/sales-units.js?v=' . time()) }}"></script>
<script src="{{ asset('assets/js/sales-dynamic.js?v=' . filemtime(public_path('assets/js/sales-dynamic.js'))) }}"></script>
        <!-- RESTAURADO para mantener funcionalidad de conversiones y cálculos -->
<script src="{{ asset('assets/js/sales-multiple-prices.js?v=' . time()) }}"></script>

<script>
// Cargar automáticamente el draft si se está editando uno
$(document).ready(function() {
    @if($corr && $operation === 'edit')
        // Cargar el draft automáticamente
        loadDraftFromUrl({{ $corr }}, {{ $typedocument }});
    @elseif($isNewSale)
        // Es una nueva venta desde el index, agregar parámetro new=true a la URL
        if (!window.location.search.includes('new=true')) {
            window.history.replaceState({}, '', window.location.href + '&new=true');
        }
        console.log('🔄 Nueva venta desde index - creando correlativo para tipo documento:', {{ $typedocument }});
        // Verificar que la función esté disponible antes de llamarla
        if (typeof createcorrsale === 'function') {
            createcorrsale({{ $typedocument }});
        } else {
            console.error('❌ Función createcorrsale no está disponible');
            // Intentar de nuevo después de un pequeño delay
            setTimeout(function() {
                if (typeof createcorrsale === 'function') {
                    createcorrsale({{ $typedocument }});
                } else {
                    console.error('❌ Función createcorrsale sigue sin estar disponible');
                    // Último intento con delay más largo
                    setTimeout(function() {
                        if (typeof createcorrsale === 'function') {
                            createcorrsale({{ $typedocument }});
                        } else {
                            console.error('❌ Función createcorrsale definitivamente no está disponible');
                        }
                    }, 500);
                }
            }, 200);
        }
        $('#isnewsale').val(1);
        $('#valdraft').val('false');
    @else
        // Crear nuevo correlativo para nueva venta
        console.log('🔄 Nueva venta - creando correlativo para tipo documento:', {{ $typedocument }});
        // Verificar que la función esté disponible antes de llamarla
        if (typeof createcorrsale === 'function') {
            createcorrsale({{ $typedocument }});
        } else {
            console.error('❌ Función createcorrsale no está disponible');
            // Intentar de nuevo después de un pequeño delay
            setTimeout(function() {
                if (typeof createcorrsale === 'function') {
                    createcorrsale({{ $typedocument }});
                } else {
                    console.error('❌ Función createcorrsale sigue sin estar disponible');
                    // Último intento con delay más largo
                    setTimeout(function() {
                        if (typeof createcorrsale === 'function') {
                            createcorrsale({{ $typedocument }});
                        } else {
                            console.error('❌ Función createcorrsale definitivamente no está disponible');
                        }
                    }, 500);
                }
            }, 200);
        }
    @endif
});

function loadDraftFromUrl(draftId, typedocument) {
    // Cargar el draft usando la función existente
    $("#corr").val(draftId);
    $("#valcorr").val(draftId);

    // Cargar los datos del draft (esto también cargará los productos)
    draftdocument(draftId, true);

    // Mostrar mensaje de confirmación
    Swal.fire({
        title: 'Draft Cargado',
        text: 'El borrador se ha cargado correctamente. Puedes continuar editando.',
        icon: 'success',
        confirmButtonText: 'Continuar'
    });
}
</script>
@endsection

@section('page-style')
<style>
    .form-control:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
    }

    .btn-primary {
        background-color: #696cff;
        border-color: #696cff;
    }

    .btn-primary:hover {
        background-color: #5f62e6;
        border-color: #5f62e6;
    }

    .card {
        border: 0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .table th {
        border-top: none;
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .alert {
        border: none;
        border-radius: 0.5rem;
    }

    .select2-container--default .select2-results__option img {
        max-width: 30px;
        max-height: 30px;
        margin-right: 8px;
        border-radius: 4px;
    }

    .imagen-producto-select2 {
        max-width: 30px;
        max-height: 30px;
        border-radius: 4px;
    }

    .select2-results__option {
        display: flex;
        align-items: center;
        padding: 8px 12px;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
    }

    .btn {
        font-size: 0.875rem;
    }

    #client-info .alert {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 4px solid #696cff;
    }

    #add-information-products .card {
        border-color: #696cff !important;
        background: linear-gradient(135deg, #f8f9ff 0%, #e7f3ff 100%);
    }

    .session-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .product-info {
        background: #f8f9fa;
        border-left: 4px solid #696cff;
    }

    /* Estilos unificados para botones de remover */
    .btn-remove-product {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
        border-radius: 0.375rem !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.2) !important;
        transition: all 0.15s ease-in-out !important;
    }

    .btn-remove-product:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 0.25rem 0.5rem rgba(220, 53, 69, 0.3) !important;
    }

    .btn-remove-product:active {
        transform: translateY(0) !important;
        box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.2) !important;
    }

    .btn-remove-product .ti-trash {
        font-size: 0.875rem !important;
        margin: 0 !important;
    }

    /* Estilos para SweetAlert con mensajes largos */
    .swal-wide {
        width: 600px !important;
        max-width: 90vw !important;
    }

    .swal-wide .swal2-html-container {
        text-align: left !important;
        white-space: pre-line !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
    }

    .swal-wide .swal2-title {
        font-size: 18px !important;
        font-weight: 600 !important;
    }
</style>

@if(isset($draftClient) && $draftClient)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const draftClientId = '{{ $draftClient->id }}';
    console.log('🔄 Intentando seleccionar cliente del draft:', draftClientId);

    // Función para intentar seleccionar el cliente
    function trySelectClient() {
        const clientSelect = $('#client');
        const options = clientSelect.find('option');

        console.log('🔍 Opciones disponibles:', options.length);
        console.log('🔍 Opciones:', options.map(function() { return this.value; }).get());

        // Verificar si hay opciones cargadas (más de la opción por defecto)
        if (options.length > 1) {
            // Verificar si existe la opción con el ID del draft
            const targetOption = clientSelect.find('option[value="' + draftClientId + '"]');
            console.log('🔍 Opción del draft encontrada:', targetOption.length > 0);

            if (targetOption.length > 0) {
                // Seleccionar el cliente del draft
                clientSelect.val(draftClientId).trigger('change');
                console.log('✅ Cliente del draft seleccionado:', draftClientId);
            } else {
                console.log('❌ Opción del cliente no encontrada en el select');
            }
        } else {
            console.log('⏳ Esperando que se carguen las opciones...');
            // Intentar de nuevo en 500ms
            setTimeout(trySelectClient, 500);
        }
    }

    // Intentar inmediatamente
    trySelectClient();

    // También intentar después de un delay por si acaso
    setTimeout(trySelectClient, 2000);
});
</script>
@endif

@endsection

