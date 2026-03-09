@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('title', 'Categorías de Productos')

@section('content')
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 card-title">Categorías de Productos</h5>
            <a href="{{ route('product.index') }}" class="btn btn-label-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Volver a Productos
            </a>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0 small">Administre las categorías que se muestran en el formulario de productos. Los productos existentes no se modifican.</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                    <i class="ti ti-plus me-1"></i> Nueva categoría
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover border-top table-striped" id="tableCategories">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Productos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCategories">
                        @forelse($categories as $cat)
                            <tr data-id="{{ $cat->id }}">
                                <td>{{ $cat->id }}</td>
                                <td>{{ $cat->name }}</td>
                                <td>{{ $cat->description ?? '—' }}</td>
                                <td>{{ $cat->productsCount() }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-icon btn-label-primary edit-category" data-id="{{ $cat->id }}" data-name="{{ $cat->name }}" data-description="{{ $cat->description ?? '' }}" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-label-danger delete-category" data-id="{{ $cat->id }}" data-name="{{ $cat->name }}" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay categorías. Agregue una desde el botón «Nueva categoría».</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Categoría -->
    <div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevaCategoria">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="name_new">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="name_new" name="name" class="form-control" placeholder="Ej: Abarrotes, Lácteos, Bebidas" required maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description_new">Descripción</label>
                            <input type="text" id="description_new" name="description" class="form-control" placeholder="Opcional" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditarCategoria">
                    <input type="hidden" id="id_edit" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="name_edit">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="name_edit" name="name" class="form-control" required maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description_edit">Descripción</label>
                            <input type="text" id="description_edit" name="description" class="form-control" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
(function() {
    const baseUrl = '{{ url("/") }}';
    const csrf = '{{ csrf_token() }}';

    function showToast(icon, title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, timer: 2500, showConfirmButton: false });
        }
    }

    // DataTable con búsqueda, orden y paginación
    const tableEl = document.getElementById('tableCategories');
    if (tableEl && typeof $ !== 'undefined' && $.fn.DataTable) {
        $(tableEl).DataTable({
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Todos']],
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                infoEmpty: 'No hay registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
                zeroRecords: 'No se encontraron resultados'
            },
            columnDefs: [
                { orderable: false, targets: 4 }
            ]
        });
    }

    // Nueva categoría
    document.getElementById('formNuevaCategoria').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        fetch(baseUrl + '/product-categories/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: document.getElementById('name_new').value.trim(),
                description: document.getElementById('description_new').value.trim() || null,
                _token: csrf
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('modalNuevaCategoria')).hide();
                this.reset();
                location.reload();
            } else {
                const msg = (data.errors && data.errors.name) ? data.errors.name[0] : (data.message || 'Error al guardar');
                showToast('error', msg);
            }
        })
        .catch(() => { btn.disabled = false; showToast('error', 'Error de conexión'); });
    });

    // Editar: abrir modal
    document.querySelectorAll('.edit-category').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('id_edit').value = this.dataset.id;
            document.getElementById('name_edit').value = this.dataset.name;
            document.getElementById('description_edit').value = this.dataset.description || '';
            new bootstrap.Modal(document.getElementById('modalEditarCategoria')).show();
        });
    });

    // Delegación para botones de editar (por si se recarga solo la tabla)
    document.getElementById('tbodyCategories').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-category');
        if (editBtn) {
            document.getElementById('id_edit').value = editBtn.dataset.id;
            document.getElementById('name_edit').value = editBtn.dataset.name;
            document.getElementById('description_edit').value = editBtn.dataset.description || '';
            new bootstrap.Modal(document.getElementById('modalEditarCategoria')).show();
        }
    });

    // Actualizar categoría
    document.getElementById('formEditarCategoria').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('id_edit').value;
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        fetch(baseUrl + '/product-categories/' + id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: document.getElementById('name_edit').value.trim(),
                description: document.getElementById('description_edit').value.trim() || null,
                _token: csrf,
                _method: 'PUT'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('modalEditarCategoria')).hide();
                location.reload();
            } else {
                const msg = (data.errors && data.errors.name) ? data.errors.name[0] : (data.message || 'Error al actualizar');
                showToast('error', msg);
            }
        })
        .catch(() => { btn.disabled = false; showToast('error', 'Error de conexión'); });
    });

    // Eliminar
    document.getElementById('tbodyCategories').addEventListener('click', function(e) {
        const delBtn = e.target.closest('.delete-category');
        if (!delBtn) return;
        const id = delBtn.dataset.id;
        const name = delBtn.dataset.name;
        if (typeof Swal === 'undefined') { if (confirm('¿Eliminar la categoría «' + name + '»?')) doDelete(id); return; }
        Swal.fire({
            title: '¿Eliminar categoría?',
            text: 'Se eliminará «' + name + '». Los productos que la usan conservarán el nombre en su registro.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then(result => { if (result.isConfirmed) doDelete(id); });
    });

    function doDelete(id) {
        fetch(baseUrl + '/product-categories/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('success', data.message); location.reload(); }
            else showToast('error', data.message || 'Error al eliminar');
        })
        .catch(() => showToast('error', 'Error de conexión'));
    }
})();
</script>
@endsection
