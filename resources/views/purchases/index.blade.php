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
    <script src="{{ asset('assets/js/app-purchase-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-purchase.js') }}"></script>
    <script>
        function payPurchase(idpurchase) {
            const encodedId = btoa(idpurchase);
            fetch(`/purchase-payment/balance/${encodedId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pendingamount').textContent = data.balance;
                        document.getElementById('idpurchase').value = idpurchase;
                        const amountInput = document.getElementById('amountpay');
                        const saveButton = document.getElementById('savepay');
                        if (amountInput) {
                            amountInput.value = '';
                            amountInput.max = data.balance;
                        }
                        if (saveButton) {
                            saveButton.disabled = true;
                        }
                        document.getElementById('notes').value = '';
                        const modal = new bootstrap.Modal(document.getElementById('PayPurchaseModal'));
                        modal.show();
                    } else {
                        Swal.fire('Error', 'No se pudo obtener la información de la compra', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Error al obtener información de la compra', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amountpay');
            const saveButton = document.getElementById('savepay');
            if (amountInput && saveButton) {
                amountInput.addEventListener('input', function() {
                    const amount = parseFloat(this.value) || 0;
                    const max = parseFloat(this.max) || 0;
                    saveButton.disabled = !(amount > 0 && amount <= max);
                });
            }
        });

        function viewPaymentHistory(idpurchase) {
            const encodedId = btoa(idpurchase);
            fetch(`/purchase-payment/history/${encodedId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire('Error', data.message || 'No se pudo obtener el historial', 'error');
                        return;
                    }
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
                                <tbody>`;
                    if (data.payments && data.payments.length > 0) {
                        data.payments.forEach(payment => {
                            historyHtml += `
                                <tr>
                                    <td>${new Date(payment.date_pay).toLocaleDateString('es-ES')}</td>
                                    <td>$${parseFloat(payment.amountpay).toFixed(2)}</td>
                                    <td>$${parseFloat(payment.current).toFixed(2)}</td>
                                    <td>${payment.user ? payment.user.name : 'N/A'}</td>
                                    <td>${payment.notes || '-'}</td>
                                </tr>`;
                        });
                    } else {
                        historyHtml += '<tr><td colspan="5" class="text-center">No hay pagos registrados</td></tr>';
                    }
                    historyHtml += `
                                </tbody>
                            </table>
                        </div>`;
                    Swal.fire({
                        title: 'Historial de Pagos',
                        html: historyHtml,
                        width: '800px',
                        confirmButtonText: 'Cerrar'
                    });
                })
                .catch(() => {
                    Swal.fire('Error', 'Error al obtener el historial de pagos', 'error');
                });
        }
    </script>
@endsection



@section('title', 'Compras')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">Compras</h5>
                <div class="gap-2 d-flex">
                    <button type="button" class="btn btn-warning btn-sm" onclick="window.location.href='{{ route('purchase.expiring-products-view') }}'">
                        <i class="ti ti-alert-triangle"></i> Dashboard Productos Vencidos
                    </button>
                </div>
            </div>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-purchase border-top nowrap">
                <thead>
                    <tr>
                        <th>ACCIONES</th>
                        <th>NUMERO</th>
                        <th>TIPO DOC</th>
                        <th>FECHA</th>
                        <th>EXENTA</th>
                        <th>GRAVADA</th>
                        <th>IVA</th>
                        <th>OTROS</th>
                        <th>TOTAL</th>
                        <th>PROVEEDOR</th>
                        <th>ESTADO PAGO</th>
                        <th>SALDO</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($purchases) && $purchases->count() > 0)
                        @foreach($purchases as $purchase)
                            <tr>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                                        <button type="button" class="btn btn-icon btn-outline-primary"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalle"
                                            onclick="viewPurchaseDetails({{ $purchase->idpurchase }});">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-outline-secondary"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Editar compra"
                                            onclick="editpurchase({{ $purchase->idpurchase }});">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-outline-info"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Historial de pagos"
                                            onclick="viewPaymentHistory({{ $purchase->idpurchase }});">
                                            <i class="ti ti-history"></i>
                                        </button>
                                        @if(isset($purchase->payment_status_display) && $purchase->payment_status_display != 'PAGADO')
                                            <button type="button" class="btn btn-icon btn-outline-success"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Registrar pago"
                                                onclick="payPurchase({{ $purchase->idpurchase }});">
                                                <i class="ti ti-credit-card"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-icon btn-outline-danger"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar"
                                            onclick="deletepurchase({{ $purchase->idpurchase }});">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>{{ $purchase->number }}</td>
                                <td>{{ $purchase->namedoc }}</td>
                                <td>{{ date('d-M-Y', strtotime($purchase->date)) }}</td>
                                <td>{{ ($purchase->exenta=="" ? "0.00" : $purchase->exenta) }}</td>
                                <td>{{ ($purchase->gravada=="" ? "0.00" : $purchase->gravada)  }}</td>
                                <td>{{ ($purchase->iva=="" ? "0.00" : $purchase->iva)  }}</td>
                                <td>{{ ($purchase->otros=="" ? "0.00" : $purchase->otros)  }}</td>
                                <td>{{ ($purchase->total=="" ? "0.00" : $purchase->total)  }}</td>
                                <td>{{ $purchase->name_provider }}</td>
                                <td>
                                    @if(isset($purchase->payment_status_display))
                                        @if($purchase->payment_status_display == 'PAGADO')
                                            <span class="badge bg-success">{{ $purchase->payment_status_display }}</span>
                                        @elseif($purchase->payment_status_display == 'PARCIAL')
                                            <span class="badge bg-warning">{{ $purchase->payment_status_display }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $purchase->payment_status_display }}</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($purchase->current_balance))
                                        $ {{ number_format($purchase->current_balance, 2, '.', ',') }}
                                    @else
                                        $ {{ number_format($purchase->total ?? 0, 2, '.', ',') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                </table>
            </div>
 <!-- Add product Modal -->
 <div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-simple modal-pricing">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Ingresar compra</h3>
          </div>
          <form id="addpurchaseForm" class="row" action="{{Route('purchase.store')}}" method="POST" enctype="multipart/form-data">
            @csrf @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">

            <!-- Información General -->
            <div class="mb-3 col-4">
              <label class="form-label" for="number">Numero</label>
              <input type="text" id="number" name="number" class="form-control" placeholder="Numero de comprobante" autofocus required/>
            </div>
            <div class="mb-3 col-4">
                <label for="period" class="form-label">Periodo</label>
                <select class="select2purchase form-select" id="period" name="period"
                    aria-label="Seleccionar opcion">
                    <?php           $mes = date('m');
									$meses = date('m');
									for ($i = 1; $i <= $meses; $i++) {
										setlocale(LC_TIME, 'spanish');
										$fecha = DateTime::createFromFormat('!m', $i);
										$nmes = strftime("%B", $fecha->getTimestamp());
										?>
											<option <?php if($mes == $i){ echo "selected"; } ?> value="<?php if($i < 10){ echo "0".$i; }else{ echo $i; } ?>">
												<?php echo ucfirst($nmes); ?>
											</option>
										<?php
									}
								?>
                </select>
            </div>
            <div class="mb-3 col-4">
                <label for="company" class="form-label">Empresa</label>
                <select class="select2company form-select" id="company" name="company"
                    aria-label="Seleccionar opcion">
                </select>
            </div>
            <div class="mb-3 col-4">
                <label for="document" class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                <select class="select2document form-select" id="document" name="document" required
                    aria-label="Seleccionar opcion">
                    <option value="" selected>Elije una opcion</option>
                    <option value="6">FACTURA</option>
                    <option value="3">COMPROBANTE DE CREDITO FISCAL</option>
                    <option value="9">NOTA DE CREDITO</option>
                </select>
            </div>
            <div class="mb-3 col-4">
                <label for="date" class="form-label">Fecha de Comprobante</label>
                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" />
            </div>
            <div class="mb-3 col-4">
                <label for="provider" class="form-label">Proveedor</label>
                <select class="select2provider form-select" id="provider" name="provider"
                    aria-label="Seleccionar opcion">
                </select>
            </div>

            <div class="mb-3 col-4">
                <label for="payment_type" class="form-label">Condición de pago</label>
                <select class="form-select" id="payment_type" name="payment_type">
                    <option value="contado" selected>Contado</option>
                    <option value="credito">Crédito</option>
                </select>
            </div>
            <div class="mb-3 col-4" id="credit_days_container" style="display: none;">
                <label for="credit_days" class="form-label">Días de crédito</label>
                <select class="form-select" id="credit_days" name="credit_days">
                    <option value="">Seleccione</option>
                    <option value="15">15 días</option>
                    <option value="30">30 días</option>
                    <option value="60">60 días</option>
                </select>
            </div>
            <div class="mb-3 col-4">
                <label for="payment_due_date" class="form-label">Fecha estimada de pago</label>
                <input type="date" class="form-control" id="payment_due_date" name="payment_due_date" value="{{ date('Y-m-d') }}">
                <small class="text-muted">Se calcula automáticamente según la condición de pago, pero puedes editarla.</small>
            </div>

            <!-- Sección de Productos -->
            <div class="mb-4 col-12">
                <h5>Productos de la Compra</h5>
                <div class="mb-3 alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    <strong>Instrucciones:</strong> Selecciona productos y registra el <strong>costo de compra</strong> (no el precio de venta).
                    El sistema calculará automáticamente tu utilidad comparando con el precio de venta registrado en el catálogo.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="productsTable">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Unidad</th>
                                <th>Cantidad</th>
                                <th style="width: 150px;">Costo Unitario</th>
                                <th>Subtotal</th>
                                <th>Fecha Caducidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Los productos se agregarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="addProductBtn">
                    <i class="ti ti-plus"></i> Agregar Producto
                </button>
            </div>

            <!-- Campos para compatibilidad -->
            <div class="mb-3 col-4">
                <label class="form-label" for="exenta">Exenta</label>
                <input type="number" step="0.00001" min="0.00000" id="exenta" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="Exenta $" name="exenta" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="gravada">Gravada</label>
                <input type="number" step="0.00001" id="gravada" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="Gravada $" name="gravada" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="iva">IVA</label>
                <input type="number" step="0.00001" id="iva" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="IVA $" name="iva" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="contrans">Contrans</label>
                <input type="number" step="0.00001" id="contrans" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="Contrans $" name="contrans" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="fovial">FOVIAL</label>
                <input type="number" step="0.00001" id="fovial" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="FOVIAL $" name="fovial" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="cesc">CESC</label>
                <input type="number" step="0.00001" id="cesc" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="CESC $" name="cesc" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="iretenido">IVA Retenido</label>
                <input type="number" step="0.00001" id="iretenido" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="IVA Retenido $" name="iretenido" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="others">Otros</label>
                <input type="number" step="0.00001" id="others" value="0.00000" class="form-control" onchange="suma()" placeholder="$" aria-label="Otros $" name="others" />
            </div>
            <div class="mb-3 col-4">
                <label class="form-label" for="total">Total</label>
                <input type="number" step="0.00001" id="total" class="form-control" onchange="suma()" placeholder="$" aria-label="Total $" name="total" />
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="button" class="btn btn-info me-sm-3 me-1" onclick="calculateTotalsFromProducts()">
                <i class="ti ti-calculator"></i> Calcular Totales
              </button>
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Agregar Compra</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

             <!-- Add update Modal -->
             <div class="modal fade" id="updatePurchaseModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-simple modal-pricing">
                  <div class="p-3 modal-content p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                      <div class="mb-4 text-center">
                        <h3 class="mb-2">Editar compra</h3>
                      </div>
                      <form id="updatepurchaseForm" class="row" action="{{Route('purchase.update')}}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        <input type="hidden" name="iduseredit" id="iduseredit" value="{{Auth::user()->id}}">
                        <input type="hidden" name="idedit" id="idedit">
                        <div class="mb-3 col-4">
                          <label class="form-label" for="numberedit">Numero</label>
                          <input type="text" id="numberedit" name="numberedit" class="form-control" placeholder="Numero de comprobante" autofocus required/>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="periodedit" class="form-label">Periodo</label>
                            <select class="select2purchaseedit form-select" id="periodedit" name="periodedit"
                                aria-label="Seleccionar opcion">
                                <?php           $mes = date('m');
                                                $meses = date('m');
                                                for ($i = 1; $i <= $meses; $i++) {
                                                    setlocale(LC_TIME, 'spanish');
                                                    $fecha = DateTime::createFromFormat('!m', $i);
                                                    $nmes = strftime("%B", $fecha->getTimestamp());
                                                    ?>
                                                        <option <?php if($mes == $i){ echo "selected"; } ?> value="<?php if($i < 10){ echo "0".$i; }else{ echo $i; } ?>">
                                                            <?php echo ucfirst($nmes); ?>
                                                        </option>
                                                    <?php
                                                }
                                            ?>
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="companyedit" class="form-label">Empresa</label>
                            <select class="select2companyedit form-select" id="companyedit" name="companyedit"
                                aria-label="Seleccionar opcion">
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="documentedit" class="form-label">Tipo Documento</label>
                            <select class="select2documentedit form-select" id="documentedit" name="documentedit"
                                aria-label="Seleccionar opcion">
                                <option selected>Elije una opcion</option>
                                <option value="6">FACTURA</option>
                                <option value="3">COMPROBANTE DE CREDITO FISCAL</option>
                                <option value="9">NOTA DE CREDITO</option>
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="dateedit" class="form-label">Fecha de Comprobante</label>
                            <input type="text" class="form-control" placeholder="DD-MM-YYYY" id="dateedit" name="dateedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label for="provideredit" class="form-label">Proveedor</label>
                            <select class="select2provideredit form-select" id="provideredit" name="provideredit"
                                aria-label="Seleccionar opcion">
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="payment_typeedit" class="form-label">Condición de pago</label>
                            <select class="form-select" id="payment_typeedit" name="payment_typeedit">
                                <option value="contado">Contado</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>
                        <div class="mb-3 col-4" id="credit_days_container_edit" style="display: none;">
                            <label for="credit_daysedit" class="form-label">Días de crédito</label>
                            <select class="form-select" id="credit_daysedit" name="credit_daysedit">
                                <option value="">Seleccione</option>
                                <option value="15">15 días</option>
                                <option value="30">30 días</option>
                                <option value="60">60 días</option>
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="payment_due_dateedit" class="form-label">Fecha estimada de pago</label>
                            <input type="date" class="form-control" id="payment_due_dateedit" name="payment_due_dateedit">
                            <small class="text-muted">Puedes ajustar la fecha estimada manualmente.</small>
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="exentaedit">Exenta</label>
                            <input type="number" step="0.00001" min="0.00000" id="exentaedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="Exenta $" name="exentaedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="gravadaedit">Gravada</label>
                            <input type="number" step="0.00001" id="gravadaedit" value="0.00000" class="form-control" onkeyup="calculaivaedit(this.value)" placeholder="$" aria-label="Gravada $" name="gravadaedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="ivaedit">IVA</label>
                            <input type="number" step="0.00001" id="ivaedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="IVA $" name="ivaedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="contransedit">Contrans</label>
                            <input type="number" step="0.00001" id="contransedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="Contrans $" name="contransedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="fovialedit">FOVIAL</label>
                            <input type="number" step="0.00001" id="fovialedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="FOVIAL $" name="fovialedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="cescedit">CESC</label>
                            <input type="number" step="0.00001" id="cescedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="CESC $" name="cescedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="iretenidoedit">IVA Retenido</label>
                            <input type="number" step="0.00001" id="iretenidoedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="IVA Retenido $" name="iretenidoedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="othersedit">Otros</label>
                            <input type="number" step="0.00001" id="othersedit" value="0.00000" class="form-control" onkeyup="sumaedit()" placeholder="$" aria-label="Otros $" name="othersedit" />
                        </div>
                        <div class="mb-3 col-4">
                            <label class="form-label" for="totaledit">Total</label>
                            <input type="number" step="0.00001" id="totaledit" class="form-control" placeholder="$" aria-label="Total $" name="totaledit" />
                        </div>

                        <!-- Sección de Productos para Edición -->
                        <div class="mb-4 col-12">
                            <h5>Productos de la Compra</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="editProductsTable">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Unidad</th>
                                            <th>Cantidad</th>
                                            <th>Costo Unitario</th>
                                            <th>Subtotal</th>
                                            <th>Fecha Caducidad</th>
                                            <th>Lote</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="editProductsTableBody">
                                        <!-- Los productos se agregarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="addEditProductBtn">
                                <i class="ti ti-plus"></i> Agregar Producto
                            </button>
                        </div>

                        <div class="text-center col-12 demo-vertical-spacing">
                          <button type="button" class="btn btn-info me-sm-3 me-1" onclick="calculateEditTotalsFromProducts()">
                            <i class="ti ti-calculator"></i> Calcular Totales
                          </button>
                          <button type="submit" class="btn btn-primary me-sm-3 me-1">Actualizar</button>
                          <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

    <!-- Modal para seleccionar producto -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="productSearch" placeholder="Buscar producto...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="productSelectionTable">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Proveedor</th>
                                    <th>Precio</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Productos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de compra -->
    <div class="modal fade" id="viewPurchaseModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Información general -->
                    <div class="mb-4 row">
                        <div class="col-md-6">
                            <h6>Información General</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Número:</strong></td>
                                    <td id="viewNumber"></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td id="viewDate"></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo Documento:</strong></td>
                                    <td id="viewDocumentType"></td>
                                </tr>
                                <tr>
                                    <td><strong>Proveedor:</strong></td>
                                    <td id="viewProvider"></td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td id="viewCompany"></td>
                                </tr>
                                <tr>
                                    <td><strong>Condición de pago:</strong></td>
                                    <td id="viewPaymentType"></td>
                                </tr>
                                <tr id="viewCreditDaysRow" style="display: none;">
                                    <td><strong>Días de crédito:</strong></td>
                                    <td id="viewCreditDays"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Totales</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Exenta:</strong></td>
                                    <td id="viewExenta"></td>
                                </tr>
                                <tr>
                                    <td><strong>Gravada:</strong></td>
                                    <td id="viewGravada"></td>
                                </tr>
                                <tr>
                                    <td><strong>IVA:</strong></td>
                                    <td id="viewIva"></td>
                                </tr>
                                <tr>
                                    <td><strong>IVA Retenido:</strong></td>
                                    <td id="viewIretenido"></td>
                                </tr>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td id="viewTotal"></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha estimada de pago:</strong></td>
                                    <td id="viewPaymentDueDate"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="viewProductsTable">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Unidad de Medida</th>
                                    <th>Costo Unit.</th>
                                    <th>Subtotal</th>
                                    <th>Fecha Caducidad</th>
                                    <th>Lote</th>
                                </tr>
                            </thead>
                            <tbody id="viewProductsTableBody">
                                <!-- Los productos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registrar pagos -->
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

    @endsection
