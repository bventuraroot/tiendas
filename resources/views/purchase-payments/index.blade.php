@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script>
        // Función para abrir el modal de pago
        function payPurchase(idpurchase) {
            // Codificar el ID en base64
            const encodedId = btoa(idpurchase);
            
            // Obtener información del saldo pendiente
            fetch(`/purchase-payment/balance/${encodedId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pendingamount').textContent = data.balance;
                        document.getElementById('idpurchase').value = idpurchase;
                        document.getElementById('amountpay').value = '';
                        document.getElementById('amountpay').max = data.balance;
                        document.getElementById('notes').value = '';
                        document.getElementById('savepay').disabled = true;
                        
                        // Abrir modal
                        const modal = new bootstrap.Modal(document.getElementById('PayPurchaseModal'));
                        modal.show();
                    } else {
                        Swal.fire('Error', 'No se pudo obtener la información de la compra', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al obtener información de la compra', 'error');
                });
        }

        // Validar monto antes de habilitar el botón de guardar
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amountpay');
            const saveButton = document.getElementById('savepay');
            
            if (amountInput && saveButton) {
                amountInput.addEventListener('input', function() {
                    const amount = parseFloat(this.value) || 0;
                    const max = parseFloat(this.max) || 0;
                    
                    if (amount > 0 && amount <= max) {
                        saveButton.disabled = false;
                    } else {
                        saveButton.disabled = true;
                    }
                });
            }
        });

        // Función para ver historial de pagos
        function viewPaymentHistory(idpurchase) {
            const encodedId = btoa(idpurchase);
            
            fetch(`/purchase-payment/history/${encodedId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let historyHtml = `
                            <div class="mb-3">
                                <h5>Compra #${data.purchase.number}</h5>
                                <p><strong>Proveedor:</strong> ${data.purchase.provider_name || 'N/A'}</p>
                                <p><strong>Total:</strong> $${parseFloat(data.total).toFixed(2)}</p>
                                <p><strong>Pagado:</strong> $${parseFloat(data.total_paid).toFixed(2)}</p>
                                <p><strong>Saldo Pendiente:</strong> $${parseFloat(data.balance).toFixed(2)}</p>
                            </div>
                            <hr>
                            <h6>Historial de Pagos:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Saldo Restante</th>
                                            <th>Usuario</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        if (data.payments && data.payments.length > 0) {
                            data.payments.forEach(payment => {
                                historyHtml += `
                                    <tr>
                                        <td>${new Date(payment.date_pay).toLocaleDateString('es-ES')}</td>
                                        <td>$${parseFloat(payment.amountpay).toFixed(2)}</td>
                                        <td>$${parseFloat(payment.current).toFixed(2)}</td>
                                        <td>${payment.user ? payment.user.name : 'N/A'}</td>
                                        <td>${payment.notes || '-'}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            historyHtml += '<tr><td colspan="5" class="text-center">No hay pagos registrados</td></tr>';
                        }
                        
                        historyHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        
                        Swal.fire({
                            title: 'Historial de Pagos',
                            html: historyHtml,
                            width: '800px',
                            confirmButtonText: 'Cerrar'
                        });
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo obtener el historial', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al obtener el historial de pagos', 'error');
                });
        }
    </script>
@endsection

@section('title', 'Cuentas por Pagar')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">Cuentas por Pagar - Compras</h5>
                <div class="gap-2 d-flex">
                    <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-arrow-left"></i> Volver a Compras
                    </a>
                </div>
            </div>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-products border-top">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>NÚMERO</th>
                        <th>FECHA COMPRA</th>
                        <th>FECHA ÚLTIMO PAGO</th>
                        <th>PROVEEDOR</th>
                        <th>NIT</th>
                        <th>EMPRESA</th>
                        <th>ESTADO</th>
                        <th>MONTO TOTAL</th>
                        <th>MONTO PAGADO</th>
                        <th>SALDO PENDIENTE</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($purchases)
                        @forelse($purchases as $purchase)
                            <tr>
                                <td></td>
                                <td>{{ $purchase->number }}</td>
                                <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</td>
                                <td>{{ $purchase->last_payment_date ? \Carbon\Carbon::parse($purchase->last_payment_date)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $purchase->provider_name }}</td>
                                <td>{{ $purchase->provider_nit ?? 'N/A' }}</td>
                                <td>{{ $purchase->company_name }}</td>
                                <td>
                                    @if($purchase->payment_status_display == 'PAGADO')
                                        <span class="badge bg-success">{{ $purchase->payment_status_display }}</span>
                                    @elseif($purchase->payment_status_display == 'PARCIAL')
                                        <span class="badge bg-warning">{{ $purchase->payment_status_display }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $purchase->payment_status_display }}</span>
                                    @endif
                                </td>
                                <td>$ {{ number_format($purchase->total, 2, '.', ',') }}</td>
                                <td>$ {{ number_format($purchase->paid_amount ?? 0, 2, '.', ',') }}</td>
                                <td>$ {{ number_format($purchase->current_balance, 2, '.', ',') }}</td>
                                @if($purchase->payment_status_display == "PAGADO")
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="viewPaymentHistory({{ $purchase->idpurchase }})">
                                                <i class="ti ti-history ti-sm me-1"></i>Historial
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="payPurchase({{ $purchase->idpurchase }})">
                                                <i class="ti ti-credit-card ti-sm me-1"></i>Abonar
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="viewPaymentHistory({{ $purchase->idpurchase }})">
                                                <i class="ti ti-history ti-sm me-1"></i>Historial
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No hay compras registradas</td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
    </div>

    <!-- Modal para realizar pago -->
    <div class="modal fade" id="PayPurchaseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="p-3 modal-content p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="mb-4 text-center">
                        <h3 class="mb-2">Registrar Pago a Proveedor</h3>
                        <h4 class="mb-1 bg-label-danger">Saldo Pendiente: $<span id="pendingamount"></span></h4>
                    </div>
                    <form id="payPurchaseForm" class="row" action="{{ route('purchase-payment.add-payment') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('POST')
                        <input type="hidden" name="idpurchase" id="idpurchase">
                        <div class="mb-3 col-12">
                            <label class="form-label" for="amountpay">Monto a Pagar <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" value="" id="amountpay" name="amountpay" class="form-control" autofocus required/>
                            <small class="text-muted">El monto no puede exceder el saldo pendiente</small>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" for="notes">Notas (Opcional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Agregar notas adicionales sobre el pago..."></textarea>
                        </div>
                        <div class="text-center col-12 demo-vertical-spacing">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1" disabled id="savepay">
                                <i class="ti ti-check me-1"></i>Registrar Pago
                            </button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}'
            });
        </script>
    @endif
@endsection

