@php
    $configData = Helper::appClasses();
    use Milon\Barcode\DNS1D;
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .valid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #198754;
        }

        .form-label .text-danger {
            font-weight: bold;
        }

        .alert {
            margin-bottom: 1rem;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
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

        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #viewProductModal .modal-body {
            padding: 1.5rem;
        }
        .product-view-modal .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .product-view-modal .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important;
        }
    </style>
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
    <script src="{{ asset('assets/js/app-product-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-product.js') }}"></script>
    <script src="{{ asset('assets/js/product-units-config.js') }}"></script>
    <script src="{{ asset('assets/js/product-unit-crud.js') }}"></script>
    <script src="{{ asset('assets/js/product-validation.js') }}"></script>
    <script>
        // Definir baseUrl si no está definida (evitar redeclaración)
        window.baseUrl = window.baseUrl || window.location.origin + '/';

        $(document).ready(function() {
            var codeInput = $('#code');
            var codeInputEdit = $('#codeedit');
            var barcodeDiv = $('#barcode');
            var barcodeDivEdit = $('#barcodeedit');

            if (!codeInput.length || !barcodeDiv.length) {
                return;
            }

            codeInput.on('input', function() {
                var code = $(this).val();
                if (!code) {
                    barcodeDiv.html('');
                    return;
                }
                var url = '{{ url("barcode") }}/' + code;
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        if (data.error) {
                            barcodeDiv.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                        } else {
                            barcodeDiv.html(data.html);
                        }
                    },
                    error: function() {
                        barcodeDiv.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                    }
                });
            });

            codeInputEdit.on('input', function() {
                var code = $(this).val();
                if (!code) {
                    barcodeDivEdit.html('');
                    return;
                }
                var url = '{{ url("barcode") }}/' + code;
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        if (data.error) {
                            barcodeDivEdit.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                        } else {
                            barcodeDivEdit.html(data.html);
                        }
                    },
                    error: function() {
                        barcodeDivEdit.html('<div class="alert alert-danger">Error al generar el código de barras</div>');
                    }
                });
            });

            $('#name').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
            $('#nameedit').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
            $('#codeedit').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            // Las validaciones ahora se manejan en product-validation.js
        });

        var select2marcaredit = $('.select2marcaredit');

        if (select2marcaredit.length) {
            var $this = select2marcaredit;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Marca',
                dropdownParent: $this.parent()
            });
        }

        var select2category = $('.select2category');

        if (select2category.length) {
            var $this = select2category;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Categoría',
                dropdownParent: $this.parent()
            });
        }

        var select2categoryedit = $('.select2categoryedit');

        if (select2categoryedit.length) {
            var $this = select2categoryedit;
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Seleccionar Categoría',
                dropdownParent: $this.parent()
            });
        }

        // Función para mostrar datos del producto en modal
        window.showProductView = function(productId) {
            const content = $('#viewProductContent');
            const editBtn = $('#viewProductEditBtn');
            content.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2 text-muted">Cargando datos del producto...</p></div>');
            editBtn.hide();
            $('#viewProductModal').modal('show');

            const baseUrl = window.baseUrl || (document.querySelector('[data-base-url]')?.getAttribute('data-base-url') || (window.location.origin + '/'));
            const url = (baseUrl.replace(/\/$/, '') + '/product/getproductid/' + btoa(productId));

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response && response[0]) {
                        const p = response[0];
                        const esc = (v) => (v == null || v === '' ? '-' : String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
                        const presColors = { tableta:'#607D8B', capsula:'#9C27B0', jarabe:'#4CAF50', suspension:'#4CAF50', ampolla:'#2196F3', frasco:'#00BCD4', crema:'#FF9800', gel:'#FF9800', sobre:'#FFC107', tubo:'#FF5722', blister:'#3F51B5', caja:'#795548', otro:'#5e6eea' };
                        const presLabels = { tableta:'TABLETA', capsula:'CÁPSULA', jarabe:'JARABE', suspension:'SUSPENSIÓN', ampolla:'AMPOLLA', frasco:'FRASCO', crema:'CREMA', gel:'GEL', sobre:'SOBRE', tubo:'TUBO', blister:'BLISTER', caja:'CAJA', otro:'MEDICAMENTO' };
                        const presType = (p.presentation_type || 'otro').toLowerCase();
                        const presBg = presColors[presType] || presColors.otro;
                        const presText = presLabels[presType] || presLabels.otro;
                        const hasCustomImg = p.image && p.image !== 'none.jpg' && !String(p.image).startsWith('http');
                        const imgUrl = hasCustomImg ? (baseUrl + 'assets/img/products/' + p.image) : ('data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="' + presBg + '" rx="12"/><text x="50" y="55" font-family="Arial,sans-serif" font-size="11" font-weight="bold" fill="white" text-anchor="middle">' + presText + '</text></svg>'));
                        const productName = esc(p.name || p.productname);
                        const statusBadge = p.state == 1 ? '<span class="badge bg-label-success">Activo</span>' : '<span class="badge bg-label-danger">Inactivo</span>';
                        const row = (label, val) => `<tr><td class="text-muted" style="width:45%">${label}</td><td>${esc(val != null && val !== '' ? val : '-')}</td></tr>`;
                        const html = `
                            <div class="product-view-modal">
                                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                    <div class="flex-shrink-0 rounded overflow-hidden" style="width:90px;height:90px;background:#f0f0f5;">
                                        <img src="${imgUrl}" alt="${productName}" class="w-100 h-100" style="object-fit:cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold">${productName}</h5>
                                        <p class="mb-1 text-muted small"><i class="ti ti-barcode me-1"></i>${esc(p.code)}</p>
                                        <div class="d-flex align-items-center gap-2">
                                            ${statusBadge}
                                            <span class="fs-4 fw-bold text-primary">$ ${parseFloat(p.price || 0).toFixed(2)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border shadow-none h-100">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-info-circle me-2"></i>Información General</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Descripción', p.description)}${row('Presentación', p.presentation_type)}${row('Unidad de medida', p.unit_measure)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border shadow-none h-100">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-tag me-2"></i>Clasificación</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Proveedor', p.provider || p.nameprovider)}${row('Marca', p.marcaname)}${row('Categoría', p.category)}${row('Laboratorio farmacéutico', p.pharmaceutical_laboratory_name)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border shadow-none">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-pill me-2"></i>Información Farmacéutica</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Especialidad', p.specialty)}${row('Nº Registro sanitario', p.registration_number)}${row('Forma de venta', p.sale_form)}${row('Tipo de producto', p.product_type)}${row('Fórmula / Ingredientes', p.formula)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border shadow-none">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-ruler me-2"></i>Presentaciones para inventario</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Pastillas por blister', p.pastillas_per_blister)}${row('Blisters por caja', p.blisters_per_caja)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border shadow-none">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-receipt me-2"></i>Información Fiscal</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Clasificación fiscal', p.cfiscal)}${row('Tipo', p.type)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border shadow-none">
                                            <div class="card-header py-2 bg-light"><h6 class="mb-0"><i class="ti ti-weight me-2"></i>Unidades de medida</h6></div>
                                            <div class="card-body py-2">
                                                <table class="table table-sm table-borderless mb-0 small"><tbody>${row('Tipo de venta', p.sale_type)}${row('Peso por unidad (lb)', p.weight_per_unit)}${row('Volumen por unidad (L)', p.volume_per_unit)}${row('Descripción contenido', p.content_per_unit)}</tbody></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        content.html(html);
                        $('#viewProductModalTitle').html('<i class="ti ti-eye me-2"></i>Detalles del Producto');
                        editBtn.off('click').on('click', function() {
                            $('#viewProductModal').modal('hide');
                            if (typeof editproduct === 'function') editproduct(productId);
                        }).show();
                    } else {
                        content.html('<div class="alert alert-danger">No se encontraron datos del producto.</div>');
                    }
                },
                error: function() {
                    content.html('<div class="alert alert-danger">Error al cargar los datos del producto.</div>');
                }
            });
        };

        // Función para mostrar el seguimiento de vencimiento de un producto
        window.showExpirationTracking = function(productId) {
            const url = window.baseUrl + 'product/expiration-tracking/' + productId;

            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        $('#expiration-content').html(response.html);
                        $('#expirationTrackingModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Error al cargar el seguimiento de vencimiento',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Hubo un error al cargar el seguimiento de vencimiento.';

                    if (xhr.status === 404) {
                        errorMessage = 'La ruta no fue encontrada. Verifique la configuración.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error interno del servidor. Verifique los logs.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Error de conexión. Verifique su conexión a internet.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        };
    </script>
@endsection

@section('title', 'Productos')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Productos</h5>
            <div class="flex-wrap gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
                <div class="gap-2 d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="ti ti-plus me-1"></i> Agregar Producto
                    </button>
                    <a href="{{ route('product-categories.index') }}" class="btn btn-sm btn-label-primary">
                        <i class="ti ti-category me-1"></i> Categorías
                    </a>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-products border-top">
                <thead>
                    <tr>
                        <th>ACCIONES</th>
                        <th>IMAGEN</th>
                        <th>CODIGO</th>
                        <th>NOMBRE</th>
                        <th>PRECIO</th>
                        <th>PROVEEDOR</th>
                        <th>MARCA</th>
                        <th>C. FISCAL</th>
                        <th>TIPO</th>
                        <th>CATEGORIA</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($products)
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:showProductView({{ $product->id }});" class="dropdown-item" title="Ver detalles">
                                            <i class="ti ti-eye ti-sm me-2"></i>Ver
                                        </a>
                                        <a href="javascript:editproduct({{ $product->id }});" class="dropdown-item"><i class="ti ti-edit ti-sm me-2"></i>Editar</a>
                                        <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                        <div class="m-0 dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('product.prices.index', $product->id) }}" class="dropdown-item" title="Gestionar precios múltiples">
                                                <i class="ti ti-tags ti-sm me-2"></i>Precios Múltiples
                                            </a>
                                            <a href="javascript:showExpirationTracking({{ $product->id }});" class="dropdown-item" title="Ver seguimiento de vencimiento">
                                                <i class="ti ti-calendar-time ti-sm me-2"></i>Vencimiento
                                            </a>
                                            @if($product->state == 1)
                                                <a href="javascript:toggleState({{ $product->id }}, 0);" class="dropdown-item"><i class="ti ti-toggle-left ti-sm me-2"></i>Desactivar</a>
                                            @else
                                                <a href="javascript:toggleState({{ $product->id }}, 1);" class="dropdown-item"><i class="ti ti-toggle-right ti-sm me-2"></i>Activar</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $imageUrl = $product->image;
                                        $isLocalImage = !empty($imageUrl) && $imageUrl != 'none.jpg' && !str_starts_with($imageUrl, 'http');

                                        if ($isLocalImage) {
                                            // Imagen subida por usuario
                                            $imgSrc = asset('assets/img/products/' . $imageUrl);
                                            $imgTitle = 'Imagen personalizada';
                                        } else {
                                            // Generar SVG local según tipo
                                            $presentationType = $product->presentation_type ?? 'otro';
                                            $imgSrc = App\Helpers\ProductImageHelper::getImageSVG($presentationType);
                                            $imgTitle = 'Imagen por defecto - ' . ucfirst($presentationType);
                                        }
                                    @endphp
                                    <img src="{{ $imgSrc }}"
                                         alt="{{ $product->name }}"
                                         width="80"
                                         height="80"
                                         style="object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                         title="{{ $imgTitle }}">
                                </td>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>$ {{ $product->price }}</td>
                                <td>{{ $product->nameprovider }}</td>
                                <td>{{ $product->marcaname }}</td>
                                <td>{{ $product->cfiscal }}</td>
                                <td>{{ $product->type }}</td>
                                <td>{{ $product->category ?? 'Sin categoría' }}</td>
                                <td>
                                    @if($product->state == 1)
                                        <span class="badge bg-label-success">Activo</span>
                                    @else
                                        <span class="badge bg-label-danger">Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No hay datos</td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>

            <!-- Add product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Crear nuevo producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        <div class="p-4 modal-body">

          @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          <form id="addproductForm" action="{{Route('product.store')}}" method="POST" enctype="multipart/form-data">
            @csrf @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">

            <!-- Sección 1: Información Básica -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-info-circle me-2"></i>Información Básica</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                <label class="form-label" for="code">Código <span class="text-danger">*</span></label>
                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="Código del producto" autofocus required value="{{ old('code') }}"/>
                <div class="invalid-feedback">El código del producto es obligatorio</div>
                @error('code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                    <div class="col-md-6">
                        <label class="form-label">Código de Barras</label>
                <div id="barcode" style="max-width: 200px; margin: 0 auto;"></div>
            </div>
                    <div class="col-12">
                        <label class="form-label" for="name">Nombre del Producto <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nombre del producto" required value="{{ old('name') }}"/>
              <div class="invalid-feedback">El nombre del producto es obligatorio</div>
              @error('name')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
                    <div class="col-12">
                <label class="form-label" for="description">Descripción <span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Descripción detallada del producto" required>{{ old('description') }}</textarea>
                <div class="invalid-feedback">La descripción del producto es obligatoria</div>
                @error('description')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Sección 2: Clasificación -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-category me-2"></i>Clasificación</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                <label for="marca" class="form-label">Marca</label>
                        <select class="select2marca form-select" id="marca" name="marca" aria-label="Seleccionar marca">
                </select>
            </div>
                    <div class="col-md-6">
                <label for="provider" class="form-label">Proveedor</label>
                        <select class="select2provider form-select" id="provider" name="provider" aria-label="Seleccionar proveedor">
                </select>
            </div>
                    <div class="col-md-6">
                <label for="category" class="form-label">Categoría</label>
                <select class="select2category form-select" id="category" name="category" aria-label="Seleccionar categoría">
                    <option value="">Seleccione una categoría</option>
                    @foreach($categoryOptions ?? [] as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
                    <!-- Campo de laboratorio farmacéutico ocultado para formato de tienda -->
                </div>
            </div>

            <hr class="my-4">

            <!-- Sección 3: Presentación del producto (ajustada para tienda/supermercado) -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-box me-2"></i>Presentación del producto</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="presentation_type" class="form-label">Tipo de presentación <span class="text-danger">*</span></label>
                        <select class="form-select" id="presentation_type" name="presentation_type" aria-label="Seleccionar presentación" required>
                            <option value="">Seleccione una presentación</option>
                            <option value="unidad" {{ old('presentation_type') == 'unidad' ? 'selected' : '' }}>Unidad</option>
                            <option value="caja" {{ old('presentation_type') == 'caja' ? 'selected' : '' }}>Caja</option>
                            <option value="paquete" {{ old('presentation_type') == 'paquete' ? 'selected' : '' }}>Paquete</option>
                            <option value="bolsa" {{ old('presentation_type') == 'bolsa' ? 'selected' : '' }}>Bolsa</option>
                            <option value="botella" {{ old('presentation_type') == 'botella' ? 'selected' : '' }}>Botella</option>
                            <option value="lata" {{ old('presentation_type') == 'lata' ? 'selected' : '' }}>Lata</option>
                            <option value="otro" {{ old('presentation_type') == 'otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                    <div class="col-md-4">
                        <label for="unit_measure" class="form-label">Unidad de medida (opcional)</label>
                        <input type="text" id="unit_measure" name="unit_measure" class="form-control" placeholder="Ej: kg, lt, unidad" value="{{ old('unit_measure') }}"/>
                    </div>
                </div>
            </div>

            <!-- Sección 3.5 eliminada: conversiones farmacéuticas no aplican a tienda genérica -->

            <hr class="my-4">

            <!-- Sección 4: Información Fiscal y Precio -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-receipt me-2"></i>Información Fiscal y Precio</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                <label for="cfiscal" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                        <select class="select2cfiscal form-select @error('cfiscal') is-invalid @enderror" id="cfiscal" name="cfiscal" required>
                    <option value="">Seleccione</option>
                    <option value="gravado" {{ old('cfiscal') == 'gravado' ? 'selected' : '' }}>Gravado</option>
                    <option value="exento" {{ old('cfiscal') == 'exento' ? 'selected' : '' }}>Exento</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una clasificación fiscal</div>
                @error('cfiscal')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                    <div class="col-md-4">
                <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="select2type form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                    <option value="">Seleccione</option>
                    <option value="directo" {{ old('type') == 'directo' ? 'selected' : '' }}>Directo</option>
                    <option value="tercero" {{ old('type') == 'tercero' ? 'selected' : '' }}>Tercero</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar un tipo</div>
                @error('type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                    <div class="col-md-4">
                <label class="form-label" for="price">Precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" step="0.01" min="0" name="price" required value="{{ old('price') }}"/>
                        </div>
                        <div class="invalid-feedback">El precio es obligatorio</div>
                @error('price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
                            </div>
                        </div>


            <hr class="my-4">

            <!-- Sección 6: Imagen -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-photo me-2"></i>Imagen del Producto</h6>
                <div class="row">
                    <div class="col-12">
                <x-simple-image-upload
                    name="image"
                            label=""
                    :required="false"
                />
            </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="px-0 pb-0 modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="createProductBtn">
                    <i class="ti ti-check me-1"></i>Crear Producto
                </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

             <!-- Add update Modal -->
<div class="modal fade" id="updateProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        <div class="p-4 modal-body">

          @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          <form id="editproductForm" action="{{Route('product.update')}}" method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <input type="hidden" name="idedit" id="idedit">

            <!-- Sección 1: Información Básica -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-info-circle me-2"></i>Información Básica</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                <label class="form-label" for="codeedit">Código</label>
                <input type="text" id="codeedit" name="codeedit" class="form-control" placeholder="Código del producto" autofocus/>
                <small class="form-text text-muted">Puede editar el código si es necesario.</small>
            </div>
                    <div class="col-md-6">
                        <label class="form-label">Código de Barras</label>
                <div id="barcodeedit" style="max-width: 200px; margin: 0 auto;"></div>
            </div>
                    <div class="col-12">
                        <label class="form-label" for="nameedit">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" id="nameedit" name="nameedit" class="form-control" placeholder="Nombre del producto" required/>
              <div class="invalid-feedback">El nombre del producto es obligatorio</div>
            </div>
                    <div class="col-12">
                <label class="form-label" for="descriptionedit">Descripción <span class="text-danger">*</span></label>
                        <textarea id="descriptionedit" class="form-control" name="descriptionedit" rows="3" placeholder="Descripción detallada del producto" required></textarea>
                <div class="invalid-feedback">La descripción del producto es obligatoria</div>
            </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Sección 2: Clasificación -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-category me-2"></i>Clasificación</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                <label for="marcaedit" class="form-label">Marca</label>
                        <select class="select2marcaredit form-select" id="marcaredit" name="marcaredit" aria-label="Seleccionar marca">
                </select>
            </div>
                    <div class="col-md-6">
                <label for="provideredit" class="form-label">Proveedor</label>
                        <select class="select2provideredit form-select" id="provideredit" name="provideredit" aria-label="Seleccionar proveedor">
                </select>
            </div>
                    <div class="col-md-6">
                <label for="categoryedit" class="form-label">Categoría</label>
                <select class="select2categoryedit form-select" id="categoryedit" name="categoryedit" aria-label="Seleccionar categoría">
                    <option value="">Seleccione una categoría</option>
                    @foreach($categoryOptions ?? [] as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
                    <!-- Campo de laboratorio farmacéutico ocultado para formato de tienda -->
                </div>
            </div>

            <hr class="my-4">

            <!-- Sección 3: Presentación del producto (ajustada para tienda/supermercado) -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-box me-2"></i>Presentación del producto</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="presentation_typeedit" class="form-label">Tipo de presentación <span class="text-danger">*</span></label>
                        <select class="form-select" id="presentation_typeedit" name="presentation_typeedit" aria-label="Seleccionar presentación" required>
                            <option value="">Seleccione una presentación</option>
                            <option value="unidad">Unidad</option>
                            <option value="caja">Caja</option>
                            <option value="paquete">Paquete</option>
                            <option value="bolsa">Bolsa</option>
                            <option value="botella">Botella</option>
                            <option value="lata">Lata</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_measureedit" class="form-label">Unidad de medida (opcional)</label>
                        <input type="text" id="unit_measureedit" name="unit_measureedit" class="form-control" placeholder="Ej: kg, lt, unidad"/>
                    </div>
                </div>
            </div>

            <!-- Sección 3.5 eliminada: conversiones farmacéuticas no aplican a tienda genérica -->

            <hr class="my-4">

            <!-- Sección 4: Información Fiscal y Precio -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-receipt me-2"></i>Información Fiscal y Precio</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                <label for="cfiscaledit" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                        <select class="select2cfiscaledit form-select" id="cfiscaledit" name="cfiscaledit" required>
                    <option value="">Seleccione</option>
                    <option value="gravado">Gravado</option>
                    <option value="exento">Exento</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar una clasificación fiscal</div>
            </div>
                    <div class="col-md-4">
                <label for="typeedit" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="select2typeedit form-select" id="typeedit" name="typeedit" required>
                    <option value="">Seleccione</option>
                    <option value="directo">Directo</option>
                    <option value="tercero">Tercero</option>
                </select>
                <div class="invalid-feedback">Debe seleccionar un tipo</div>
            </div>
                    <div class="col-md-4">
                <label class="form-label" for="priceedit">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="priceedit" class="form-control" placeholder="0.00" step="0.01" min="0" name="priceedit"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 5: Unidades de Medida (Acordeón) -->
            <div class="mb-4">
                <div class="accordion" id="uomCalculatorEdit">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingCalcEdit">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCalcEdit" aria-expanded="false" aria-controls="collapseCalcEdit">
                                <i class="ti ti-ruler me-2"></i>
                                Configuración de Unidades de Medida (Opcional)
                            </button>
                        </h2>
                        <div id="collapseCalcEdit" class="accordion-collapse collapse" aria-labelledby="headingCalcEdit" data-bs-parent="#uomCalculatorEdit">
                            <div class="accordion-body">
                                                <div class="row">
                            <div class="mb-3 col-12">
                                <label for="sale_type_edit" class="form-label">Tipo de Venta <span class="text-danger">*</span></label>
                                <select class="form-select" id="sale_type_edit" name="sale_type_edit" required>
                                    <option value="">Seleccione el tipo de venta</option>
                                    <option value="unit">Por Unidad (precio normal)</option>
                                    <option value="weight">Por Peso (libras, kg, etc.)</option>
                                    <option value="volume">Por Volumen (litros, ml, etc.)</option>
                                </select>
                                <div class="invalid-feedback">Debe seleccionar el tipo de venta</div>
                            </div>
                        </div>

                        <!-- Campos para productos por peso (Edición) -->
                        <div id="weight_fields_edit" class="row" style="display: none;">
                            <div class="mb-3 col-6">
                                <label for="weight_per_unit_edit" class="form-label">Peso Total en Libras</label>
                                <input type="number" id="weight_per_unit_edit" name="weight_per_unit_edit" class="form-control" placeholder="0.00" step="0.01" min="0"/>
                                <small class="form-text text-muted">Ej: 55 libras (peso total del saco)</small>
                            </div>
                            <div class="mb-3 col-6">
                                <label class="form-label">Precio Total</label>
                                <small class="form-text text-muted d-block">Usa el campo "Precio" de arriba para establecer el precio total. Se recalcula automáticamente.</small>
                            </div>
                        </div>

                        <!-- Campos para productos por volumen (Edición) -->
                        <div id="volume_fields_edit" class="row" style="display: none;">
                            <div class="mb-3 col-6">
                                <label for="volume_per_unit_edit" class="form-label">Volumen Total en Litros</label>
                                <input type="number" id="volume_per_unit_edit" name="volume_per_unit_edit" class="form-control" placeholder="0.00" step="0.01" min="0"/>
                                <small class="form-text text-muted">Ej: 5 litros (volumen total del galón)</small>
                            </div>
                            <div class="mb-3 col-6">
                                <label class="form-label">Precio Total</label>
                                <small class="form-text text-muted d-block">Usa el campo "Precio" de arriba para establecer el precio total. Se recalcula automáticamente.</small>
                            </div>
                        </div>

                        <div class="mb-3 col-12">
                            <label for="content_per_unit_edit" class="form-label">Descripción del Contenido</label>
                            <input type="text" id="content_per_unit_edit" name="content_per_unit_edit" class="form-control" placeholder="Ej: 55 libras por saco, 5 litros por galón"/>
                            <small class="form-text text-muted">Descripción clara del contenido por unidad</small>
                        </div>

                        <!-- Información de conversiones (Edición) -->
                        <div id="conversion_info_edit" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-info-circle me-2"></i>Cálculos Automáticos</h6>
                            <div id="conversion_details_edit">
                                <p class="mb-1"><strong>Precio por libra:</strong> <span id="price_per_lb_edit">$0.00</span></p>
                                <p class="mb-1"><strong>Precio por kilogramo:</strong> <span id="price_per_kg_edit">$0.00</span></p>
                                <p class="mb-1"><strong>Precio por unidad completa:</strong> <span id="price_per_sack_edit">$0.00</span></p>
                                <p class="mb-1"><strong>Libras por dólar:</strong> <span id="value_per_dollar_edit">0.00 libras</span></p>
                            </div>

                        </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Sección 6: Imagen -->
            <div class="mb-4">
                <h6 class="mb-3 text-muted text-uppercase"><i class="ti ti-photo me-2"></i>Imagen del Producto</h6>
                <div class="row">
                    <div class="col-12">
            <!-- Preview de imagen actual para edición -->
                        <div id="current-image-imageedit" class="mb-3"></div>
                <x-simple-image-upload
                    name="imageedit"
                            label=""
                    :required="false"
                />
            </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="px-0 pb-0 modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="updateProductBtn">
                    <i class="ti ti-check me-1"></i>Guardar Cambios
                </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

    <!-- Modal para Ver Datos del Producto -->
    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="viewProductModalTitle"><i class="ti ti-eye me-2"></i>Detalles del Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewProductContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando datos del producto...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="viewProductEditBtn" style="display:none;">
                        <i class="ti ti-edit me-1"></i>Editar
                    </button>
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
    @endsection
