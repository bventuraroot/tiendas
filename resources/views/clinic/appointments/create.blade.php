@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nueva Cita Médica')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/flatpickr/flatpickr.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-calendar-plus me-2"></i>Nueva Cita Médica</h5>
                <a href="/appointments" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver a Agenda
                </a>
            </div>
            <div class="card-body">
                <form id="formNuevaCita" method="POST" action="{{ route('appointments.store') }}">
                    @csrf
                    
                    <div class="row">
                        <!-- Información del Paciente -->
                        <div class="col-12">
                            <h6 class="text-primary mb-3"><i class="fa-solid fa-user-injured me-2"></i>Información del Paciente</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="patient_id">Paciente <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="patient_id" name="patient_id" required>
                                <option value="">Seleccione un paciente</option>
                                @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" 
                                    data-documento="{{ $patient->documento_identidad }}"
                                    data-telefono="{{ $patient->telefono }}">
                                    {{ $patient->nombre_completo }} - {{ $patient->documento_identidad }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Busca por nombre o documento</small>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Documento</label>
                            <input type="text" class="form-control bg-light" id="patient_documento" readonly>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control bg-light" id="patient_telefono" readonly>
                        </div>

                        <!-- Información del Médico -->
                        <div class="col-12 mt-3">
                            <h6 class="text-success mb-3"><i class="fa-solid fa-user-doctor me-2"></i>Información del Médico</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="doctor_id">Médico <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="doctor_id" name="doctor_id" required>
                                <option value="">Seleccione un médico</option>
                                @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" 
                                    data-especialidad="{{ $doctor->especialidad }}"
                                    data-horario="{{ $doctor->horario_atencion }}">
                                    {{ $doctor->nombre_completo }} - {{ $doctor->especialidad }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Especialidad</label>
                            <input type="text" class="form-control bg-light" id="doctor_especialidad" readonly>
                        </div>

                        <!-- Información de la Cita -->
                        <div class="col-12 mt-3">
                            <h6 class="text-info mb-3"><i class="fa-solid fa-calendar-check me-2"></i>Detalles de la Cita</h6>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="fecha_cita">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="hora_cita">Hora Disponible <span class="text-danger">*</span></label>
                            <select class="form-select" id="hora_cita" name="hora_cita" required disabled>
                                <option value="">Primero seleccione médico y fecha</option>
                            </select>
                            <small class="text-muted" id="horarios-info"></small>
                        </div>

                        <div class="col-md-4 mb-3 d-none">
                            <label class="form-label" for="fecha_hora">Fecha y Hora Completa</label>
                            <input type="text" class="form-control" id="fecha_hora" name="fecha_hora" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="duracion_minutos">Duración (minutos) <span class="text-danger">*</span></label>
                            <select class="form-select" id="duracion_minutos" name="duracion_minutos" required>
                                <option value="15">15 minutos</option>
                                <option value="30" selected>30 minutos</option>
                                <option value="45">45 minutos</option>
                                <option value="60">1 hora</option>
                                <option value="90">1.5 horas</option>
                                <option value="120">2 horas</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="tipo_cita">Tipo de Cita <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_cita" name="tipo_cita" required>
                                <option value="primera_vez">Primera Vez</option>
                                <option value="seguimiento">Seguimiento</option>
                                <option value="control">Control</option>
                                <option value="emergencia">Emergencia</option>
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label" for="motivo_consulta">Motivo de la Consulta</label>
                            <textarea class="form-control" id="motivo_consulta" name="motivo_consulta" rows="3" 
                                placeholder="Describa brevemente el motivo de la cita"></textarea>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label" for="notas">Notas Adicionales</label>
                            <textarea class="form-control" id="notas" name="notas" rows="2" 
                                placeholder="Notas internas sobre la cita"></textarea>
                        </div>

                        <!-- Alerta de disponibilidad -->
                        <div class="col-12">
                            <div id="alertaDisponibilidad" class="alert alert-info d-none">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <span id="mensajeDisponibilidad"></span>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-solid fa-save me-1"></i>Guardar Cita
                        </button>
                        <a href="/appointments" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-times me-1"></i>Cancelar
                        </a>
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
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true
    });

    // Obtener parámetro de fecha de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const fechaParam = urlParams.get('fecha');
    const patientIdParam = urlParams.get('patient_id');
    
    // Si hay parámetro de paciente, precargarlo
    if (patientIdParam) {
        $('#patient_id').val(patientIdParam).trigger('change');
    }
    
    // Inicializar Flatpickr para fecha y hora
    const flatpickrConfig = {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        minDate: "today",
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
            },
            months: {
                shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
            }
        }
    };
    
    // Si hay fecha en la URL, establecerla como valor inicial
    if (fechaParam) {
        // Convertir fecha YYYY-MM-DD a formato con hora
        const fechaConHora = fechaParam + ' 09:00'; // Hora por defecto 9:00 AM
        flatpickrConfig.defaultDate = fechaConHora;
    }
    
    const flatpickrInstance = flatpickr('.flatpickr-datetime', flatpickrConfig);

    // Mostrar información del paciente seleccionado
    $('#patient_id').on('change', function() {
        const selectedOption = $(this).find(':selected');
        $('#patient_documento').val(selectedOption.data('documento') || '');
        $('#patient_telefono').val(selectedOption.data('telefono') || '');
    });

    // Mostrar información del médico seleccionado
    $('#doctor_id').on('change', function() {
        const selectedOption = $(this).find(':selected');
        $('#doctor_especialidad').val(selectedOption.data('especialidad') || '');
        
        // Cargar horarios disponibles si hay fecha seleccionada
        if ($('#fecha_cita').val()) {
            cargarHorariosDisponibles();
        }
    });

    // Cargar horarios disponibles al cambiar fecha
    $('#fecha_cita').on('change', function() {
        if ($('#doctor_id').val()) {
            cargarHorariosDisponibles();
        } else {
            $('#hora_cita').html('<option value="">Primero seleccione un médico</option>').prop('disabled', true);
        }
    });

    // Función para cargar horarios disponibles
    function cargarHorariosDisponibles() {
        const doctorId = $('#doctor_id').val();
        const fecha = $('#fecha_cita').val();

        if (!doctorId || !fecha) {
            return;
        }

        $('#hora_cita').html('<option value="">Cargando horarios...</option>').prop('disabled', true);
        $('#horarios-info').text('Cargando horarios disponibles...');

        $.ajax({
            url: '/appointments/available-hours',
            method: 'GET',
            data: {
                doctor_id: doctorId,
                fecha: fecha
            },
            success: function(response) {
                if (response.success && response.horas && response.horas.length > 0) {
                    let options = '<option value="">Seleccione una hora</option>';
                    response.horas.forEach(function(hora) {
                        options += `<option value="${hora}">${hora}</option>`;
                    });
                    $('#hora_cita').html(options).prop('disabled', false);
                    $('#horarios-info').text(`${response.horas.length} horarios disponibles`);
                } else {
                    $('#hora_cita').html('<option value="">No hay horarios disponibles para esta fecha</option>').prop('disabled', true);
                    $('#horarios-info').text('El médico no tiene horario configurado para este día');
                }
            },
            error: function() {
                $('#hora_cita').html('<option value="">Error al cargar horarios</option>').prop('disabled', true);
                $('#horarios-info').text('Error al cargar horarios');
            }
        });
    }

    // Actualizar campo fecha_hora cuando se selecciona hora
    $('#hora_cita').on('change', function() {
        const fecha = $('#fecha_cita').val();
        const hora = $(this).val();
        
        if (fecha && hora) {
            $('#fecha_hora').val(fecha + ' ' + hora + ':00');
        }
    });

    // Envío del formulario
    $('#formNuevaCita').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'La cita ha sido creada correctamente',
                        confirmButtonText: 'Ir a Agenda'
                    }).then(() => {
                        window.location.href = '/appointments';
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al crear la cita';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Cita');
            }
        });
    });
});
</script>
@endsection

