<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errores DTE - RomaCopies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-card {
            border-left: 4px solid #dc3545;
        }
        .error-card.resuelto {
            border-left-color: #28a745;
        }
        .badge-critico {
            background-color: #dc3545;
        }
        .badge-hacienda {
            background-color: #fd7e14;
        }
        .badge-sistema {
            background-color: #6c757d;
        }
        .badge-validacion {
            background-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Errores de DTE - Sistema de Captura
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Estadísticas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">{{ $estadisticas['total'] ?? 0 }}</h5>
                                        <p class="card-text">Total Errores</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">{{ $estadisticas['no_resueltos'] ?? 0 }}</h5>
                                        <p class="card-text">No Resueltos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">{{ $estadisticas['resueltos'] ?? 0 }}</h5>
                                        <p class="card-text">Resueltos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">{{ $estadisticas['criticos'] ?? 0 }}</h5>
                                        <p class="card-text">Críticos</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <select name="tipo" class="form-select">
                                            <option value="">Todos los tipos</option>
                                            <option value="validacion" {{ request('tipo') == 'validacion' ? 'selected' : '' }}>Validación</option>
                                            <option value="hacienda" {{ request('tipo') == 'hacienda' ? 'selected' : '' }}>Hacienda</option>
                                            <option value="sistema" {{ request('tipo') == 'sistema' ? 'selected' : '' }}>Sistema</option>
                                            <option value="autenticacion" {{ request('tipo') == 'autenticacion' ? 'selected' : '' }}>Autenticación</option>
                                            <option value="firma" {{ request('tipo') == 'firma' ? 'selected' : '' }}>Firma</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="resuelto" class="form-select">
                                            <option value="">Todos los estados</option>
                                            <option value="0" {{ request('resuelto') === '0' ? 'selected' : '' }}>No resueltos</option>
                                            <option value="1" {{ request('resuelto') === '1' ? 'selected' : '' }}>Resueltos</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i> Filtrar
                                        </button>
                                        <a href="{{ route('dte.errores') }}" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i> Limpiar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Lista de errores -->
                        @if($errores->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>DTE ID</th>
                                            <th>Tipo</th>
                                            <th>Empresa</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                            <th>Intentos</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($errores as $error)
                                            <tr class="{{ $error->resuelto ? 'table-success' : '' }}">
                                                <td>{{ $error->id }}</td>
                                                <td>
                                                    <a href="{{ route('dte.show', $error->dte_id) }}" class="text-primary">
                                                        {{ $error->dte_id }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($error->tipo_error) {
                                                            'hacienda' => 'badge-hacienda',
                                                            'sistema' => 'badge-sistema',
                                                            'validacion' => 'badge-validacion',
                                                            'autenticacion', 'firma' => 'badge-critico',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ ucfirst($error->tipo_error) }}
                                                    </span>
                                                </td>
                                                <td>{{ $error->dte->company->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="text-truncate d-inline-block" style="max-width: 300px;"
                                                          title="{{ $error->descripcion }}">
                                                        {{ Str::limit($error->descripcion, 80) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($error->resuelto)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i> Resuelto
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i> Pendiente
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ $error->intentos_realizados }}/{{ $error->max_intentos }}
                                                    </span>
                                                </td>
                                                <td>{{ $error->created_at->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('dte.show', $error->dte_id) }}"
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Ver DTE">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if(!$error->resuelto)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="resolverError({{ $error->id }})"
                                                                    title="Marcar como resuelto">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-center">
                                {{ $errores->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3">¡Excelente!</h4>
                                <p class="text-muted">No se encontraron errores de DTE.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para resolver error -->
    <div class="modal fade" id="resolverErrorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resolver Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resolverErrorForm">
                        <div class="mb-3">
                            <label for="solucion" class="form-label">Solución aplicada:</label>
                            <textarea class="form-control" id="solucion" name="solucion" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="confirmarResolucion()">Resolver</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let errorIdActual = null;

        function resolverError(errorId) {
            errorIdActual = errorId;
            document.getElementById('solucion').value = '';
            new bootstrap.Modal(document.getElementById('resolverErrorModal')).show();
        }

        function confirmarResolucion() {
            const solucion = document.getElementById('solucion').value;
            if (!solucion.trim()) {
                alert('Por favor ingrese una solución');
                return;
            }

            fetch(`/dte/errores/${errorIdActual}/resolver`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ solucion: solucion })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al resolver: ' + error.message);
            });
        }
    </script>
</body>
</html>
