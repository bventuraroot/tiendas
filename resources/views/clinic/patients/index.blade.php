@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Gestión de Pacientes')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Botón para agregar nuevo paciente
    $('#btnAddPatient').on('click', function() {
        window.location.href = '/patients/create';
    });

    // Cargar lista de pacientes
    cargarPacientes();
});

function cargarPacientes() {
    $.ajax({
        url: '/patients/data',
        method: 'GET',
        success: function(response) {
            let html = '';
            
            if (response.data && response.data.length > 0) {
                response.data.forEach(patient => {
                    html += `
                        <tr>
                            <td><code>${patient.codigo_paciente}</code></td>
                            <td><strong>${patient.numero_expediente}</strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            ${patient.primer_nombre.charAt(0)}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">${patient.nombre_completo}</div>
                                    </div>
                                </div>
                            </td>
                            <td>${patient.documento_identidad}</td>
                            <td>${patient.telefono}</td>
                            <td>
                                <span class="badge bg-label-${patient.estado === 'activo' ? 'success' : 'secondary'}">
                                    ${patient.estado === 'activo' ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-sm btn-outline-info" onclick="verPaciente(${patient.id})">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="agendarCita(${patient.id})">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editarPaciente(${patient.id})">
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
                            <i class="fa-solid fa-user-plus fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-3">No hay pacientes registrados</p>
                            <button class="btn btn-primary" onclick="window.location.href='/patients/create'">
                                <i class="fa-solid fa-plus me-1"></i>Registrar Primer Paciente
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            $('#patientsTable tbody').html(html);
        }
    });
}

function verPaciente(id) {
    window.location.href = `/patients/${id}`;
}

function agendarCita(id) {
    window.location.href = `/appointments/create?patient_id=${id}`;
}

function editarPaciente(id) {
    window.location.href = `/patients/${id}/edit`;
}
</script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Clínica /</span> Pacientes
</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista de Pacientes</h5>
        <button type="button" class="btn btn-primary" id="btnAddPatient">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Paciente
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info" role="alert">
            <h6 class="alert-heading mb-2"><i class="fa-solid fa-info-circle me-2"></i>Módulo de Pacientes</h6>
            <p class="mb-0">Este módulo permite gestionar la información de los pacientes de la clínica. Aquí podrás:</p>
            <ul class="mb-0 mt-2">
                <li>Registrar nuevos pacientes con su información personal y médica</li>
                <li>Ver el expediente clínico completo de cada paciente</li>
                <li>Gestionar el historial de consultas y tratamientos</li>
                <li>Vincular pacientes con citas médicas y órdenes de laboratorio</li>
            </ul>
            <hr>
            <p class="mb-0"><strong>Estado:</strong> <span class="badge bg-success">Módulo Activo</span></p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="patientsTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Expediente</th>
                        <th>Nombre Completo</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fa-solid fa-user-plus fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay pacientes registrados. Haz clic en "Nuevo Paciente" para comenzar.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

