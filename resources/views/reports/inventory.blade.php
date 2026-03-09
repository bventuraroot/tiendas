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
<script src="{{ asset('assets/js/tables-datatables-advanced.js') }}"></script>
@endsection

@section('title', 'Reporte de Inventario')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Inventario
</h4>

<!-- Filtros de búsqueda -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('report.inventory-search') }}" id="searchForm">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <label for="company" class="form-label">Empresa</label>
                    <select class="form-select" name="company" id="company" required>
                        <option value="">Seleccionar empresa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-select" name="category" id="category">
                        <option value="">Todas</option>
                        @if(isset($categories))
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ (isset($filters['category']) && $filters['category'] == $category) ? 'selected' : '' }}>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="provider_id" class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id" id="provider_id">
                        <option value="">Todos</option>
                        @if(isset($providers))
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ (isset($filters['provider_id']) && $filters['provider_id'] == $provider->id) ? 'selected' : '' }}>
                                    {{ $provider->razonsocial }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="stock_status" class="form-label">Estado Stock</label>
                    <select class="form-select" name="stock_status" id="stock_status">
                        <option value="">Todos</option>
                        <option value="low_stock" {{ (isset($filters['stock_status']) && $filters['stock_status'] == 'low_stock') ? 'selected' : '' }}>Stock Bajo</option>
                        <option value="out_of_stock" {{ (isset($filters['stock_status']) && $filters['stock_status'] == 'out_of_stock') ? 'selected' : '' }}>Sin Stock</option>
                        <option value="expiring_soon" {{ (isset($filters['stock_status']) && $filters['stock_status'] == 'expiring_soon') ? 'selected' : '' }}>Por Vencer</option>
                        <option value="active" {{ (isset($filters['stock_status']) && $filters['stock_status'] == 'active') ? 'selected' : '' }}>Activos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="location" class="form-label">Ubicación</label>
                    <select class="form-select" name="location" id="location">
                        <option value="">Todas</option>
                        @if(isset($locations))
                            @foreach($locations as $location)
                                <option value="{{ $location }}" {{ (isset($filters['location']) && $filters['location'] == $location) ? 'selected' : '' }}>
                                    {{ $location }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-2">
                    <label for="order_by" class="form-label">Ordenar por</label>
                    <select class="form-select" name="order_by" id="order_by">
                        <option value="product_name" {{ (isset($filters['order_by']) && $filters['order_by'] == 'product_name') ? 'selected' : '' }}>Nombre</option>
                        <option value="quantity" {{ (isset($filters['order_by']) && $filters['order_by'] == 'quantity') ? 'selected' : '' }}>Cantidad</option>
                        <option value="price" {{ (isset($filters['order_by']) && $filters['order_by'] == 'price') ? 'selected' : '' }}>Precio</option>
                        <option value="expiration" {{ (isset($filters['order_by']) && $filters['order_by'] == 'expiration') ? 'selected' : '' }}>Vencimiento</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="order_direction" class="form-label">Dirección</label>
                    <select class="form-select" name="order_direction" id="order_direction">
                        <option value="asc" {{ (isset($filters['order_direction']) && $filters['order_direction'] == 'asc') ? 'selected' : '' }}>Ascendente</option>
                        <option value="desc" {{ (isset($filters['order_direction']) && $filters['order_direction'] == 'desc') ? 'selected' : '' }}>Descendente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="{{ route('report.inventory-by-category') }}" class="btn btn-info">
                            <i class="fas fa-chart-pie"></i> Por Categoría
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="{{ route('report.inventory-by-provider') }}" class="btn btn-warning">
                            <i class="fas fa-truck"></i> Por Proveedor
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultados del reporte -->
@if(isset($inventory) && $inventory->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Resultados del Reporte
            @if(isset($heading))
                - {{ $heading->name }}
            @endif
        </h5>
        <div>
            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Resumen estadístico -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Productos</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_products']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Valor Total</h6>
                        <h3 class="mb-0">${{ number_format($stats['total_value'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Stock Bajo</h6>
                        <h3 class="mb-0">{{ number_format($stats['low_stock_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Sin Stock</h6>
                        <h3 class="mb-0">{{ number_format($stats['out_of_stock_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Por Vencer</h6>
                        <h3 class="mb-0">{{ number_format($stats['expiring_soon_count']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <h6 class="card-title">Activos</h6>
                        <h3 class="mb-0">{{ number_format($stats['active_products']) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="inventoryTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>Marca</th>
                        <th>Stock</th>
                        <th>Stock Mínimo</th>
                        <th>Unidad Base</th>
                        <th>Precio</th>
                        <th>Valor Total</th>
                        <th>Ubicación</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($inventory as $item)
                    <tr class="{{ ($item->quantity <= $item->minimum_stock) ? 'table-warning' : (($item->quantity == 0) ? 'table-danger' : '') }}">
                        <td>{{ $counter++ }}</td>
                        <td>
                            <strong>{{ $item->product_code ?? 'N/A' }}</strong>
                        </td>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->product_description)
                                <br><small class="text-muted">{{ Str::limit($item->product_description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ ucfirst($item->product_type) }}</span>
                        </td>
                        <td>{{ $item->provider_name ?? 'N/A' }}</td>
                        <td>{{ $item->marca_name ?? 'N/A' }}</td>
                        <td>
                            @if($item->quantity <= $item->minimum_stock)
                                <span class="badge bg-warning">{{ number_format($item->quantity) }}</span>
                            @elseif($item->quantity == 0)
                                <span class="badge bg-danger">0</span>
                            @else
                                <span class="badge bg-success">{{ number_format($item->quantity) }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->minimum_stock) }}</td>
                        <td>
                            @if($item->base_unit_name)
                                {{ $item->base_unit_name }} ({{ $item->base_unit_code }})
                                @if($item->base_quantity)
                                    <br><small class="text-muted">{{ number_format($item->base_quantity, 2) }}</small>
                                @endif
                            @else
                                Unidad
                            @endif
                        </td>
                        <td>
                            @if($item->base_unit_price)
                                <strong>${{ number_format($item->base_unit_price, 2) }}</strong>
                                <br><small class="text-muted">por {{ $item->base_unit_name ?? 'unidad' }}</small>
                            @else
                                <strong>${{ number_format($item->product_price, 2) }}</strong>
                            @endif
                        </td>
                        <td>
                            <strong>${{ number_format(($item->base_quantity ?? $item->quantity) * ($item->base_unit_price ?? $item->product_price ?? 0), 2) }}</strong>
                        </td>
                        <td>{{ $item->location ?? 'N/A' }}</td>
                        <td>
                            @if($item->expiration_date)
                                @if($item->expiration_date <= now()->addDays(30))
                                    <span class="badge bg-danger">{{ \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-info">{{ \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') }}</span>
                                @endif
                                @if($item->batch_number)
                                    <br><small class="text-muted">Lote: {{ $item->batch_number }}</small>
                                @endif
                            @else
                                <span class="text-muted">Sin vencimiento</span>
                            @endif
                        </td>
                        <td>
                            @if($item->product_state == 1)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@elseif(isset($inventory))
<div class="card">
    <div class="card-body text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No se encontraron resultados</h5>
        <p class="text-muted">No hay productos en inventario para los filtros seleccionados.</p>
    </div>
</div>
@endif
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Cargar empresas
    loadCompanies();

    // Cargar filtros cuando se seleccione una empresa
    $('#company').change(function() {
        loadFilters($(this).val());
    });

    // Inicializar DataTable
    if ($('#inventoryTable').length) {
        $('#inventoryTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[2, 'asc']], // Ordenar por nombre de producto ascendente
            pageLength: 25
        });
    }
});

function loadCompanies() {
    $.ajax({
        url: '/company/getcompanies',
        method: 'GET',
        success: function(response) {
            let options = '<option value="">Seleccionar empresa</option>';
            response.forEach(function(company) {
                let selected = '{{ isset($heading) ? $heading->id : "" }}' == company.id ? 'selected' : '';
                options += `<option value="${company.id}" ${selected}>${company.name}</option>`;
            });
            $('#company').html(options);
        },
        error: function() {
            console.error('Error al cargar empresas');
        }
    });
}

function loadFilters(companyId) {
    if (!companyId) {
        return;
    }

    // Cargar categorías
    $.ajax({
        url: '/report/inventory-search',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            company: companyId
        },
        success: function(response) {
            // Actualizar filtros con los datos de la empresa
            updateFilters(response);
        },
        error: function() {
            console.error('Error al cargar filtros');
        }
    });
}

function updateFilters(data) {
    // Actualizar categorías
    if (data.categories) {
        let categoryOptions = '<option value="">Todas</option>';
        data.categories.forEach(function(category) {
            categoryOptions += `<option value="${category}">${category.charAt(0).toUpperCase() + category.slice(1)}</option>`;
        });
        $('#category').html(categoryOptions);
    }

    // Actualizar proveedores
    if (data.providers) {
        let providerOptions = '<option value="">Todos</option>';
        data.providers.forEach(function(provider) {
            providerOptions += `<option value="${provider.id}">${provider.razonsocial}</option>`;
        });
        $('#provider_id').html(providerOptions);
    }

    // Actualizar marcas
    if (data.marcas) {
        let marcaOptions = '<option value="">Todas</option>';
        data.marcas.forEach(function(marca) {
            marcaOptions += `<option value="${marca.id}">${marca.name}</option>`;
        });
        $('#marca_id').html(marcaOptions);
    }

    // Actualizar ubicaciones
    if (data.locations) {
        let locationOptions = '<option value="">Todas</option>';
        data.locations.forEach(function(location) {
            locationOptions += `<option value="${location}">${location}</option>`;
        });
        $('#location').html(locationOptions);
    }
}

function exportToExcel() {
    let table = $('#inventoryTable').DataTable();
    let data = table.data().toArray();

    // Crear CSV
    let csv = 'Código,Producto,Categoría,Proveedor,Marca,Stock,Stock Mínimo,Unidad Base,Precio,Valor Total,Ubicación,Vencimiento,Estado\n';

    data.forEach(function(row) {
        csv += `"${row[1]}","${row[2]}","${row[3]}","${row[4]}","${row[5]}","${row[6]}","${row[7]}","${row[8]}","${row[9]}","${row[10]}","${row[11]}","${row[12]}","${row[13]}"\n`;
    });

    // Descargar archivo
    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'reporte_inventario.csv';
    link.click();
}

function exportToPDF() {
    // Implementar exportación a PDF
    Swal.fire({
        icon: 'info',
        title: 'Funcionalidad en desarrollo',
        text: 'La exportación a PDF estará disponible próximamente'
    });
}
</script>
@endsection
