@extends('layouts/layoutMaster')

@section('title', 'Estadísticas de Correlativos')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de distribución por estado
    const ctx = document.getElementById('estadoChart').getContext('2d');

    const estadoChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Activos', 'Agotados', 'Inactivos', 'Suspendidos'],
            datasets: [{
                data: [
                    {{ $estadisticas['activos'] }},
                    {{ $estadisticas['agotados'] }},
                    {{ $estadisticas['total'] - $estadisticas['activos'] - $estadisticas['agotados'] }},
                    0 // Suspendidos - agregar lógica si es necesario
                ],
                backgroundColor: [
                    '#28a745',
                    '#dc3545',
                    '#6c757d',
                    '#ffc107'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Cargar datos detallados
    cargarDetalleCorrelativos();
});

function actualizarEstadisticas() {
    const empresaId = document.getElementById('empresa_id').value;
    window.location.href = `{{ route('correlativos.estadisticas') }}?empresa_id=${empresaId}`;
}

function cargarDetalleCorrelativos() {
    const empresaId = {{ $empresaId }};

    fetch(`/api/correlativos/por-empresa?empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <table class="table table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th>Actual</th>
                            <th>Final</th>
                            <th>Restantes</th>
                            <th>Uso %</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (data.length === 0) {
                html += `
                    <tr>
                        <td colspan="8" class="text-center">No hay correlativos para esta empresa</td>
                    </tr>
                `;
            } else {
                data.forEach(correlativo => {
                    const porcentajeClass = correlativo.porcentaje_uso > 90 ? 'text-danger' :
                                          correlativo.porcentaje_uso > 70 ? 'text-warning' : 'text-success';

                    html += `
                        <tr>
                            <td>${correlativo.id}</td>
                            <td><small>${correlativo.tipo}</small></td>
                            <td>${correlativo.serie}</td>
                            <td>${correlativo.actual.toLocaleString()}</td>
                            <td>${correlativo.final.toLocaleString()}</td>
                            <td>
                                <span class="badge ${correlativo.restantes < 100 ? 'bg-warning' : 'bg-success'}">
                                    ${correlativo.restantes.toLocaleString()}
                                </span>
                            </td>
                            <td class="${porcentajeClass}">
                                <strong>${correlativo.porcentaje_uso.toFixed(1)}%</strong>
                            </td>
                            <td>
                                <small class="text-muted">${correlativo.estado}</small>
                            </td>
                        </tr>
                    `;
                });
            }

            html += `
                    </tbody>
                </table>
            `;

            document.getElementById('detalleCorrelativo').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalleCorrelativo').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los datos detallados
                </div>
            `;
        });
}

function exportarDatos() {
    const empresaId = {{ $empresaId }};

    fetch(`{{ route('correlativos.api.estadisticas') }}?empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            const dataStr = JSON.stringify(data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});

            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `estadisticas_correlativos_empresa_${empresaId}_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al exportar los datos');
        });
}
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas de Correlativos
                </h4>
                <div>
                    <a href="{{ route('correlativos.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtro de Empresa -->
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
                    <form method="GET" action="{{ route('correlativos.estadisticas') }}">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="empresa_id" class="form-label">Empresa</label>
                                <select name="empresa_id" id="empresa_id" class="form-select" onchange="this.form.submit()">
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ $empresaId == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('correlativos.index') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-1"></i>
                                        Ver Lista
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="actualizarEstadisticas()">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        Actualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Correlativos</h6>
                            <h3 class="mb-0">{{ $estadisticas['total'] }}</h3>
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
                            <h3 class="mb-0 text-success">{{ $estadisticas['activos'] }}</h3>
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
                            <h3 class="mb-0 text-warning">{{ $estadisticas['agotados'] }}</h3>
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
                            <h6 class="card-title text-muted">Alertas</h6>
                            <h3 class="mb-0 text-danger">{{ count($estadisticas['alertas']) }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-danger rounded">
                            <i class="fas fa-bell text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    @if(!empty($estadisticas['alertas']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Alertas de Correlativos
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($estadisticas['alertas'] as $alerta)
                            <div class="alert alert-{{ $alerta['tipo'] }} mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ $alerta['mensaje'] }}
                                <span class="badge bg-secondary ms-2">{{ $alerta['restantes'] }} números restantes</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estadísticas por Tipo de Documento -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Correlativos por Tipo de Documento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo de Documento</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Números Restantes</th>
                                    <th class="text-center">Uso Promedio</th>
                                    <th>Estado Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estadisticas['por_tipo'] as $tipo => $datos)
                                    <tr>
                                        <td>
                                            <strong>{{ $tipo }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $datos['total'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $datos['restantes'] < 100 ? 'bg-warning' : 'bg-success' }}">
                                                {{ number_format($datos['restantes']) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $datos['porcentaje_uso'] > 90 ? 'bg-danger' : ($datos['porcentaje_uso'] > 70 ? 'bg-warning' : 'bg-success') }}">
                                                {{ number_format($datos['porcentaje_uso'], 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $colorClass = $datos['porcentaje_uso'] > 90 ? 'bg-danger' : ($datos['porcentaje_uso'] > 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}"
                                                     role="progressbar"
                                                     style="width: {{ $datos['porcentaje_uso'] }}%"
                                                     aria-valuenow="{{ $datos['porcentaje_uso'] }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($datos['porcentaje_uso'], 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                            <p>No hay datos estadísticos disponibles</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Gráfico de Estado de Correlativos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Distribución por Estado
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="estadoChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('correlativos.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>
                            Nuevo Correlativo
                        </a>
                        <a href="{{ route('correlativos.index') }}" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i>
                            Ver Todos
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="exportarDatos()">
                            <i class="fas fa-download me-1"></i>
                            Exportar Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        Resumen Detallado por Correlativo
                    </h5>
                </div>
                <div class="card-body">
                    <div id="detalleCorrelativo" class="table-responsive">
                        <!-- Se carga dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando datos detallados...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
