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
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/client-validation.css') }}">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"
    integrity="sha512-efAcjYoYT0sXxQRtxGY37CKYmqsFVOIwMApaEbrxJr4RwqVVGw8o+Lfh/+59TU07+suZn1BWq4fDl5fdgyCNkw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection

@section('page-script')
<script src="{{ asset('assets/js/app-client-list.js') }}"></script>
<script src="{{ asset('assets/js/forms-client.js') }}"></script>
@endsection

@section('title', 'Clientes')

@section('content')
<div class="card">
    <div class="card-header border-bottom">
        <h5 class="mb-3 card-title">Empresa</h5>
        <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
            <div class="col-md-4 companies"></div>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table datatables-client border-top">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="25%">Cliente</th>
                    <th width="10%">Tipo</th>
                    <th width="12%">Documento</th>
                    <th width="10%">Contribuyente</th>
                    <th width="12%">Contacto</th>
                    <th width="8%">Estado</th>
                    <th width="18%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @isset($clients)
                @forelse($clients as $client)
                <tr>
                    {{-- ID --}}
                    <td>
                        <span class="text-muted fw-bold">#{{ $client->id }}</span>
                    </td>
                    
                    {{-- Cliente (Nombre + Info Rápida) --}}
                    <td>
                        <div class="d-flex flex-column">
                            @switch( Str::lower($client->tpersona) )
                            @case('j')
                                <strong class="text-heading">{{ $client->name_contribuyente }}</strong>
                                @if($client->comercial_name && $client->comercial_name != 'N/A')
                                    <small class="text-muted">{{ $client->comercial_name }}</small>
                                @endif
                            @break
                            @case('n')
                                <strong class="text-heading">{{ $client->firstname }} {{ $client->firstlastname }}</strong>
                                <small class="text-muted">{{ $client->secondname }} {{ $client->secondlastname }}</small>
                            @break
                            @endswitch
                        </div>
                    </td>
                    
                    {{-- Tipo --}}
                    <td>
                        @switch( Str::lower($client->tpersona) )
                        @case('j')
                            <span class="badge bg-label-primary">
                                <i class="ti ti-building-bank"></i> Jurídica
                            </span>
                        @break
                        @case('n')
                            <span class="badge bg-label-info">
                                <i class="ti ti-user"></i> Natural
                            </span>
                        @break
                        @endswitch
                        
                        @if ($client->extranjero=="1")
                            <br><span class="badge bg-label-warning mt-1">
                                <i class="ti ti-world"></i> Extranjero
                            </span>
                        @endif
                    </td>
                    
                    {{-- Documento --}}
                    <td>
                        @if ($client->extranjero=="1")
                            <div class="text-nowrap">
                                <small class="text-muted d-block">Pasaporte</small>
                                <strong>{{ $client->pasaporte }}</strong>
                            </div>
                        @else
                            <div class="text-nowrap">
                                @if(Str::lower($client->tpersona) == 'j')
                                    <small class="text-muted d-block">NIT</small>
                                @else
                                    <small class="text-muted d-block">DUI</small>
                                @endif
                                <strong>{{ str_replace('-', '', $client->nit) }}</strong>
                            </div>
                        @endif
                        
                        @if($client->ncr && $client->ncr != 'N/A')
                            <div class="text-nowrap mt-1">
                                <small class="text-muted d-block">NRC</small>
                                <strong>{{ str_replace('-', '', $client->ncr) }}</strong>
                            </div>
                        @endif
                    </td>
                    
                    {{-- Contribuyente --}}
                    <td>
                        <div class="d-flex flex-column gap-1">
                            @if ($client->contribuyente=="1" || Str::lower($client->tpersona)=='j')
                                <span class="badge bg-label-success">
                                    <i class="ti ti-check"></i> Contribuyente
                                </span>
                            @endif
                            
                            @if (isset($client->agente_retencion) && $client->agente_retencion=="1")
                                <span class="badge bg-label-warning">
                                    <i class="ti ti-discount-check"></i> Ag. Retención
                                </span>
                            @endif
                            
                            @switch($client->tipoContribuyente)
                            @case('GRA')
                                <span class="badge bg-label-danger">Grande</span>
                            @break
                            @case('MED')
                                <span class="badge bg-label-info">Mediano</span>
                            @break
                            @case('PEQU')
                                <span class="badge bg-label-secondary">Pequeño</span>
                            @break
                            @case('OTR')
                                <span class="badge bg-label-secondary">Otro</span>
                            @break
                            @endswitch
                        </div>
                    </td>
                    
                    {{-- Contacto --}}
                    <td>
                        <div class="d-flex flex-column">
                            @if($client->phone)
                                <small class="text-nowrap">
                                    <i class="ti ti-device-mobile text-primary"></i> {{ $client->phone }}
                                </small>
                            @endif
                            @if($client->email)
                                <small class="text-nowrap text-truncate" style="max-width: 150px;" title="{{ $client->email }}">
                                    <i class="ti ti-mail text-info"></i> {{ $client->email }}
                                </small>
                            @endif
                        </div>
                    </td>
                    
                    {{-- Estado/Badges Rápidos --}}
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-label-success">
                                <i class="ti ti-check"></i> Activo
                            </span>
                            @if($client->econo && $client->econo != 'N/A')
                                <small class="text-muted text-truncate" style="max-width: 100px;" title="{{ $client->econo }}">
                                    {{ Str::limit($client->econo, 15) }}
                                </small>
                            @endif
                        </div>
                    </td>
                    
                    {{-- Acciones Mejoradas --}}
                    <td>
                        <div class="d-flex gap-2">
                            {{-- Botón Ver Detalles --}}
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info" 
                                    onclick="viewClientDetails({{ $client->id }})"
                                    title="Ver Detalles Completos"
                                    data-bs-toggle="tooltip">
                                <i class="ti ti-eye"></i>
                            </button>
                            
                            {{-- Botón Editar --}}
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary" 
                                    onclick="editClient({{ $client->id }})"
                                    title="Editar Cliente"
                                    data-bs-toggle="tooltip">
                                <i class="ti ti-edit"></i>
                            </button>
                            
                            {{-- Menú Dropdown con más opciones --}}
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                        title="Más Opciones">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="viewClientDetails({{ $client->id }})">
                                            <i class="ti ti-eye me-2"></i>
                                            Ver Detalles
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="editClient({{ $client->id }})">
                                            <i class="ti ti-edit me-2"></i>
                                            Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteClient({{ $client->id }})">
                                            <i class="ti ti-trash me-2"></i>
                                            Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="ti ti-users-off mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                            <h5 class="text-muted">No hay clientes registrados</h5>
                            <p class="text-muted">Comienza agregando tu primer cliente</p>
                        </div>
                    </td>
                </tr>
                @endforelse
                @endisset
            </tbody>
        </table>
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddClient" aria-labelledby="offcanvasAddUserLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Nuevo Cliente</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="flex-grow-0 pt-0 mx-0 offcanvas-body h-100">
            <form class="pt-0 add-new-user" id="addNewClientForm" action="{{ route('client.store') }}" method="POST">
                @csrf @method('POST')
                <input type="hidden" id="companyselected" name="companyselected"
                    value="{{ isset($companyselected) ? $companyselected : 0 }}">
                <div class="mb-3">
                    <label for="tpersona" class="form-label">Tipo de cliente</label>
                    <select class="select2typeperson form-select" id="tpersona" name="tpersona"
                        aria-label="Seleccionar opcion" onchange="typeperson(this.value)">
                        <option value="0" selected>Seleccione</option>
                        <option value="N">NATURAL</option>
                        <option value="J">JURIDICA</option>
                    </select>
                </div>
                <div id="fields_natural" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label" for="firstname">Primer Nombre</label>
                        <input type="text" class="form-control" id="firstname" placeholder="Primer Nombre"
                            name="firstname" aria-label="Primer Nombre" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="secondname">Segundo Nombre</label>
                        <input type="text" class="form-control" id="secondname" placeholder="Segundo Nombre"
                            name="secondname" aria-label="Segundo Nombre" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="firstlastname">Primer Apellido</label>
                        <input type="text" class="form-control" id="firstlastname" placeholder="Primer Apellido"
                            name="firstlastname" aria-label="Primer Apellido" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="secondlastname">Segundo Apellido</label>
                        <input type="text" class="form-control" id="secondlastname" placeholder="Segundo Apellido"
                            name="secondlastname" aria-label="Segundo Apellido" />
                    </div>
                </div>
                <div id="fields_juridico" style="display: none">
                    <div class="mb-3">
                        <label class="form-label" for="comercial_name">Nombre Comercial</label>
                        <input type="text" class="form-control" id="comercial_name" placeholder="Nombre Comercial"
                            name="comercial_name" aria-label="Nombre Comercial" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name_contribuyente">Nombre Contribuyente</label>
                        <input type="text" class="form-control" id="name_contribuyente"
                            placeholder="Nombre Contribuyente" name="name_contribuyente"
                            aria-label="Nombre Contribuyente" />
                    </div>
                </div>
                <div id="fields_with_option" style="display: none">
                <div class="mb-3">
                    <label class="form-label" for="tel1">Teléfono</label>
                    <input type="text" id="tel1" class="form-control" placeholder="7488-8811" aria-label="7488-8811"
                        name="tel1" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="tel2">Teléfono Fijo</label>
                    <input type="text" id="tel2" class="form-control" placeholder="2422-5654" aria-label="2422-5654"
                        name="tel2" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Correo</label>
                    <input type="email" id="email" class="form-control" placeholder="john.doe@example.com"
                        aria-label="john.doe@example.com" name="email" />
                </div>
                <div class="mb-3">
                    <label for="country" class="form-label">País</label>
                    <select class="select2country form-select" id="country" name="country"
                        aria-label="Seleccionar opcion" onchange="getdepartamentos(this.value,'','','')">
                        <option>Seleccione</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="departament" class="form-label">Departamento</label>
                    <select class="select2dep form-select" id="departament" name="departament"
                        aria-label="Seleccionar opcion" onchange="getmunicipio(this.value,'','')">
                        <option selected>Seleccione</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="municipio" class="form-label">Municipio</label>
                    <select class="select2muni form-select" id="municipio" name="municipio"
                        aria-label="Seleccionar opcion">
                        <option selected>Seleccione</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="address">Dirección</label>
                    <input type="text" id="address" class="form-control" placeholder="Av. 5 Norte "
                        aria-label="Direccion" name="address" />
                </div>
                <div class="mb-3">
                    <label class="switch switch-success" id="extranjerolabel" name="extranjerolabel"
                        style="display: none;">
                        <input type="checkbox" class="switch-input" id="extranjero" name="extranjero"
                            onclick="esextranjero();" />
                        <span class="switch-toggle-slider">
                            <span class="switch-on">
                                <i class="ti ti-check"></i>
                            </span>
                            <span class="switch-off">
                                <i class="ti ti-x"></i>
                            </span>
                        </span>
                        <span class="switch-label">¿Es Extranjero?</span>
                    </label>
                </div>
                <div class="mb-3" id="siextranjeroduinit">
                    <label class="form-label" for="nit">DUI/NIT</label>
                    <input type="text" id="nit" class="form-control" placeholder="xxxxxxxx-x"
                        onkeyup="nitDuiMask(this);" maxlength="25" aria-label="nit" name="nit" />
                </div>
                <div id="siextranjero" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label" for="pasaporte">Pasaporte</label>
                        <input type="text" id="pasaporte" class="form-control" placeholder="xxxxxx-x"
                            onkeyup="pasaporteMask(this);" maxlength="15" aria-label="pasaporte" name="pasaporte" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="switch switch-success" id="contribuyentelabel" name="contribuyentelabel"
                        style="display: none;">
                        <input type="checkbox" class="switch-input" id="contribuyente" name="contribuyente"
                            onclick="escontri()" />
                        <span class="switch-toggle-slider">
                            <span class="switch-on">
                                <i class="ti ti-check"></i>
                            </span>
                            <span class="switch-off">
                                <i class="ti ti-x"></i>
                            </span>
                        </span>
                        <span class="switch-label">¿Es Contribuyente?</span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="switch switch-warning" id="agenteretencionlabel" name="agenteretencionlabel">
                        <input type="checkbox" class="switch-input" id="agente_retencion" name="agente_retencion" />
                        <span class="switch-toggle-slider">
                            <span class="switch-on">
                                <i class="ti ti-check"></i>
                            </span>
                            <span class="switch-off">
                                <i class="ti ti-x"></i>
                            </span>
                        </span>
                        <span class="switch-label">¿Es Agente de Retención?</span>
                    </label>
                </div>
                <div id="siescontri" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label" for="legal">Representante Legal</label>
                        <input type="text" id="legal" class="form-control" placeholder="Representante Legal"
                            aria-label="legal" name="legal" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ncr">NRC</label>
                        <input type="text" id="ncr" class="form-control" placeholder="xxxxxx-x" onkeyup="NRCMask(this);"
                            maxlength="15" aria-label="ncr" name="ncr" />
                    </div>
                    <div class="mb-3">
                        <label for="tipocontribuyente" class="form-label">Tipo de contribuyente</label>
                        <select class="select2tipocontri form-select" id="tipocontribuyente" name="tipocontribuyente"
                            aria-label="Seleccionar opcion">
                            <option selected>Seleccione</option>
                            <option value="GRA">Gran Contribuyente</option>
                            <option value="MED">Mediano</option>
                            <option value="PEQU">Pequeño</option>
                            <option value="OTR">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="acteconomica" class="form-label">Actividad Económica</label>
                        <select class="select2act form-select" id="acteconomica" name="acteconomica"
                            aria-label="Seleccionar opcion">
                            <option value="0" selected>Seleccione</option>
                        </select>
                    </div>
                    <div class="mb-3" style="display: none;">
                        <label class="form-label" for="giro">GIRO</label>
                        <input type="text" id="giro" class="form-control" placeholder="giro" aria-label="giro"
                            name="giro" />
                    </div>
                    <div class="mb-3" style="display: none;">
                        <label class="form-label" for="empresa">Nombre Comercial</label>
                        <input type="text" id="empresa" class="form-control" placeholder="Nombre Comercial"
                            aria-label="empresa" name="empresa" />
                    </div>
                </div>
                <div class="mb-3" id="nacimientof">
                    <label for="birthday" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" placeholder="DD-MM-YY" id="birthday" name="birthday" />
                </div>

                <button type="button" class="btn btn-primary me-sm-3 me-1 data-submit"
                    id="btnsavenewclient" onclick="submitClientForm()">Guardar</button>
                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
        </div>
        </form>
    </div>
