@php
$configData = \App\Helpers\Helpers::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Productos Próximos a Vencer')

@section('page-style')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    /* Prevenir que DataTables afecte esta tabla */
    #expiringProductsTable.no-datatables,
    [data-exclude-datatables="true"] {
        width: 100% !important;
    }

    /* Protección adicional contra DataTables */
    #expiringProductsTable.dataTable {
        width: 100% !important;
    }

    /* Asegurar que la tabla de productos próximos a vencer no reciba estilos de DataTables */
    #expiringProductsTable.dataTable tbody tr {
        background-color: inherit !important;
    }

    /* Prevenir que DataTables procese tablas marcadas como excluidas */
    [data-exclude-datatables="true"] {
        width: 100% !important;
    }

    /* Prevenir que DataTables añada clases automáticamente a la tabla de productos próximos a vencer */
    #expiringProductsTable.dataTable {
        border-collapse: collapse !important;
    }

    /* Ocultar cualquier wrapper de DataTables que se pueda generar */
    #expiringProductsTable .dataTables_wrapper {
        display: none !important;
    }
</style>
@endsection

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
<script src="{{ asset('assets/js/app-expiring-products.js') }}"></script>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Productos Próximos a Vencer</h4>
                        <div>
                            <button class="btn btn-primary" onclick="refreshData()">
                                <i class="ti ti-refresh"></i> Actualizar
                            </button>
                            <a href="{{ route('purchase.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="mb-4 row">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Estado</label>
                            <select class="form-select" id="statusFilter" onchange="filterByStatus()">
                                                            <option value="">Todos</option>
                            <option value="critical">Críticos (≤7 días)</option>
                            <option value="warning">Advertencia (8-30 días)</option>
                            <option value="expired">Vencidos</option>
                            <option value="no_expiration">Sin Fecha de Expiración</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="providerFilter" class="form-label">Proveedor</label>
                            <select class="form-select" id="providerFilter" onchange="filterByProvider()">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="daysFilter" class="form-label">Días para vencer</label>
                            <input type="number" class="form-control" id="daysFilter" min="1" max="365" value="30" onchange="filterByDays()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                                            <button class="btn btn-success" onclick="exportToExcel()">
                                <i class="ti ti-download"></i> Exportar
                            </button>
                            <!--<button class="btn btn-warning" onclick="generateExpirationDates()">
                                <i class="ti ti-calendar"></i> Generar Fechas
                            </button>
                            <button class="btn btn-info" onclick="testManual()">
                                <i class="ti ti-bug"></i> Test Manual
                            </button>-->
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="mb-4 row">
                        <div class="col-md-3">
                            <div class="text-white card bg-danger">
                                <div class="card-body">
                                    <h5 class="card-title">Críticos</h5>
                                    <h3 id="criticalCount">0</h3>
                                    <small>≤7 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Advertencia</h5>
                                    <h3 id="warningCount">0</h3>
                                    <small>8-30 días</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-white card bg-secondary">
                                <div class="card-body">
                                    <h5 class="card-title">Vencidos</h5>
                                    <h3 id="expiredCount">0</h3>
                                    <small>Ya vencidos</small>
                                </div>
                            </div>
                        </div>
                        <!--<div class="col-md-3">
                            <div class="text-white card bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Sin Fecha</h5>
                                    <h3 id="noExpirationCount">0</h3>
                                    <small>Sin expiración</small>
                                </div>
                            </div>
                        </div>-->
                        <div class="col-md-3">
                            <div class="text-white card bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Total</h5>
                                    <h3 id="totalCount">0</h3>
                                    <small>Productos</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="expiringProductsTable" data-exclude-datatables="true">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>Cantidad</th>
                                    <th>Fecha Caducidad</th>
                                    <th>Días Restantes</th>
                                    <th>Estado</th>
                                    <th>Lote</th>
                                    <!--<th>Ubicación</th>
                                    <th>Acciones</th>-->
                                </tr>
                            </thead>
                            <tbody id="expiringProductsTableBody">
                                <!-- Los productos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del producto -->
<div class="modal fade" id="productDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailModalBody">
                <!-- Los detalles se cargarán dinámicamente -->
            </div>
        </div>
    </div>
</div>

@endsection
