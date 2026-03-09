@extends('layouts/layoutMaster')

@section('title', 'Detalles de la Nota de Débito')

@section('vendor-style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="py-3 mb-0 fw-bold">
                    <i class="fas fa-file-invoice me-2"></i>
                    Nota de Débito #{{ $debitNote->id }}
                </h4>
                <div class="gap-2 d-flex">
                    @can('notas-debito.print')
                        <a href="{{ route('debit-notes.print', $debitNote) }}" class="btn btn-success" target="_blank">
                            <i class="fas fa-print me-1"></i>
                            Imprimir
                        </a>
                    @endcan
                    @can('notas-debito.edit')
                        <a href="{{ route('debit-notes.edit', $debitNote) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>
                            Editar
                        </a>
                    @endcan
                    <a href="{{ route('debit-notes.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver a la Lista
                    </a>
                </div>
            </div>

            <!-- Información General -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha de Emisión:</label>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($debitNote->date)->format('d/m/Y') }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Empresa:</label>
                                <p class="mb-0">{{ $debitNote->company->name ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Creado por:</label>
                                <p class="mb-0">{{ $debitNote->user->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Estado:</label>
                                <p class="mb-0">
                                    <span class="badge bg-{{ $debitNote->state == 1 ? 'success' : 'danger' }}">
                                        {{ $debitNote->state == 1 ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Motivo:</label>
                                <p class="mb-0">{{ $debitNote->motivo ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Total:</label>
                                <p class="mb-0 fw-bold text-primary">${{ number_format($debitNote->totalamount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>
                        Información del Cliente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre:</label>
                                <p class="mb-0">
                                    @switch($debitNote->client->tpersona)
                                        @case('N')
                                            {{ $debitNote->client->firstname . ' ' . $debitNote->client->firstlastname }}
                                            @break
                                        @case('J')
                                            {{ $debitNote->client->name_contribuyente ?? 'N/A' }}
                                            @break
                                        @default
                                            {{ $debitNote->client->nameClient ?? 'N/A' }}
                                    @endswitch
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">NIT:</label>
                                <p class="mb-0">{{ $debitNote->client->nit ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email:</label>
                                <p class="mb-0">{{ $debitNote->client->email ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Teléfono:</label>
                                <p class="mb-0">{{ $debitNote->client->phone ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles de Productos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-boxes me-2"></i>
                        Detalles de Productos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end">Total</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($debitNote->details as $detalle)
                                    <tr>
                                        <td>{{ $detalle->product->code ?? 'N/A' }}</td>
                                        <td>{{ ($detalle->ruta && trim($detalle->ruta) !== '' && $detalle->product && $detalle->product->code == 'LAB') ? $detalle->ruta : ($detalle->product->name ?? 'N/A') }}</td>
                                        <td class="text-center">{{ number_format($detalle->amountp, 2) }}</td>
                                        <td class="text-end">${{ number_format($detalle->priceunit, 2) }}</td>
                                        <td class="text-end">${{ number_format($detalle->pricesale, 2) }}</td>
                                        <td class="text-end">${{ number_format($detalle->detained13, 2) }}</td>
                                        <td class="text-end">${{ number_format($detalle->pricesale + $detalle->detained13, 2) }}</td>
                                        <td>{{ $detalle->exempt > 0 ? 'Exenta' : ($detalle->nosujeta > 0 ? 'No Sujeta' : 'Gravada') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th class="text-end">${{ number_format($debitNote->details->sum('pricesale'), 2) }}</th>
                                    <th class="text-end">${{ number_format($debitNote->details->sum('detained13'), 2) }}</th>
                                    <th class="text-end">${{ number_format($debitNote->totalamount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Información del DTE -->
            @if($debitNote->dte)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-invoice me-2"></i>
                            Información del DTE
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Número DTE:</label>
                                    <p class="mb-0">{{ $debitNote->dte->id_doc ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tipo DTE:</label>
                                    <p class="mb-0">
                                        @if($debitNote->dte->tipoDte == '06')
                                            <span class="badge bg-info">06 - Nota de Débito</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $debitNote->dte->tipoDte }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Código de Generación:</label>
                                    <p class="mb-0">{{ $debitNote->dte->codigoGeneracion ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Estado Hacienda:</label>
                                    <p class="mb-0">
                                        @if($debitNote->dte->estadoHacienda == 'PROCESADO')
                                            <span class="badge bg-success">Procesado</span>
                                        @elseif($debitNote->dte->estadoHacienda == 'RECHAZADO')
                                            <span class="badge bg-danger">Rechazado</span>
                                        @else
                                            <span class="badge bg-warning">{{ $debitNote->dte->estadoHacienda ?? 'Pendiente' }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fecha de Recepción:</label>
                                    <p class="mb-0">
                                        @if($debitNote->dte->fhRecibido)
                                            {{ \Carbon\Carbon::parse($debitNote->dte->fhRecibido)->format('d/m/Y H:i:s') }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sello Recibido:</label>
                                    <p class="mb-0 text-break">{{ $debitNote->dte->selloRecibido ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
