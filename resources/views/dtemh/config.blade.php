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
    <script src="{{ asset('assets/js/app-config-list.js') }}"></script>
@endsection

@section('title', 'Configuraciones Credenciales Facturacion Electronica SV-DTE')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Errores de validación:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Configuraciones</h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                        <i class="fas fa-plus me-2"></i>Nueva Configuración
                    </button>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-config border-top">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>ID</th>
                        <th>EMPRESA</th>
                        <th>VERSION</th>
                        <th>AMBIENTE</th>
                        <!--<th>TIPO MODELO</th>
                        <th>TIPO TRANSMISION</th>
                        <th>TIPO CONTINGENCIA</th>-->
                        <th>VERSION JSON</th>
                        <th>PASS_PRIVATE_KEY</th>
                        <th>PASS_PUBLIC_KEY</th>
                        <th>PASS_MH</th>
                        <th>CODE COUNTRY</th>
                        <th>NAME COUNTRY</th>
                        <th>EMISIÓN DTE</th>
                        <th>NOTAS DTE</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($configs)
                        @forelse($configs as $config)
                            <tr>
                                <td></td>
                                <td>{{ $config->id }}</td>
                                <td>{{ $config->name_company }}</td>
                                <td>{{ $config->version }}</td>
                                <td>{{ $config->ambiente }}</td>
                                <!--<td></td>
                                <td></td>
                                <td></td>-->
                                <td>{{ $config->versionJson }}</td>
                                <td>
                                    <span class="text-muted" id="passPrivateKey_{{ $config->id }}">
                                        <i class="ti ti-lock"></i> ••••••••
                                    </span>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="toggleShowPassword({{ $config->id }}, '{{ $config->passPrivateKey }}', 'passPrivateKey')" title="Mostrar/Ocultar">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                                <td>
                                    <span class="text-muted" id="passkeyPublic_{{ $config->id }}">
                                        <i class="ti ti-lock"></i> ••••••••
                                    </span>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="toggleShowPassword({{ $config->id }}, '{{ $config->passkeyPublic }}', 'passkeyPublic')" title="Mostrar/Ocultar">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                                <td>
                                    <span class="text-muted" id="passMH_{{ $config->id }}">
                                        <i class="ti ti-lock"></i> ••••••••
                                    </span>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="toggleShowPassword({{ $config->id }}, '{{ $config->passMH }}', 'passMH')" title="Mostrar/Ocultar">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                                <td>{{ $config->codeCountry }}</td>
                                <td>{{ $config->nameCountry }}</td>
                                <td>
                                    @if($config->dte_emission_enabled)
                                        <span class="badge bg-success">Habilitado</span>
                                    @else
                                        <span class="badge bg-danger">Deshabilitado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($config->dte_emission_notes)
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $config->dte_emission_notes }}">
                                            {{ $config->dte_emission_notes }}
                                        </span>
                                    @else
                                        <span class="text-muted">Sin notas</span>
                                    @endif
                                </td>
                                <td><div class="d-flex align-items-center">
                                    <a href="javascript: editconfig({{ $config->id }});" class="dropdown-item"><i
                                        class="ti ti-edit ti-sm me-2"></i>Editar</a>
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                    <div class="m-0 dropdown-menu dropdown-menu-end">
                                        <a href="javascript:deleteconfig({{ $config->id }});" class="dropdown-item"><i
                                                class="ti ti-eraser ti-sm me-2"></i>Eliminar</a>

                                    </div>
                                </div></td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="text-center">
                                        <div class="py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay configuraciones</h5>
                                            <p class="text-muted">No se han encontrado configuraciones de DTE.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                                                <i class="fas fa-plus me-2"></i>Crear Primera Configuración
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>

            <!-- Add config Modal -->
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Crear nueva configuracion</h3>
          </div>
          <form id="addproductForm" class="row" action="{{Route('config.store')}}" method="POST">
            @csrf @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Empresa</label>
              <select class="select2 form-select" id="company" name="company" aria-label="Seleccionar opcion"></select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Version</label>
              <input type="text" id="version" name="version" class="form-control" placeholder="Version" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Ambiente</label>
              <select class="select2 form-select" id="ambiente" name="ambiente" aria-label="Seleccionar opcion">
                <option value="1">Ambiente Desarrollo</option>
                <option value="2">Ambiente Produccion</option>
              </select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Modelo</label>
              <input type="text" id="typemodel" name="typemodel" class="form-control" placeholder="Tipo Modelo" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Transmision</label>
              <input type="text" id="typetransmission" name="typetransmission" class="form-control" placeholder="Tipo Transmision" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Contingencia</label>
              <input type="text" id="typecontingencia" name="typecontingencia" class="form-control" placeholder="Tipo Contingencia" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Version Json</label>
              <select class="select2versionjson form-select" id="versionjson" name="versionjson" aria-label="Seleccionar opcion">
                <option value="1">v1</option>
                <option value="2">v2</option>
                <option value="3">v3</option>
                <option value="4">v4</option>
              </select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passprivatekey">
                <i class="ti ti-key me-1"></i>Contraseña Llave Privada
              </label>
              <div class="input-group">
                <input type="password" id="passprivatekey" name="passprivatekey" class="form-control" placeholder="Ingrese la contraseña de la llave privada" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passprivatekey', this)">
                  <i class="ti ti-eye" id="icon-passprivatekey"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña utilizada para firmar digitalmente los documentos</small>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passpublickey">
                <i class="ti ti-key me-1"></i>Contraseña Llave Pública
              </label>
              <div class="input-group">
                <input type="password" id="passpublickey" name="passpublickey" class="form-control" placeholder="Ingrese la contraseña de la llave pública" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passpublickey', this)">
                  <i class="ti ti-eye" id="icon-passpublickey"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña utilizada para validar la firma digital</small>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passmh">
                <i class="ti ti-shield-lock me-1"></i>Contraseña Ministerio de Hacienda (MH)
              </label>
              <div class="input-group">
                <input type="password" id="passmh" name="passmh" class="form-control" placeholder="Ingrese la contraseña del Ministerio de Hacienda" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passmh', this)">
                  <i class="ti ti-eye" id="icon-passmh"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña proporcionada por el Ministerio de Hacienda para autenticación</small>
            </div>
            <div class="mb-3 col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="dte_emission_enabled" name="dte_emission_enabled" checked>
                <label class="form-check-label" for="dte_emission_enabled">
                  <i class="fas fa-toggle-on text-success me-2"></i>Habilitar emisión de DTE
                </label>
              </div>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="dte_emission_notes">
                <i class="fas fa-sticky-note me-2"></i>Notas sobre emisión DTE
                <small class="text-muted">(Obligatorio si la emisión está deshabilitada)</small>
              </label>
              <textarea id="dte_emission_notes" name="dte_emission_notes" class="form-control" rows="3" placeholder="Notas sobre la configuración de emisión DTE..."></textarea>
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Crear</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

             <!-- Add update Modal -->
