@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Catálogo de Exámenes')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Laboratorio /</span> Catálogo de Exámenes
        </h4>
    </div>
</div>

<div class="row">
    <!-- Categorías -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Categorías</h6>
                <button class="btn btn-xs btn-primary" id="btnNuevaCategoria">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="listaCategorias">
                    @foreach($categories as $category)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 category-item"
                        data-id="{{ $category->id }}" style="cursor: pointer;">
                        <div>
                            <i class="fa-solid fa-folder me-2 text-primary"></i>
                            {{ $category->nombre }}
                        </div>
                        <span class="badge bg-label-primary rounded-pill">{{ $category->exams->count() }}</span>
                    </li>
                    @endforeach
                    @if($categories->count() == 0)
                    <li class="list-group-item text-center text-muted">
                        <small>No hay categorías</small>
                    </li>
                    @endif
                </ul>
                <button class="btn btn-sm btn-outline-primary w-100 mt-3" id="btnVerTodos">
                    <i class="fa-solid fa-list me-1"></i>Ver Todos
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de Exámenes -->
    <div class="col-md-9 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Exámenes de Laboratorio</h5>
                    <small class="text-muted" id="filtroActual">Mostrando todos los exámenes</small>
                </div>
                <button type="button" class="btn btn-primary" id="btnNuevoExamen">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo Examen
                </button>
            </div>
            <div class="card-body">
                <!-- Panel de Búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa-solid fa-search"></i>
                            </span>
                            <input type="text"
                                   class="form-control"
                                   id="searchInput"
                                   placeholder="Buscar por nombre, código o tipo de muestra..."
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" style="display: none;">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Escribe para buscar exámenes por nombre, código o tipo de muestra
                        </small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="tableExamenes">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre del Examen</th>
                                <th>Categoría</th>
                                <th>Tipo Muestra</th>
                                <th>Tiempo Proc.</th>
                                <th>Precio</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="bodyExamenes">
                            <tr>
                                <td colspan="9" class="text-center">
                                    <i class="fa-solid fa-spinner fa-spin fa-2x text-muted my-4"></i>
                                    <p class="text-muted">Cargando exámenes...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Categoría de Exámenes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaCategoria">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required
                            placeholder="Ej: Hematología, Química Clínica">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" class="form-control" name="orden" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Examen -->