</div>

<!-- Update client-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUpdateClient"
    aria-labelledby="offcanvasUpdateClientLabel">
    <div class="offcanvas-header">
        <h5 id="offcanvasUpdateClientLabel" class="offcanvas-title">Editar Cliente</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="flex-grow-0 pt-0 mx-0 offcanvas-body h-100">
        <form class="pt-0 add-new-user" id="addNewClientForm" action="{{ route('client.update') }}" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" id="companyselectededit" name="companyselectededit"
                value="{{ isset($companyselected) ? $companyselected : 0 }}">
            <input type="hidden" name="idedit" id="idedit">
            <div class="mb-3">
                <label for="tpersonaedit" class="form-label">Tipo de cliente</label>
                <select class="select2typepersonedit form-select" id="tpersonaedit" name="tpersonaedit"
                    aria-label="Seleccionar opcion" onchange="typepersonedit(this.value)">
                </select>
            </div>
            <div id="fields_natural_edit">
                <div class="mb-3">
                    <label class="form-label" for="firstnameedit">Primer Nombre</label>
                    <input type="text" class="form-control" id="firstnameedit" placeholder="Primer Nombre"
                        name="firstnameedit" aria-label="Primer Nombre" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="secondnameedit">Segundo Nombre</label>
                    <input type="text" class="form-control" id="secondnameedit" placeholder="Segundo Nombre"
                        name="secondnameedit" aria-label="Segundo Nombre" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="firstlastnameedit">Primer Apellido</label>
                    <input type="text" class="form-control" id="firstlastnameedit" placeholder="Primer Apellido"
                        name="firstlastnameedit" aria-label="Primer Apellido" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="secondlastnameedit">Segundo Apellido</label>
                    <input type="text" class="form-control" id="secondlastnameedit" placeholder="Segundo Apellido"
                        name="secondlastnameedit" aria-label="Segundo Apellido" />
                </div>
            </div>
            <div id="fields_juridico_edit">
                <div class="mb-3">
                    <label class="form-label" for="comercial_nameedit">Nombre Comercial</label>
                    <input type="text" id="comercial_nameedit" class="form-control" placeholder="Nombre Comercial"
                        aria-label="empresa" name="comercial_nameedit" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="name_contribuyenteedit">Nombre Contribuyente</label>
                    <input type="text" class="form-control" id="name_contribuyenteedit"
                        placeholder="Nombre Contribuyente" name="name_contribuyenteedit"
                        aria-label="Nombre Contribuyente" />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="tel1edit">Teléfono</label>
                <input type="text" id="tel1edit" class="form-control" placeholder="7488-8811" aria-label="7488-8811"
                    name="tel1edit" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="tel2edit">Teléfono Fijo</label>
                <input type="text" id="tel2edit" class="form-control" placeholder="2422-5654" aria-label="2422-5654"
                    name="tel2edit" />
                <input type="hidden" name="phoneeditid" id="phoneeditid">
            </div>
            <div class="mb-3">
                <label class="form-label" for="emailedit">Correo</label>
                <input type="text" id="emailedit" class="form-control" placeholder="john.doe@example.com"
                    aria-label="john.doe@example.com" name="emailedit" />
            </div>
            <div class="mb-3">
                <label for="countryedit" class="form-label">País</label>
                <select class="select2countryedit form-select" id="countryedit" name="countryedit"
                    aria-label="Seleccionar opcion" onchange="getdepartamentos(this.value,'','','')">
                    <option>Seleccione</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="departamentedit" class="form-label">Departamento</label>
                <select class="select2depedit form-select" id="departamentedit" name="departamentedit"
                    aria-label="Seleccionar opcion" onchange="getmunicipio(this.value,'','')">
                    <option selected>Seleccione</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="municipioedit" class="form-label">Municipio</label>
                <select class="select2muniedit form-select" id="municipioedit" name="municipioedit"
                    aria-label="Seleccionar opcion">
                    <option selected>Seleccione</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="addressedit">Dirección</label>
                <input type="text" id="addressedit" class="form-control" placeholder="john.doe@example.com"
                    aria-label="Direccion" name="addressedit" />
                <input type="hidden" name="addresseditid" id="addresseditid">
            </div>
            <div class="mb-3">
                <label class="switch switch-primary" id="extranjerolabeledit" name="extranjerolabeledit" style="display: none;">
                    <input type="checkbox" class="switch-input" id="extranjeroedit" name="extranjeroedit" onclick="esextranjeroedit()" />
                    <span class="switch-toggle-slider">
                        <span class="switch-on">
                            <i class="ti ti-check"></i>
                        </span>
                        <span class="switch-off">
                            <i class="ti ti-x"></i>
                        </span>
                    </span>
                    <span class="switch-label">¿Es Extranjero?</span>
                </label>
            </div>
            <div class="mb-3">
                <label class="switch switch-success" id="contribuyentelabeledit" name="contribuyentelabeledit"
                    style="display: none;">
                    <input type="checkbox" class="switch-input" id="contribuyenteedit" name="contribuyenteedit"
                        onclick="escontriedit()" />
                    <span class="switch-toggle-slider">
                        <span class="switch-on">
                            <i class="ti ti-check"></i>
                        </span>
                        <span class="switch-off">
                            <i class="ti ti-x"></i>
                        </span>
                    </span>
                    <span class="switch-label">¿Es Contribuyente?</span>
                </label>
                <input type="hidden" value="0" name="contribuyenteeditvalor" id="contribuyenteeditvalor">
            </div>
            <div class="mb-3">
                <label class="switch switch-warning" id="agenteretencionlabeledit" name="agenteretencionlabeledit">
                    <input type="checkbox" class="switch-input" id="agente_retencionedit" name="agente_retencionedit" onclick="updateAgenteRetencionEdit()" />
                    <span class="switch-toggle-slider">
                        <span class="switch-on">
                            <i class="ti ti-check"></i>
                        </span>
                        <span class="switch-off">
                            <i class="ti ti-x"></i>
                        </span>
                    </span>
                    <span class="switch-label">¿Es Agente de Retención?</span>
                </label>
                <input type="hidden" value="0" name="agente_retencionedit_hidden" id="agente_retencionedit_hidden">
            </div>
            <div class="mb-3" id="siextranjeroduinitedit">
                <label class="form-label" for="nitedit">DUI/NIT</label>
                <input type="text" id="nitedit" class="form-control" placeholder="xxxxxxxx-x"
                    onkeyup="nitDuiMask(this);" maxlength="25" aria-label="nit" name="nitedit" />
            </div>
            <div id="siextranjeroedit" style="display: none;">
                <div class="mb-3">
                    <label class="form-label" for="pasaporteedit">Pasaporte</label>
                    <input type="text" id="pasaporteedit" class="form-control" placeholder="xxxxxx-x"
                        onkeyup="pasaporteMask(this);" maxlength="15" aria-label="pasaporte" name="pasaporteedit" />
                </div>
            </div>
            <div id="siescontriedit" style="display: none;">
                <div class="mb-3">
                    <label class="form-label" for="legaledit">Representante Legal</label>
                    <input type="text" id="legaledit" class="form-control" placeholder="Representante Legal"
                        aria-label="legal" name="legaledit" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ncredit">NRC</label>
                    <input type="text" id="ncredit" class="form-control" onkeyup="NRCMask(this);" maxlength="15"
                        placeholder="xxxxxx-x" aria-label="ncr" name="ncredit" />
                </div>
                <div class="mb-3">
                    <label for="tipocontribuyenteedit" class="form-label">Tipo de contribuyente</label>
                    <select class="select2tipocontri form-select" id="tipocontribuyenteedit"
                        name="tipocontribuyenteedit" aria-label="Seleccionar opcion">
                        <option selected>Seleccione</option>
                        <option value="GRA">Gran Contribuyente</option>
                        <option value="MED">Mediano</option>
                        <option value="PEQU">Pequeño</option>
                        <option value="OTR">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="acteconomicaedit" class="form-label">Actividad Económica</label>
                    <select class="select2actedit form-select" id="acteconomicaedit" name="acteconomicaedit"
                        aria-label="Seleccionar opcion">
                        <option value="0" selected>Seleccione</option>
                    </select>
                </div>
                <div class="mb-3" style="display: none;">
                    <label class="form-label" for="giroedit">GIRO</label>
                    <input type="text" id="giroedit" class="form-control" placeholder="giro" aria-label="giro"
                        name="giroedit" />
                </div>
            </div>
            <div class="mb-3" id="DOB_field">
                <label for="birthdayedit" class="form-label">Fecha de Nacimiento</label>
                <input type="date" class="form-control" placeholder="DD-MM-YY" id="birthdayedit" name="birthdayedit" />
            </div>
            <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit" id="btnupdate">Guardar</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
        </form>
    </div>
