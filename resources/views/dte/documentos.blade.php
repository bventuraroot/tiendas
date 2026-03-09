@extends('layouts/layoutMaster')

@section('title', 'Gestión de Documentos DTE')

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
    $('#documentosTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Confirmar eliminación
    $('.btn-delete').click(function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const documentoId = $(this).data('id');

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

    // Reprocesar documento
    $('.btn-reprocess').click(function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const btn = $(this);
        const originalText = btn.text();

        btn.prop('disabled', true).text('Procesando...');

        $.get(url)
        .done(function(response) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Documento reprocesado correctamente',
                timer: 3000
            });
            location.reload();
        })
        .fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al reprocesar el documento'
            });
        })
        .always(function() {
            btn.prop('disabled', false).text(originalText);
        });
    });
});
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-file-invoice me-2"></i>
                    Gestión de Documentos DTE
                </h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('dte.dashboard') }}" class="btn btn-outline-primary">
                        <i class="fas fa-chart-line me-1"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('dte.errores') }}" class="btn btn-outline-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Ver Errores
                    </a>
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
                    <form method="GET" action="{{ route('dte.documentos') }}">
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
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="01" {{ request('estado') == '01' ? 'selected' : '' }}>En Cola</option>
                                    <option value="02" {{ request('estado') == '02' ? 'selected' : '' }}>Enviado</option>
                                    <option value="03" {{ request('estado') == '03' ? 'selected' : '' }}>Rechazado</option>
                                    <option value="10" {{ request('estado') == '10' ? 'selected' : '' }}>En Revisión</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>
                                    Filtrar
                                </button>
                                <a href="{{ route('dte.documentos') }}" class="btn btn-outline-secondary">
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

    <!-- Tabla de documentos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Documentos DTE
                        <span class="badge bg-primary ms-2">{{ $documentos->total() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="documentosTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Número Control</th>
                                    <th>Tipo</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Intentos</th>
                                    <th>Fecha Creación</th>
                                    <th>Última Actualización</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentos as $documento)
                                <tr>
                                    <td>{{ $documento->id }}</td>
                                    <td>{{ $documento->id_doc }}</td>
                                    <td>
                                        @switch($documento->tipoDte)
                                            @case('01')
                                                <span class="badge bg-info">Factura</span>
                                                @break
                                            @case('02')
                                                <span class="badge bg-warning">Nota de Crédito</span>
                                                @break
                                            @case('03')
                                                <span class="badge bg-danger">Nota de Débito</span>
                                                @break
                                            @case('04')
                                                <span class="badge bg-secondary">Tiquete</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ $documento->tipoDte }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $documento->company->name ?? 'N/A' }}</td>
                                    <td>
                                        @switch($documento->codEstado)
                                            @case('01')
                                                <span class="badge bg-warning">En Cola</span>
                                                @break
                                            @case('02')
                                                <span class="badge bg-success">Enviado</span>
                                                @break
                                            @case('03')
                                                <span class="badge bg-danger">Rechazado</span>
                                                @break
                                            @case('10')
                                                <span class="badge bg-info">En Revisión</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $documento->codEstado }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $documento->nSends >= 3 ? 'danger' : 'primary' }}">
                                            {{ $documento->nSends ?? 0 }}
                                        </span>
                                    </td>
                                    <td>{{ $documento->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $documento->updated_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('dte.show', $documento->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($documento->codEstado == '03' && $documento->nSends < 3)
                                                <a href="{{ route('dte.reprocesar', $documento->id) }}"
                                                   class="btn btn-sm btn-outline-warning btn-reprocess"
                                                   title="Reprocesar">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('dte.destroy', $documento->id) }}"
                                               class="btn btn-sm btn-outline-danger btn-delete"
                                               data-id="{{ $documento->id }}"
                                               title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $documentos->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