<div class="modal fade" id="updateConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="p-3 modal-content p-md-5">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="mb-4 text-center">
            <h3 class="mb-2">Editar Config</h3>
          </div>
          <form id="updateConfigForm" class="row" action="{{Route('config.update')}}" method="POST">
            @csrf
            @method('POST')
            <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
            <input type="hidden" name="idedit" id="idedit">
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Empresa</label>
              <select class="select2 form-select" id="companyedit" name="companyedit" aria-label="Seleccionar opcion"></select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Version</label>
              <input type="text" id="versionedit" name="versionedit" class="form-control" placeholder="Version" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Ambiente</label>
              <select class="select2 form-select" id="ambienteedit" name="ambienteedit" aria-label="Seleccionar opcion">
                <option value="1">Ambiente Desarrollo</option>
                <option value="2">Ambiente Produccion</option>
              </select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Modelo</label>
              <input type="text" id="typemodeledit" name="typemodeledit" class="form-control" placeholder="Tipo Modelo" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Transmision</label>
              <input type="text" id="typetransmissionedit" name="typetransmissionedit" class="form-control" placeholder="Tipo Transmision" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Tipo Contingencia</label>
              <input type="text" id="typecontingenciaedit" name="typecontingenciaedit" class="form-control" placeholder="Tipo Contingencia" required/>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="name">Version Json</label>
              <select class="select2 form-select" id="versionjsonedit" name="versionjsonedit" aria-label="Seleccionar opcion">
                <option value="1">v1</option>
                <option value="2">v2</option>
                <option value="3">v3</option>
                <option value="4">v4</option>
              </select>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passprivatekeyedit">
                <i class="ti ti-key me-1"></i>Contraseña Llave Privada
              </label>
              <div class="input-group">
                <input type="password" id="passprivatekeyedit" name="passprivatekeyedit" class="form-control" placeholder="Ingrese la contraseña de la llave privada" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passprivatekeyedit', this)">
                  <i class="ti ti-eye" id="icon-passprivatekeyedit"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña utilizada para firmar digitalmente los documentos</small>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passpublickeyedit">
                <i class="ti ti-key me-1"></i>Contraseña Llave Pública
              </label>
              <div class="input-group">
                <input type="password" id="passpublickeyedit" name="passpublickeyedit" class="form-control" placeholder="Ingrese la contraseña de la llave pública" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passpublickeyedit', this)">
                  <i class="ti ti-eye" id="icon-passpublickeyedit"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña utilizada para validar la firma digital</small>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="passmhedit">
                <i class="ti ti-shield-lock me-1"></i>Contraseña Ministerio de Hacienda (MH)
              </label>
              <div class="input-group">
                <input type="password" id="passmhedit" name="passmhedit" class="form-control" placeholder="Ingrese la contraseña del Ministerio de Hacienda" required/>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passmhedit', this)">
                  <i class="ti ti-eye" id="icon-passmhedit"></i>
                </button>
              </div>
              <small class="text-muted">Contraseña proporcionada por el Ministerio de Hacienda para autenticación</small>
            </div>
            <div class="mb-3 col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="dte_emission_enabled_edit" name="dte_emission_enabled_edit">
                <label class="form-check-label" for="dte_emission_enabled_edit">
                  <i class="fas fa-toggle-on text-success me-2"></i>Habilitar emisión de DTE
                </label>
              </div>
            </div>
            <div class="mb-3 col-12">
              <label class="form-label" for="dte_emission_notes_edit">
                <i class="fas fa-sticky-note me-2"></i>Notas sobre emisión DTE
                <small class="text-muted">(Obligatorio si la emisión está deshabilitada)</small>
              </label>
              <textarea id="dte_emission_notes_edit" name="dte_emission_notes_edit" class="form-control" rows="3" placeholder="Notas sobre la configuración de emisión DTE..."></textarea>
            </div>
            <div class="text-center col-12 demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-3 me-1">Guardar</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
    @endsection

