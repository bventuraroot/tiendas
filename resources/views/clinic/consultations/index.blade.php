@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Consultas Médicas')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Clínica /</span> Consultas Médicas
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Historial de Consultas</h5>
        <button type="button" class="btn btn-primary" id="btnAddConsultation">
            <i class="fa-solid fa-plus me-1"></i> Nueva Consulta
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info" role="alert">
            <h6 class="alert-heading mb-2"><i class="fa-solid fa-notes-medical me-2"></i>Módulo de Consultas</h6>
            <p class="mb-0">Este módulo permite registrar y gestionar las consultas médicas. Incluye:</p>
            <ul class="mb-0 mt-2">
                <li>Registro completo de consultas con signos vitales</li>
                <li>Diagnósticos con códigos CIE-10</li>
                <li>Generación de recetas médicas</li>
                <li>Plan de tratamiento y seguimiento</li>
                <li>Vinculación con expediente del paciente</li>
            </ul>
            <hr>
            <p class="mb-0"><strong>Estado:</strong> <span class="badge bg-success">Módulo Activo</span></p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="consultationsTable">
                <thead>
                    <tr>
                        <th>No. Consulta</th>
                        <th>Fecha/Hora</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Diagnóstico</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fa-solid fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay consultas registradas. Haz clic en "Nueva Consulta" para comenzar.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Botón para agregar nueva consulta
    $('#btnAddConsultation').on('click', function() {
        window.location.href = '/consultations/create';
    });

    // Cargar lista de consultas
    cargarConsultas();
});

function cargarConsultas() {
    $.ajax({
        url: '/consultations/data',
        method: 'GET',
        success: function(response) {
            let html = '';
            
            if (response.data && response.data.length > 0) {
                response.data.forEach(consulta => {
                    html += `
                        <tr>
                            <td><code>${consulta.numero_consulta}</code></td>
                            <td>
                                ${new Date(consulta.fecha_hora).toLocaleDateString('es-ES', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            ${consulta.patient ? consulta.patient.primer_nombre.charAt(0) : '?'}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">${consulta.patient ? consulta.patient.nombre_completo : 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                ${consulta.doctor ? consulta.doctor.nombre_completo : 'N/A'}
                                ${consulta.doctor ? `<br><small class="text-muted">${consulta.doctor.especialidad}</small>` : ''}
                            </td>
                            <td>
                                <span class="badge bg-label-info">${consulta.diagnostico_descripcion ? consulta.diagnostico_descripcion.substring(0, 40) + '...' : 'Sin diagnóstico'}</span>
                            </td>
                            <td>
                                <span class="badge bg-label-${
                                    consulta.estado === 'finalizada' ? 'success' : 
                                    (consulta.estado === 'cancelada' ? 'danger' : 'warning')
                                }">
                                    ${consulta.estado.charAt(0).toUpperCase() + consulta.estado.slice(1).replace('_', ' ')}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/consultations/${consulta.id}" class="btn btn-sm btn-outline-info">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="/patients/${consulta.patient_id}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-user"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fa-solid fa-clipboard-list fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-3">No hay consultas registradas</p>
                            <button class="btn btn-primary" onclick="window.location.href='/consultations/create'">
                                <i class="fa-solid fa-plus me-1"></i>Registrar Primera Consulta
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            $('#consultationsTable tbody').html(html);
        },
        error: function(xhr) {
            console.error('Error al cargar consultas:', xhr);
        }
    });
}
</script>
@endsection

