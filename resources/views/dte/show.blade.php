@extends('layouts/layoutMaster')

@section('title', 'Detalles DTE')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Reintentar DTE
    $('#reintentarDte').click(function() {
        const btn = $(this);
        const originalText = btn.text();

        btn.prop('disabled', true).text('Procesando...');

        $.get('{{ route("dte.reprocesar", $dte->id) }}')
        .done(function(response) {
            Swal.fire({
                icon: 'success',
                title: '¡DTE procesado!',
                text: 'El documento ha sido reprocesado exitosamente',
                timer: 2000
            }).then(() => {
                location.reload();
            });
        })
        .fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
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
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="py-3 mb-0 fw-bold">
                    <i class="fas fa-file-invoice me-2"></i>
                    Detalles DTE #{{ $dte->id }}
                </h4>
                <div class="gap-2 d-flex">
                    @if($dte->puedeReintentar())
                        <button id="reintentarDte" class="btn btn-warning">
                            <i class="fas fa-redo me-1"></i>
                            Reintentar
                        </button>
                    @endif
                    <a href="{{ route('dte.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información principal -->
    <div class="mb-4 row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del DTE
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $dte->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Número Control:</strong></td>
                                    <td>{{ $dte->id_doc }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo DTE:</strong></td>
                                    <td>{{ $dte->tipoDte }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td>{{ $dte->company->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $dte->estado_color }}">
                                            {{ $dte->estado_texto }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Código Generación:</strong></td>
                                    <td>{{ $dte->codigoGeneracion ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Creación:</strong></td>
                                    <td>{{ $dte->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Última Actualización:</strong></td>
                                    <td>{{ $dte->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Intentos:</strong></td>
                                    <td>{{ $dte->nSends }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Contingencia:</strong></td>
                                    <td>
                                        @if($dte->idContingencia)
                                            <span class="badge bg-warning">En Contingencia</span>
                                        @else
                                            <span class="badge bg-secondary">Sin Contingencia</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Días desde creación:</span>
                        <strong>{{ $dte->created_at->diffInDays(now()) }}</strong>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Puede reintentar:</span>
                        <strong>{{ $dte->puedeReintentar() ? 'Sí' : 'No' }}</strong>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Necesita contingencia:</span>
                        <strong>{{ $dte->necesitaContingencia() ? 'Sí' : 'No' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Es urgente:</span>
                        <strong>{{ $dte->created_at->diffInDays(now()) > 7 ? 'Sí' : 'No' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de respuesta de Hacienda -->
    @if($dte->codigoGeneracion)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-building me-2"></i>
                        Respuesta de Hacienda
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Código Estado:</strong></td>
                                    <td>{{ $dte->codEstado }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado Hacienda:</strong></td>
                                    <td>{{ $dte->estadoHacienda ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sello Recibido:</strong></td>
                                    <td>
                                        @if($dte->selloRecibido)
                                            <code class="small">{{ Str::limit($dte->selloRecibido, 50) }}</code>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Recibido:</strong></td>
                                    <td>{{ $dte->fhRecibido ? $dte->fhRecibido->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Código Mensaje:</strong></td>
                                    <td>{{ $dte->codeMessage ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Clasificación Mensaje:</strong></td>
                                    <td>{{ $dte->claMessage ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Descripción Mensaje:</strong></td>
                                    <td>{{ $dte->descriptionMessage ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Detalles Mensaje:</strong></td>
                                    <td>{{ $dte->detailsMessage ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Información de la venta -->
    @if($dte->sale)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Información de la Venta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID Venta:</strong></td>
                                    <td>{{ $dte->sale->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cliente:</strong></td>
                                    <td>{{ $dte->sale->client->name_contribuyente ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td>${{ number_format($dte->sale->totalamount ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Venta:</strong></td>
                                    <td>{{ $dte->sale->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Tipo Venta:</strong></td>
                                    <td>{{ $dte->sale->typesale == 1 ? 'Finalizada' : 'Pendiente' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Número Documento:</strong></td>
                                    <td>{{ $dte->sale->nu_doc ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Usuario:</strong></td>
                                    <td>{{ $dte->sale->user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $dte->sale->typesale == 1 ? 'success' : 'warning' }}">
                                            {{ $dte->sale->typesale == 1 ? 'Finalizada' : 'Pendiente' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Mensajes de error -->
    @if($dte->descriptionMessage)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Mensajes de Error
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h6><strong>Descripción del Error:</strong></h6>
                        <p>{{ $dte->descriptionMessage }}</p>

                        @if($dte->detailsMessage)
                        <h6><strong>Detalles:</strong></h6>
                        <p>{{ $dte->detailsMessage }}</p>
                        @endif

                        @if($dte->codeMessage)
                        <h6><strong>Código de Error:</strong></h6>
                        <p><code>{{ $dte->codeMessage }}</code></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- JSON del DTE -->
    @if($dte->json)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-code me-2"></i>
                        JSON del DTE
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="p-3 rounded bg-light" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode(json_decode($dte->json), JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