@section('page-script')
<script>
// Función global para mostrar/ocultar contraseñas en formularios
window.togglePassword = function(inputId, button) {
    var input = document.getElementById(inputId);
    var icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        input.type = 'password';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
};

// Función global para mostrar/ocultar contraseñas en la tabla
window.toggleShowPassword = function(configId, password, fieldName) {
    var spanId = fieldName + '_' + configId;
    var span = document.getElementById(spanId);
    var button = event.target.closest('button');
    var icon = button.querySelector('i');
    
    if (span.innerHTML.includes('••••••••')) {
        span.innerHTML = '<code>' + password + '</code>';
        icon.classList.remove('ti-eye');
        icon.classList.add('ti-eye-off');
    } else {
        span.innerHTML = '<i class="ti ti-lock"></i> ••••••••';
        icon.classList.remove('ti-eye-off');
        icon.classList.add('ti-eye');
    }
};

$(document).ready(function() {
    // Inicializar DataTable
    $('.datatables-config').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        columnDefs: [
            { orderable: false, targets: [0, 14] } // Deshabilitar ordenamiento en columna de acciones
        ]
    });

    // Inicializar Select2 para empresas
    $('.select2').select2({
        placeholder: 'Seleccionar empresa',
        allowClear: true,
        ajax: {
            url: '{{ route("company.getcompanies") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name
                        };
                    })
                };
            },
            cache: true
        }
    });

    // La función editconfig está definida en app-config-list.js

    // Función para eliminar configuración
    window.deleteconfig = function(id) {
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
                $.ajax({
                    url: '{{ route("config.destroy", ":id") }}'.replace(':id', btoa(id)),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.res == "1") {
                            Swal.fire('Eliminado', 'La configuración ha sido eliminada', 'success')
                                .then(() => {
                                    location.reload();
                                });
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar la configuración', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error al eliminar la configuración', 'error');
                    }
                });
            }
        });
    };

    // Manejar el cambio del switch de emisión DTE
    $('#dte_emission_enabled, #dte_emission_enabled_edit').on('change', function() {
        var isEnabled = $(this).is(':checked');
        var notesField = $(this).attr('id') === 'dte_emission_enabled' ?
            $('#dte_emission_notes') : $('#dte_emission_notes_edit');
        var label = $(this).closest('.form-check').find('label');
        var icon = label.find('i');

        if (isEnabled) {
            notesField.prop('required', false);
            notesField.closest('.mb-3').find('.form-label').removeClass('text-danger');
            icon.removeClass('fa-toggle-off text-danger').addClass('fa-toggle-on text-success');
        } else {
            notesField.prop('required', true);
            notesField.closest('.mb-3').find('.form-label').addClass('text-danger');
            icon.removeClass('fa-toggle-on text-success').addClass('fa-toggle-off text-danger');
        }
    });

    // Validación del formulario de creación
    $('#addproductForm').on('submit', function(e) {
        var isDteEnabled = $('#dte_emission_enabled').is(':checked');
        var notes = $('#dte_emission_notes').val().trim();

        if (!isDteEnabled && notes === '') {
            e.preventDefault();
            Swal.fire('Error', 'Debe proporcionar una razón para deshabilitar la emisión DTE', 'error');
            $('#dte_emission_notes').focus();
            return false;
        }
    });

    // Validación del formulario de edición
    $('#updateConfigForm').on('submit', function(e) {
        var isDteEnabled = $('#dte_emission_enabled_edit').is(':checked');
        var notes = $('#dte_emission_notes_edit').val().trim();

        if (!isDteEnabled && notes === '') {
            e.preventDefault();
            Swal.fire('Error', 'Debe proporcionar una razón para deshabilitar la emisión DTE', 'error');
            $('#dte_emission_notes_edit').focus();
            return false;
        }
    });

    // Inicializar estado visual de los switches
    function initializeSwitchState() {
        $('#dte_emission_enabled, #dte_emission_enabled_edit').each(function() {
            var isEnabled = $(this).is(':checked');
            var label = $(this).closest('.form-check').find('label');
            var icon = label.find('i');

            if (isEnabled) {
                icon.removeClass('fa-toggle-off text-danger').addClass('fa-toggle-on text-success');
            } else {
                icon.removeClass('fa-toggle-on text-success').addClass('fa-toggle-off text-danger');
            }
        });
    }

    // Inicializar estado al cargar
    initializeSwitchState();

    // Limpiar formularios al cerrar modales
    $('#addConfigModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.select2').val(null).trigger('change');
        $('#dte_emission_enabled').prop('checked', true);
        $('#dte_emission_notes').prop('required', false);
        initializeSwitchState();
    });

    $('#updateConfigModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.select2').val(null).trigger('change');
        initializeSwitchState();
    });
});
</script>
@endsection
