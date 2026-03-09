@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nuevo Médico')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Registrar Nuevo Médico</h5>
                <a href="/doctors" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver a Médicos
                </a>
            </div>
            <div class="card-body">
                <form id="formNuevoMedico" method="POST" action="{{ route('doctors.store') }}">
                    @csrf

                    <!-- Pestañas -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-datos">
                                <i class="fa-solid fa-user me-1"></i>Datos Personales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-profesional">
                                <i class="fa-solid fa-stethoscope me-1"></i>Información Profesional
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-horarios">
                                <i class="fa-solid fa-clock me-1"></i>Horarios de Atención
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-contacto">
                                <i class="fa-solid fa-phone me-1"></i>Contacto
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        
                        <!-- TAB DATOS PERSONALES -->
                        <div class="tab-pane fade show active" id="tab-datos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="nombres">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required
                                        placeholder="Ej: Juan Carlos">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="apellidos">Apellidos <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" required
                                        placeholder="Ej: García López">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="numero_jvpm">Número JVPM <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="numero_jvpm" name="numero_jvpm" required
                                        placeholder="Ej: JVPM-12345">
                                    <small class="text-muted">Junta de Vigilancia de la Profesión Médica</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="user_id">Usuario del Sistema (Opcional)</label>
                                    <select class="form-select select2" id="user_id" name="user_id">
                                        <option value="">Sin usuario asignado</option>
                                        @if(isset($users))
                                        @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <small class="text-muted">Si el médico usará el sistema</small>
                                </div>
                            </div>
                        </div>

                        <!-- TAB PROFESIONAL -->
                        <div class="tab-pane fade" id="tab-profesional" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="especialidad">Especialidad Principal <span class="text-danger">*</span></label>
                                    <select class="form-select" id="especialidad" name="especialidad" required>
                                        <option value="">Seleccione especialidad</option>
                                        <option value="Medicina General">Medicina General</option>
                                        <option value="Pediatría">Pediatría</option>
                                        <option value="Ginecología">Ginecología</option>
                                        <option value="Cardiología">Cardiología</option>
                                        <option value="Dermatología">Dermatología</option>
                                        <option value="Oftalmología">Oftalmología</option>
                                        <option value="Odontología">Odontología</option>
                                        <option value="Nutrición">Nutrición</option>
                                        <option value="Psicología">Psicología</option>
                                        <option value="Traumatología">Traumatología</option>
                                        <option value="Otra">Otra</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="especialidades_secundarias">Especialidades Secundarias</label>
                                    <input type="text" class="form-control" id="especialidades_secundarias" name="especialidades_secundarias"
                                        placeholder="Ej: Medicina Interna, Geriatría">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label" for="direccion_consultorio">Dirección del Consultorio</label>
                                    <textarea class="form-control" id="direccion_consultorio" name="direccion_consultorio" rows="2"
                                        placeholder="Dirección del consultorio o clínica"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB HORARIOS -->
                        <div class="tab-pane fade" id="tab-horarios" role="tabpanel">
                            <div class="alert alert-info mb-4">
                                <h6 class="alert-heading"><i class="fa-solid fa-info-circle me-2"></i>Configurar Horarios de Atención</h6>
                                <p class="mb-0">Configure los horarios de atención del médico. Solo se podrán agendar citas en los horarios configurados.</p>
                            </div>

                            <div id="horariosContainer">
                                @php
                                $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                                @endphp
                                
                                @foreach($diasSemana as $dia)
                                <div class="card mb-3 horario-item" data-dia="{{ $dia }}">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input horario-activo" type="checkbox" 
                                                        id="horario_{{ $dia }}_activo" 
                                                        data-dia="{{ $dia }}">
                                                    <label class="form-check-label fw-bold" for="horario_{{ $dia }}_activo">
                                                        {{ ucfirst($dia) }}
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Hora Inicio</label>
                                                <input type="time" class="form-control horario-inicio" 
                                                    id="horario_{{ $dia }}_inicio" 
                                                    data-dia="{{ $dia }}" 
                                                    disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Hora Fin</label>
                                                <input type="time" class="form-control horario-fin" 
                                                    id="horario_{{ $dia }}_fin" 
                                                    data-dia="{{ $dia }}" 
                                                    disabled>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Notas</label>
                                                <input type="text" class="form-control horario-notas" 
                                                    id="horario_{{ $dia }}_notas" 
                                                    data-dia="{{ $dia }}" 
                                                    placeholder="Opcional" 
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
                                    <label class="form-label" for="email">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        placeholder="doctor@email.com">
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <h6 class="alert-heading"><i class="fa-solid fa-info-circle me-2"></i>Información</h6>
                                        <p class="mb-0">Una vez registrado el médico, podrá:</p>
                                        <ul class="mb-0 mt-2">
                                            <li>Recibir citas médicas</li>
                                            <li>Atender consultas</li>
                                            <li>Generar recetas médicas</li>
                                            <li>Solicitar exámenes de laboratorio</li>
                                            <li>Acceder al sistema (si se le asigna usuario)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botones -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-solid fa-save me-1"></i>Guardar Médico
                        </button>
                        <a href="/doctors" class="btn btn-outline-secondary">
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

    // Habilitar/deshabilitar campos de horario
    $('.horario-activo').on('change', function() {
        const dia = $(this).data('dia');
        const activo = $(this).is(':checked');
        
        $(`#horario_${dia}_inicio, #horario_${dia}_fin, #horario_${dia}_notas`).prop('disabled', !activo);
        
        if (!activo) {
            $(`#horario_${dia}_inicio, #horario_${dia}_fin, #horario_${dia}_notas`).val('');
        }
    });

    // Envío del formulario
    $('#formNuevoMedico').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const horarios = [];
        
        // Recopilar horarios
        $('.horario-item').each(function() {
            const dia = $(this).data('dia');
            const activo = $(`#horario_${dia}_activo`).is(':checked');
            
            if (activo) {
                const inicio = $(`#horario_${dia}_inicio`).val();
                const fin = $(`#horario_${dia}_fin`).val();
                
                if (inicio && fin) {
                    horarios.push({
                        dia_semana: dia,
                        hora_inicio: inicio,
                        hora_fin: fin,
                        activo: true,
                        notas: $(`#horario_${dia}_notas`).val() || null
                    });
                }
            }
        });
        
        // Agregar horarios al FormData
        formData.append('horarios', JSON.stringify(horarios));
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');

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
                        title: '¡Médico Registrado!',
                        html: `
                            <p>El médico ha sido registrado exitosamente.</p>
                            <p><strong>Código:</strong> ${response.doctor.codigo_medico}</p>
                            <p><strong>JVPM:</strong> ${response.doctor.numero_jvpm}</p>
                        `,
                        confirmButtonText: 'Ver Médicos'
                    }).then(() => {
                        window.location.href = '/doctors';
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al registrar el médico';
                
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
                
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i>Guardar Médico');
            }
        });
    });
});
</script>
@endsection

