@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Órdenes de Laboratorio')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Laboratorio /</span> Órdenes de Laboratorio
</h4>

<div class="row mb-4">
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold d-block mb-1">Pendientes</span>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="fa-solid fa-hourglass-half fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold d-block mb-1">En Proceso</span>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="fa-solid fa-spinner fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold d-block mb-1">Completadas Hoy</span>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="fa-solid fa-check-circle fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold d-block mb-1">Total del Mes</span>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="fa-solid fa-flask fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Órdenes de Laboratorio</h5>
        <button type="button" class="btn btn-primary" id="btnAddOrder">
            <i class="fa-solid fa-plus me-1"></i> Nueva Orden
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info" role="alert">
            <h6 class="alert-heading mb-2"><i class="fa-solid fa-flask me-2"></i>Módulo de Laboratorio</h6>
            <p class="mb-0">Este módulo permite gestionar las órdenes de laboratorio clínico. Funcionalidades:</p>
            <ul class="mb-0 mt-2">
                <li>Crear órdenes de exámenes de laboratorio</li>
                <li>Gestionar toma de muestras</li>
                <li>Registrar y validar resultados</li>
                <li>Generar reportes de resultados</li>
                <li>Control de calidad y equipamiento</li>
            </ul>
            <hr>
            <p class="mb-0"><strong>Estado:</strong> <span class="badge bg-success">Módulo Activo</span></p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead>
                    <tr>
                        <th>No. Orden</th>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Exámenes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fa-solid fa-vial fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay órdenes de laboratorio. Haz clic en "Nueva Orden" para comenzar.</p>
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
// Empresa actual (para filtrar médicos activos)
const LAB_COMPANY_ID = {{ $company_id }};

$(document).ready(function() {
    // Botón para agregar nueva orden
    $('#btnAddOrder').on('click', function() {
        window.location.href = '/lab-orders/create';
    });

    // Cargar estadísticas
    cargarEstadisticas();
    
    // Cargar lista de órdenes
    cargarOrdenes();
});

function cargarEstadisticas() {
    $.ajax({
        url: '/lab-orders/pending-count',
        method: 'GET',
        success: function(response) {
            // Actualizar contadores si la respuesta tiene datos
            if (response.pendientes !== undefined) {
                $('.card:eq(0) .card-title').text(response.pendientes || 0);
            }
            if (response.en_proceso !== undefined) {
                $('.card:eq(1) .card-title').text(response.en_proceso || 0);
            }
            if (response.completadas_hoy !== undefined) {
                $('.card:eq(2) .card-title').text(response.completadas_hoy || 0);
            }
            if (response.total_mes !== undefined) {
                $('.card:eq(3) .card-title').text(response.total_mes || 0);
            }
        }
    });
}

