@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/forms-debit-notes.js') }}"></script>
@endsection

@section('title', 'Nueva Nota de Débito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-file-plus me-2"></i>
                Nueva Nota de Débito
            </h5>
        </div>

        <form id="debitNoteForm" action="{{ $sale ? route('debit-notes.store', $sale->id) : route('debit-notes.store', ['sale_id' => '__SALE_ID__']) }}" method="POST">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id ?? '' }}">
            <input type="hidden" id="redirectToSales" value="{{ route('sale.index') }}">

            <div class="card-body">
                <!-- Información de la venta original -->
                @if($sale)
                    <div class="mb-4 card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-file-text me-2"></i>
                                Información de la Factura Original
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Información básica de la venta -->
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-primary">Datos de la Venta</h6>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Cliente:</strong></div>
                                        <div class="col-7">
                                            {{ $sale->client->tpersona == 'N' ? $sale->client->firstname . ' ' . $sale->client->firstlastname : $sale->client->nameClient }}
                                        </div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>NIT Cliente:</strong></div>
                                        <div class="col-7">{{ $sale->client->nit ?? 'N/A' }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Fecha:</strong></div>
                                        <div class="col-7">{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Total:</strong></div>
                                        <div class="col-7 text-success fw-bold">${{ number_format($sale->totalamount, 2) }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Estado:</strong></div>
                                        <div class="col-7">
                                            @if($sale->state == 1)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-danger">Inactiva</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del DTE -->
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-primary">Datos del DTE</h6>
                                    @if($sale->dte)
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Número DTE:</strong></div>
                                            <div class="col-7">{{ $sale->dte->id_doc ?? 'N/A' }}</div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Tipo DTE:</strong></div>
                                            <div class="col-7">
                                                @if($sale->dte->tipoDte == '01')
                                                    <span class="badge bg-info">01 - Factura</span>
                                                @elseif($sale->dte->tipoDte == '03')
                                                    <span class="badge bg-warning">03 - Crédito Fiscal</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $sale->dte->tipoDte }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Estado Hacienda:</strong></div>
                                            <div class="col-7">
                                                @if($sale->dte->estadoHacienda == 'PROCESADO')
                                                    <span class="badge bg-success">Procesado</span>
                                                @elseif($sale->dte->estadoHacienda == 'RECHAZADO')
                                                    <span class="badge bg-danger">Rechazado</span>
                                                @else
                                                    <span class="badge bg-warning">{{ $sale->dte->estadoHacienda ?? 'Pendiente' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="ti ti-alert-triangle me-2"></i>
                                            Esta venta no tiene DTE asociado
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Notas de Débito -->
                    @if($historialNDB->count() > 0)
                        <div class="mb-4 card">
                            <div class="card-header">
                                <h6 class="mb-0 card-title">
                                    <i class="ti ti-history me-2"></i>
                                    Historial de Notas de Débito
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($historialNDB as $ndb)
                                                <tr>
                                                    <td>{{ $ndb->id }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($ndb->date)->format('d/m/Y') }}</td>
                                                    <td>${{ number_format($ndb->totalamount, 2) }}</td>
                                                    <td>
                                                        @if($ndb->state == 1)
                                                            <span class="badge bg-success">Activa</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactiva</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('debit-notes.show', $ndb->id) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Configuración de la Nota de Débito -->
                <div class="mb-4 card">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-settings me-2"></i>
                            Configuración de la Nota de Débito
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Empresa <span class="text-danger">*</span></label>
                                    <select name="company_id" class="form-select" required>
                                        <option value="">Seleccionar empresa</option>
                                        @foreach($empresas as $empresa)
                                            <option value="{{ $empresa->id }}"
                                                {{ ($empresaSeleccionada == $empresa->id) ? 'selected' : '' }}>
                                                {{ $empresa->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                                    <select name="typedocument_id" class="form-select" required>
                                        <option value="">Seleccionar tipo</option>
                                        @foreach($tiposDocumento as $tipo)
                                            <option value="{{ $tipo->id }}"
                                                {{ ($tipoDocumentoSeleccionado == $tipo->id) ? 'selected' : '' }}>
                                                {{ $tipo->description }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo de la Nota de Débito <span class="text-danger">*</span></label>
                            <textarea name="motivo" class="form-control" rows="3"
                                placeholder="Describa el motivo de la nota de débito..." required>{{ old('motivo') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Productos para la Nota de Débito -->
                <div class="mb-4 card">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-package me-2"></i>
                            Productos para la Nota de Débito
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($sale && $sale->details->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">Incluir</th>
                                            <th width="10%">Código</th>
                                            <th width="25%">Producto</th>
                                            <th width="10%">Cant. Original</th>
                                            <th width="10%">Precio Unit.</th>
                                            <th width="10%">Cantidad</th>
                                            <th width="10%">Precio</th>
                                            <th width="10%">IVA</th>
                                            <th width="10%">Tipo Venta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sale->details as $index => $detalle)
                                            @php
                                                $precioUnitario = $detalle->amountp > 0 ? $detalle->pricesale / $detalle->amountp : $detalle->priceunit;
                                            @endphp
                                            <tr data-original-cant="{{ $detalle->amountp }}" data-original-price="{{ number_format($precioUnitario, 6) }}">
                                                <td>
                                                    <input type="checkbox" name="productos[{{ $index }}][incluir]" class="form-check-input product-checkbox" value="1">
                                                </td>
                                                <td>{{ $detalle->product->code }}</td>
                                                <td>{{ ($detalle->ruta && trim($detalle->ruta) !== '' && $detalle->product && $detalle->product->code == 'LAB') ? $detalle->ruta : ($detalle->product->name ?? 'N/A') }}</td>
                                                <td>{{ $detalle->amountp }}</td>
                                                <td>${{ number_format($precioUnitario, 2) }}</td>
                                                <td>
                                                    <input type="number" name="productos[{{ $index }}][cantidad]"
                                                           class="form-control form-control-sm"
                                                           min="0" max="{{ $detalle->amountp }}"
                                                           step="1" value="0">
                                                </td>
                                                <td>
                                                    <input type="number" name="productos[{{ $index }}][precio]"
                                                           class="form-control form-control-sm"
                                                           min="0" step="0.000001" value="{{ number_format($precioUnitario, 6) }}" readonly>
                                                </td>
                                                <td class="text-end">
                                                    <span class="iva-display">${{ number_format($detalle->detained13, 2) }}</span>
                                                </td>
                                                <td>
                                                    <select name="productos[{{ $index }}][tipo_venta]" class="form-select form-select-sm">
                                                        <option value="gravada" {{ $detalle->exempt == 0 && $detalle->nosujeta == 0 ? 'selected' : '' }}>Gravada</option>
                                                        <option value="exenta" {{ $detalle->exempt > 0 ? 'selected' : '' }}>Exenta</option>
                                                        <option value="nosujeta" {{ $detalle->nosujeta > 0 ? 'selected' : '' }}>No Sujeta</option>
                                                    </select>
                                                </td>
                                                <input type="hidden" name="productos[{{ $index }}][product_id]" value="{{ $detalle->product->id }}">
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                No hay productos disponibles para crear la nota de débito.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Totales -->
                <div class="mb-4 card">
                    <div class="card-header">
                        <h6 class="mb-0 card-title">
                            <i class="ti ti-calculator me-2"></i>
                            Totales de la Nota de Débito
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Subtotal Gravado</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="subtotalGravado" class="form-control" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">IVA (13%)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="iva" class="form-control" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Subtotal Exento</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="subtotalExento" class="form-control" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Subtotal No Sujeto</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="subtotalNoSujeto" class="form-control" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Total de la Nota de Débito</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="total" class="form-control fw-bold text-primary" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('debit-notes.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        Crear Nota de Débito
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
