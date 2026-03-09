@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nueva Consulta Médica')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/flatpickr/flatpickr.js')}}"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-notes-medical me-2"></i>Nueva Consulta Médica</h5>
                <a href="/consultations" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver a Consultas
                </a>
            </div>
            <div class="card-body">
                <form id="formNuevaConsulta" method="POST" action="{{ route('consultations.store') }}">
                    @csrf

                    @if($appointment)
                    <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                    <div class="alert alert-info mb-4">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Consulta generada desde la cita: <strong>{{ $appointment->codigo_cita }}</strong> -
                        {{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y H:i') }}
                    </div>
                    @endif

                    <!-- Pestañas para organizar la información -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-paciente">
                                <i class="fa-solid fa-user me-1"></i>Paciente
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-signos">
                                <i class="fa-solid fa-heartbeat me-1"></i>Signos Vitales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-diagnostico">
                                <i class="fa-solid fa-stethoscope me-1"></i>Diagnóstico
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-tratamiento">
                                <i class="fa-solid fa-prescription me-1"></i>Tratamiento
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">

                        <!-- TAB PACIENTE -->
                        <div class="tab-pane fade show active" id="tab-paciente" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="patient_id">Paciente <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="patient_id" name="patient_id" required
                                        @if($appointment) disabled @endif>
                                        <option value="">Seleccione un paciente</option>
                                        @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}"
                                            @if($appointment && $appointment->patient_id == $patient->id) selected @endif>
                                            {{ $patient->nombre_completo }} - {{ $patient->documento_identidad }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if($appointment)
                                    <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="doctor_id">Médico <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="doctor_id" name="doctor_id" required
                                        @if($appointment) disabled @endif>
                                        <option value="">Seleccione un médico</option>
                                        @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}"
                                            @if($appointment && $appointment->doctor_id == $doctor->id) selected @endif>
                                            {{ $doctor->nombre_completo }} - {{ $doctor->especialidad }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if($appointment)
                                    <input type="hidden" name="doctor_id" value="{{ $appointment->doctor_id }}">
                                    @endif
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="motivo_consulta">Motivo de Consulta <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="motivo_consulta" name="motivo_consulta" rows="2" required
                                        placeholder="Ej: Dolor abdominal, fiebre, control de presión">@if($appointment){{ $appointment->motivo_consulta }}@endif</textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="sintomas">Síntomas</label>
                                    <textarea class="form-control" id="sintomas" name="sintomas" rows="3"
                                        placeholder="Describa los síntomas presentados por el paciente"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB SIGNOS VITALES -->
                        <div class="tab-pane fade" id="tab-signos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="temperatura">Temperatura (°C)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" min="30" max="45" class="form-control"
                                            id="temperatura" name="temperatura" placeholder="36.5">
                                        <span class="input-group-text">°C</span>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="presion_arterial">Presión Arterial</label>
                                    <input type="text" class="form-control" id="presion_arterial" name="presion_arterial"
                                        placeholder="120/80">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="frecuencia_cardiaca">Frecuencia Cardíaca</label>
                                    <div class="input-group">
                                        <input type="number" min="30" max="200" class="form-control"
                                            id="frecuencia_cardiaca" name="frecuencia_cardiaca" placeholder="70">
                                        <span class="input-group-text">lpm</span>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="frecuencia_respiratoria">Frecuencia Respiratoria</label>
                                    <div class="input-group">
                                        <input type="number" min="5" max="60" class="form-control"
                                            id="frecuencia_respiratoria" name="frecuencia_respiratoria" placeholder="16">
                                        <span class="input-group-text">rpm</span>
                                    </div>
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label" for="peso">Peso (kg)</label>
                                    <input type="number" step="0.1" min="0" max="500" class="form-control"
                                        id="peso" name="peso" placeholder="70.5">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label" for="altura">Altura (cm)</label>
                                    <input type="number" step="0.1" min="0" max="300" class="form-control"
                                        id="altura" name="altura" placeholder="170">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label" for="imc">IMC</label>
                                    <input type="text" class="form-control bg-light" id="imc" readonly
                                        placeholder="Auto">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="saturacion_oxigeno">Saturación O₂</label>
                                    <div class="input-group">
                                        <input type="number" min="50" max="100" class="form-control"
                                            id="saturacion_oxigeno" name="saturacion_oxigeno" placeholder="98">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Clasificación IMC</label>
                                    <input type="text" class="form-control bg-light" id="clasificacion_imc" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- TAB DIAGNÓSTICO -->
                        <div class="tab-pane fade" id="tab-diagnostico" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="exploracion_fisica">Exploración Física</label>
                                    <textarea class="form-control" id="exploracion_fisica" name="exploracion_fisica" rows="3"
                                        placeholder="Describa los hallazgos de la exploración física"></textarea>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="diagnostico_cie10">Código CIE-10</label>
                                    <input type="text" class="form-control" id="diagnostico_cie10" name="diagnostico_cie10"
                                        placeholder="Ej: J00">
                                    <small class="text-muted">Opcional</small>
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label class="form-label" for="diagnostico_descripcion">Diagnóstico <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="diagnostico_descripcion"
                                        name="diagnostico_descripcion" required
                                        placeholder="Ej: Rinofaringitis aguda (resfriado común)">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="diagnosticos_secundarios">Diagnósticos Secundarios</label>
                                    <textarea class="form-control" id="diagnosticos_secundarios" name="diagnosticos_secundarios" rows="2"
                                        placeholder="Otros diagnósticos o condiciones encontradas"></textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="observaciones">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                        placeholder="Notas adicionales sobre la consulta"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB TRATAMIENTO -->
                        <div class="tab-pane fade" id="tab-tratamiento" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="plan_tratamiento">Plan de Tratamiento</label>
                                    <textarea class="form-control" id="plan_tratamiento" name="plan_tratamiento" rows="4"
                                        placeholder="Describa el plan de tratamiento para el paciente"></textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="indicaciones">Indicaciones Médicas</label>
                                    <textarea class="form-control" id="indicaciones" name="indicaciones" rows="3"
                                        placeholder="Indicaciones y recomendaciones para el paciente"></textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="genera_receta" name="genera_receta" value="1">
                                        <label class="form-check-label" for="genera_receta">
                                            <i class="fa-solid fa-prescription me-1"></i>Generar Receta Médica
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="requiere_seguimiento" name="requiere_seguimiento" value="1">
                                        <label class="form-check-label" for="requiere_seguimiento">
                                            <i class="fa-solid fa-calendar-check me-1"></i>Requiere Seguimiento
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12" id="div_receta" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="fa-solid fa-pills me-2"></i>Receta Digital</h6>
                                            <textarea class="form-control" id="receta_digital" name="receta_digital" rows="4"
                                                placeholder="Medicamento, dosis, frecuencia, duración&#10;Ej: Paracetamol 500mg - 1 tableta cada 8 horas por 5 días"></textarea>
                                            <small class="text-muted">Posteriormente se podrá vincular con productos de farmacia</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3" id="div_seguimiento" style="display: none;">
                                    <label class="form-label" for="fecha_proximo_control">Fecha Próximo Control</label>
                                    <input type="text" class="form-control flatpickr-date" id="fecha_proximo_control"
                                        name="fecha_proximo_control" placeholder="Seleccione fecha">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-save me-1"></i>Guardar Consulta
                            </button>
                            <a href="/consultations" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-info" id="btnSolicitarExamen">
                                <i class="fa-solid fa-flask me-1"></i>Solicitar Examen de Laboratorio
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Configurar token CSRF para todas las peticiones AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
        }
    });
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true
    });

    // Inicializar Flatpickr
    flatpickr('.flatpickr-date', {
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    // Calcular IMC automáticamente
    $('#peso, #altura').on('input', function() {
        const peso = parseFloat($('#peso').val());
        const altura = parseFloat($('#altura').val());

        if (peso && altura && altura > 0) {
            const alturaMetros = altura / 100;
            const imc = peso / (alturaMetros * alturaMetros);
            $('#imc').val(imc.toFixed(2));

            // Clasificación del IMC
            let clasificacion = '';
            if (imc < 18.5) clasificacion = 'Bajo peso';
            else if (imc < 25) clasificacion = 'Peso normal';
            else if (imc < 30) clasificacion = 'Sobrepeso';
            else if (imc < 35) clasificacion = 'Obesidad I';
            else if (imc < 40) clasificacion = 'Obesidad II';
            else clasificacion = 'Obesidad III';

            $('#clasificacion_imc').val(clasificacion);
        } else {
            $('#imc').val('');
            $('#clasificacion_imc').val('');
        }
    });

    // Mostrar/ocultar sección de receta
    $('#genera_receta').on('change', function() {
        if ($(this).is(':checked')) {
            $('#div_receta').slideDown();
        } else {
            $('#div_receta').slideUp();
        }
    });

    // Mostrar/ocultar fecha de seguimiento
    $('#requiere_seguimiento').on('change', function() {
        if ($(this).is(':checked')) {
            $('#div_seguimiento').slideDown();
        } else {
            $('#div_seguimiento').slideUp();
        }
    });

    // Envío del formulario
    $('#formNuevaConsulta').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');

        // Validar campos requeridos antes de enviar
        let isValid = true;
        let errorFields = [];

        if (!form.find('#patient_id').val() && !form.find('input[name="patient_id"][type="hidden"]').val()) {
            isValid = false;
            errorFields.push('Paciente');
        }

        if (!form.find('#doctor_id').val() && !form.find('input[name="doctor_id"][type="hidden"]').val()) {
            isValid = false;
            errorFields.push('Médico');
        }

        if (!form.find('#motivo_consulta').val().trim()) {
            isValid = false;
            errorFields.push('Motivo de Consulta');
        }

        if (!form.find('#diagnostico_descripcion').val().trim()) {
            isValid = false;
            errorFields.push('Diagnóstico');
        }

        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                html: 'Por favor complete los siguientes campos requeridos:<br><strong>' + errorFields.join(', ') + '</strong>',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

        // Preparar datos del formulario
        const formData = new FormData(form[0]);

        // Asegurar que los checkboxes se envíen correctamente
        if ($('#genera_receta').is(':checked')) {
            formData.set('genera_receta', '1');
        } else {
            formData.set('genera_receta', '0');
        }

        if ($('#requiere_seguimiento').is(':checked')) {
            formData.set('requiere_seguimiento', '1');
        } else {
            formData.set('requiere_seguimiento', '0');
        }

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Consulta Registrada!',
                        text: 'La consulta médica ha sido creada correctamente',
                        confirmButtonText: 'Ver Consultas'
                    }).then(() => {
                        window.location.href = '/consultations';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al crear la consulta'
                    });
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Consulta');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al crear la consulta';
                let errorDetails = '';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }

                    // Mostrar errores de validación
                    if (xhr.responseJSON.errors) {
                        errorDetails = '<ul class="text-start mt-2" style="max-height: 200px; overflow-y: auto;">';
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            const fieldName = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            xhr.responseJSON.errors[key].forEach(function(error) {
                                errorDetails += '<li><strong>' + fieldName + ':</strong> ' + error + '</li>';
                            });
                        });
                        errorDetails += '</ul>';
                    }
                } else if (xhr.status === 500) {
                    errorMsg = 'Error interno del servidor. Por favor, intente nuevamente.';
                } else if (xhr.status === 422) {
                    errorMsg = 'Error de validación. Por favor, revise los datos ingresados.';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMsg + errorDetails,
                    width: 600
                });

                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Consulta');
            }
        });
    });

    // Botón Solicitar Examen
    $('#btnSolicitarExamen').on('click', function() {
        // Obtener patient_id (del select o del input hidden si está disabled)
        let patientId = $('#patient_id').val();
        if (!patientId || $('#patient_id').prop('disabled')) {
            patientId = $('input[name="patient_id"][type="hidden"]').val();
        }

        // Obtener doctor_id (del select o del input hidden si está disabled)
        let doctorId = $('#doctor_id').val();
        if (!doctorId || $('#doctor_id').prop('disabled')) {
            doctorId = $('input[name="doctor_id"][type="hidden"]').val();
        }

        if (!patientId) {
            Swal.fire({
                icon: 'warning',
                title: 'Información incompleta',
                text: 'Debe seleccionar un paciente primero'
            });
            return;
        }

        // Construir URL con parámetros
        let url = `/lab-orders/create?patient_id=${patientId}`;
        if (doctorId) {
            url += `&doctor_id=${doctorId}`;
        }

        // También pasar consultation_id si existe
        const consultationId = $('input[name="consultation_id"]').val();
        if (consultationId) {
            url += `&consultation_id=${consultationId}`;
        }

        // Abrir en nueva ventana
        window.open(url, '_blank');
    });

    // Las consultas médicas no se facturan - solo control clínico
});
</script>
@endsection

