@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            $('.datatables-laboratories').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                dom: '<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                buttons: [
                    {
                        text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Nuevo Laboratorio</span>',
                        className: 'create-new btn btn-primary',
                        action: function(e, dt, button, config) {
                            $('#addLaboratoryModal').modal('show');
                        }
                    }
                ]
            });

            $('div.head-label').html('<h5 class="card-title mb-0">Laboratorios Farmacéuticos</h5>');
        });

        function editLaboratory(id) {
            $.ajax({
                url: `/pharmaceutical-laboratories/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    $('#idupdate').val(response.id);
                    $('#nameupdate').val(response.name);
                    $('#codeupdate').val(response.code);
                    $('#countryupdate').val(response.country);
                    $('#phoneupdate').val(response.phone);
                    $('#emailupdate').val(response.email);
                    $('#websiteupdate').val(response.website);
                    $('#descriptionupdate').val(response.description);
                    $('#addressupdate').val(response.address);
                    $('#activeupdate').val(response.active ? '1' : '0');
                    $('#updateLaboratoryModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo cargar la información del laboratorio', 'error');
                }
            });
        }

        function deleteLaboratory(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede revertir",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/pharmaceutical-laboratories/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Eliminado', 'Laboratorio eliminado exitosamente', 'success');
                            location.reload();
                        },
                        error: function(xhr) {
                            const error = xhr.responseJSON?.message || 'No se pudo eliminar el laboratorio';
                            Swal.fire('Error', error, 'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection

@section('title', 'Laboratorios Farmacéuticos')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table datatables-laboratories border-top">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>País</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laboratories as $laboratory)
                        <tr>
                            <td>{{ $laboratory->id }}</td>
                            <td>
                                <strong>{{ $laboratory->name }}</strong>
                                @if($laboratory->description)
                                    <br><small class="text-muted">{{ Str::limit($laboratory->description, 50) }}</small>
                                @endif
                            </td>
                            <td>{{ $laboratory->code ?? '-' }}</td>
                            <td>{{ $laboratory->country ?? '-' }}</td>
                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $laboratory->products_count }} productos
                                </span>
                            </td>
                            <td>
                                @if($laboratory->active)
                                    <span class="badge bg-label-success">Activo</span>
                                @else
                                    <span class="badge bg-label-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('pharmaceutical-laboratories.show', $laboratory->id) }}" 
                                       class="btn btn-sm btn-icon me-2" title="Ver detalle">
                                        <i class="ti ti-eye ti-sm"></i>
                                    </a>
                                    <a href="javascript:editLaboratory({{ $laboratory->id }});" 
                                       class="btn btn-sm btn-icon me-2" title="Editar">
                                        <i class="ti ti-edit ti-sm"></i>
                                    </a>
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" 
                                       data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical ti-sm"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end m-0">
                                        <a href="javascript:deleteLaboratory({{ $laboratory->id }});" 
                                           class="dropdown-item text-danger">
                                            <i class="ti ti-trash ti-sm me-2"></i>Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay laboratorios registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Crear Laboratorio -->
    <div class="modal fade" id="addLaboratoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Crear Nuevo Laboratorio</h3>
                        <p class="text-muted">Ingresa la información del laboratorio farmacéutico</p>
                    </div>
                    <form action="{{ route('pharmaceutical-laboratories.store') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nombre *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   placeholder="Ej: BAYER" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="code">Código</label>
                            <input type="text" id="code" name="code" class="form-control" 
                                   placeholder="Ej: LAB001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="country">País</label>
                            <input type="text" id="country" name="country" class="form-control" 
                                   placeholder="Ej: El Salvador">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Teléfono</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   placeholder="Ej: 2222-2222">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="info@laboratorio.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website">Sitio Web</label>
                            <input type="url" id="website" name="website" class="form-control" 
                                   placeholder="https://www.laboratorio.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Dirección</label>
                            <textarea id="address" name="address" class="form-control" rows="2" 
                                      placeholder="Dirección completa"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Descripción</label>
                            <textarea id="description" name="description" class="form-control" rows="3" 
                                      placeholder="Información adicional del laboratorio"></textarea>
                        </div>
                        <div class="col-12 text-center demo-vertical-spacing">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Crear</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Laboratorio -->
    <div class="modal fade" id="updateLaboratoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">Editar Laboratorio</h3>
                    </div>
                    <form id="updateLaboratoryForm" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="idupdate" name="id">
                        
                        <div class="col-md-6">
                            <label class="form-label" for="nameupdate">Nombre *</label>
                            <input type="text" id="nameupdate" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="codeupdate">Código</label>
                            <input type="text" id="codeupdate" name="code" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="countryupdate">País</label>
                            <input type="text" id="countryupdate" name="country" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phoneupdate">Teléfono</label>
                            <input type="text" id="phoneupdate" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="emailupdate">Email</label>
                            <input type="email" id="emailupdate" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="websiteupdate">Sitio Web</label>
                            <input type="url" id="websiteupdate" name="website" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="addressupdate">Dirección</label>
                            <textarea id="addressupdate" name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="descriptionupdate">Descripción</label>
                            <textarea id="descriptionupdate" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="activeupdate">Estado</label>
                            <select class="form-select" id="activeupdate" name="active">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12 text-center demo-vertical-spacing">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Actualizar</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configurar formulario de actualización
        $('#updateLaboratoryForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#idupdate').val();
            const formData = $(this).serializeArray();
            let data = {};
            formData.forEach(item => {
                if (item.name !== '_token' && item.name !== '_method' && item.name !== 'id') {
                    data[item.name] = item.value;
                }
            });

            $.ajax({
                url: `/pharmaceutical-laboratories/${id}`,
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    ...data
                },
                success: function(response) {
                    Swal.fire('Actualizado', 'Laboratorio actualizado exitosamente', 'success');
                    location.reload();
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Error al actualizar';
                    Swal.fire('Error', error, 'error');
                }
            });
        });
    </script>
@endsection