<div class="modal fade" id="modalExamen" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExamenTitle">Nuevo Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formExamen">
                @csrf
                <input type="hidden" id="exam_id" name="exam_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="category_id" required>
                                <option value="">Seleccione categoría</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Examen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required
                                placeholder="Ej: Hemograma Completo o Ácido Valpróico"
                                list="exam-templates">
                            <datalist id="exam-templates">
                                <option value="Ácido Valpróico">
                                <option value="Acido Valproico">
                                <option value="Ácido Valproico">
                            </datalist>
                            <small class="text-muted">Escriba el nombre del examen. Si tiene formato específico, se cargarán los campos correspondientes.</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="descripcion" rows="2"
                                placeholder="Descripción del examen"></textarea>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo de Muestra <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_muestra" id="tipo_muestra" required>
                                <option value="Sangre">Sangre</option>
                                <option value="Orina">Orina</option>
                                <option value="Heces">Heces</option>
                                <option value="Saliva">Saliva</option>
                                <option value="Esputo">Esputo</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tiempo Procesamiento <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="tiempo_procesamiento_horas"
                                    id="tiempo_procesamiento_horas" required value="24" min="1">
                                <span class="input-group-text">horas</span>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="precio"
                                    id="precio" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unidad de Medida</label>
                            <select class="form-select" name="unidad_medida" id="unidad_medida">
                                <option value="">Seleccione una unidad</option>
                                <optgroup label="Unidades de Hormonas">
                                    <option value="mUI/mL">mUI/mL</option>
                                    <option value="mIU/mL">mIU/mL</option>
                                    <option value="UI/mL">UI/mL</option>
                                    <option value="U/mL">U/mL</option>
                                    <option value="UI/L">UI/L</option>
                                    <option value="mUI/L">mUI/L</option>
                                </optgroup>
                                <optgroup label="Unidades de Peso/Volumen">
                                    <option value="ng/mL">ng/mL</option>
                                    <option value="ug/mL">ug/mL</option>
                                    <option value="pg/mL">pg/mL</option>
                                    <option value="mg/dL">mg/dL</option>
                                    <option value="g/dL">g/dL</option>
                                    <option value="ug/dL">ug/dL</option>
                                    <option value="ng/dL">ng/dL</option>
                                </optgroup>
                                <optgroup label="Unidades Molares">
                                    <option value="mmol/L">mmol/L</option>
                                    <option value="umol/L">umol/L</option>
                                    <option value="pmol/L">pmol/L</option>
                                    <option value="nmol/L">nmol/L</option>
                                </optgroup>
                                <optgroup label="Unidades de Concentración">
                                    <option value="g/L">g/L</option>
                                    <option value="mg/L">mg/L</option>
                                    <option value="%">%</option>
                                </optgroup>
                                <optgroup label="Unidades de Tiempo">
                                    <option value="minutos">minutos</option>
                                    <option value="horas">horas</option>
                                    <option value="días">días</option>
                                    <option value="segundos">segundos</option>
                                </optgroup>
                                <optgroup label="Unidades Hematológicas">
                                    <option value="cel/uL">cel/uL</option>
                                    <option value="x10³/uL">x10³/uL</option>
                                    <option value="x10⁶/uL">x10⁶/uL</option>
                                    <option value="mm/h">mm/h</option>
                                </optgroup>
                                <optgroup label="Otras Unidades">
                                    <option value="mm">mm</option>
                                    <option value="cm">cm</option>
                                    <option value="L/min">L/min</option>
                                    <option value="mL/min">mL/min</option>
                                    <option value="mL/Minuto">mL/Minuto</option>
                                    <option value="mL/24 Horas">mL/24 Horas</option>
                                    <option value="UFC/mL">UFC/mL</option>
                                </optgroup>
                            </select>
                            <small class="text-muted">Seleccione la unidad de medida del resultado</small>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Preparación Requerida</label>
                            <textarea class="form-control" name="preparacion_requerida" id="preparacion_requerida" rows="2"
                                placeholder="Ej: Ayuno de 8 horas, No tomar medicamentos 24h antes"></textarea>
                        </div>

                        <!-- Campos dinámicos según el template del examen -->
                        <div id="template-fields" class="col-12 mb-3" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <i class="fa-solid fa-flask me-2"></i>Configuración Específica del Examen
                                </div>
                                <div class="card-body" id="template-fields-content">
                                    <!-- Los campos se cargarán dinámicamente aquí -->
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Valores de Referencia (General)</label>
                            <textarea class="form-control" name="valores_referencia" id="valores_referencia" rows="2"
                                placeholder="Ej: Hombres: 4.5-5.5 millones/uL, Mujeres: 4.0-5.0 millones/uL"></textarea>
                            <small class="text-muted">Valores de referencia generales. Si el examen tiene formato específico, use los campos de arriba.</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" name="prioridad" id="prioridad">
                                <option value="normal" selected>Normal</option>
                                <option value="urgente">Urgente</option>
                                <option value="stat">STAT (Inmediato)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="requiere_ayuno" name="requiere_ayuno" value="1">
                                <label class="form-check-label" for="requiere_ayuno">Requiere Ayuno</label>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
let categoriaSeleccionada = null;
let terminoBusqueda = '';

