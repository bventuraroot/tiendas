@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nueva Orden de Laboratorio')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}">
<style>
.exam-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #e0e0e0;
}
.exam-card:hover {
    border-color: #696cff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.exam-card.selected {
    border-color: #696cff;
    background-color: #f5f5ff;
}
.exam-selected-badge {
    position: absolute;
    top: 10px;
    right: 10px;
}
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-flask me-2"></i>Nueva Orden de Laboratorio</h5>
                <a href="/lab-orders" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver a Órdenes
                </a>
            </div>
            <div class="card-body">
                <form id="formNuevaOrden" method="POST" action="{{ route('lab-orders.store') }}">
                    @csrf

                    @if($consultation)
                    <input type="hidden" name="consultation_id" value="{{ $consultation->id }}">
                    <div class="alert alert-info mb-4">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Orden generada desde consulta: <strong>{{ $consultation->numero_consulta }}</strong>
                    </div>
                    @endif

                    <!-- Información del Paciente -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3"><i class="fa-solid fa-user-injured me-2"></i>Información del Paciente</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="patient_id">Paciente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select select2" id="patient_id" name="patient_id" required
                                    @if($consultation) disabled @endif>
                                    <option value="">Seleccione un paciente</option>
                                    @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}"
                                        @if($consultation && $consultation->patient_id == $patient->id) selected @endif
                                        data-nombre="{{ $patient->nombre_completo }}"
                                        data-documento="{{ $patient->documento_identidad }}"
                                        data-telefono="{{ $patient->telefono }}">
                                        {{ $patient->nombre_completo }} - {{ $patient->documento_identidad }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(!$consultation)
                                <button type="button" class="btn btn-outline-primary" id="btnNuevoPaciente" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">
                                    <i class="fa-solid fa-user-plus me-1"></i>Nuevo
                                </button>
                                @endif
                            </div>
                            @if($consultation)
                            <input type="hidden" name="patient_id" value="{{ $consultation->patient_id }}">
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="doctor_id">Médico Solicitante</label>
                            <select class="form-select select2" id="doctor_id" name="doctor_id"
                                @if($consultation) disabled @endif>
                                <option value="">Sin médico (orden externa)</option>
                                @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}"
                                    @if($consultation && $consultation->doctor_id == $doctor->id) selected @endif>
                                    {{ $doctor->nombre_completo }} - {{ $doctor->especialidad }}
                                </option>
                                @endforeach
                            </select>
                            @if($consultation)
                            <input type="hidden" name="doctor_id" value="{{ $consultation->doctor_id }}">
                            @endif
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Documento</label>
                            <input type="text" class="form-control bg-light" id="patient_documento" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control bg-light" id="patient_telefono" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="prioridad">Prioridad <span class="text-danger">*</span></label>
                            <select class="form-select" id="prioridad" name="prioridad" required>
                                <option value="normal" selected>Normal (72 horas)</option>
                                <option value="urgente">Urgente (12 horas)</option>
                                <option value="stat">STAT - Inmediato (2 horas)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Selección de Exámenes -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-success mb-3"><i class="fa-solid fa-vial me-2"></i>Seleccionar Exámenes</h6>
                        </div>

                        <!-- Filtro por categoría -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Filtrar por Categoría</label>
                            <select class="form-select" id="filtroCategoria">
                                <option value="">Todas las categorías</option>
                                @foreach($exams->groupBy('category.nombre') as $categoryName => $categoryExams)
                                <option value="{{ $categoryName }}">{{ $categoryName }} ({{ $categoryExams->count() }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buscar Examen</label>
                            <input type="text" class="form-control" id="buscarExamen" placeholder="Buscar por nombre...">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary" id="btnLimpiarSeleccion">
                                <i class="fa-solid fa-times me-1"></i>Limpiar Selección
                            </button>
                        </div>

                        <!-- Lista de exámenes disponibles -->
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <strong>Exámenes Seleccionados: <span id="cantidadSeleccionados">0</span></strong> -
                                Total: $<span id="totalSeleccionados">0.00</span>
                            </div>

                            <div class="row" id="listaExamenes">
                                @foreach($exams as $exam)
                                <div class="col-md-4 mb-3 exam-item"
                                    data-id="{{ $exam->id }}"
                                    data-category="{{ $exam->category->nombre }}"
                                    data-nombre="{{ $exam->nombre }}">
                                    <div class="card exam-card h-100" onclick="toggleExamen({{ $exam->id }}, {{ $exam->precio }}, '{{ $exam->nombre }}')">
                                        <div class="card-body position-relative">
                                            <span class="exam-selected-badge badge bg-success d-none" id="badge-{{ $exam->id }}">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <h6 class="card-title">{{ $exam->nombre }}</h6>
                                            @if($exam->requiere_ayuno)
                                            <span class="badge bg-label-warning mb-2">
                                                <i class="fa-solid fa-utensils-slash me-1"></i>Requiere Ayuno
                                            </span>
                                            @endif
                                            <p class="card-text small text-muted">
                                                <i class="fa-solid fa-vial me-1"></i>{{ $exam->tipo_muestra }}<br>
                                                <i class="fa-solid fa-clock me-1"></i>{{ $exam->tiempo_procesamiento_horas }}h
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-label-primary">{{ $exam->category->nombre }}</span>
                                                <strong class="text-primary">${{ number_format($exam->precio, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            @if($exams->count() == 0)
                            <div class="text-center py-5">
                                <i class="fa-solid fa-flask fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay exámenes disponibles</p>
                                <a href="/lab-exams" class="btn btn-primary">
                                    <i class="fa-solid fa-plus me-1"></i>Crear Exámenes
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Indicaciones Especiales -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-info mb-3"><i class="fa-solid fa-notes-medical me-2"></i>Indicaciones Especiales</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="requiere_ayuno" name="requiere_ayuno" value="1">
                                <label class="form-check-label" for="requiere_ayuno">
                                    <i class="fa-solid fa-utensils-slash me-1"></i>Requiere Ayuno
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label" for="preparacion_requerida">Preparación del Paciente</label>
                            <textarea class="form-control" id="preparacion_requerida" name="preparacion_requerida" rows="2"
                                placeholder="Ej: Ayuno de 8 horas, suspender medicamentos, etc."></textarea>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label" for="indicaciones_especiales">Indicaciones Especiales</label>
                            <textarea class="form-control" id="indicaciones_especiales" name="indicaciones_especiales" rows="2"
                                placeholder="Indicaciones adicionales para el laboratorio"></textarea>
                        </div>
                    </div>

                    <!-- Exámenes seleccionados (hidden inputs) -->
                    <!-- Los exámenes se enviarán dinámicamente como array -->

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2" id="btnGuardarOrden">
                            <i class="fa-solid fa-save me-1"></i>Guardar Orden
                        </button>
                        <a href="/lab-orders" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nuevo Paciente -->
<div class="modal fade" id="modalNuevoPaciente" tabindex="-1" aria-labelledby="modalNuevoPacienteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoPacienteLabel">
                    <i class="fa-solid fa-user-plus me-2"></i>Registrar Nuevo Paciente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoPacienteRapido">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        <small>Ingrese los datos mínimos del paciente. Los demás datos podrán completarse después.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="primer_nombre" id="primer_nombre" required placeholder="Ej: Juan">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Segundo Nombre</label>
                        <input type="text" class="form-control" name="segundo_nombre" id="segundo_nombre" placeholder="Ej: Carlos">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="primer_apellido" id="primer_apellido" required placeholder="Ej: Pérez">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Segundo Apellido</label>
                        <input type="text" class="form-control" name="segundo_apellido" id="segundo_apellido" placeholder="Ej: García">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Número de DUI</label>
                        <input type="text" class="form-control" name="documento_identidad" id="documento_identidad" placeholder="Ej: 00000000-0" maxlength="50">
                        <small class="text-muted">Opcional - Puede completarse después</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Número de Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="telefono" placeholder="Ej: 2222-0000" maxlength="20">
                        <small class="text-muted">Opcional - Puede completarse después</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPacienteRapido">
                        <i class="fa-solid fa-save me-1"></i>Guardar Paciente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
let examenesSeleccionados = [];

$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true
    });

    // Mostrar info del paciente
    $('#patient_id').on('change', function() {
        const selected = $(this).find(':selected');
        $('#patient_documento').val(selected.data('documento') || '');
        $('#patient_telefono').val(selected.data('telefono') || '');
    });

    // Formulario de nuevo paciente rápido
    $('#formNuevoPacienteRapido').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#btnGuardarPacienteRapido');
        const formData = $(this).serialize();

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: '/patients/store-quick',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    const patient = response.patient;
                    const nombreCompleto = `${patient.primer_nombre} ${patient.segundo_nombre || ''} ${patient.primer_apellido} ${patient.segundo_apellido || ''}`.trim();

                    // Agregar el nuevo paciente al select
                    const optionText = nombreCompleto + ' - ' + (patient.documento_identidad || 'PENDIENTE');
                    const newOption = new Option(optionText, patient.id, true, true);

                    // Agregar los atributos data
                    $(newOption).attr('data-nombre', nombreCompleto);
                    $(newOption).attr('data-documento', patient.documento_identidad || 'PENDIENTE');
                    $(newOption).attr('data-telefono', patient.telefono || '');

                    // Agregar la opción al select y actualizar Select2
                    $('#patient_id').append(newOption);
                    $('#patient_id').val(patient.id).trigger('change');

                    // Actualizar los campos de información del paciente
                    $('#patient_documento').val(patient.documento_identidad || 'PENDIENTE');
                    $('#patient_telefono').val(patient.telefono || '');

                    // Cerrar el modal y limpiar el formulario
                    $('#modalNuevoPaciente').modal('hide');
                    $('#formNuevoPacienteRapido')[0].reset();

                    Swal.fire({
                        icon: 'success',
                        title: '¡Paciente Registrado!',
                        text: 'El paciente ha sido registrado y seleccionado automáticamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al registrar el paciente';

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let errorMessages = [];

                    for (let field in errors) {
                        if (errors[field]) {
                            errorMessages.push(errors[field][0]);
                        }
                    }

                    errorMsg = errorMessages.length > 0
                        ? errorMessages.join('<br>')
                        : 'Por favor, verifique los datos ingresados';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMsg
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Paciente');
            }
        });
    });

    // Filtrar por categoría
    $('#filtroCategoria').on('change', function() {
        const categoria = $(this).val();
        filtrarExamenes(categoria, $('#buscarExamen').val());
    });

    // Buscar examen
    $('#buscarExamen').on('input', function() {
        const busqueda = $(this).val().toLowerCase();
        filtrarExamenes($('#filtroCategoria').val(), busqueda);
    });

    // Limpiar selección
    $('#btnLimpiarSeleccion').on('click', function() {
        examenesSeleccionados = [];
        $('.exam-card').removeClass('selected');
        $('.exam-selected-badge').addClass('d-none');
        actualizarResumen();
    });

    // Envío del formulario
    $('#formNuevaOrden').on('submit', function(e) {
        e.preventDefault();

        if (examenesSeleccionados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin exámenes',
                text: 'Debe seleccionar al menos un examen'
            });
            return;
        }

        // Preparar datos - examenesSeleccionados ya contiene los IDs directamente
        // Convertir a números y filtrar valores inválidos
        const examIds = examenesSeleccionados
            .map(id => parseInt(id))
            .filter(id => !isNaN(id) && id > 0);

        // Validar que haya al menos un examen válido
        if (examIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin exámenes',
                text: 'Debe seleccionar al menos un examen válido'
            });
            return;
        }

        // Verificar que los exámenes seleccionados existan en el DOM
        const examenesInvalidos = [];
        examIds.forEach(id => {
            const examItem = $(`.exam-item[data-id="${id}"]`);
            if (examItem.length === 0) {
                examenesInvalidos.push(id);
            }
        });

        if (examenesInvalidos.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Exámenes no válidos',
                text: `Los siguientes exámenes no son válidos: ${examenesInvalidos.join(', ')}. Por favor, recargue la página.`
            });
            return;
        }

        // Obtener datos del formulario
        const formData = new FormData(this);

        // Si el select está deshabilitado, obtener el valor del campo oculto
        const patientIdSelect = $('#patient_id');
        if (patientIdSelect.prop('disabled')) {
            const hiddenPatientId = $('input[name="patient_id"][type="hidden"]').val();
            if (hiddenPatientId) {
                formData.set('patient_id', hiddenPatientId);
            }
        }

        const doctorIdSelect = $('#doctor_id');
        if (doctorIdSelect.prop('disabled')) {
            const hiddenDoctorId = $('input[name="doctor_id"][type="hidden"]').val();
            if (hiddenDoctorId) {
                formData.set('doctor_id', hiddenDoctorId);
            }
        }

        // Eliminar cualquier campo exams existente
        formData.delete('exams[]');
        formData.delete('exams');

        // Agregar cada ID de examen como exams[] (convertir a string para FormData)
        examIds.forEach(id => {
            formData.append('exams[]', String(id));
        });

        // Verificar que los campos requeridos estén presentes
        const patientId = formData.get('patient_id');
        const prioridad = formData.get('prioridad');

        if (!patientId || patientId === '') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un paciente'
            });
            return;
        }

        if (!prioridad || prioridad === '') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar una prioridad'
            });
            return;
        }

        // Debug: mostrar datos que se enviarán (solo en desarrollo)
        const examsArray = [];
        for (let pair of formData.entries()) {
            if (pair[0] === 'exams[]') {
                examsArray.push(pair[1]);
            }
        }
        console.log('Datos a enviar:', {
            patient_id: formData.get('patient_id'),
            doctor_id: formData.get('doctor_id'),
            prioridad: formData.get('prioridad'),
            exams: examsArray,
            consultation_id: formData.get('consultation_id'),
            examIds_original: examIds
        });

        const submitBtn = $('#btnGuardarOrden');

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Creando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Orden Creada!',
                        text: 'La orden de laboratorio ha sido creada correctamente',
                        confirmButtonText: 'Ver Órdenes'
                    }).then(() => {
                        window.location.href = '/lab-orders';
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al crear la orden';

                if (xhr.status === 422) {
                    // Error de validación
                    const errors = xhr.responseJSON?.errors || {};
                    let errorMessages = [];

                    // Mapeo de nombres de campos a etiquetas amigables
                    const fieldLabels = {
                        'patient_id': 'Paciente',
                        'exams': 'Exámenes',
                        'prioridad': 'Prioridad',
                        'doctor_id': 'Médico',
                        'consultation_id': 'Consulta'
                    };

                    for (let field in errors) {
                        if (errors[field]) {
                            const fieldLabel = fieldLabels[field] || field;
                            errorMessages.push(`<strong>${fieldLabel}:</strong> ${errors[field][0]}`);
                        }
                    }

                    errorMsg = errorMessages.length > 0
                        ? errorMessages.join('<br>')
                        : 'Por favor, verifique los datos ingresados';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    html: errorMsg,
                    width: 600
                });

                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Crear Orden');
            }
        });
    });

});

