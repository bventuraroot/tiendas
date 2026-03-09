@extends('layouts/layoutMaster')

@section('title', 'Facturación Integral - Todos los Módulos')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.servicio-card {
    transition: all 0.2s;
    cursor: pointer;
    border: 2px solid transparent;
}
.servicio-card:hover {
    border-color: #696cff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.servicio-card.selected {
    border-color: #696cff;
    background-color: #f5f5ff;
}
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('content')

<!-- Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold py-3 mb-2">
            <span class="text-muted fw-light"><a href="/dashboard" class="text-muted"><i class="fa-solid fa-arrow-left me-2"></i>Dashboard</a> /</span>
            Facturación Integral
        </h4>
        <p class="text-muted">Factura servicios de Farmacia y Laboratorio desde un solo lugar. La clínica es solo para control de citas, pacientes y consultas médicas.</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="card bg-primary text-white">
            <div class="card-body p-3">
                <h6 class="text-white mb-1">Total Facturado Hoy</h6>
                <h3 class="text-white mb-0">${{ number_format($totalHoy, 2) }}</h3>
                <small class="text-white-50">{{ $ventasHoy }} facturas emitidas</small>
            </div>
        </div>
    </div>
</div>

<!-- Pestañas de Facturación -->
<div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tipo == 'farmacia' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-farmacia" aria-controls="navs-farmacia">
                <i class="fa-solid fa-pills tf-icons me-2"></i>
                Farmacia
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tipo == 'laboratorio' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#navs-laboratorio" aria-controls="navs-laboratorio">
                <i class="fa-solid fa-flask tf-icons me-2"></i>
                Órdenes de Laboratorio
                @if(count($ordenesLabPorFacturar) > 0)
                <span class="badge rounded-pill badge-center bg-danger ms-2">{{ count($ordenesLabPorFacturar) }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- TAB FARMACIA -->
        <div class="tab-pane fade {{ $tipo == 'farmacia' ? 'show active' : '' }}" id="navs-farmacia" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Venta de Productos - Farmacia</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Para realizar ventas de productos de farmacia, utiliza el módulo de ventas estándar.
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('sale.create-dynamic', ['typedocument' => 6, 'new' => true]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-cash-register me-2"></i>Nueva Venta de Productos
                        </a>
                        <a href="/sale/index" class="btn btn-outline-primary">
                            <i class="fa-solid fa-list me-2"></i>Ver Todas las Ventas
                        </a>
                        <a href="/presales/index" class="btn btn-outline-success">
                            <i class="fa-solid fa-cart-shopping me-2"></i>Ventas Menudeo
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- TAB ÓRDENES DE LABORATORIO -->
        <div class="tab-pane fade {{ $tipo == 'laboratorio' ? 'show active' : '' }}" id="navs-laboratorio" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Órdenes de Laboratorio por Facturar</h5>
                        <small class="text-muted">
                            Órdenes pendientes de facturación (se permiten órdenes en cualquier estado excepto canceladas)
                        </small>
                    </div>
                    <a href="/lab-orders" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-list me-1"></i>Ver Todas las Órdenes
                    </a>
                </div>
                <div class="card-body">
                    @if(count($ordenesLabPorFacturar) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Orden</th>
                                    <th>Fecha</th>
                                    <th>Paciente</th>
                                    <th>Exámenes</th>
                                    <th>Estado</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordenesLabPorFacturar as $orden)
                                <tr>
                                    <td>
                                        <strong>{{ $orden->numero_orden }}</strong>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($orden->fecha_orden)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-warning">
                                                    {{ substr($orden->patient->primer_nombre, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $orden->patient->nombre_completo }}</div>
                                                <small class="text-muted">{{ $orden->patient->documento_identidad }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $orden->exams->count() }} examen(es)</span>
                                        <br>
                                        <small class="text-muted">
                                            @foreach($orden->exams->take(2) as $exam)
                                                {{ $exam->exam->nombre }}{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                            @if($orden->exams->count() > 2)
                                                ...
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $estado = $orden->estado ?? 'pendiente';
                                            $badgeClass = match($estado) {
                                                'pendiente' => 'bg-label-secondary',
                                                'muestra_tomada' => 'bg-label-info',
                                                'en_proceso' => 'bg-label-warning',
                                                'completada' => 'bg-label-success',
                                                'entregada' => 'bg-label-primary',
                                                'cancelada' => 'bg-label-danger',
                                                default => 'bg-label-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($estado) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-warning">${{ number_format($orden->total, 2) }}</strong>
                                        <br>
                                        <small class="text-muted">Exámenes</small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" onclick="facturarOrdenLab({{ $orden->id }})">
                                            <i class="fa-solid fa-file-invoice me-1"></i>Facturar
                                        </button>
                                        <a href="/lab-orders/{{ $orden->id }}" class="btn btn-sm btn-outline-info" target="_blank">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-check-circle fa-4x text-success mb-3 d-block"></i>
                        <h5>No hay órdenes pendientes de facturar</h5>
                        <p class="text-muted">Todas las órdenes completadas han sido facturadas</p>
                        <a href="/lab-orders/create" class="btn btn-warning mt-3">
                            <i class="fa-solid fa-plus me-2"></i>Nueva Orden de Laboratorio
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Estadísticas Rápidas -->
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Productos Farmacia</h6>
                        <h4 class="mb-0">-</h4>
                        <small class="text-muted">Usar módulo de ventas</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="fa-solid fa-pills fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Órdenes Lab. Pendientes</h6>
                        <h4 class="mb-0 text-warning">{{ count($ordenesLabPorFacturar) }}</h4>
                        <small class="text-muted">Por facturar</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="fa-solid fa-flask fa-2x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
// Las consultas médicas NO se facturan - solo control clínico

function facturarOrdenLab(ordenId) {
    console.log('facturarOrdenLab llamado con ordenId:', ordenId);

    if (!ordenId) {
        alert('Error: ID de orden no válido');
        return;
    }

    // Verificar si Swal está disponible, si no, usar confirm nativo
    const useSwal = typeof Swal !== 'undefined';

    if (useSwal) {
        Swal.fire({
            title: '¿Crear draft de factura?',
            text: 'Se creará un borrador de factura con los exámenes de esta orden',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, crear draft',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarFacturacion(ordenId, useSwal);
            }
        });
    } else {
        if (confirm('¿Desea crear el draft de factura para esta orden de laboratorio?')) {
            procesarFacturacion(ordenId, useSwal);
        }
    }
}

function procesarFacturacion(ordenId, useSwal) {
    console.log('Procesando facturación para orden:', ordenId);

    // Mostrar loading
    if (useSwal) {
        Swal.fire({
            title: 'Creando draft...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    } else {
        // Si no hay Swal, mostrar un mensaje simple
        const loadingMsg = document.createElement('div');
        loadingMsg.id = 'loading-msg';
        loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border:2px solid #333;z-index:9999;';
        loadingMsg.innerHTML = 'Creando draft... Por favor espere';
        document.body.appendChild(loadingMsg);
    }

    // Obtener token CSRF del meta tag o del formulario
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    fetch(`/facturacion/orden-lab/${ordenId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || `Error HTTP: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);

        // Remover loading si existe
        const loadingMsg = document.getElementById('loading-msg');
        if (loadingMsg) loadingMsg.remove();

        if (data.success) {
            if (useSwal) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Draft creado!',
                    text: 'El draft de factura ha sido creado exitosamente',
                    confirmButtonText: 'Ir a la venta'
                }).then(() => {
                    redirectToSale(data);
                });
            } else {
                if (confirm('¡Draft creado exitosamente! ¿Desea ir a la venta ahora?')) {
                    redirectToSale(data);
                }
            }
        } else {
            const errorMsg = data.message || 'Error al crear el draft de factura';
            console.error('Error:', errorMsg);

            if (useSwal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            } else {
                alert('Error: ' + errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);

        // Remover loading si existe
        const loadingMsg = document.getElementById('loading-msg');
        if (loadingMsg) loadingMsg.remove();

        const errorMsg = error.message || 'Error al procesar la solicitud';

        if (useSwal) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg,
                footer: 'Por favor, verifique la consola del navegador para más detalles'
            });
        } else {
            alert('Error: ' + errorMsg);
        }
    });
}

function redirectToSale(data) {
    // Usar redirect_url del servidor si está disponible (ya tiene el formato correcto)
    let url = data.redirect_url;

    // Si no hay redirect_url, construir la URL con el formato correcto
    if (!url && data.sale_id) {
        url = '/sale/create-dynamic?corr=' + data.sale_id + '&draft=true&typedocument=6&operation=edit';
    }

    // Fallback si no hay sale_id ni redirect_url
    if (!url) {
        url = '/sale/create-dynamic?typedocument=6&new=true';
    }

    console.log('Redirigiendo a:', url);
    window.location.href = url;
}
</script>
@endsection

