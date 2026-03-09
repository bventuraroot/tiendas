@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Expediente Clínico - ' . $patient->nombre_completo)

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<style>
.expediente-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    margin: -25px -25px 30px -25px;
    border-radius: 8px 8px 0 0;
}
.consulta-card {
    border-left: 4px solid #696cff;
    margin-bottom: 20px;
    transition: all 0.2s;
}
.consulta-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateX(5px);
}
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #696cff;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e0e0e0;
}
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('content')

<!-- Header del Expediente -->
<div class="expediente-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-lg me-3">
                    <span class="avatar-initial rounded-circle bg-white text-primary" style="font-size: 24px;">
                        {{ substr($patient->primer_nombre, 0, 1) }}{{ substr($patient->primer_apellido, 0, 1) }}
                    </span>
                </div>
                <div>
                    <h3 class="text-white mb-1">{{ $patient->nombre_completo }}</h3>
                    <p class="text-white-50 mb-0">
                        <i class="fa-solid fa-id-card me-2"></i>{{ $patient->documento_identidad }}
                        <span class="mx-2">|</span>
                        <i class="fa-solid fa-calendar me-2"></i>{{ $patient->edad }} años
                        <span class="mx-2">|</span>
                        <i class="fa-solid fa-venus-mars me-2"></i>{{ $patient->sexo == 'M' ? 'Masculino' : 'Femenino' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="card bg-white bg-opacity-25 text-white border-0">
                <div class="card-body p-3">
                    <h6 class="text-white mb-1">Expediente Clínico</h6>
                    <h4 class="text-white mb-0">{{ $patient->numero_expediente }}</h4>
                    <small class="text-white-50">Código: {{ $patient->codigo_paciente }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group" role="group">
            <a href="/patients" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver
            </a>
            <a href="/appointments/create?patient_id={{ $patient->id }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-calendar-plus me-1"></i>Agendar Cita
            </a>
            <a href="/consultations/create?patient_id={{ $patient->id }}" class="btn btn-outline-success">
                <i class="fa-solid fa-notes-medical me-1"></i>Nueva Consulta
            </a>
            <a href="/lab-orders/create?patient_id={{ $patient->id }}" class="btn btn-outline-warning">
                <i class="fa-solid fa-flask me-1"></i>Solicitar Exámenes
            </a>
            <a href="/patients/{{ $patient->id }}/edit" class="btn btn-outline-info">
                <i class="fa-solid fa-edit me-1"></i>Editar Datos
            </a>
            <button class="btn btn-outline-danger" onclick="imprimirExpediente()">
                <i class="fa-solid fa-print me-1"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<!-- Contenido del Expediente -->
<div class="row">
    
    <!-- Columna Izquierda: Información del Paciente -->
    <div class="col-lg-4 col-md-12 mb-4">
        
        <!-- Datos Personales -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-user me-2"></i>Datos Personales
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-semibold">Documento:</td>
                        <td>{{ $patient->tipo_documento }} - {{ $patient->documento_identidad }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Fecha Nacimiento:</td>
                        <td>{{ \Carbon\Carbon::parse($patient->fecha_nacimiento)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Edad:</td>
                        <td><strong>{{ $patient->edad }} años</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Sexo:</td>
                        <td>{{ $patient->sexo == 'M' ? 'Masculino' : 'Femenino' }}</td>
                    </tr>
                    @if($patient->tipo_sangre)
                    <tr>
                        <td class="fw-semibold">Tipo de Sangre:</td>
                        <td><span class="badge bg-label-danger">{{ $patient->tipo_sangre }}</span></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Contacto -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-phone me-2"></i>Contacto
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-semibold">Teléfono:</td>
                        <td>{{ $patient->telefono }}</td>
                    </tr>
                    @if($patient->telefono_emergencia)
                    <tr>
                        <td class="fw-semibold">Emergencia:</td>
                        <td>{{ $patient->telefono_emergencia }}</td>
                    </tr>
                    @endif
                    @if($patient->email)
                    <tr>
                        <td class="fw-semibold">Email:</td>
                        <td>{{ $patient->email }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="fw-semibold">Dirección:</td>
                        <td>{{ $patient->direccion }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Información Médica -->
        <div class="card mb-4">
            <div class="card-header bg-label-danger">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>Información Médica Importante
                </h5>
            </div>
            <div class="card-body">
                <h6 class="text-danger mb-2">Alergias:</h6>
                @if($patient->alergias)
                <div class="alert alert-danger mb-3">
                    <strong>{{ $patient->alergias }}</strong>
                </div>
                @else
                <p class="text-muted mb-3"><em>Sin alergias registradas</em></p>
                @endif

                <h6 class="text-warning mb-2">Enfermedades Crónicas:</h6>
                @if($patient->enfermedades_cronicas)
                <div class="alert alert-warning mb-0">
                    <strong>{{ $patient->enfermedades_cronicas }}</strong>
                </div>
                @else
                <p class="text-muted mb-0"><em>Sin enfermedades crónicas registradas</em></p>
                @endif
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-chart-simple me-2"></i>Estadísticas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Consultas:</span>
                    <strong class="text-primary">{{ $patient->consultations->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Citas Programadas:</span>
                    <strong class="text-success">{{ $patient->appointments->where('estado', '!=', 'completada')->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Órdenes de Lab:</span>
                    <strong class="text-warning">{{ $patient->labOrders->count() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Primera Consulta:</span>
                    <strong>
                        @if($patient->consultations->count() > 0)
                            {{ \Carbon\Carbon::parse($patient->consultations->first()->fecha_hora)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </strong>
                </div>
            </div>
        </div>

    </div>

    <!-- Columna Derecha: Historial Clínico -->
    <div class="col-lg-8 col-md-12">
        
        <!-- Pestañas de Información -->
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-consultas">
                    <i class="fa-solid fa-notes-medical me-1"></i>Historial de Consultas ({{ $patient->consultations->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-citas">
                    <i class="fa-solid fa-calendar me-1"></i>Citas ({{ $patient->appointments->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-laboratorio">
                    <i class="fa-solid fa-flask me-1"></i>Exámenes de Lab ({{ $patient->labOrders->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-documentos">
                    <i class="fa-solid fa-file-medical me-1"></i>Documentos
                </button>
            </li>
        </ul>

        <div class="tab-content">
            
            <!-- TAB HISTORIAL DE CONSULTAS -->
            <div class="tab-pane fade show active" id="tab-consultas" role="tabpanel">
                @if($patient->consultations->count() > 0)
                <div class="timeline">
                    @foreach($patient->consultations->sortByDesc('fecha_hora') as $consulta)
                    <div class="timeline-item">
                        <div class="card consulta-card">
                            <div class="card-header bg-label-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">
                                            <i class="fa-solid fa-calendar me-2"></i>
                                            {{ \Carbon\Carbon::parse($consulta->fecha_hora)->format('d/m/Y H:i') }}
                                        </h6>
                                        <small class="text-muted">
                                            Hace {{ \Carbon\Carbon::parse($consulta->fecha_hora)->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-{{ $consulta->estado == 'finalizada' ? 'success' : 'warning' }}">
                                            {{ ucfirst($consulta->estado) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary mb-2">
                                            <i class="fa-solid fa-user-doctor me-2"></i>Médico Tratante
                                        </h6>
                                        <p class="mb-1"><strong>{{ $consulta->doctor->nombre_completo }}</strong></p>
                                        <small class="text-muted">{{ $consulta->doctor->especialidad }}</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-success mb-2">
                                            <i class="fa-solid fa-file-medical me-2"></i>No. Consulta
                                        </h6>
                                        <p class="mb-0"><code>{{ $consulta->numero_consulta }}</code></p>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <h6 class="text-info mb-2">
                                            <i class="fa-solid fa-clipboard-list me-2"></i>Motivo de Consulta
                                        </h6>
                                        <p class="mb-0">{{ $consulta->motivo_consulta }}</p>
                                    </div>

                                    @if($consulta->sintomas)
                                    <div class="col-12 mb-3">
                                        <h6 class="text-warning mb-2">
                                            <i class="fa-solid fa-thermometer me-2"></i>Síntomas
                                        </h6>
                                        <p class="mb-0">{{ $consulta->sintomas }}</p>
                                    </div>
                                    @endif

                                    <!-- Signos Vitales -->
                                    <div class="col-12 mb-3">
                                        <h6 class="text-danger mb-2">
                                            <i class="fa-solid fa-heartbeat me-2"></i>Signos Vitales
                                        </h6>
                                        <div class="row g-2">
                                            @if($consulta->temperatura)
                                            <div class="col-auto">
                                                <span class="badge bg-label-danger">Temp: {{ $consulta->temperatura }}°C</span>
                                            </div>
                                            @endif
                                            @if($consulta->presion_arterial)
                                            <div class="col-auto">
                                                <span class="badge bg-label-primary">PA: {{ $consulta->presion_arterial }}</span>
                                            </div>
                                            @endif
                                            @if($consulta->frecuencia_cardiaca)
                                            <div class="col-auto">
                                                <span class="badge bg-label-danger">FC: {{ $consulta->frecuencia_cardiaca }} lpm</span>
                                            </div>
                                            @endif
                                            @if($consulta->frecuencia_respiratoria)
                                            <div class="col-auto">
                                                <span class="badge bg-label-info">FR: {{ $consulta->frecuencia_respiratoria }} rpm</span>
                                            </div>
                                            @endif
                                            @if($consulta->peso)
                                            <div class="col-auto">
                                                <span class="badge bg-label-warning">Peso: {{ $consulta->peso }} kg</span>
                                            </div>
                                            @endif
                                            @if($consulta->altura)
                                            <div class="col-auto">
                                                <span class="badge bg-label-success">Altura: {{ $consulta->altura }} cm</span>
                                            </div>
                                            @endif
                                            @if($consulta->imc)
                                            <div class="col-auto">
                                                <span class="badge bg-label-primary">IMC: {{ $consulta->imc }}</span>
                                            </div>
                                            @endif
                                            @if($consulta->saturacion_oxigeno)
                                            <div class="col-auto">
                                                <span class="badge bg-label-info">SpO₂: {{ $consulta->saturacion_oxigeno }}%</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Diagnóstico -->
                                    <div class="col-12 mb-3">
                                        <h6 class="text-primary mb-2">
                                            <i class="fa-solid fa-stethoscope me-2"></i>Diagnóstico
                                        </h6>
                                        <div class="alert alert-primary mb-2">
                                            @if($consulta->diagnostico_cie10)
                                            <span class="badge bg-primary">CIE-10: {{ $consulta->diagnostico_cie10 }}</span>
                                            @endif
                                            <p class="mb-0 mt-2"><strong>{{ $consulta->diagnostico_descripcion }}</strong></p>
                                        </div>
                                        @if($consulta->diagnosticos_secundarios)
                                        <small class="text-muted">Diagnósticos secundarios: {{ $consulta->diagnosticos_secundarios }}</small>
                                        @endif
                                    </div>

                                    @if($consulta->exploracion_fisica)
                                    <div class="col-12 mb-3">
                                        <h6 class="mb-2">
                                            <i class="fa-solid fa-hand-pointer me-2"></i>Exploración Física
                                        </h6>
                                        <p class="mb-0">{{ $consulta->exploracion_fisica }}</p>
                                    </div>
                                    @endif

                                    @if($consulta->plan_tratamiento)
                                    <div class="col-12 mb-3">
                                        <h6 class="text-success mb-2">
                                            <i class="fa-solid fa-pills me-2"></i>Plan de Tratamiento
                                        </h6>
                                        <p class="mb-0">{{ $consulta->plan_tratamiento }}</p>
                                    </div>
                                    @endif

                                    @if($consulta->receta_digital)
                                    <div class="col-12 mb-3">
                                        <h6 class="text-warning mb-2">
                                            <i class="fa-solid fa-prescription me-2"></i>Receta Médica
                                        </h6>
                                        <div class="alert alert-warning mb-0">
                                            <pre class="mb-0">{{ $consulta->receta_digital }}</pre>
                                        </div>
                                    </div>
                                    @endif

                                    @if($consulta->indicaciones)
                                    <div class="col-12 mb-3">
                                        <h6 class="mb-2">
                                            <i class="fa-solid fa-lightbulb me-2"></i>Indicaciones
                                        </h6>
                                        <p class="mb-0">{{ $consulta->indicaciones }}</p>
                                    </div>
                                    @endif

                                    @if($consulta->requiere_seguimiento)
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">
                                            <i class="fa-solid fa-calendar-check me-2"></i>
                                            <strong>Requiere Seguimiento</strong>
                                            @if($consulta->fecha_proximo_control)
                                            <br>Próximo control: {{ \Carbon\Carbon::parse($consulta->fecha_proximo_control)->format('d/m/Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        Registrado: {{ \Carbon\Carbon::parse($consulta->created_at)->format('d/m/Y H:i') }}
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-sm btn-outline-primary" onclick="imprimirConsulta({{ $consulta->id }})">
                                            <i class="fa-solid fa-print"></i> Imprimir
                                        </button>
                                        <a href="/consultations/{{ $consulta->id }}" class="btn btn-sm btn-outline-info">
                                            <i class="fa-solid fa-eye"></i> Ver Detalle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa-solid fa-notes-medical fa-4x text-muted mb-3 d-block"></i>
                        <h5>Sin Historial de Consultas</h5>
                        <p class="text-muted">Este paciente aún no tiene consultas registradas</p>
                        <a href="/consultations/create?patient_id={{ $patient->id }}" class="btn btn-primary mt-2">
                            <i class="fa-solid fa-plus me-1"></i>Registrar Primera Consulta
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <!-- TAB CITAS -->
            <div class="tab-pane fade" id="tab-citas" role="tabpanel">
                @if($patient->appointments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Fecha y Hora</th>
                                <th>Médico</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->appointments->sortByDesc('fecha_hora') as $cita)
                            <tr>
                                <td><code>{{ $cita->codigo_cita }}</code></td>
                                <td>{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('d/m/Y H:i') }}</td>
                                <td>{{ $cita->doctor->nombre_completo }}</td>
                                <td><span class="badge bg-label-info">{{ ucfirst($cita->tipo_cita) }}</span></td>
                                <td>
                                    <span class="badge bg-label-{{ 
                                        $cita->estado == 'completada' ? 'success' : 
                                        ($cita->estado == 'cancelada' ? 'danger' : 'warning') 
                                    }}">
                                        {{ ucfirst($cita->estado) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="/appointments/{{ $cita->id }}" class="btn btn-sm btn-outline-info">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-4x text-muted mb-3 d-block"></i>
                        <h5>Sin Citas Registradas</h5>
                        <p class="text-muted">No hay citas programadas para este paciente</p>
                        <a href="/appointments/create?patient_id={{ $patient->id }}" class="btn btn-primary mt-2">
                            <i class="fa-solid fa-calendar-plus me-1"></i>Agendar Primera Cita
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <!-- TAB ÓRDENES DE LABORATORIO -->
            <div class="tab-pane fade" id="tab-laboratorio" role="tabpanel">
                @if($patient->labOrders->count() > 0)
                <div class="row">
                    @foreach($patient->labOrders->sortByDesc('fecha_orden') as $orden)
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><code>{{ $orden->numero_orden }}</code></h6>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($orden->fecha_orden)->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <span class="badge bg-label-{{
                                        $orden->estado == 'completada' ? 'success' :
                                        ($orden->estado == 'en_proceso' ? 'info' : 'warning')
                                    }}">
                                        {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($orden->doctor)
                                <p class="mb-2">
                                    <strong>Médico:</strong> {{ $orden->doctor->nombre_completo }}
                                </p>
                                @endif
                                <p class="mb-2"><strong>Exámenes Solicitados:</strong></p>
                                <ul class="mb-2">
                                    @foreach($orden->exams as $examOrder)
                                    <li>{{ $examOrder->exam->nombre }} - ${{ number_format($examOrder->precio, 2) }}</li>
                                    @endforeach
                                </ul>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-primary">Total: ${{ number_format($orden->total, 2) }}</strong>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/lab-orders/{{ $orden->id }}" class="btn btn-sm btn-outline-info">
                                            <i class="fa-solid fa-eye me-1"></i>Ver Resultados
                                        </a>
                                        <button class="btn btn-sm btn-outline-primary" onclick="imprimirOrden({{ $orden->id }})">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa-solid fa-flask fa-4x text-muted mb-3 d-block"></i>
                        <h5>Sin Exámenes de Laboratorio</h5>
                        <p class="text-muted">No hay órdenes de laboratorio para este paciente</p>
                        <a href="/lab-orders/create?patient_id={{ $patient->id }}" class="btn btn-warning mt-2">
                            <i class="fa-solid fa-plus me-1"></i>Solicitar Primera Orden
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <!-- TAB DOCUMENTOS -->
            <div class="tab-pane fade" id="tab-documentos" role="tabpanel">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa-solid fa-folder-open fa-4x text-muted mb-3 d-block"></i>
                        <h5>Documentos Médicos</h5>
                        <p class="text-muted">Radiografías, estudios, análisis externos, etc.</p>
                        <button class="btn btn-primary mt-2">
                            <i class="fa-solid fa-upload me-1"></i>Subir Documento
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
function imprimirExpediente() {
    window.print();
}

function imprimirConsulta(id) {
    window.open(`/consultations/${id}/print`, '_blank');
}

function imprimirOrden(id) {
    window.open(`/lab-orders/${id}/print`, '_blank');
}
</script>
@endsection