function toggleExamen(id, precio, nombre) {
    const index = examenesSeleccionados.indexOf(id);

    if (index > -1) {
        // Quitar examen
        examenesSeleccionados.splice(index, 1);
        $(`.exam-card`).parent().find(`[data-id="${id}"]`).find('.exam-card').removeClass('selected');
        $(`#badge-${id}`).addClass('d-none');
    } else {
        // Agregar examen
        examenesSeleccionados.push(id);
        $(`.exam-card`).parent().find(`[data-id="${id}"]`).find('.exam-card').addClass('selected');
        $(`#badge-${id}`).removeClass('d-none');
    }

    actualizarResumen();
}

function actualizarResumen() {
    $('#cantidadSeleccionados').text(examenesSeleccionados.length);

    // Calcular total
    let total = 0;
    examenesSeleccionados.forEach(id => {
        const card = $(`.exam-item[data-id="${id}"]`).find('.text-primary').text();
        const precio = parseFloat(card.replace('$', '').replace(',', ''));
        total += precio;
    });

    $('#totalSeleccionados').text(total.toFixed(2));
}

function filtrarExamenes(categoria, busqueda) {
    $('.exam-item').each(function() {
        const itemCategoria = $(this).data('category');
        const itemNombre = $(this).data('nombre').toLowerCase();

        let mostrar = true;

        if (categoria && itemCategoria !== categoria) {
            mostrar = false;
        }

        if (busqueda && !itemNombre.includes(busqueda)) {
            mostrar = false;
        }

        $(this).toggle(mostrar);
    });
}
</script>
@endsection

