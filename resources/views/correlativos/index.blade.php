@extends('layouts/layoutMaster')

@section('title', 'Gestión de Correlativos')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#correlativosTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: -1 } // Deshabilitar ordenamiento en columna de acciones
        ]
    });

    // Confirmar eliminación
    $('.btn-delete').click(function(e) {
        e.preventDefault();
        const url = $(this).attr('href');

        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario para enviar DELETE request
                const form = $('<form>', {
                    'method': 'POST',
                    'action': url
                });

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));

                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));

                $('body').append(form);
                form.submit();
            }
        });
    });

    // Reactivar correlativo
    $('.btn-reactivar').click(function(e) {
        e.preventDefault();
        const correlativoId = $(this).data('id');
        mostrarModalReactivar(correlativoId);
    });
});

function mostrarModalReactivar(correlativoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalReactivar'));
    const form = document.getElementById('formReactivar');
    form.action = `/correlativos/${correlativoId}/reactivar`;
    modal.show();
}

// Limpiar formulario al cerrar modal
document.getElementById('modalReactivar').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formReactivar').reset();
});
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-hashtag me-2"></i>
                    Gestión de Correlativos
                </h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('correlativos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Nuevo Correlativo
                    </a>
                    <a href="{{ route('correlativos.estadisticas') }}" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar me-1"></i>
                        Estadísticas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Correlativos</h6>
                            <h3 class="mb-0">{{ $correlativos->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-primary rounded">
                            <i class="fas fa-hashtag text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Activos</h6>
                            <h3 class="mb-0 text-success">{{ $correlativos->where('estado', 1)->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-success rounded">
                            <i class="fas fa-check text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Agotados</h6>
                            <h3 class="mb-0 text-warning">{{ $correlativos->where('estado', 2)->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-warning rounded">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Inactivos</h6>
                            <h3 class="mb-0 text-danger">{{ $correlativos->where('estado', 0)->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-danger rounded">
                            <i class="fas fa-times text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('correlativos.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="empresa_id" class="form-label">Empresa</label>
                                <select name="empresa_id" id="empresa_id" class="form-select">
                                    <option value="">Todas las empresas</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_documento" class="form-label">Tipo de Documento</label>
                                <select name="tipo_documento" id="tipo_documento" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    @foreach($tiposDocumento as $tipo)
                                        <option value="{{ $tipo->type }}" {{ request('tipo_documento') == $tipo->type ? 'selected' : '' }}>
                                            {{ $tipo->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Agotado</option>
                                    <option value="3" {{ request('estado') == '3' ? 'selected' : '' }}>Suspendido</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>
                                    Filtrar
                                </button>
                                <a href="{{ route('correlativos.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Correlativos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Correlativos
                        <span class="badge bg-primary ms-2">{{ $correlativos->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="correlativosTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Tipo Documento</th>
                                    <th>Serie</th>
                                    <th>Rango</th>
                                    <th>Actual</th>
                                    <th>Restantes</th>
                                    <th>Progreso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($correlativos as $correlativo)
                                    <tr>
                                        <td>{{ $correlativo->id }}</td>
                                        <td>{{ $correlativo->empresa->name ?? 'N/A' }}</td>
                                        <td>
                                            <div>
                                                <span class="badge bg-primary">{{ $correlativo->tipoDocumento->type ?? 'N/A' }}</span>
                                                <br>
                                                <small class="text-muted">{{ $correlativo->tipoDocumento->description ?? 'Sin definir' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $correlativo->serie }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ number_format($correlativo->inicial) }} - {{ number_format($correlativo->final) }}
                                            </small>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($correlativo->actual) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge {{ $correlativo->numerosRestantes() < 100 ? 'bg-warning' : 'bg-success' }}">
                                                {{ number_format($correlativo->numerosRestantes()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $porcentaje = $correlativo->porcentajeUso();
                                                    $colorClass = $porcentaje > 90 ? 'bg-danger' : ($porcentaje > 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}" role="progressbar"
                                                     style="width: {{ $porcentaje }}%"
                                                     aria-valuenow="{{ $porcentaje }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($porcentaje, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @switch($correlativo->estado)
                                                @case(1)
                                                    <span class="badge bg-success">Activo</span>
                                                    @break
                                                @case(0)
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                    @break
                                                @case(2)
                                                    <span class="badge bg-warning">Agotado</span>
                                                    @break
                                                @case(3)
                                                    <span class="badge bg-danger">Suspendido</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-dark">Desconocido</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('correlativos.show', $correlativo->id) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('correlativos.edit', $correlativo->id) }}"
                                                   class="btn btn-sm btn-outline-info"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($correlativo->estado == App\Models\Correlativo::ESTADO_AGOTADO)
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-reactivar"
                                                            title="Reactivar" data-id="{{ $correlativo->id }}">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                @endif
                                                @if($correlativo->actual == $correlativo->inicial)
                                                    <a href="{{ route('correlativos.destroy', $correlativo->id) }}"
                                                       class="btn btn-sm btn-outline-danger btn-delete"
                                                       title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-hashtag fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No se encontraron correlativos</p>
                                                <a href="{{ route('correlativos.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i>
                                                    Crear primer correlativo
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reactivar Correlativo -->
<div class="modal fade" id="modalReactivar" tabindex="-1" aria-labelledby="modalReactivarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formReactivar" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReactivarLabel">
                        <i class="fas fa-redo me-2"></i>
                        Reactivar Correlativo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Para reactivar este correlativo, debe asignar un nuevo rango de números que continúe después del rango anterior.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nuevo_inicial" class="form-label">Nuevo Número Inicial</label>
                            <input type="number" class="form-control" id="nuevo_inicial" name="nuevo_inicial" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nuevo_final" class="form-label">Nuevo Número Final</label>
                            <input type="number" class="form-control" id="nuevo_final" name="nuevo_final" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-redo me-1"></i>
                        Reactivar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
