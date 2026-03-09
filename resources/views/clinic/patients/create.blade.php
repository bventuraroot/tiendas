@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nuevo Paciente')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}">
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
                <h5 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Registrar Nuevo Paciente</h5>
                <a href="/patients" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver a Pacientes
                </a>
            </div>
            <div class="card-body">
                <form id="formNuevoPaciente" method="POST" action="{{ route('patients.store') }}">
                    @csrf

                    <!-- Pestañas -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-datos-personales">
                                <i class="fa-solid fa-user me-1"></i>Datos Personales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-contacto">
                                <i class="fa-solid fa-phone me-1"></i>Contacto
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-medico">
                                <i class="fa-solid fa-heartbeat me-1"></i>Información Médica
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        
                        <!-- TAB DATOS PERSONALES -->
                        <div class="tab-pane fade show active" id="tab-datos-personales" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="primer_nombre">Primer Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required
                                        placeholder="Ej: Juan">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="segundo_nombre">Segundo Nombre</label>
                                    <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre"
                                        placeholder="Ej: Carlos">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="primer_apellido">Primer Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required
                                        placeholder="Ej: García">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="segundo_apellido">Segundo Apellido</label>
                                    <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido"
                                        placeholder="Ej: López">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="tipo_documento">Tipo de Documento <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                                        <option value="DUI" selected>DUI</option>
                                        <option value="NIT">NIT</option>
                                        <option value="Pasaporte">Pasaporte</option>
                                        <option value="Carnet_residente">Carnet de Residente</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="documento_identidad">Número de Documento <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="documento_identidad" name="documento_identidad" required
                                        placeholder="00000000-0">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control flatpickr-date" id="fecha_nacimiento" 
                                        name="fecha_nacimiento" required placeholder="Seleccione fecha">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sexo">Sexo <span class="text-danger">*</span></label>
                                    <select class="form-select" id="sexo" name="sexo" required>
                                        <option value="">Seleccione</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Edad</label>
                                    <input type="text" class="form-control bg-light" id="edad_calculada" readonly 
                                        placeholder="Se calcula automáticamente">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="tipo_sangre">Tipo de Sangre</label>
                                    <select class="form-select" id="tipo_sangre" name="tipo_sangre">
                                        <option value="">Seleccione</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB CONTACTO -->
                        <div class="tab-pane fade" id="tab-contacto" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="telefono">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" required
                                        placeholder="0000-0000">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="telefono_emergencia">Teléfono de Emergencia</label>
                                    <input type="text" class="form-control" id="telefono_emergencia" name="telefono_emergencia"
                                        placeholder="Contacto en caso de emergencia">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="paciente@email.com">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="direccion">Dirección Completa <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3" required
                                        placeholder="Calle, colonia, municipio, departamento"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB INFORMACIÓN MÉDICA -->
                        <div class="tab-pane fade" id="tab-medico" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="alergias">Alergias Conocidas</label>
                                    <textarea class="form-control" id="alergias" name="alergias" rows="3"
                                        placeholder="Ej: Penicilina, Polen, Mariscos, Ninguna"></textarea>
                                    <small class="text-muted">Importante para evitar reacciones adversas</small>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="enfermedades_cronicas">Enfermedades Crónicas</label>
                                    <textarea class="form-control" id="enfermedades_cronicas" name="enfermedades_cronicas" rows="3"
                                        placeholder="Ej: Diabetes, Hipertensión, Asma, Ninguna"></textarea>
                                    <small class="text-muted">Condiciones médicas permanentes o de largo plazo</small>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading"><i class="fa-solid fa-shield-heart me-2"></i>Información de Seguridad</h6>
                                        <p class="mb-0">Esta información es confidencial y solo será accesible por el personal médico autorizado. Se utiliza para brindar atención médica segura y personalizada.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-save me-1"></i>Guardar Paciente
                            </button>
                            <button type="button" class="btn btn-success me-2" id="btnGuardarYAgendar">
                                <i class="fa-solid fa-calendar-plus me-1"></i>Guardar y Agendar Cita
                            </button>
                            <a href="/patients" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                        <div>
                            <span class="text-muted">
                                <i class="fa-solid fa-lock me-1"></i>Información protegida por confidencialidad médica
                            </span>
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
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione una opción'
    });

    // Inicializar Flatpickr para fecha de nacimiento
    flatpickr('.flatpickr-date', {
        dateFormat: "Y-m-d",
        maxDate: "today",
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
    });

    // Calcular edad automáticamente
    $('#fecha_nacimiento').on('change', function() {
        const fechaNac = new Date($(this).val());
        const hoy = new Date();
        let edad = hoy.getFullYear() - fechaNac.getFullYear();
        const mes = hoy.getMonth() - fechaNac.getMonth();
        
        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
            edad--;
        }
        
        $('#edad_calculada').val(edad + ' años');
    });

    // Formatear documento según tipo
    $('#tipo_documento').on('change', function() {
        const tipo = $(this).val();
        const inputDoc = $('#documento_identidad');
        
        if (tipo === 'DUI') {
            inputDoc.attr('placeholder', '00000000-0');
            inputDoc.attr('maxlength', '10');
        } else if (tipo === 'NIT') {
            inputDoc.attr('placeholder', '0000-000000-000-0');
            inputDoc.attr('maxlength', '17');
        } else {
            inputDoc.attr('placeholder', 'Número de documento');
            inputDoc.removeAttr('maxlength');
        }
    });

    // Envío del formulario
    $('#formNuevoPaciente').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                // Verificar si la respuesta es un string JSON o un objeto
                let data = response;
                if (typeof response === 'string') {
                    try {
                        data = JSON.parse(response);
                    } catch(e) {
                        console.error('Error parsing response:', e);
                    }
                }
                
                if (data && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Paciente Registrado!',
                        html: `
                            <p>El paciente ha sido registrado exitosamente.</p>
                            <p><strong>Código:</strong> ${data.patient.codigo_paciente}</p>
                            <p><strong>Expediente:</strong> ${data.patient.numero_expediente}</p>
                        `,
                        confirmButtonText: 'Ver Pacientes',
                        showCancelButton: true,
                        cancelButtonText: 'Agendar Cita'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '/patients';
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            window.location.href = `/appointments/create?patient_id=${data.patient.id}`;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Advertencia',
                        text: data.message || 'Respuesta inesperada del servidor'
                    });
                    submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Paciente');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al registrar el paciente';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errores = '';
                    Object.keys(xhr.responseJSON.errors).forEach(key => {
                        errores += xhr.responseJSON.errors[key][0] + '<br>';
                    });
                    errorMsg += '<br><br>' + errores;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMsg
                });
                
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Paciente');
            }
        });
    });

    // Botón Guardar y Agendar
    $('#btnGuardarYAgendar').on('click', function() {
        // Agregar campo hidden para indicar que se debe agendar cita
        $('#formNuevoPaciente').append('<input type="hidden" name="agendar_cita" value="1">');
        $('#formNuevoPaciente').submit();
    });

    // Validación de documento único
    let timeoutDocumento;
    $('#documento_identidad').on('input', function() {
        clearTimeout(timeoutDocumento);
        const documento = $(this).val();
        
        if (documento.length >= 5) {
            timeoutDocumento = setTimeout(function() {
                $.ajax({
                    url: '/patients/search/document',
                    method: 'GET',
                    data: { document: documento },
                    success: function(response) {
                        if (response.success && response.patient) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Paciente Ya Existe',
                                html: `
                                    <p>Ya existe un paciente con este documento:</p>
                                    <p><strong>${response.patient.nombre_completo}</strong></p>
                                    <p>Expediente: ${response.patient.numero_expediente}</p>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Ver Paciente',
                                cancelButtonText: 'Continuar de Todos Modos'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = `/patients/${response.patient.id}`;
                                }
                            });
                        }
                    }
                });
            }, 1000);
        }
    });
});
</script>
@endsection

