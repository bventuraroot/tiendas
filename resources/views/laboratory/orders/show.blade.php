@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Orden de Laboratorio #' . $order->numero_orden)

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Orden de Laboratorio</h4>
                <p class="text-muted mb-0">
                    <code>{{ $order->numero_orden }}</code> -
                    {{ \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y H:i') }}
                </p>
            </div>
            <div>
                <a href="/lab-orders" class="btn btn-outline-secondary me-2">
                    <i class="fa-solid fa-arrow-left me-1"></i>Volver
                </a>
                <a href="/lab-orders/{{ $order->id }}/print" class="btn btn-primary" target="_blank">
                    <i class="fa-solid fa-print me-1"></i>Imprimir
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Información de la Orden -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-user-injured me-2"></i>Información del Paciente</h6>
            </div>
            <div class="card-body">
                @if($order->patient)
                <p class="mb-2">
                    <strong>Nombre:</strong>
                    {{ $order->patient->primer_nombre }} {{ $order->patient->primer_apellido }}
                </p>
                <p class="mb-2">
                    <strong>Documento:</strong> {{ $order->patient->documento_identidad ?? 'N/A' }}
                </p>
                <p class="mb-2">
                    <strong>Edad:</strong>
                    @if($order->patient->fecha_nacimiento)
                        {{ \Carbon\Carbon::parse($order->patient->fecha_nacimiento)->age }} años
                    @else
                        N/A
                    @endif
                </p>
                <p class="mb-0">
                    <strong>Teléfono:</strong> {{ $order->patient->telefono ?? 'N/A' }}
                </p>
                @else
                <p class="text-muted">No hay información del paciente</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Información del Médico</h6>
            </div>
            <div class="card-body">
                @if($order->doctor)
                <p class="mb-2">
                    <strong>Nombre:</strong>
                    {{ $order->doctor->nombres }} {{ $order->doctor->apellidos }}
                </p>
                <p class="mb-2">
                    <strong>Especialidad:</strong> {{ $order->doctor->especialidad ?? 'N/A' }}
                </p>
                <p class="mb-0">
                    <strong>Teléfono:</strong> {{ $order->doctor->telefono ?? 'N/A' }}
                </p>
                @else
                <p class="text-muted">Sin médico asignado</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Estado de la Orden -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Estado de la Orden</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-{{
                        $order->estado === 'completada' ? 'success' :
                        ($order->estado === 'en_proceso' ? 'info' :
                        ($order->estado === 'cancelada' ? 'danger' : 'warning'))
                    }}">
                        {{ ucfirst(str_replace('_', ' ', $order->estado)) }}
                    </span>
                    @if(in_array($order->estado, ['pendiente', 'muestra_tomada']))
                        <button type="button" class="btn btn-sm btn-info" onclick="cambiarEstadoOrden({{ $order->id }}, 'en_proceso')">
                            <i class="fa-solid fa-play me-1"></i>Iniciar Procesamiento
                        </button>
                    @endif
                    @if($order->estado === 'en_proceso' && $order->allExamsHaveResults() && $order->estado !== 'completada' && $order->estado !== 'entregada')
                        <button type="button" class="btn btn-sm btn-success" onclick="autorizarOrdenDesdeDetalle({{ $order->id }})">
                            <i class="fa-solid fa-check me-1"></i>Autorizar Orden (Firma Final)
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Prioridad:</strong></p>
                        <p class="mb-0">{{ ucfirst($order->prioridad) }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Fecha de Orden:</strong></p>
                        <p class="mb-0">{{ \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Entrega Estimada:</strong></p>
                        <p class="mb-0">
                            @if($order->fecha_entrega_estimada)
                                {{ \Carbon\Carbon::parse($order->fecha_entrega_estimada)->format('d/m/Y H:i') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Total:</strong></p>
                        <p class="mb-0 text-primary fw-bold">${{ number_format($order->total, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Exámenes y Resultados -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa-solid fa-vial me-2"></i>Exámenes Solicitados y Resultados</h6>
            </div>
            <div class="card-body">
                @if($order->exams->count() > 0)
                    @foreach($order->exams as $orderExam)
                    <div class="card mb-3 border">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">{{ $orderExam->exam->nombre }}</h6>
                                    <small class="text-muted">
                                        {{ $orderExam->exam->tipo_muestra }} -
                                        ${{ number_format($orderExam->precio, 2) }}
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-label-{{
                                        $orderExam->estado === 'completado' ? 'success' :
                                        ($orderExam->estado === 'en_proceso' ? 'info' : 'warning')
                                    }}">
                                        {{ ucfirst(str_replace('_', ' ', $orderExam->estado)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($orderExam->results->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Parámetro</th>
                                                <th>Resultado</th>
                                                <th>Unidad</th>
                                                <th>Valor de Referencia</th>
                                                <th>Estado</th>
                                                <th>Validado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orderExam->results as $result)
                                            <tr>
                                                <td>{{ $result->parametro }}</td>
                                                <td><strong>{{ $result->resultado }}</strong></td>
                                                <td>{{ $result->unidad_medida ?? 'N/A' }}</td>
                                                <td>{{ $result->valor_referencia ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-label-{{
                                                        $result->estado_resultado === 'normal' ? 'success' :
                                                        ($result->estado_resultado === 'critico' ? 'danger' :
                                                        ($result->estado_resultado === 'alto' ? 'warning' : 'info'))
                                                    }}">
                                                        {{ ucfirst($result->estado_resultado) }}
                                                    </span>
                                                    @if($result->resultado_critico)
                                                        <span class="badge bg-danger ms-1">⚠️ CRÍTICO</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($result->validado_por)
                                                        <span class="badge bg-success">
                                                            <i class="fa-solid fa-check me-1"></i>
                                                            Firmado
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">Pendiente</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <a href="/lab-results/{{ $orderExam->id }}/pdf"
                                       class="btn btn-sm btn-outline-danger me-2"
                                       target="_blank">
                                        <i class="fa-solid fa-file-pdf me-1"></i>Ver PDF
                                    </a>
                                    <a href="/lab-results/{{ $orderExam->id }}/pdf/download"
                                       class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fa-solid fa-download me-1"></i>Descargar PDF
                                    </a>
                                    <a href="/lab-results/{{ $orderExam->id }}/doc"
                                       class="btn btn-sm btn-outline-info me-2"
                                       target="_blank">
                                        <i class="fa-solid fa-file-word me-1"></i>Ver DOC
                                    </a>
                                    <a href="/lab-results/{{ $orderExam->id }}/doc/download"
                                       class="btn btn-sm btn-outline-info me-2">
                                        <i class="fa-solid fa-download me-1"></i>Descargar DOC
                                    </a>
                                    @if($order->estado !== 'completada' && $order->estado !== 'entregada' && $orderExam->results->count() > 0)
                                        <a href="/lab-results/{{ $orderExam->results->first()->id }}/edit"
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fa-solid fa-edit me-1"></i>Editar Resultado
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-info mb-3">
                                    <i class="fa-solid fa-info-circle me-2"></i>
                                    Este examen aún no tiene resultados registrados.
                                </div>
                                @if($order->estado !== 'completada' && $order->estado !== 'entregada')
                                    <a href="/lab-results/{{ $orderExam->id }}/create" class="btn btn-primary">
                                        <i class="fa-solid fa-plus me-1"></i>Registrar Resultado
                                    </a>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fa-solid fa-lock me-2"></i>
                                        La orden está autorizada. No se pueden registrar nuevos resultados.
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        No hay exámenes asociados a esta orden.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
// Empresa actual (para filtrar médicos activos)
const LAB_COMPANY_ID = {{ $order->company_id ?? 'null' }};

/**
 * Cambiar el estado de una orden de laboratorio
 */
function cambiarEstadoOrden(orderId, nuevoEstado) {
    const estadoLabels = {
        'pendiente': 'Pendiente',
        'muestra_tomada': 'Muestra Tomada',
        'en_proceso': 'En Proceso',
        'completada': 'Completada',
        'entregada': 'Entregada',
        'cancelada': 'Cancelada'
    };

    Swal.fire({
        title: 'Cambiar estado de la orden',
        text: `¿Desea cambiar el estado a "${estadoLabels[nuevoEstado] || nuevoEstado}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Actualizando...',
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
                estado: nuevoEstado
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Estado actualizado',
                    text: 'El estado de la orden ha sido actualizado correctamente.',
                    confirmButtonText: 'Recargar página'
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function(xhr) {
                let errorMsg = 'Error al actualizar el estado';
                if (xhr.responseJSON && xhr.responseJSON.message) {
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
}

/**
 * Autorizar una orden de laboratorio desde la vista de detalles
 */
function autorizarOrdenDesdeDetalle(orderId) {
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
                        estado: 'completada',
                        doctor_id: doctorId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Orden autorizada',
                            text: 'La orden ha sido autorizada y completada correctamente.',
                            confirmButtonText: 'Recargar página'
                        }).then(() => {
                            window.location.reload();
                        });
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