function cargarOrdenes() {
    $.ajax({
        url: '/lab-orders/data',
        method: 'GET',
        success: function(response) {
            let html = '';
            
            if (response.data && response.data.length > 0) {
                response.data.forEach(orden => {
                    const fechaOrden = new Date(orden.fecha_orden);
                    const fechaFormateada = fechaOrden.toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                    html += `
                        <tr>
                            <td><code>${orden.numero_orden}</code></td>
                            <td>${fechaFormateada}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            ${orden.patient && orden.patient.primer_nombre ? orden.patient.primer_nombre.charAt(0).toUpperCase() : '?'}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            ${orden.patient ? 
                                                (orden.patient.primer_nombre || '') + ' ' + (orden.patient.primer_apellido || '') : 
                                                'N/A'}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                ${orden.doctor ? 
                                    (orden.doctor.nombres || '') + ' ' + (orden.doctor.apellidos || '') : 
                                    'Sin médico'}
                            </td>
                            <td>
                                <span class="badge bg-label-info">${orden.exams ? orden.exams.length : 0} exámenes</span>
                                <br><small class="text-muted">$${parseFloat(orden.total || 0).toFixed(2)}</small>
                            </td>
                            <td>
                                <span class="badge bg-label-${
                                    orden.estado === 'completada' ? 'success' :
                                    (orden.estado === 'en_proceso' ? 'info' :
                                    (orden.estado === 'cancelada' ? 'danger' : 'warning'))
                                }">
                                    ${orden.estado ? orden.estado.replace('_', ' ').charAt(0).toUpperCase() + orden.estado.replace('_', ' ').slice(1) : 'Pendiente'}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    ${
                                        // Mostrar botón "Autorizar" solo si:
                                        // 1. El estado es 'en_proceso' (ya fue autorizada inicialmente)
                                        // 2. Todos los exámenes tienen resultados completos
                                        // 3. El estado NO es 'completada' ni 'entregada'
                                        (orden.estado === 'en_proceso' && orden.all_exams_completed && 
                                         orden.estado !== 'completada' && orden.estado !== 'entregada')
                                            ? `<button class="btn btn-sm btn-outline-success" title="Autorizar orden (firma final)" onclick="autorizarOrden(${orden.id})">
                                                    <i class="fa-solid fa-check"></i>
                                               </button>`
                                            : ''
                                    }
                                    <a href="/lab-orders/${orden.id}" class="btn btn-sm btn-outline-info" title="Ver detalles">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="/lab-orders/${orden.id}/print" class="btn btn-sm btn-outline-primary" target="_blank" title="Imprimir orden">
                                        <i class="fa-solid fa-print"></i>
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
                            <i class="fa-solid fa-vial fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-3">No hay órdenes de laboratorio registradas</p>
                            <button class="btn btn-primary" onclick="window.location.href='/lab-orders/create'">
                                <i class="fa-solid fa-plus me-1"></i>Crear Primera Orden
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            $('#ordersTable tbody').html(html);
        },
        error: function(xhr) {
            console.error('Error al cargar órdenes:', xhr);
        }
    });
}

/**
 * Autorizar una orden de laboratorio y asignar médico
 */
function autorizarOrden(orderId) {
    // Cargar médicos activos
    $.ajax({
        url: '/doctors/active?company_id=' + LAB_COMPANY_ID,
        method: 'GET',
        success: function(doctors) {
            if (!doctors || doctors.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin médicos activos',
                    text: 'No hay médicos activos configurados. Debe registrar al menos un médico.'
                });
                return;
            }

            let options = doctors.map(doc => {
                const nombre = `${doc.nombres ?? ''} ${doc.apellidos ?? ''}`.trim();
                const especialidad = doc.especialidad ? ` - ${doc.especialidad}` : '';
                return `<option value="${doc.id}">${nombre}${especialidad}</option>`;
            }).join('');

            Swal.fire({
                title: 'Autorizar orden de laboratorio',
                html: `
                    <p class="mb-2 text-start">
                        <strong>Firma final:</strong> Seleccione el médico que autoriza esta orden como completa.
                    </p>
                    <p class="mb-2 text-start text-muted small">
                        Todos los exámenes deben tener resultados registrados para poder autorizar.
                    </p>
                    <select id="swal-doctor" class="swal2-select" style="width:100%">
                        <option value="">Seleccione un médico</option>
                        ${options}
                    </select>
                `,
                showCancelButton: true,
                confirmButtonText: 'Autorizar y Completar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: () => {
                    const doctorId = document.getElementById('swal-doctor').value;
                    if (!doctorId) {
                        Swal.showValidationMessage('Debe seleccionar un médico');
                    }
                    return doctorId;
                }
            }).then(result => {
                if (!result.isConfirmed) {
                    return;
                }

                const doctorId = result.value;

                Swal.fire({
                    title: 'Autorizando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/lab-orders/${orderId}/status`,
                    method: 'POST',
                    data: {
                        _method: 'PUT',
                        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        estado: 'completada', // Cambiar a 'completada' cuando se autoriza (firma final)
                        doctor_id: doctorId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Orden autorizada',
                            text: 'La orden ha sido autorizada y completada correctamente.'
                        });
                        cargarEstadisticas();
                        cargarOrdenes();
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error al autorizar la orden';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            const msgs = [];
                            for (let field in errors) {
                                if (errors[field] && errors[field][0]) {
                                    msgs.push(errors[field][0]);
                                }
                            }
                            if (msgs.length) {
                                errorMsg = msgs.join('\n');
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    }
                });
            });
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los médicos activos.'
            });
        }
    });
}
</script>
@endsection