$(document).ready(function() {
    cargarExamenes();

    // Búsqueda en tiempo real con debounce
    let searchTimeout;
    $('#searchInput').on('input', function() {
        const valor = $(this).val().trim();
        terminoBusqueda = valor;

        // Mostrar/ocultar botón de limpiar
        if (valor.length > 0) {
            $('#btnClearSearch').show();
        } else {
            $('#btnClearSearch').hide();
        }

        // Debounce: esperar 500ms después de que el usuario deje de escribir
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            cargarExamenes();
        }, 500);
    });

    // Limpiar búsqueda
    $('#btnClearSearch').on('click', function() {
        $('#searchInput').val('');
        terminoBusqueda = '';
        $(this).hide();
        cargarExamenes();
    });

    // Permitir búsqueda con Enter
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            clearTimeout(searchTimeout);
            cargarExamenes();
        }
    });

    // Botón nueva categoría
    $('#btnNuevaCategoria').on('click', function() {
        $('#formNuevaCategoria')[0].reset();
        $('#formNuevaCategoria').find('input[name="orden"]').val('0');
        $('#modalNuevaCategoria').modal('show');
    });

    // Resetear formulario al cerrar el modal
    $('#modalNuevaCategoria').on('hidden.bs.modal', function() {
        $('#formNuevaCategoria')[0].reset();
        $('#formNuevaCategoria').find('button[type="submit"]').prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar');
    });

    // Guardar nueva categoría
    $('#formNuevaCategoria').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Deshabilitar botón y mostrar carga
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: '/lab-categories/store',
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || 'Categoría creada exitosamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalNuevaCategoria').modal('hide');
                    form[0].reset();
                    // Recargar la página para mostrar la nueva categoría
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al crear la categoría'
                    });
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al crear la categoría';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                } else if (xhr.status === 404) {
                    errorMessage = 'La ruta no existe. Verifique la configuración del servidor.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error interno del servidor. Por favor, intente nuevamente.';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage
                });
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Filtrar por categoría
    $('.category-item').on('click', function() {
        $('.category-item').removeClass('active bg-light');
        $(this).addClass('active bg-light');

        categoriaSeleccionada = $(this).data('id');
        const categoriaNombre = $(this).find('div').text().trim();
        actualizarFiltroActual('Categoría: ' + categoriaNombre);
        cargarExamenes();
    });

    // Ver todos
    $('#btnVerTodos').on('click', function() {
        $('.category-item').removeClass('active bg-light');
        categoriaSeleccionada = null;
        actualizarFiltroActual('Mostrando todos los exámenes');
        cargarExamenes();
    });

    // Nuevo examen
    $('#btnNuevoExamen').on('click', function() {
        $('#exam_id').val('');
        $('#modalExamenTitle').text('Nuevo Examen');
        $('#formExamen')[0].reset();
        $('#activo').prop('checked', true);
        $('#template-fields').hide();
        $('#template-fields-content').html('');
        $('#modalExamen').modal('show');
    });

    // Detectar tipo de examen y cargar template
    $('#nombre').on('input blur', function() {
        const nombre = $(this).val().toLowerCase().trim();
        detectarTemplateExamen(nombre);
    });

    // Guardar examen
    $('#formExamen').on('submit', function(e) {
        e.preventDefault();

        const examId = $('#exam_id').val();
        const url = examId ? `/lab-exams/${examId}` : '/lab-exams/store';
        const method = examId ? 'PUT' : 'POST';

        // Preparar datos del formulario
        let formData = $(this).serialize();

        // Si es edición (PUT), agregar el campo _method para Laravel
        if (examId) {
            formData += '&_method=PUT';
        }

        $.ajax({
            url: url,
            method: 'POST', // Laravel requiere POST cuando se usa _method
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#modalExamen').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000
                    });
                    cargarExamenes();
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al guardar el examen';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Si hay errores de validación
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage
                });
            }
        });
    });
});

function actualizarFiltroActual(texto) {
    if (terminoBusqueda) {
        $('#filtroActual').html(texto + ' <span class="badge bg-info">Buscando: "' + terminoBusqueda + '"</span>');
    } else {
        $('#filtroActual').text(texto);
    }
}

