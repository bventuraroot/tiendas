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

@section('title', 'Editar Nota de Débito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-edit me-2"></i>
                Editar Nota de Débito #{{ $debitNote->id }}
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('debit-notes.show', $debitNote) }}" class="btn btn-outline-secondary me-2">
                    <i class="ti ti-eye me-1"></i>
                    Ver Detalles
                </a>
                <a href="{{ route('debit-notes.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-arrow-left me-1"></i>
                    Volver a la Lista
                </a>
            </div>
        </div>

        <form id="editDebitNoteForm" action="{{ route('debit-notes.update', $debitNote) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
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
                                                {{ ($debitNote->company_id == $empresa->id) ? 'selected' : '' }}>
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
                                                {{ ($debitNote->typedocument_id == $tipo->id) ? 'selected' : '' }}>
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
                                placeholder="Describa el motivo de la nota de débito..." required>{{ old('motivo', $debitNote->motivo) }}</textarea>
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
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">Código</th>
                                        <th width="25%">Producto</th>
                                        <th width="10%">Cantidad</th>
                                        <th width="10%">Precio</th>
                                        <th width="10%">Subtotal</th>
                                        <th width="10%">IVA</th>
                                        <th width="10%">Total</th>
                                        <th width="15%">Tipo Venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debitNote->details as $index => $detail)
                                        <tr>
                                            <td>{{ $detail->product->code ?? 'N/A' }}</td>
                                            <td>{{ ($detail->ruta && trim($detail->ruta) !== '' && $detail->product && $detail->product->code == 'LAB') ? $detail->ruta : ($detail->product->name ?? 'N/A') }}</td>
                                            <td>
                                                <input type="number" name="productos[{{ $index }}][cantidad]"
                                                       class="form-control form-control-sm"
                                                       min="0.01" step="0.01"
                                                       value="{{ old('productos.'.$index.'.cantidad', $detail->amountp) }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="productos[{{ $index }}][precio]"
                                                       class="form-control form-control-sm"
                                                       min="0" step="0.000001"
                                                       value="{{ old('productos.'.$index.'.precio', $detail->priceunit) }}" required>
                                            </td>
                                            <td class="text-end">
                                                <span class="subtotal-display">${{ number_format($detail->pricesale, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="iva-display">${{ number_format($detail->detained13, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="total-display">${{ number_format($detail->pricesale + $detail->detained13, 2) }}</span>
                                            </td>
                                            <td>
                                                <select name="productos[{{ $index }}][tipo_venta]" class="form-select form-select-sm">
                                                    <option value="gravada" {{ $detail->exempt == 0 && $detail->nosujeta == 0 ? 'selected' : '' }}>Gravada</option>
                                                    <option value="exenta" {{ $detail->exempt > 0 ? 'selected' : '' }}>Exenta</option>
                                                    <option value="nosujeta" {{ $detail->nosujeta > 0 ? 'selected' : '' }}>No Sujeta</option>
                                                </select>
                                            </td>
                                            <input type="hidden" name="productos[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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
                                        <input type="text" id="totalGeneral" class="form-control fw-bold text-primary" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('debit-notes.show', $debitNote) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        Actualizar Nota de Débito
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Calcular totales iniciales
    calculateTotals();

    // Recalcular cuando cambien los valores
    $('input[name*="[cantidad]"], input[name*="[precio]"], select[name*="[tipo_venta]"]').on('input change', function() {
        calculateTotals();
    });
});

function calculateTotals() {
    let subtotalGravado = 0;
    let subtotalExento = 0;
    let subtotalNoSujeto = 0;
    let iva = 0;

    $('tbody tr').each(function() {
        const row = $(this);
        const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
        const precio = parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
        const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
        const subtotal = cantidad * precio;

        // Actualizar displays de la fila
        row.find('.subtotal-display').text('$' + subtotal.toFixed(2));

        if (tipoVenta === 'gravada') {
            const ivaRow = subtotal * 0.13;
            row.find('.iva-display').text('$' + ivaRow.toFixed(2));
            row.find('.total-display').text('$' + (subtotal + ivaRow).toFixed(2));

            subtotalGravado += subtotal;
            iva += ivaRow;
        } else if (tipoVenta === 'exenta') {
            row.find('.iva-display').text('$0.00');
            row.find('.total-display').text('$' + subtotal.toFixed(2));
            subtotalExento += subtotal;
        } else if (tipoVenta === 'nosujeta') {
            row.find('.iva-display').text('$0.00');
            row.find('.total-display').text('$' + subtotal.toFixed(2));
            subtotalNoSujeto += subtotal;
        }
    });

    const total = subtotalGravado + subtotalExento + subtotalNoSujeto + iva;

    $('#subtotalGravado').val(subtotalGravado.toFixed(2));
    $('#iva').val(iva.toFixed(2));
    $('#subtotalExento').val(subtotalExento.toFixed(2));
    $('#subtotalNoSujeto').val(subtotalNoSujeto.toFixed(2));
    $('#totalGeneral').val(total.toFixed(2));
}
</script>
@endpush
