@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Gestión de Médicos')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Botón para agregar nuevo médico
    $('#btnAddDoctor').on('click', function() {
        window.location.href = '/doctors/create';
    });

    // Cargar lista de médicos
    cargarMedicos();
});

function cargarMedicos() {
    $.ajax({
        url: '/doctors/data',
        method: 'GET',
        success: function(response) {
            let html = '';
            
            if (response.data && response.data.length > 0) {
                response.data.forEach(doctor => {
                    html += `
                        <tr>
                            <td><code>${doctor.codigo_medico}</code></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            <i class="fa-solid fa-user-doctor"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">${doctor.nombre_completo}</div>
                                    </div>
                                </div>
                            </td>
                            <td><strong>${doctor.numero_jvpm}</strong></td>
                            <td><span class="badge bg-label-info">${doctor.especialidad}</span></td>
                            <td>${doctor.telefono}</td>
                            <td>
                                <span class="badge bg-label-${doctor.estado === 'activo' ? 'success' : 'secondary'}">
                                    ${doctor.estado.charAt(0).toUpperCase() + doctor.estado.slice(1)}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-sm btn-outline-info" onclick="verMedico(${doctor.id})">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editarMedico(${doctor.id})">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fa-solid fa-stethoscope fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-3">No hay médicos registrados</p>
                            <button class="btn btn-success" onclick="window.location.href='/doctors/create'">
                                <i class="fa-solid fa-plus me-1"></i>Registrar Primer Médico
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            $('#doctorsTable tbody').html(html);
        }
    });
}

function verMedico(id) {
    window.location.href = `/doctors/${id}`;
}

function editarMedico(id) {
    window.location.href = `/doctors/${id}/edit`;
}
</script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Clínica /</span> Médicos
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Personal Médico</h5>
        <button type="button" class="btn btn-primary" id="btnAddDoctor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Médico
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info" role="alert">
            <h6 class="alert-heading mb-2"><i class="fa-solid fa-user-md me-2"></i>Módulo de Médicos</h6>
            <p class="mb-0">Este módulo permite gestionar el personal médico de la clínica. Funcionalidades:</p>
            <ul class="mb-0 mt-2">
                <li>Registrar médicos con su información profesional (JVPM, especialidades)</li>
                <li>Gestionar horarios de atención</li>
                <li>Ver agenda de citas por médico</li>
                <li>Vincular con usuarios del sistema para acceso a la plataforma</li>
            </ul>
            <hr>
            <p class="mb-0"><strong>Estado:</strong> <span class="badge bg-success">Módulo Activo</span></p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="doctorsTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>JVPM</th>
                        <th>Especialidad</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fa-solid fa-stethoscope fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay médicos registrados. Haz clic en "Nuevo Médico" para comenzar.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