function cargarExamenes() {
    let url = '/lab-exams/data';
    const params = [];

    if (categoriaSeleccionada) {
        params.push(`category_id=${categoriaSeleccionada}`);
    }

    if (terminoBusqueda) {
        params.push(`search=${encodeURIComponent(terminoBusqueda)}`);
    }

    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    // Mostrar indicador de carga
    $('#bodyExamenes').html(`
        <tr>
            <td colspan="9" class="text-center">
                <i class="fa-solid fa-spinner fa-spin fa-2x text-muted my-4"></i>
                <p class="text-muted">${terminoBusqueda ? 'Buscando exámenes...' : 'Cargando exámenes...'}</p>
            </td>
        </tr>
    `);

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            let html = '';

            if (response.data && response.data.length > 0) {
                response.data.forEach(exam => {
                    html += `
                        <tr>
                            <td><code>${exam.codigo_examen}</code></td>
                            <td>
                                <strong>${exam.nombre}</strong>
                                ${exam.descripcion ? `<br><small class="text-muted">${exam.descripcion.substring(0, 60)}...</small>` : ''}
                            </td>
                            <td><span class="badge bg-label-primary">${exam.category.nombre}</span></td>
                            <td>${exam.tipo_muestra}</td>
                            <td>${exam.tiempo_procesamiento_horas}h</td>
                            <td><strong>$${parseFloat(exam.precio).toFixed(2)}</strong></td>
                            <td>${exam.unidad_medida ? `<span class="badge bg-label-info">${exam.unidad_medida}</span>` : '<span class="text-muted">-</span>'}</td>
                            <td>
                                <span class="badge bg-label-${exam.activo ? 'success' : 'secondary'}">
                                    ${exam.activo ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-sm btn-outline-info" onclick="verExamen(${exam.id})" title="Ver examen">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editarExamen(${exam.id})" title="Editar examen">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarExamen(${exam.id})" title="Eliminar examen">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                if (terminoBusqueda) {
                    html = `
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa-solid fa-search fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No se encontraron exámenes que coincidan con "<strong>${terminoBusqueda}</strong>"</p>
                                <button class="btn btn-outline-secondary btn-sm" id="btnLimpiarBusquedaVacio">
                                    <i class="fa-solid fa-times me-1"></i>Limpiar búsqueda
                                </button>
                            </td>
                        </tr>
                    `;
                } else {
                    html = `
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa-solid fa-flask fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No hay exámenes registrados</p>
                                <button class="btn btn-primary btn-sm" id="btnNuevoExamenVacio">
                                    <i class="fa-solid fa-plus me-1"></i>Crear Primer Examen
                                </button>
                            </td>
                        </tr>
                    `;
                }
            }

            $('#bodyExamenes').html(html);

            // Agregar evento al botón de limpiar búsqueda si existe
            $('#btnLimpiarBusquedaVacio').on('click', function() {
                $('#searchInput').val('');
                terminoBusqueda = '';
                $('#btnClearSearch').hide();
                cargarExamenes();
            });

            // Agregar evento al botón de nuevo examen si existe
            $('#btnNuevoExamenVacio').on('click', function() {
                $('#btnNuevoExamen').click();
            });
        }
    });
}

function editarExamen(id) {
    $.ajax({
        url: `/lab-exams/${id}`,
        method: 'GET',
        success: function(exam) {
            $('#exam_id').val(exam.id);
            $('#modalExamenTitle').text('Editar Examen');
            $('#category_id').val(exam.category_id);
            $('#nombre').val(exam.nombre);
            $('#descripcion').val(exam.descripcion || '');
            $('#tipo_muestra').val(exam.tipo_muestra);
            $('#tiempo_procesamiento_horas').val(exam.tiempo_procesamiento_horas);
            $('#precio').val(exam.precio);
            $('#unidad_medida').val(exam.unidad_medida || '');
            $('#preparacion_requerida').val(exam.preparacion_requerida || '');
            $('#valores_referencia').val(exam.valores_referencia || '');
            $('#prioridad').val(exam.prioridad || 'normal');
            $('#requiere_ayuno').prop('checked', exam.requiere_ayuno || false);
            $('#activo').prop('checked', exam.activo !== undefined ? exam.activo : true);

            // Cargar template si existe
            if (exam.template_id) {
                cargarTemplateParaEdicion(exam);
            } else {
                $('#template-fields').hide();
                $('#template-fields-content').html('');
                $('input[name="template_id"]').remove();
            }

            $('#modalExamen').modal('show');
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message || 'Error al cargar el examen para edición'
            });
        }
    });
}

// Cargar template para edición
function cargarTemplateParaEdicion(exam) {
    if (exam.template_id === 'acido_valproico') {
        const valores = exam.valores_referencia_especificos || {};
        const html = `
            <input type="hidden" name="template_id" value="acido_valproico">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label text-primary"><strong>Categoría del Examen</strong></label>
                    <input type="text" class="form-control" name="categoria_examen" value="DROGAS TERAPEUTICAS" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><strong>Unidad de Medida</strong> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="unidad_medida" value="${valores.unidad_medida || 'ug/mL'}" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label text-primary"><strong>Valores de Referencia</strong></label>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rango Terapéutico <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" name="ref_terapeutico_min"
                            value="${valores.terapeutico?.min || 50}" placeholder="Mínimo" required>
                        <span class="input-group-text">a</span>
                        <input type="number" step="0.01" class="form-control" name="ref_terapeutico_max"
                            value="${valores.terapeutico?.max || 100}" placeholder="Máximo" required>
                        <span class="input-group-text">ug/mL</span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rango Tóxico <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" name="ref_toxico_min"
                            value="${valores.toxico?.min || 100}" placeholder="Mínimo" required>
                        <span class="input-group-text">Mayor de</span>
                        <span class="input-group-text">ug/mL</span>
                    </div>
                </div>
            </div>
        `;
        $('#template-fields-content').html(html);
        $('#template-fields').slideDown();
    }
}

function verExamen(id) {
    $.ajax({
        url: `/lab-exams/${id}`,
        method: 'GET',
        success: function(exam) {
            Swal.fire({
                title: exam.nombre,
                html: `
                    <div class="text-start">
                        <p><strong>Código:</strong> ${exam.codigo_examen}</p>
                        <p><strong>Categoría:</strong> ${exam.category ? exam.category.nombre : 'N/A'}</p>
                        <p><strong>Tipo de Muestra:</strong> ${exam.tipo_muestra || 'N/A'}</p>
                        <p><strong>Tiempo de Procesamiento:</strong> ${exam.tiempo_procesamiento_horas || 0} horas</p>
                        <p><strong>Precio:</strong> $${parseFloat(exam.precio || 0).toFixed(2)}</p>
                        ${exam.unidad_medida ? `<p><strong>Unidad de Medida:</strong> <span class="badge bg-label-info">${exam.unidad_medida}</span></p>` : ''}
                        <p><strong>Prioridad:</strong> ${exam.prioridad || 'normal'}</p>
                        ${exam.requiere_ayuno ? '<p><strong class="text-warning">⚠️ Requiere Ayuno</strong></p>' : ''}
                        ${exam.activo ? '<p><span class="badge bg-success">Activo</span></p>' : '<p><span class="badge bg-secondary">Inactivo</span></p>'}
                        ${exam.descripcion ? `<p><strong>Descripción:</strong><br>${exam.descripcion}</p>` : ''}
                        ${exam.preparacion_requerida ? `<p><strong>Preparación:</strong><br>${exam.preparacion_requerida}</p>` : ''}
                        ${exam.valores_referencia ? `<p><strong>Valores de Referencia:</strong><br>${exam.valores_referencia}</p>` : ''}
                    </div>
                `,
                width: 600,
                confirmButtonText: 'Cerrar'
            });
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error al cargar el examen', 'error');
        }
    });
}

// Detectar y cargar template de examen
function detectarTemplateExamen(nombre) {
    // Limpiar campos anteriores
    $('#template-fields').hide();
    $('#template-fields-content').html('');
    $('input[name="template_id"]').remove();

    // Detectar Ácido Valpróico
    if (nombre.includes('ácido valpróico') || nombre.includes('acido valproico') ||
        nombre.includes('valpróico') || nombre.includes('valproico')) {
        cargarTemplateAcidoValproico();
    }
}

// Cargar template para Ácido Valpróico
function cargarTemplateAcidoValproico() {
    const html = `
        <input type="hidden" name="template_id" value="acido_valproico">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label text-primary"><strong>Categoría del Examen</strong></label>
                <input type="text" class="form-control" name="categoria_examen" value="DROGAS TERAPEUTICAS" readonly>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Unidad de Medida</strong> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="unidad_medida" value="ug/mL" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label text-primary"><strong>Valores de Referencia</strong></label>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Rango Terapéutico <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control" name="ref_terapeutico_min"
                        value="50" placeholder="Mínimo" required>
                    <span class="input-group-text">a</span>
                    <input type="number" step="0.01" class="form-control" name="ref_terapeutico_max"
                        value="100" placeholder="Máximo" required>
                    <span class="input-group-text">ug/mL</span>
                </div>
                <small class="text-muted">Rango terapéutico: 50 a 100 ug/mL</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Rango Tóxico <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control" name="ref_toxico_min"
                        value="100" placeholder="Mínimo" required>
                    <span class="input-group-text">Mayor de</span>
                    <span class="input-group-text">ug/mL</span>
                </div>
                <small class="text-muted">Tóxico: Mayor de 100 ug/mL</small>
            </div>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <strong>Formato específico:</strong> Este examen se mostrará con el formato de "DROGAS TERAPEUTICAS"
                    con los rangos de referencia terapéutico y tóxico configurados arriba.
                </div>
            </div>
        </div>
    `;

    $('#template-fields-content').html(html);
    $('#template-fields').slideDown();
}

function eliminarExamen(id) {
    Swal.fire({
        title: '¿Eliminar examen?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/lab-exams/${id}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Eliminado!', response.message, 'success');
                        cargarExamenes();
                    } else {
                        Swal.fire('Error', response.message || 'Error al eliminar el examen', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error al eliminar el examen', 'error');
                }
            });
        }
    });
}
</script>
@endsection