</div>
</div>

{{-- Modal de Detalles del Cliente --}}
<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="clientDetailsModalLabel">
                    <i class="ti ti-user-circle me-2"></i>Detalles Completos del Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="clientDetailsContent">
                    {{-- El contenido se cargará dinámicamente mediante JavaScript --}}
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted">Cargando información del cliente...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="editClientFromModal()">
                    <i class="ti ti-edit me-1"></i>Editar Cliente
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentClientIdForModal = null;

/**
 * Ver detalles completos del cliente en un modal
 */
function viewClientDetails(clientId) {
    currentClientIdForModal = clientId;
    const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
    
    // Mostrar loading
    document.getElementById('clientDetailsContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando información del cliente...</p>
        </div>
    `;
    
    modal.show();
    
    // Cargar datos del cliente
    $.ajax({
        url: "/client/getClientid/" + btoa(clientId),
        method: "GET",
        success: function(response) {
            if (response && response.length > 0) {
                const client = response[0];
                displayClientDetails(client);
            } else {
                document.getElementById('clientDetailsContent').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        No se pudo cargar la información del cliente.
                    </div>
                `;
            }
        },
        error: function(error) {
            console.error('Error al cargar cliente:', error);
            document.getElementById('clientDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    Error al cargar la información del cliente.
                </div>
            `;
        }
    });
}

/**
 * Mostrar los detalles del cliente en el modal
 */
function displayClientDetails(client) {
    const isJuridica = client.tpersona === 'J';
    const isNatural = client.tpersona === 'N';
    const isExtranjero = client.extranjero == '1';
    const isContribuyente = client.contribuyente == '1' || isJuridica;
    const isAgenteRetencion = client.agente_retencion == '1';
    
    let tipoContribuyenteText = 'No especificado';
    let tipoContribuyenteBadge = 'secondary';
    
    switch(client.tipoContribuyente) {
        case 'GRA':
            tipoContribuyenteText = 'Gran Contribuyente';
            tipoContribuyenteBadge = 'danger';
            break;
        case 'MED':
            tipoContribuyenteText = 'Mediano Contribuyente';
            tipoContribuyenteBadge = 'warning';
            break;
        case 'PEQU':
            tipoContribuyenteText = 'Pequeño Contribuyente';
            tipoContribuyenteBadge = 'info';
            break;
        case 'OTR':
            tipoContribuyenteText = 'Otro Tipo';
            tipoContribuyenteBadge = 'secondary';
            break;
    }
    
    const html = `
        <div class="row g-4">
            {{-- Tarjeta de Información Principal --}}
            <div class="col-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-2">
                                    ${isJuridica ? client.name_contribuyente : (client.firstname + ' ' + client.firstlastname)}
                                </h4>
                                ${isJuridica && client.comercial_name && client.comercial_name !== 'N/A' ? 
                                    '<p class="text-muted mb-0"><strong>Nombre Comercial:</strong> ' + client.comercial_name + '</p>' : ''}
                                ${isNatural ? 
                                    '<p class="text-muted mb-0">' + client.secondname + ' ' + client.secondlastname + '</p>' : ''}
                            </div>
                            <div class="text-end">
                                <span class="badge bg-label-${isJuridica ? 'primary' : 'info'} mb-2">
                                    <i class="ti ti-${isJuridica ? 'building-bank' : 'user'}"></i>
                                    ${isJuridica ? 'Persona Jurídica' : 'Persona Natural'}
                                </span>
                                ${isExtranjero ? '<br><span class="badge bg-label-warning"><i class="ti ti-world"></i> Extranjero</span>' : ''}
                                <br><span class="badge bg-label-secondary mt-1">ID: #${client.id}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Información de Documentos --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-label-primary">
                        <h5 class="mb-0"><i class="ti ti-file-certificate me-2"></i>Documentos de Identidad</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tbody>
                                ${isExtranjero ? `
                                    <tr>
                                        <td><strong>Pasaporte:</strong></td>
                                        <td>${client.pasaporte || 'N/A'}</td>
                                    </tr>
                                ` : `
                                    <tr>
                                        <td><strong>${isJuridica ? 'NIT' : 'DUI'}:</strong></td>
                                        <td>${client.nit ? client.nit.replace(/-/g, '') : 'N/A'}</td>
                                    </tr>
                                `}
                                ${client.ncr && client.ncr !== 'N/A' ? `
                                    <tr>
                                        <td><strong>NRC:</strong></td>
                                        <td>${client.ncr.replace(/-/g, '')}</td>
                                    </tr>
                                ` : ''}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Información Tributaria --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-label-success">
                        <h5 class="mb-0"><i class="ti ti-receipt-tax me-2"></i>Información Tributaria</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Contribuyente:</strong>
                            <span class="badge bg-label-${isContribuyente ? 'success' : 'secondary'} ms-2">
                                <i class="ti ti-${isContribuyente ? 'check' : 'x'}"></i>
                                ${isContribuyente ? 'Sí' : 'No'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Tipo de Contribuyente:</strong>
                            <span class="badge bg-label-${tipoContribuyenteBadge} ms-2">${tipoContribuyenteText}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Agente de Retención:</strong>
                            <span class="badge bg-label-${isAgenteRetencion ? 'warning' : 'secondary'} ms-2">
                                <i class="ti ti-${isAgenteRetencion ? 'discount-check' : 'x'}"></i>
                                ${isAgenteRetencion ? 'Sí - Retiene 1% sobre $120' : 'No'}
                            </span>
                        </div>
                        ${client.legal && client.legal !== 'N/A' ? `
                            <div class="mb-0">
                                <strong>Representante Legal:</strong><br>
                                <span class="text-muted">${client.legal}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            {{-- Información de Contacto --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-label-info">
                        <h5 class="mb-0"><i class="ti ti-phone me-2"></i>Información de Contacto</h5>
                    </div>
                    <div class="card-body">
                        ${client.phone ? `
                            <div class="mb-3">
                                <i class="ti ti-device-mobile text-primary"></i>
                                <strong>Teléfono Móvil:</strong><br>
                                <span class="ms-4">${client.phone}</span>
                            </div>
                        ` : ''}
                        ${client.phone_fijo ? `
                            <div class="mb-3">
                                <i class="ti ti-phone text-success"></i>
                                <strong>Teléfono Fijo:</strong><br>
                                <span class="ms-4">${client.phone_fijo}</span>
                            </div>
                        ` : ''}
                        ${client.email ? `
                            <div class="mb-0">
                                <i class="ti ti-mail text-info"></i>
                                <strong>Correo Electrónico:</strong><br>
                                <span class="ms-4">${client.email}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            {{-- Información de Ubicación --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-label-danger">
                        <h5 class="mb-0"><i class="ti ti-map-pin me-2"></i>Ubicación</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <i class="ti ti-world text-danger"></i>
                            <strong>País:</strong>
                            <span class="ms-2">${client.pais || 'N/A'}</span>
                        </div>
                        ${client.departamento ? `
                            <div class="mb-2">
                                <i class="ti ti-map-2 text-warning"></i>
                                <strong>Departamento:</strong>
                                <span class="ms-2">${client.departamento}</span>
                            </div>
                        ` : ''}
                        ${client.municipioname ? `
                            <div class="mb-2">
                                <i class="ti ti-building-community text-info"></i>
                                <strong>Municipio:</strong>
                                <span class="ms-2">${client.municipioname}</span>
                            </div>
                        ` : ''}
                        ${client.address ? `
                            <div class="mb-0">
                                <i class="ti ti-home text-primary"></i>
                                <strong>Dirección:</strong><br>
                                <span class="ms-4">${client.address}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            {{-- Información Adicional --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-label-secondary">
                        <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>Información Adicional</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            ${client.econo && client.econo !== 'N/A' ? `
                                <div class="col-md-6 mb-3">
                                    <strong>Actividad Económica:</strong><br>
                                    <span class="text-muted">${client.econo}</span>
                                </div>
                            ` : ''}
                            ${client.birthday ? `
                                <div class="col-md-6 mb-3">
                                    <strong>Fecha de Nacimiento/Constitución:</strong><br>
                                    <span class="text-muted">${new Date(client.birthday).toLocaleDateString('es-ES')}</span>
                                </div>
                            ` : ''}
                            <div class="col-md-6 mb-3">
                                <strong>Estado:</strong>
                                <span class="badge bg-label-success ms-2">
                                    <i class="ti ti-check"></i> Activo
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('clientDetailsContent').innerHTML = html;
}

/**
 * Editar cliente desde el modal de detalles
 */
function editClientFromModal() {
    if (currentClientIdForModal) {
        // Cerrar el modal de detalles
        const modal = bootstrap.Modal.getInstance(document.getElementById('clientDetailsModal'));
        modal.hide();
        
        // Abrir el formulario de edición
        setTimeout(() => {
            editClient(currentClientIdForModal);
        }, 500);
    }
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

@endsection
