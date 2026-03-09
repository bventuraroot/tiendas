@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Respaldos de Base de Datos')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Respaldos de Base de Datos</h5>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Respaldos</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar">
                                <div class="avatar-initial bg-primary rounded">
                                    <i class="bx bx-data"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="small text-muted">Total Respaldos</div>
                                <h6 class="mb-0" id="total-backups">{{ $stats['total_backups'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="bx bx-hdd"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="small text-muted">Espacio Total</div>
                                <h6 class="mb-0" id="total-size">{{ $stats['total_size'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar">
                                <div class="avatar-initial bg-warning rounded">
                                    <i class="bx bx-archive"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="small text-muted">Comprimidos</div>
                                <h6 class="mb-0" id="compressed-count">{{ $stats['compressed_count'] }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar">
                                <div class="avatar-initial bg-info rounded">
                                    <i class="bx bx-calendar"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="small text-muted">Último Respaldo</div>
                                <h6 class="mb-0" id="newest-backup">
                                    @if($stats['newest_backup'])
                                        {{ \Carbon\Carbon::parse($stats['newest_backup'])->format('d/m/Y') }}
                                    @else
                                        N/A
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Control -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Panel de Control</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="compress-backup" class="form-label">
                                    <i class="bx bx-archive me-1"></i>Comprimir respaldo
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="compress-backup">
                                    <label class="form-check-label" for="compress-backup">
                                        Reducir tamaño del archivo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="keep-backups" class="form-label">
                                    <i class="bx bx-trash me-1"></i>Mantener respaldos
                                </label>
                                <select class="form-select" id="keep-backups">
                                    <option value="3">3 respaldos</option>
                                    <option value="5">5 respaldos</option>
                                    <option value="7" selected>7 respaldos</option>
                                    <option value="10">10 respaldos</option>
                                    <option value="15">15 respaldos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bx bx-time me-1"></i>Respaldos Automáticos
                                </label>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-info btn-sm" id="test-auto-backup">
                                        <i class="bx bx-play me-1"></i>Probar Automático
                                    </button>
                                    <small class="text-muted">Diario: 2:00 AM<br>Semanal: Dom 3:00 AM<br>Mensual: Día 1, 4:00 AM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" id="create-backup">
                                <i class="bx bx-plus-circle me-1"></i>Crear Nuevo Respaldo
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="refresh-list">
                                <i class="bx bx-refresh me-1"></i>Actualizar Lista
                            </button>
                            <button type="button" class="btn btn-outline-info" id="test-button">
                                <i class="bx bx-test-tube me-1"></i>Prueba JS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Respaldos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Respaldos Disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="backups-table">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Base de Datos</th>
                                    <th>Tamaño</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="backups-tbody">
                                @forelse($backups as $backup)
                                <tr data-filename="{{ $backup['filename'] }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-file me-2 text-primary"></i>
                                            <span class="fw-medium">{{ $backup['filename'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $backup['database'] }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $backup['size'] }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium">{{ $backup['modified'] }}</div>
                                            <small class="text-muted">{{ $backup['age'] }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($backup['compressed'])
                                            <span class="badge bg-label-success">
                                                <i class="bx bx-archive me-1"></i>Comprimido
                                            </span>
                                        @else
                                            <span class="badge bg-label-warning">
                                                <i class="bx bx-file me-1"></i>Sin comprimir
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item download-backup" href="#" data-filename="{{ $backup['filename'] }}">
                                                    <i class="bx bx-download me-1"></i> Descargar
                                                </a>
                                                <a class="dropdown-item restore-backup" href="#" data-filename="{{ $backup['filename'] }}">
                                                    <i class="bx bx-reset me-1"></i> Restaurar
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger delete-backup" href="#" data-filename="{{ $backup['filename'] }}">
                                                    <i class="bx bx-trash me-1"></i> Eliminar
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bx bx-data display-4 mb-3"></i>
                                            <h6 class="mb-1">No hay respaldos disponibles</h6>
                                            <small>Crea tu primer respaldo usando el botón de arriba</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                ¿Estás seguro de que quieres realizar esta acción?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none"
     style="background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    console.log('Backup module loaded');
    let currentAction = null;
    let currentFilename = null;

    // Cargar lista de respaldos al iniciar
    refreshBackupsList();
    updateStats();

    // Crear nuevo respaldo
    $('#create-backup').click(function(e) {
        e.preventDefault();
        console.log('Create backup button clicked');

        const compressCheckbox = $('#compress-backup');
        const compress = compressCheckbox.is(':checked');
        const keep = $('#keep-backups').val();

        console.log('Checkbox encontrado:', compressCheckbox.length > 0);
        console.log('Checkbox marcado:', compress);
        console.log('Valor compress:', compress);
        console.log('Keep:', keep);

        showLoading();

        $.ajax({
            url: '{{ route("backups.create") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                compress: compress,
                keep: keep,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Backup creation response:', response);
                hideLoading();
                if (response.success) {
                    showAlert('success', 'Respaldo creado exitosamente');
                    refreshBackupsList();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                console.error('Backup creation error:', xhr);
                hideLoading();
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Error al crear el respaldo');
            }
        });
    });

    // Descargar respaldo
    $(document).on('click', '.download-backup', function(e) {
        e.preventDefault();
        const filename = $(this).data('filename');

        // Mostrar indicador de descarga
        Swal.fire({
            title: 'Descargando...',
            text: `Preparando descarga de ${filename}`,
            icon: 'info',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true
        });

        // Iniciar descarga
        window.location.href = '{{ route("backups.download", ":filename") }}'.replace(':filename', filename);
    });

    // Restaurar respaldo
    $(document).on('click', '.restore-backup', function(e) {
        e.preventDefault();
        const filename = $(this).data('filename');

        showConfirmModal(
            'Restaurar Respaldo',
            `¿Estás seguro de que quieres restaurar el respaldo <strong>${filename}</strong>?<br><br>
             <div class="alert alert-warning">
                <i class="bx bx-warning me-1"></i>
                <strong>Advertencia:</strong> Esta acción sobrescribirá la base de datos actual.
             </div>`,
            function() {
                restoreBackup(filename);
            }
        );
    });

    // Eliminar respaldo
    $(document).on('click', '.delete-backup', function(e) {
        e.preventDefault();
        const filename = $(this).data('filename');

        showConfirmModal(
            'Eliminar Respaldo',
            `¿Estás seguro de que quieres eliminar el respaldo <strong>${filename}</strong>?<br><br>
             Esta acción no se puede deshacer.`,
            function() {
                deleteBackup(filename);
            }
        );
    });

    // Actualizar lista
    $('#refresh-list').click(function() {
        refreshBackupsList();
    });

    // Botón de prueba automática
    $('#test-auto-backup').click(function() {
        console.log('Botón de prueba automática clickeado');

        Swal.fire({
            title: '¿Ejecutar respaldo automático?',
            text: 'Esto creará un respaldo de prueba usando el comando automático',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ejecutar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                executeAutoBackup();
            }
        });
    });

    // Botón de prueba JavaScript
    $('#test-button').click(function() {
        console.log('Botón de prueba clickeado');

        // Usar SweetAlert2 como en otros módulos
        Swal.fire({
            title: '¡JavaScript funciona!',
            text: 'jQuery versión: ' + $.fn.jquery,
            icon: 'success',
            confirmButtonText: 'Continuar'
        });

        // Probar petición AJAX
        $.ajax({
            url: '{{ route("backups.test") }}',
            method: 'GET',
            success: function(response) {
                console.log('Respuesta de prueba:', response);
                Swal.fire({
                    title: 'Petición AJAX exitosa',
                    text: response.message,
                    icon: 'success'
                });
            },
            error: function(xhr) {
                console.error('Error en petición de prueba:', xhr);
                Swal.fire({
                    title: 'Error en petición AJAX',
                    text: xhr.responseJSON?.message || 'Error desconocido',
                    icon: 'error'
                });
            }
        });
    });

    // Funciones auxiliares
    function showLoading() {
        $('#loading-overlay').removeClass('d-none');
    }

    function hideLoading() {
        $('#loading-overlay').addClass('d-none');
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'bx-check-circle' : 'bx-x-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="bx ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insertar al inicio del contenido
        $('.container-fluid').prepend(alertHtml);

        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }

    function showConfirmModal(title, body, callback) {
        $('#confirmModalTitle').text(title);
        $('#confirmModalBody').html(body);
        $('#confirmAction').off('click').on('click', function() {
            $('#confirmModal').modal('hide');
            callback();
        });
        $('#confirmModal').modal('show');
    }

    function restoreBackup(filename) {
        showLoading();

        $.ajax({
            url: '{{ route("backups.restore", ":filename") }}'.replace(':filename', filename),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('success', 'Respaldo restaurado exitosamente');
                    refreshBackupsList();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                hideLoading();
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Error al restaurar el respaldo');
            }
        });
    }

    function deleteBackup(filename) {
        showLoading();

        $.ajax({
            url: '{{ route("backups.destroy", ":filename") }}'.replace(':filename', filename),
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showAlert('success', 'Respaldo eliminado exitosamente');
                    refreshBackupsList();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                hideLoading();
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Error al eliminar el respaldo');
            }
        });
    }

    function executeAutoBackup() {
        Swal.fire({
            title: 'Ejecutando respaldo automático...',
            text: 'Por favor espera mientras se ejecuta el comando',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("backups.create") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                compress: true,
                keep: 7,
                auto_test: true
            },
            success: function(response) {
                Swal.close();
                if (response.success) {
                    Swal.fire({
                        title: '¡Respaldo automático exitoso!',
                        html: `
                            <div class="text-start">
                                <p><strong>Mensaje:</strong> ${response.message}</p>
                                <p><strong>Info:</strong> ${response.info || 'N/A'}</p>
                                <p><strong>Comprimido:</strong> ${response.debug?.compressed ? 'Sí' : 'No'}</p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Actualizar lista'
                    }).then(() => {
                        refreshBackupsList();
                        updateStats();
                    });
                } else {
                    Swal.fire({
                        title: 'Error en respaldo automático',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                const response = xhr.responseJSON;
                Swal.fire({
                    title: 'Error al ejecutar respaldo automático',
                    text: response?.message || 'Error desconocido',
                    icon: 'error'
                });
            }
        });
    }

    function refreshBackupsList() {
        showLoading();

        $.ajax({
            url: '{{ route("backups.list") }}',
            method: 'GET',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    updateBackupsTable(response.backups);
                    updateStats();
                }
            },
            error: function() {
                hideLoading();
                showAlert('error', 'Error al actualizar la lista de respaldos');
            }
        });
    }

    function updateBackupsTable(backups) {
        const tbody = $('#backups-tbody');
        tbody.empty();

        if (backups.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bx bx-data display-4 mb-3"></i>
                            <h6 class="mb-1">No hay respaldos disponibles</h6>
                            <small>Crea tu primer respaldo usando el botón de arriba</small>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        backups.forEach(function(backup) {
            const row = `
                <tr data-filename="${backup.filename}">
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bx bx-file me-2 text-primary"></i>
                            <span class="fw-medium">${backup.filename}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-label-info">${backup.database}</span>
                    </td>
                    <td>
                        <span class="text-muted">${backup.size}</span>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium">${backup.modified}</div>
                            <small class="text-muted">${backup.age}</small>
                        </div>
                    </td>
                    <td>
                        ${backup.compressed ?
                            '<span class="badge bg-label-success"><i class="bx bx-archive me-1"></i>Comprimido</span>' :
                            '<span class="badge bg-label-warning"><i class="bx bx-file me-1"></i>Sin comprimir</span>'
                        }
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item download-backup" href="#" data-filename="${backup.filename}">
                                    <i class="bx bx-download me-1"></i> Descargar
                                </a>
                                <a class="dropdown-item restore-backup" href="#" data-filename="${backup.filename}">
                                    <i class="bx bx-reset me-1"></i> Restaurar
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger delete-backup" href="#" data-filename="${backup.filename}">
                                    <i class="bx bx-trash me-1"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function updateStats() {
        $.ajax({
            url: '{{ route("backups.stats") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const stats = response.stats;
                    $('#total-backups').text(stats.total_backups);
                    $('#total-size').text(stats.total_size);
                    $('#compressed-count').text(stats.compressed_count);

                    if (stats.newest_backup) {
                        const date = new Date(stats.newest_backup);
                        $('#newest-backup').text(date.toLocaleDateString('es-ES'));
                    } else {
                        $('#newest-backup').text('N/A');
                    }
                }
            }
        });
    }

    // Funciones auxiliares
    function showLoading() {
        console.log('Mostrando loading...');
        $('#loading-overlay').removeClass('d-none');
    }

    function hideLoading() {
        console.log('Ocultando loading...');
        $('#loading-overlay').addClass('d-none');
    }

    function showAlert(type, message) {
        console.log('Mostrando alerta:', type, message);

        // Usar SweetAlert2 como en otros módulos
        Swal.fire({
            title: type === 'success' ? 'Éxito' : 'Error',
            text: message,
            icon: type,
            timer: 3000,
            showConfirmButton: false
        });
    }

    function refreshBackupsList() {
        console.log('Actualizando lista de respaldos...');
        $.ajax({
            url: '{{ route("backups.list") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateBackupsTable(response.backups);
                    updateStats();
                }
            },
            error: function(xhr) {
                console.error('Error al actualizar lista:', xhr);
                showAlert('error', 'Error al actualizar la lista de respaldos');
            }
        });
    }

    function showConfirmModal(title, body, callback) {
        Swal.fire({
            title: title,
            html: body,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }
});
</script>
@endsection

@section('page-style')
<style>
#loading-overlay {
    backdrop-filter: blur(2px);
}
</style>
@endsection
