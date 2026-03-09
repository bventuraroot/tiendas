@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Detalle de Nota de Crédito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">
                    <i class="ti ti-file-minus me-2"></i>
                    Detalle de Nota de Crédito
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('credit-notes.print', $creditNote->id) }}"
                       class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="ti ti-printer me-1"></i>
                        Imprimir
                    </a>
                    <a href="{{ route('credit-notes.edit', $creditNote->id) }}"
                       class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-edit me-1"></i>
                        Editar
                    </a>
                    <a href="{{ route('credit-notes.index') }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-arrow-left me-1"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Información general -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Información General</h6>
                            <div class="row">
                                <div class="col-5"><strong>Número:</strong></div>
                                <div class="col-7">{{ $creditNote->dte->id_doc ?? $creditNote->id }}</div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>Fecha:</strong></div>
                                <div class="col-7">{{ \Carbon\Carbon::parse($creditNote->date)->format('d/m/Y') }}</div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>Estado:</strong></div>
                                <div class="col-7">
                                    @switch($creditNote->state)
                                        @case(0)
                                            <span class="badge bg-danger">ANULADO</span>
                                            @break
                                        @case(1)
                                            <span class="badge bg-success">ACTIVO</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-warning">PENDIENTE</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">DESCONOCIDO</span>
                                    @endswitch
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>Total:</strong></div>
                                <div class="col-7 text-success fw-bold">${{ number_format($creditNote->totalamount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Información del Cliente</h6>
                            <div class="row">
                                <div class="col-5"><strong>Cliente:</strong></div>
                                <div class="col-7">
                                    @switch($creditNote->client->tpersona)
                                        @case('N')
                                            {{ $creditNote->client->firstname . ' ' . $creditNote->client->firstlastname }}
                                            @break
                                        @case('J')
                                            {{ $creditNote->client->nameClient }}
                                            @break
                                        @default
                                            {{ $creditNote->client->nameClient }}
                                    @endswitch
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>NIT:</strong></div>
                                <div class="col-7">{{ $creditNote->client->nit ?? 'N/A' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>Email:</strong></div>
                                <div class="col-7">{{ $creditNote->client->email ?? 'N/A' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-5"><strong>Teléfono:</strong></div>
                                <div class="col-7">{{ $creditNote->client->phone ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del DTE -->
            @if($creditNote->dte)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-primary">Información del DTE</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-6"><strong>Tipo DTE:</strong></div>
                                            <div class="col-6">
                                                @if($creditNote->dte->tipoDte == '05')
                                                    <span class="badge bg-info">05 - Nota de Crédito</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $creditNote->dte->tipoDte }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-6"><strong>Estado Hacienda:</strong></div>
                                            <div class="col-6">
                                                @if($creditNote->dte->estadoHacienda == 'PROCESADO')
                                                    <span class="badge bg-success">Procesado</span>
                                                @elseif($creditNote->dte->estadoHacienda == 'RECHAZADO')
                                                    <span class="badge bg-danger">Rechazado</span>
                                                @elseif($creditNote->dte->estadoHacienda == 'PENDIENTE')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $creditNote->dte->estadoHacienda ?? 'N/A' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-6"><strong>Fecha DTE:</strong></div>
                                            <div class="col-6">
                                                {{ $creditNote->dte->fhRecibido ? \Carbon\Carbon::parse($creditNote->dte->fhRecibido)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-6"><strong>Ambiente:</strong></div>
                                            <div class="col-6">
                                                @php $amb = $creditNote->dte->ambiente_id ?? null; @endphp
                                                @if($amb === 1 || $amb === '1' || $amb === '00')
                                                    <span class="badge bg-info">Producción</span>
                                                @elseif($amb === 2 || $amb === '2' || $amb === '01')
                                                    <span class="badge bg-warning">Pruebas</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($creditNote->dte->selloRecibido)
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <div class="row">
                                                <div class="col-2"><strong>Autorización:</strong></div>
                                                <div class="col-10">
                                                    <small class="text-muted font-monospace">{{ $creditNote->dte->selloRecibido }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Motivo -->
            @if($creditNote->motivo)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-primary">Motivo de la Nota de Crédito</h6>
                                <p class="mb-0">{{ $creditNote->motivo }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Productos -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-package me-2"></i>
                                Productos de la Nota de Crédito
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                            <th>IVA</th>
                                            <th>Total</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($creditNote->details as $detalle)
                                            <tr>
                                                <td>{{ $detalle->product->code ?? 'N/A' }}</td>
                                                <td>{{ ($detalle->ruta && trim($detalle->ruta) !== '' && $detalle->product && $detalle->product->code == 'LAB') ? $detalle->ruta : ($detalle->product->name ?? 'N/A') }}</td>
                                                <td class="text-center">{{ number_format($detalle->amountp, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->priceunit, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->detained13, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale + $detalle->detained13, 2) }}</td>
                                                <td class="text-center">
                                                    @if($detalle->exempt > 0)
                                                        <span class="badge bg-warning">Exento</span>
                                                    @elseif($detalle->nosujeta > 0)
                                                        <span class="badge bg-info">No Sujeta</span>
                                                    @else
                                                        <span class="badge bg-success">Gravado</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">Subtotal Gravado:</th>
                                            <th class="text-end">${{ number_format($creditNote->details->sum('pricesale'), 2) }}</th>
                                            <th class="text-end">${{ number_format($creditNote->details->sum('detained13'), 2) }}</th>
                                            <th class="text-end">${{ number_format($creditNote->totalamount, 2) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
