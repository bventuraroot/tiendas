@extends('layouts/layoutMaster')

@section('title', 'Gestión de Contingencias DTE')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endsection

@section('page-style')
<style>
/* Corregir z-index de Select2 en modales */
.select2-container {
    z-index: 9999 !important;
}

.select2-dropdown {
    z-index: 9999 !important;
}

.select2-search--dropdown {
    z-index: 9999 !important;
}

.select2-results {
    z-index: 9999 !important;
}

/* Asegurar que el dropdown aparezca por encima de Bootstrap modals */
.modal .select2-container {
    z-index: 1055 !important;
}

.modal .select2-dropdown {
    z-index: 1055 !important;
}

/* Estilo específico para el modal de borradores */
#crearContingenciaBorradoresModal .select2-container {
    z-index: 9999 !important;
}

#crearContingenciaBorradoresModal .select2-dropdown {
    z-index: 9999 !important;
    position: absolute !important;
}

/* Forzar que el dropdown se muestre dentro del modal */
#crearContingenciaBorradoresModal .select2-dropdown {
    position: fixed !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
}

/* Mejorar la apariencia del dropdown en modales */
.select2-container--bootstrap-5 .select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.select2-container--bootstrap-5 .select2-results__option {
    padding: 0.375rem 0.75rem;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #0d6efd;
    color: white;
}

/* Asegurar que los badges se muestren correctamente */
.select2-container--bootstrap-5 .select2-results__option .badge {
    margin-left: 0.5rem;
    font-size: 0.75em;
}

/* Estilos para DataTable con scroll horizontal - sin agrupación */
#contingenciasTable {
    width: 100% !important;
    table-layout: auto !important;
}

.dataTables_wrapper {
    overflow-x: auto !important;
    overflow-y: hidden !important;
}

/* Desactivar completamente cualquier funcionalidad de agrupación/expansión */
#contingenciasTable tbody tr {
    display: table-row !important;
}

#contingenciasTable tbody tr td {
    display: table-cell !important;
}

/* Ocultar cualquier control de expansión o agrupación */
#contingenciasTable .dtr-control,
#contingenciasTable .control,
#contingenciasTable .dtr-data,
#contingenciasTable .child {
    display: none !important;
}

/* Eliminar cualquier padding o margin que cause expansión */
#contingenciasTable tbody tr.child {
    display: none !important;
}

#contingenciasTable tbody tr.dtr-group {
    display: none !important;
}

/* Mejorar visualización de botones en columna de acciones */
#contingenciasTable td:nth-child(2) {
    min-width: 150px;
    white-space: nowrap;
}

/* Mejorar visualización de vigencia */
#contingenciasTable td:nth-child(6) {
    min-width: 200px;
    white-space: nowrap;
}

/* Mejorar visualización de documentos */
#contingenciasTable td:nth-child(7) {
    min-width: 100px;
    text-align: center;
    white-space: nowrap;
}

/* Asegurar que todas las celdas estén en una sola línea */
#contingenciasTable td {
    white-space: nowrap;
    vertical-align: middle;
}

/* Forzar que todas las filas sean de una sola línea */
#contingenciasTable tbody tr {
    height: auto !important;
}

#contingenciasTable tbody tr > td {
    padding: 0.75rem !important;
}
</style>
@endsection

@section('vendor-script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Mostrar errores con SweetAlert2 si existen
    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error al Crear Contingencia',
            html: `
                <div class="text-start">
                    <p><strong>{{ session('error') }}</strong></p>
                    @if (session('error_details'))
                        @php
                            $errorDetails = session('error_details');
                        @endphp
                        <hr>
                        <h6 class="text-start">Detalles técnicos:</h6>
                        <div class="text-start small">
                            @if (is_array($errorDetails))
                                @foreach ($errorDetails as $key => $value)
                                    <div class="mb-1">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                        @if (is_array($value) || is_object($value))
                                            <pre class="mb-0" style="font-size: 0.8em;">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @else
                                            <span>{{ $value }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <pre class="mb-0" style="font-size: 0.8em;">{{ json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        </div>
                    @endif
                </div>
            `,
            confirmButtonText: 'Entendido',
            width: '700px'
        });
    @endif

    // Mostrar éxito con SweetAlert2 si existe
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif
    // Inicializar DataTable
    $('#contingenciasTable').DataTable({
        responsive: false, // Desactivar responsive para evitar agrupación/expansión
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
            emptyTable: 'No hay contingencias registradas'
        },
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 1 }, // Columna de Acciones no ordenable
            { className: 'text-nowrap', targets: '_all' } // Evitar saltos de línea
        ],
        drawCallback: function(settings) {
            // Si no hay datos, mostrar mensaje personalizado
            if (this.api().data().length === 0) {
                var api = this.api();
                $(api.table().body()).html(
                    '<tr><td colspan="9" class="text-center py-5">' +
                    '<div class="d-flex flex-column align-items-center justify-content-center">' +
                    '<i class="fas fa-inbox fa-3x text-muted mb-3"></i>' +
                    '<h5 class="text-muted mb-2">No hay contingencias registradas</h5>' +
                    '<p class="text-muted mb-3">Comienza creando una nueva contingencia</p>' +
                    '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearContingenciaBorradoresModal">' +
                    '<i class="fas fa-plus me-1"></i>Crear Nueva Contingencia</button>' +
                    '</div></td></tr>'
                );
            }
        }
    });

    // Inicializar Select2
    $('#company, #dte_ids, #company_borradores, #dte_ids_borradores').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione...',
        width: '100%'
    });

    // Configuración específica para Select2 en modales
    $('#company_borradores, #dte_ids_borradores').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione...',
        width: '100%'
    });

    // Función de prueba que se ejecuta inmediatamente
    function pruebaInmediata() {
        console.log('=== PRUEBA INMEDIATA ===');
        console.log('Modal encontrado:', $('#crearContingenciaBorradoresModal').length);
        console.log('Empresa encontrada:', $('#company_borradores').length);
        console.log('Opciones empresa:', $('#company_borradores option').length);

        // Mostrar todas las opciones
        $('#company_borradores option').each(function(index) {
            console.log(`Opción ${index}:`, $(this).val(), $(this).text());
        });

        // Forzar selección de empresa de prueba después de un delay
        setTimeout(function() {
            const empresaPrueba = $('#company_borradores option[value="1"]');
            if (empresaPrueba.length > 0) {
                console.log('Empresa de prueba encontrada:', empresaPrueba.text());
                $('#company_borradores').val('1').trigger('change');
                console.log('Empresa seleccionada:', $('#company_borradores').val());
            } else {
                console.log('Empresa de prueba NO encontrada');
            }
        }, 2000);
    }

    // Ejecutar prueba inmediatamente
    pruebaInmediata();

    // Evento de prueba para Select2 (opcional)
    $('#company_borradores').on('select2:select', function (e) {
        console.log('Select2 select disparado:', e.params.data);
    });

    // Evento cuando se abre el modal de borradores
    $('#crearContingenciaBorradoresModal').on('shown.bs.modal', function () {
        console.log('Modal de borradores abierto');

        // Debug: Verificar elementos
        console.log('Elemento company_borradores encontrado:', $('#company_borradores').length);
        console.log('Opciones en company_borradores:', $('#company_borradores option').length);
        console.log('Primera opción:', $('#company_borradores option:first').text());
        console.log('Segunda opción:', $('#company_borradores option:eq(1)').text());

        // Re-inicializar Select2 para asegurar que funcione
        $('#company_borradores').select2('destroy').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione...',
            width: '100%'
        });

        // Auto-seleccionar la primera empresa disponible (ya que solo hay una)
        const primeraEmpresa = $('#company_borradores option:not(:first)').first();
        console.log('Primera empresa encontrada:', primeraEmpresa.length, primeraEmpresa.text());

        if (primeraEmpresa.length > 0) {
            const empresaId = primeraEmpresa.val();
            $('#company_borradores').val(empresaId).trigger('change');
            console.log('Empresa auto-seleccionada:', empresaId, primeraEmpresa.text());
        }

        // Cargar documentos automáticamente sin necesidad de empresa_id
        cargarBorradoresParaContingencia();
    });

    // Función para cargar borradores para contingencia
    function cargarBorradoresParaContingencia(empresaId = null) {
        const incluirBorradores = $('#incluir_borradores_modal').is(':checked');
        const select = $('#dte_ids_borradores');

        // Si no se proporciona empresaId, usar el valor del dropdown o no enviarlo
        if (!empresaId) {
            empresaId = $('#company_borradores').val();
        }

        console.log('Cargando borradores para empresa:', empresaId, 'incluir borradores:', incluirBorradores);

        // Mostrar indicador de carga
        select.empty();
        select.append('<option value="">Cargando documentos...</option>');
        select.prop('disabled', true);

        // Construir parámetros de la petición
        const params = {
            incluir_borradores: incluirBorradores
        };

        // Solo agregar empresa_id si existe
        if (empresaId) {
            params.empresa_id = empresaId;
        }
        $.get('{{ route("dte.dtes-para-contingencia") }}', params)
            .done(function(data) {
                console.log('Borradores cargados:', data);
                console.log('Elemento select encontrado:', select.length);
                console.log('Select ID:', select.attr('id'));

                select.empty();
                select.prop('disabled', false);

                if (data && data.length > 0) {
                    select.append('<option value="">Seleccione documentos...</option>');
                    console.log('Agregando', data.length, 'documentos al dropdown');

                    // Filtrar solo documentos relevantes (sin N/A)
                    const documentosFiltrados = data.filter(function(doc) {
                        return doc.cliente && doc.cliente !== 'N/A' &&
                               doc.tipo_documento && doc.tipo_documento !== 'N/A';
                    });

                    console.log(`Documentos filtrados: ${documentosFiltrados.length} de ${data.length}`);

                    documentosFiltrados.forEach(function(doc, index) {
                        console.log(`Documento ${index}:`, doc);

                        let estadoBadge = '';

                        if (doc.estado === 'Necesita Contingencia') {
                            estadoBadge = '<span class="badge bg-danger">Necesita</span>';
                        } else if (doc.estado === 'En Cola (Borrador)') {
                            estadoBadge = '<span class="badge bg-warning">En Cola</span>';
                        } else if (doc.estado === 'Rechazado (Borrador)') {
                            estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                        } else if (doc.estado === 'En Revisión (Borrador)') {
                            estadoBadge = '<span class="badge bg-info">En Revisión</span>';
                        } else if (doc.estado === 'Sin DTE (Borrador)') {
                            estadoBadge = '<span class="badge bg-secondary">Sin DTE</span>';
                        } else if (doc.estado === 'Con DTE (Borrador)') {
                            estadoBadge = '<span class="badge bg-primary">Con DTE</span>';
                        } else if (doc.estado === 'Borrador') {
                            estadoBadge = '<span class="badge bg-warning">Borrador</span>';
                        }

                        const numeroControl = doc.numero_control || doc.id;
                        const optionHtml = `<option value="${doc.id}">
                            ${numeroControl} - ${doc.cliente} (${doc.tipo_documento}) ${estadoBadge}
                        </option>`;

                        console.log(`Agregando opción ${index}:`, optionHtml);
                        select.append(optionHtml);
                    });

                    console.log('Total opciones después de agregar:', select.find('option').length);

                    // Reinicializar Select2 después de agregar opciones
                    console.log('Reinicializando Select2...');
                    select.select2('destroy').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Seleccione documentos...',
                        allowClear: true,
                        width: '100%'
                    });
                    console.log('Select2 reinicializado');
                } else {
                    select.append('<option value="" disabled>No hay documentos disponibles</option>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error al cargar documentos:', xhr.responseText);
                select.empty();
                select.prop('disabled', false);

                let errorMessage = 'Error al cargar documentos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.status === 500) {
                    errorMessage = 'Error de conexión a la base de datos';
                }

                select.append(`<option value="" disabled>${errorMessage}</option>`);

                // Mostrar alerta
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
    }

    // Cargar DTEs para contingencia
    $('#company').change(function() {
        const empresaId = $(this).val();
        const incluirBorradores = $('#incluir_borradores').is(':checked');
        const select = $('#dte_ids');

        if (empresaId) {
            // Mostrar indicador de carga
            select.empty();
            select.append('<option value="">Cargando documentos...</option>');
            select.prop('disabled', true);

            $.get('{{ route("dte.dtes-para-contingencia") }}', {
                empresa_id: empresaId,
                incluir_borradores: incluirBorradores
            })
                .done(function(data) {
                    select.empty();
                    select.prop('disabled', false);

                    if (data && data.length > 0) {
                        select.append('<option value="">Seleccione documentos...</option>');

                        data.forEach(function(doc) {
                            let estadoBadge = '';

                            if (doc.estado === 'Necesita Contingencia') {
                                estadoBadge = '<span class="badge bg-danger">Necesita</span>';
                            } else if (doc.estado === 'En Cola (Borrador)') {
                                estadoBadge = '<span class="badge bg-warning">En Cola</span>';
                            } else if (doc.estado === 'Rechazado (Borrador)') {
                                estadoBadge = '<span class="badge bg-danger">Rechazado</span>';
                            } else if (doc.estado === 'En Revisión (Borrador)') {
                                estadoBadge = '<span class="badge bg-info">En Revisión</span>';
                            } else if (doc.estado === 'Sin DTE (Borrador)') {
                                estadoBadge = '<span class="badge bg-secondary">Sin DTE</span>';
                            } else if (doc.estado.includes('Con DTE')) {
                                const estado = doc.estado.replace('Con DTE (', '').replace(')', '');
                                estadoBadge = `<span class="badge bg-primary">Con DTE (${estado})</span>`;
                            } else {
                                estadoBadge = '<span class="badge bg-info">Borrador</span>';
                            }

                            select.append(`<option value="${doc.id}">
                                ${doc.numero_control} - ${doc.cliente} (${doc.tipo_documento}) ${estadoBadge}
                            </option>`);
                        });
                    } else {
                        select.append('<option value="" disabled>No hay documentos disponibles</option>');
                    }
                })
                .fail(function(xhr, status, error) {
                    select.empty();
                    select.prop('disabled', false);

                    let errorMessage = 'Error al cargar documentos';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Ruta no encontrada. Verifique que la ruta esté configurada correctamente.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error interno del servidor. Revise los logs.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Error de conexión. Verifique su conexión a internet.';
                    }

                    select.append(`<option value="" disabled>${errorMessage}</option>`);

                    console.error('Error al cargar documentos para contingencia:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });

                    // Mostrar alerta al usuario
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
        } else {
            select.empty();
            select.append('<option value="">Seleccione empresa primero...</option>');
            select.prop('disabled', true);
        }
    });

    // Toggle para incluir borradores
    $('#incluir_borradores').change(function() {
        const empresaId = $('#company').val();
        if (empresaId) {
            $('#company').trigger('change');
        }
    });

    // Evento de cambio para empresa en modal de borradores
    $('#company_borradores').on('change', function() {
        const empresaId = $(this).val();
        if (empresaId) {
            cargarBorradoresParaContingencia(empresaId);
        }
    });

    // Toggle para incluir borradores en modal específico
    $('#incluir_borradores_modal').change(function() {
        const empresaId = $('#company_borradores').val();
        if (empresaId) {
            cargarBorradoresParaContingencia(empresaId);
        }
    });

    // Aprobar contingencia
    $('.aprobar-contingencia').click(function() {
        const contingenciaId = $(this).data('contingencia-id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: 'Aprobar Contingencia',
            text: `¿Está seguro de aprobar la contingencia "${nombre}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dte/contingencias/${contingenciaId}/aprobar`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contingencia aprobada!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión'
                    });
                });
            }
        });
    });

    // Activar contingencia
    $('.activar-contingencia').click(function() {
        const contingenciaId = $(this).data('contingencia-id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: 'Activar Contingencia',
            text: `¿Está seguro de activar la contingencia "${nombre}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dte/contingencias/${contingenciaId}/activar`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contingencia activada!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión'
                    });
                });
            }
        });
        });
    });

    // Función para confirmar autorización de contingencia
    function confirmarAutorizacion(contingenciaId, empresaId) {
        Swal.fire({
            title: '¿Autorizar Contingencia?',
            html: `
                <div class="text-start">
                    <p><strong>¿Está seguro de autorizar esta contingencia?</strong></p>
                    <p class="text-muted">Esta acción:</p>
                    <ul class="text-muted text-start">
                        <li>Procesará todas las ventas en borrador asignadas</li>
                        <li>Generará los DTEs correspondientes</li>
                        <li>Creará el JSON de contingencia</li>
                        <li>Enviará el documento a Hacienda para su aprobación</li>
                    </ul>
                    <div class="alert alert-warning mt-3">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Esta acción no se puede deshacer
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-send me-1"></i>Sí, Autorizar',
            cancelButtonText: '<i class="ti ti-x me-1"></i>Cancelar',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de progreso
                Swal.fire({
                    title: 'Procesando Contingencia...',
                    html: `
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="text-muted">Procesando ventas y enviando a Hacienda...</p>
                            <small class="text-warning">Este proceso puede tomar varios minutos</small>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Redirigir a la URL de autorización (método simple como RomaCopies)
                window.location.href = `{{ route('dte.autorizar-contingencia-get', ['empresa' => ':empresa', 'id' => ':id']) }}`
                    .replace(':empresa', empresaId)
                    .replace(':id', contingenciaId);
            }
        });
    }

    /**
     * Mostrar errores de una contingencia específica
     */
    function verErroresContingencia(contingenciaId) {
        Swal.fire({
            title: 'Cargando errores...',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted">Obteniendo información de errores...</p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `{{ route('dte.errores-contingencia', ['id' => ':id']) }}`.replace(':id', contingenciaId),
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    mostrarErroresModal(response);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al obtener errores'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error al obtener errores de contingencia';
                let errorDetails = null;

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    if (response.error_details) {
                        errorDetails = response.error_details;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                }

                let errorHtml = `
                    <div class="text-start">
                        <p><strong>Error:</strong> ${errorMessage}</p>
                `;

                if (errorDetails) {
                    errorHtml += `<hr><h6 class="text-start">Detalles técnicos:</h6><div class="text-start small">`;

                    if (errorDetails.sql_message) {
                        errorHtml += `<div class="mb-2"><strong>SQL Error:</strong> ${errorDetails.sql_message}</div>`;
                    }
                    if (errorDetails.sql_code) {
                        errorHtml += `<div class="mb-2"><strong>Código SQL:</strong> ${errorDetails.sql_code}</div>`;
                    }
                    if (errorDetails.query) {
                        errorHtml += `<div class="mb-2"><strong>Query:</strong> <pre class="small">${errorDetails.query}</pre></div>`;
                    }
                    if (errorDetails.file) {
                        errorHtml += `<div class="mb-2"><strong>Archivo:</strong> ${errorDetails.file}:${errorDetails.line}</div>`;
                    }
                    if (errorDetails.trace) {
                        errorHtml += `<details class="mt-2"><summary>Stack Trace</summary><pre class="small text-start">${errorDetails.trace}</pre></details>`;
                    }

                    errorHtml += `</div>`;
                } else {
                    errorHtml += `
                        <hr>
                        <p><strong>Información técnica:</strong></p>
                        <small>
                            Status: ${xhr.status}<br>
                            URL: ${xhr.responseURL}<br>
                            Timestamp: ${new Date().toLocaleString()}
                        </small>
                    `;
                }

                errorHtml += `</div>`;

                Swal.fire({
                    icon: 'error',
                    title: 'Error al Obtener Errores',
                    html: errorHtml,
                    width: '800px',
                    confirmButtonText: 'Cerrar'
                });

                console.error('Error obteniendo errores:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    errorDetails: errorDetails
                });
            }
        });
    }

    /**
     * Ver detalle completo de la contingencia
     */
    function verDetalleContingencia(contingenciaId) {
        // Obtener datos de la contingencia desde la tabla
        const row = $(`tr:has(td:first-child:contains('${contingenciaId}'))`);

        // Construir HTML con los detalles
        let detalleHtml = `
            <div class="text-start">
                <h6 class="mb-3">Información de la Contingencia</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${contingenciaId}</p>
                        <p><strong>Motivo:</strong> ${row.find('td:eq(2) strong').text() || 'N/A'}</p>
                        <p><strong>Descripción:</strong> ${row.find('td:eq(2) small').text() || 'N/A'}</p>
                        <p><strong>Empresa:</strong> ${row.find('td:eq(3)').text() || 'N/A'}</p>
                        <p><strong>Tipo:</strong> ${row.find('td:eq(4) .badge').text() || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Estado:</strong> ${row.find('td:eq(7) .badge').text() || 'N/A'}</p>
                        <p><strong>Creado por:</strong> ${row.find('td:eq(8)').text() || 'N/A'}</p>
                        <p><strong>Vigencia:</strong><br>
                            ${row.find('td:eq(5)').html() || 'N/A'}
                        </p>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <button class="btn btn-primary" onclick="verDocumentosContingencia(${contingenciaId})">
                        <i class="fas fa-file-alt me-1"></i>
                        Ver Documentos Relacionados
                    </button>
                    <button class="btn btn-warning ms-2" onclick="verErroresContingencia(${contingenciaId})">
                        <i class="ti ti-alert-triangle me-1"></i>
                        Ver Errores
                    </button>
                </div>
            </div>
        `;

        Swal.fire({
            title: `Detalle de Contingencia #${contingenciaId}`,
            html: detalleHtml,
            width: '800px',
            showConfirmButton: true,
            confirmButtonText: 'Cerrar'
        });
    }

    /**
     * Ver documentos relacionados con la contingencia
     */
    function verDocumentosContingencia(contingenciaId) {
        Swal.fire({
            title: 'Cargando documentos...',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted">Obteniendo documentos relacionados...</p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `{{ route('dte.documentos-contingencia') }}`,
            method: 'GET',
            data: { contingencia_id: contingenciaId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.documentos) {
                    mostrarDocumentosModal(response.documentos, contingenciaId);
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin documentos',
                        text: response.message || 'No se encontraron documentos relacionados con esta contingencia'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'No se pudieron cargar los documentos';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Usar mensaje por defecto
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    }

    /**
     * Mostrar modal con documentos relacionados
     */
    function mostrarDocumentosModal(documentos, contingenciaId) {
        let documentosHtml = '';

        if (documentos.length === 0) {
            documentosHtml = '<p class="text-muted text-center">No hay documentos relacionados</p>';
        } else {
            documentosHtml = '<div class="table-responsive"><table class="table table-sm table-hover table-striped">';
            documentosHtml += '<thead><tr><th>Tipo</th><th>ID</th><th>Número Control</th><th>Cliente</th><th>Tipo Documento</th><th>Fecha</th><th>Estado</th></tr></thead><tbody>';

            documentos.forEach(function(doc) {
                const estadoClass = doc.estado === 'Borrador' ? 'bg-warning' :
                                   doc.estado === 'Enviado' ? 'bg-success' :
                                   doc.estado === 'Rechazado' ? 'bg-danger' : 'bg-info';

                documentosHtml += `
                    <tr>
                        <td><span class="badge bg-primary">${doc.tipo || 'N/A'}</span></td>
                        <td>${doc.id || 'N/A'}</td>
                        <td>${doc.numero_control || 'N/A'}</td>
                        <td>${doc.cliente || 'N/A'}</td>
                        <td>${doc.tipo_documento || 'N/A'}</td>
                        <td>${doc.fecha || 'N/A'}</td>
                        <td><span class="badge ${estadoClass}">${doc.estado || 'N/A'}</span></td>
                    </tr>
                `;
            });

            documentosHtml += '</tbody></table></div>';
            documentosHtml += `<div class="mt-3 text-center"><strong>Total: ${documentos.length} documento(s)</strong></div>`;
        }

        Swal.fire({
            title: `Documentos de Contingencia #${contingenciaId}`,
            html: documentosHtml,
            width: '900px',
            showConfirmButton: true,
            confirmButtonText: 'Cerrar'
        });
    }

    /**
     * Mostrar modal con errores detallados
     */
    function mostrarErroresModal(response) {
        const contingencia = response.contingencia;
        const errores = response.errores;
        const totalErrores = response.total_errores;

        let erroresHtml = '';

        if (errores.length === 0) {
            erroresHtml = `
                <div class="text-center text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <p>No se encontraron errores para esta contingencia</p>
                </div>
            `;
        } else {
            erroresHtml = `
                <div class="accordion" id="erroresAccordion">
            `;

            errores.forEach((error, index) => {
                const severidadClass = error.severidad === 'error' ? 'danger' : 'warning';
                const iconClass = error.severidad === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-exclamation-triangle';

                erroresHtml += `
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading${index}">
                            <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse${index}"
                                    aria-expanded="false" aria-controls="collapse${index}">
                                <i class="${iconClass} text-${severidadClass} me-2"></i>
                                <strong>${error.tipo}</strong>
                                ${error.venta_id ? ` - Venta #${error.venta_id}` : ''}
                                ${error.dte_id ? ` - DTE #${error.dte_id}` : ''}
                                <span class="badge bg-${severidadClass} ms-2">${error.severidad}</span>
                            </button>
                        </h2>
                        <div id="collapse${index}" class="accordion-collapse collapse"
                             aria-labelledby="heading${index}" data-bs-parent="#erroresAccordion">
                            <div class="accordion-body">
                                <p><strong>Mensaje:</strong></p>
                                <p class="text-muted">${error.mensaje}</p>
                                <hr>
                                <p><strong>Fecha:</strong> ${new Date(error.fecha).toLocaleString()}</p>
                            </div>
                        </div>
                    </div>
                `;
            });

            erroresHtml += '</div>';
        }

        Swal.fire({
            title: `Errores de Contingencia #${contingencia.id}`,
            html: `
                <div class="text-start">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><strong>Información de Contingencia:</strong></h6>
                            <p><strong>Nombre:</strong> ${contingencia.nombre || 'N/A'}</p>
                            <p><strong>Estado:</strong> <span class="badge bg-info">${contingencia.estado || 'N/A'}</span></p>
                            <p><strong>Estado Hacienda:</strong> ${contingencia.estado_hacienda || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Detalles:</strong></h6>
                            <p><strong>Sello Recibido:</strong> ${contingencia.sello_recibido ? 'Sí' : 'No'}</p>
                            <p><strong>Creada:</strong> ${new Date(contingencia.fecha_creacion).toLocaleString()}</p>
                            <p><strong>Actualizada:</strong> ${new Date(contingencia.fecha_actualizacion).toLocaleString()}</p>
                        </div>
                    </div>

                    <hr>

                    <h6><strong>Errores Encontrados (${totalErrores}):</strong></h6>
                    ${erroresHtml}
                </div>
            `,
            width: '800px',
            showConfirmButton: true,
            confirmButtonText: 'Cerrar',
            customClass: {
                popup: 'swal-wide'
            }
        });
    }
</script>

<style>
    .swal-wide {
        width: 600px !important;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if (session('success') || session('warning') || session('error'))
    <div class="mb-3">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i>
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif
      @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          {{ session('warning') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <strong>Error:</strong> {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('estado_hacienda'))
        <div class="small text-muted">Estado Hacienda: {{ session('estado_hacienda') }}</div>
      @endif

      @if (session('observaciones_list') && count(session('observaciones_list')) > 0)
        <ul class="mt-2 small text-muted">
          @foreach (session('observaciones_list') as $obs)
            <li>{{ $obs }}</li>
          @endforeach
        </ul>
      @endif

      @if (session('error_details'))
        @php
          $errorDetails = session('error_details');
        @endphp
        <div class="alert alert-danger mt-2">
          <h6 class="alert-heading"><i class="fas fa-bug me-2"></i>Detalles del Error:</h6>
          <hr>
          @if (is_array($errorDetails))
            @foreach ($errorDetails as $key => $value)
              <div class="mb-2">
                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                @if (is_array($value) || is_object($value))
                  <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                  <span class="text-break">{{ $value }}</span>
                @endif
              </div>
            @endforeach
          @else
            <pre class="mb-0 small">{{ json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          @endif
        </div>
      @endif
    </div>
  @endif
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="py-3 mb-0 fw-bold">
                    <i class="fas fa-shield-alt me-2"></i>
                    Gestión de Contingencias DTE
                </h4>
                <div class="gap-2 d-flex">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearContingenciaModal">
                        <i class="fas fa-plus me-1"></i>
                        Nueva Contingencia
                    </button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#crearContingenciaBorradoresModal">
                        <i class="fas fa-file-alt me-1"></i>
                        Contingencia con Borradores
                    </button>
                    <a href="{{ route('dte.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-filter me-2"></i>
                        Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('dte.contingencias') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" {{ ($filtros['estado'] ?? '') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="aprobada" {{ ($filtros['estado'] ?? '') == 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                                <option value="activa" {{ ($filtros['estado'] ?? '') == 'activa' ? 'selected' : '' }}>Activa</option>
                                <option value="finalizada" {{ ($filtros['estado'] ?? '') == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                                <option value="cancelada" {{ ($filtros['estado'] ?? '') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="empresa_id" class="form-label">Empresa</label>
                            <select name="empresa_id" id="empresa_id" class="form-select">
                                <option value="">Todas las empresas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ ($filtros['empresa_id'] ?? '') == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select name="tipo" id="tipo" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="tecnica" {{ ($filtros['tipo'] ?? '') == 'tecnica' ? 'selected' : '' }}>Técnica</option>
                                <option value="administrativa" {{ ($filtros['tipo'] ?? '') == 'administrativa' ? 'selected' : '' }}>Administrativa</option>
                                <option value="emergencia" {{ ($filtros['tipo'] ?? '') == 'emergencia' ? 'selected' : '' }}>Emergencia</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="incluir_borradores_filtro" class="form-label">Mostrar Borradores</label>
                            <select name="incluir_borradores_filtro" id="incluir_borradores_filtro" class="form-select">
                                <option value="">Solo contingencias</option>
                                <option value="1" {{ ($filtros['incluir_borradores_filtro'] ?? '') == '1' ? 'selected' : '' }}>Incluir borradores</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('dte.contingencias') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de contingencias -->
    <div class="mb-4 row">
        <div class="mb-4 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Contingencias</h6>
                            <h3 class="mb-0">{{ $contingencias->total() }}</h3>
                        </div>
                        <div class="rounded avatar avatar-md bg-secondary">
                            <i class="text-white fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Activas</h6>
                            <h3 class="mb-0 text-success">{{ $contingencias->where('estado', 'activa')->count() }}</h3>
                        </div>
                        <div class="rounded avatar avatar-md bg-success">
                            <i class="text-white fas fa-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Pendientes</h6>
                            <h3 class="mb-0 text-warning">{{ $contingencias->where('estado', 'pendiente')->count() }}</h3>
                        </div>
                        <div class="rounded avatar avatar-md bg-warning">
                            <i class="text-white fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Vigentes</h6>
                            <h3 class="mb-0 text-info">{{ $contingencias->filter(function($contingencia) { return $contingencia->isVigente(); })->count() }}</h3>
                        </div>
                        <div class="rounded avatar avatar-md bg-info">
                            <i class="text-white fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de contingencias -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-list me-2"></i>
                        Lista de Contingencias
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto; overflow-y: hidden;">
                        <table id="contingenciasTable" class="table table-striped table-hover align-middle text-nowrap" style="width: 100%; table-layout: auto;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Acciones</th>
                                    <th>Nombre</th>
                                    <th>Empresa</th>
                                    <th>Tipo</th>
                                    <th>Vigencia</th>
                                    <th>Documentos</th>
                                    <th>Estado</th>
                                    <th>Creado por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contingencias as $contingencia)
                                <tr>
                                    <td>{{ $contingencia->id }}</td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            @php
                                                // Determinar si se puede autorizar/reintentar
                                                $puedeAutorizar = false;
                                                $puedeReintentar = false;
                                                $estadoActual = $contingencia->estado ?? null;
                                                $codEstado = $contingencia->codEstado ?? null;
                                                $tieneSello = !empty($contingencia->selloRecibido);
                                                $tieneErrores = !empty($contingencia->observacionesMsg) || !empty($contingencia->estadoHacienda);

                                                // Puede autorizar si está en cola o pendiente y no tiene sello
                                                if (($estadoActual === 'En Cola' || $estadoActual === 'pendiente') && !$tieneSello) {
                                                    $puedeAutorizar = true;
                                                }

                                                // Puede reintentar si:
                                                // 1. Está rechazada
                                                // 2. Tiene errores y no está procesada
                                                // 3. CodEstado es 03 (rechazado)
                                                // 4. Está en cola pero tiene errores
                                                if ($estadoActual === 'Rechazado' ||
                                                    $codEstado === '03' ||
                                                    ($tieneErrores && $estadoActual !== 'Enviado' && !$tieneSello) ||
                                                    ($estadoActual === 'En Cola' && $tieneErrores)) {
                                                    $puedeReintentar = true;
                                                }
                                            @endphp

                                            @if($estadoActual === 'Enviado' && $tieneSello)
                                                <span class="badge bg-success mb-1">Procesada</span>
                                            @elseif($puedeReintentar)
                                                <button onclick="confirmarAutorizacion({{ $contingencia->id }}, {{ $contingencia->empresa_id ?? $contingencia->idEmpresa ?? 1 }})"
                                                        class="btn btn-sm {{ $estadoActual === 'Rechazado' || $codEstado === '03' ? 'btn-outline-danger' : 'btn-warning' }} mb-1"
                                                        title="Reintentar autorización y envío">
                                                    <i class="ti ti-refresh me-1"></i>
                                                    {{ $estadoActual === 'Rechazado' || $codEstado === '03' ? 'Reintentar' : 'Reenviar' }}
                                                </button>
                                            @elseif($puedeAutorizar)
                                                <button onclick="confirmarAutorizacion({{ $contingencia->id }}, {{ $contingencia->empresa_id ?? $contingencia->idEmpresa ?? 1 }})"
                                                        class="btn btn-sm btn-success mb-1"
                                                        title="Autorizar y Enviar a Hacienda">
                                                    <i class="ti ti-send me-1"></i>
                                                    Autorizar
                                                </button>
                                            @else
                                                <span class="badge bg-secondary mb-1">{{ $estadoActual ?? 'Desconocido' }}</span>
                                                @if(!$tieneSello && $estadoActual !== 'Enviado')
                                                    <button onclick="confirmarAutorizacion({{ $contingencia->id }}, {{ $contingencia->empresa_id ?? $contingencia->idEmpresa ?? 1 }})"
                                                            class="btn btn-sm btn-outline-primary mb-1"
                                                            title="Intentar autorizar nuevamente">
                                                        <i class="ti ti-refresh me-1"></i>
                                                        Reintentar
                                                    </button>
                                                @endif
                                            @endif

                                            @if($tieneErrores)
                                                <button class="btn btn-sm btn-outline-warning mb-1"
                                                        onclick="verErroresContingencia({{ $contingencia->id }})"
                                                        title="Ver errores y detalles">
                                                    <i class="ti ti-alert-triangle"></i>
                                                    Errores
                                                </button>
                                            @endif

                                            <button class="btn btn-sm btn-outline-info"
                                                    onclick="verDetalleContingencia({{ $contingencia->id }})"
                                                    title="Ver detalles completos">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $contingencia->motivoContingencia ?? 'Sin motivo' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($contingencia->descripcionMsg ?? 'Sin descripción', 50) }}</small>
                                    </td>
                                    <td>{{ $contingencia->empresa->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $tipos = [
                                                1 => 'No disponibilidad de sistema del MH',
                                                2 => 'No disponibilidad de sistema del emisor',
                                                3 => 'Falla de Internet del emisor',
                                                4 => 'Falla de energía eléctrica del emisor',
                                                5 => 'Otro'
                                            ];
                                            $tipoTexto = $tipos[$contingencia->tipoContingencia ?? 0] ?? 'Desconocido';
                                        @endphp
                                        <span class="badge bg-label-primary">{{ $tipoTexto }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $fechaInicio = $contingencia->fInicio ?? null;
                                            $fechaFin = $contingencia->fFin ?? null;
                                            $horaInicio = $contingencia->hInicio ?? null;
                                            $horaFin = $contingencia->hFin ?? null;
                                            $esVigente = $contingencia->isVigente();
                                        @endphp
                                        <div class="small">
                                            @if($fechaInicio)
                                                <div class="mb-1">
                                                    <strong>Inicio:</strong><br>
                                                    {{ $fechaInicio->format('d/m/Y') }}
                                                    @if($horaInicio)
                                                        {{ $horaInicio }}
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-1 text-muted">Inicio: N/A</div>
                                            @endif
                                            @if($fechaFin)
                                                <div>
                                                    <strong>Fin:</strong><br>
                                                    {{ $fechaFin->format('d/m/Y') }}
                                                    @if($horaFin)
                                                        {{ $horaFin }}
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-muted">Fin: N/A</div>
                                            @endif
                                            @if($esVigente)
                                                <span class="mt-1 badge bg-success">Vigente</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $totalDocumentos = ($contingencia->dtes->count() ?? 0) + ($contingencia->ventas->count() ?? 0);
                                            $dtes = $contingencia->dtes ?? collect();
                                            $ventas = $contingencia->ventas ?? collect();
                                        @endphp
                                        @if($totalDocumentos > 0)
                                            <button class="btn btn-sm btn-info"
                                                    onclick="verDocumentosContingencia({{ $contingencia->id }})"
                                                    title="Ver documentos relacionados">
                                                <i class="fas fa-file-alt me-1"></i>
                                                {{ $totalDocumentos }}
                                            </button>
                                        @else
                                            <span class="badge bg-secondary">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $estadoVista = $contingencia->estado ?? null;
                                            // Si no hay estado de app, intentar con estadoHacienda
                                            if (!$estadoVista && !empty($contingencia->estadoHacienda)) {
                                                $estadoVista = $contingencia->estadoHacienda;
                                            }
                                        @endphp
                                        @switch($estadoVista)
                                            @case('En Cola')
                                                <span class="badge bg-label-secondary">En cola</span>
                                                @break
                                            @case('Enviado')
                                            @case('RECIBIDO')
                                                <span class="badge bg-label-success">Procesada</span>
                                                @break
                                            @case('Rechazado')
                                            @case('RECHAZADO')
                                                <span class="badge bg-label-danger">Rechazada</span>
                                                @break
                                            @default
                                                <span class="badge bg-label-light">Desconocido</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $contingencia->creador->name ?? 'Sistema' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5" style="white-space: nowrap;">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted mb-2">No hay contingencias registradas</h5>
                                            <p class="text-muted mb-3">Comienza creando una nueva contingencia</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearContingenciaBorradoresModal">
                                                <i class="fas fa-plus me-1"></i>
                                                Crear Nueva Contingencia
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $contingencias->appends($filtros)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Contingencia -->
<div class="modal fade" id="crearContingenciaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Contingencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dte.crear-contingencia') }}" method="POST">
                @csrf
                <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="company" class="form-label">Empresa *</label>
                        <select name="company" id="company" class="form-select" required>
                            <option value="">Seleccione empresa...</option>
                            @if(isset($empresas) && $empresas->count() > 0)
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">{{ $empresa->name }}</option>
                                @endforeach
                            @endif
                            @if(!isset($empresas) || $empresas->count() == 0)
                                <option value="" disabled>No hay empresas disponibles en BD</option>
                            @endif
                        </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="versionJson">Version Json</label>
                            <input type="text" id="versionJson" name="versionJson" class="form-control" placeholder="Version Json" value="3" readonly/>
                        </div>
                        <div class="col-md-6">
                            <label for="ambiente" class="form-label">Ambiente</label>
                            <select class="form-select" id="ambiente" name="ambiente" aria-label="Seleccionar opcion">
                                <option value="00">Prueba</option>
                                <option value="01">Produccion</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="tipoContingencia" class="form-label">Tipo Contingencia</label>
                            <select class="form-select" id="tipoContingencia" name="tipoContingencia" aria-label="Seleccionar opcion" required>
                                <option value="1">No disponibilidad de sistema del MH</option>
                                <option value="2">No disponibilidad de sistema del emisor</option>
                                <option value="3">Falla en el suministro de servicio de Internet del Emisor</option>
                                <option value="4">Falla en el suministro de servicio de energia eléctrica del emisor que impida la transmisión de los DTE</option>
                                <option value="5">Otro (deberá digitar un máximo de 500 caracteres explicando el motivo)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="motivoContingencia">Motivo Contingencia</label>
                            <input type="text" id="motivoContingencia" class="form-control" aria-label="Direccion" name="motivoContingencia" required/>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="nombreResponsable">Nombre del Responsable</label>
                            <input type="text" id="nombreResponsable" class="form-control" aria-label="Direccion" name="nombreResponsable" required/>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="tipoDocResponsable">Tipo Documento</label>
                            <select class="form-select" id="tipoDocResponsable" name="tipoDocResponsable" aria-label="Seleccionar opcion" required>
                                <option value="36">NIT</option>
                                <option value="13">DUI</option>
                                <option value="37">Otro</option>
                                <option value="03">Pasaporte</option>
                                <option value="02">Carnet de Residente</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="nuDocResponsable">Numero de Documento</label>
                            <input type="text" id="nuDocResponsable" onkeyup="nitDuiMask(this);" class="form-control" name="nuDocResponsable" required/>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fechaCreacion">Fecha y Hora Creación</label>
                            <input type="datetime-local" id="fechaCreacion" class="form-control" value="{{Now()}}" maxlength="15" aria-label="fechaCreacion" name="fechaCreacion" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fechaInicioFin">Fecha y Hora Inicio</label>
                            <input type="datetime-local" id="fechaInicioFin" class="form-control" value="{{Now()}}" maxlength="15" aria-label="fechaInicioFin" name="fechaInicioFin" />
                        </div>
                        <div class="col-12">
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="incluir_borradores" name="incluir_borradores">
                                <label class="form-check-label" for="incluir_borradores">
                                    <strong>Incluir documentos en borrador</strong>
                                    <br>
                                    <small class="text-muted">Incluir todos los borradores: DTEs en cola, rechazados, en revisión y ventas sin DTE o con DTE en borrador</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="dte_ids" class="form-label">Documentos Afectados</label>
                            <select name="dte_ids[]" id="dte_ids" class="form-select" multiple disabled>
                                <option value="">Seleccione empresa primero...</option>
                            </select>
                            <div class="form-text">
                                <strong>Instrucciones:</strong>
                                <ol class="mb-2">
                                    <li>Primero seleccione una empresa</li>
                                    <li>Los documentos disponibles se cargarán automáticamente</li>
                                    <li>Seleccione los documentos que serán incluidos en esta contingencia</li>
                                </ol>
                                <div class="flex-wrap gap-2 d-flex">
                                    <span class="badge bg-danger">Necesita</span> = DTE rechazado que necesita contingencia
                                    <span class="badge bg-warning">En Cola</span> = DTE en cola (pendiente de envío)
                                    <span class="badge bg-danger">Rechazado</span> = DTE rechazado (borrador)
                                    <span class="badge bg-info">En Revisión</span> = DTE en revisión (borrador)
                                    <span class="badge bg-secondary">Sin DTE</span> = Venta sin DTE generado
                                    <span class="badge bg-primary">Con DTE</span> = Venta con DTE en borrador
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Crear Contingencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Contingencia con Borradores -->
<div class="modal fade" id="crearContingenciaBorradoresModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i>
                    Contingencia con Documentos en Borrador
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dte.crear-contingencia') }}" method="POST">
                @csrf
                <input type="hidden" name="iduser" id="iduser_borradores" value="{{Auth::user()->id}}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Contingencia con Borradores:</strong> Esta opción te permite crear una contingencia incluyendo documentos que están en estado borrador (DTEs en cola y ventas sin DTE generado).
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="versionJson_borradores">Version Json</label>
                            <input type="text" id="versionJson_borradores" name="versionJson" class="form-control" placeholder="Version Json" value="3" readonly/>
                        </div>
                        <div class="col-md-6">
                            <label for="ambiente_borradores" class="form-label">Ambiente</label>
                            <select class="form-select" id="ambiente_borradores" name="ambiente" aria-label="Seleccionar opcion">
                                <option value="00">Prueba</option>
                                <option value="01">Produccion</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="company_borradores" class="form-label">Empresa *</label>
                            <select name="company" id="company_borradores" class="form-select" required>
                                <option value="">Seleccione empresa...</option>
                                @if(isset($empresas) && $empresas->count() > 0)
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}">{{ $empresa->name }}</option>
                                    @endforeach
                                @endif
                                @if(!isset($empresas) || $empresas->count() == 0)
                                    <option value="" disabled>No hay empresas disponibles en BD</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fechaCreacion_borradores">Fecha y Hora Creación</label>
                            <input type="datetime-local" id="fechaCreacion_borradores" class="form-control" value="{{Now()}}" maxlength="15" aria-label="fechaCreacion" name="fechaCreacion" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fechaInicioFin_borradores">Fecha y Hora Inicio</label>
                            <input type="datetime-local" id="fechaInicioFin_borradores" class="form-control" value="{{Now()}}" maxlength="15" aria-label="fechaInicioFin" name="fechaInicioFin" />
                        </div>
                        <div class="col-md-12">
                            <label for="tipoContingencia_borradores" class="form-label">Tipo Contingencia</label>
                            <select class="form-select" id="tipoContingencia_borradores" name="tipoContingencia" aria-label="Seleccionar opcion" required>
                                <option value="1">No disponibilidad de sistema del MH</option>
                                <option value="2">No disponibilidad de sistema del emisor</option>
                                <option value="3">Falla en el suministro de servicio de Internet del Emisor</option>
                                <option value="4">Falla en el suministro de servicio de energia eléctrica del emisor que impida la transmisión de los DTE</option>
                                <option value="5">Otro (deberá digitar un máximo de 500 caracteres explicando el motivo)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="motivoContingencia_borradores">Motivo Contingencia</label>
                            <input type="text" id="motivoContingencia_borradores" class="form-control" aria-label="Direccion" name="motivoContingencia" required/>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="nombreResponsable_borradores">Nombre del Responsable</label>
                            <input type="text" id="nombreResponsable_borradores" class="form-control" aria-label="Direccion" name="nombreResponsable" required/>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="tipoDocResponsable_borradores">Tipo Documento</label>
                            <select class="form-select" id="tipoDocResponsable_borradores" name="tipoDocResponsable" aria-label="Seleccionar opcion" required>
                                <option value="36">NIT</option>
                                <option value="13">DUI</option>
                                <option value="37">Otro</option>
                                <option value="03">Pasaporte</option>
                                <option value="02">Carnet de Residente</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="nuDocResponsable_borradores">Numero de Documento</label>
                            <input type="text" id="nuDocResponsable_borradores" onkeyup="nitDuiMask(this);" class="form-control" name="nuDocResponsable" required/>
                        </div>
                        <div class="col-12">
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="incluir_borradores_modal" checked>
                                <label class="form-check-label" for="incluir_borradores_modal">
                                    <strong>Incluir documentos en borrador</strong>
                                    <br>
                                    <small class="text-muted">Incluir DTEs en cola y ventas sin DTE (no confirmadas)</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="dte_ids_borradores" class="form-label">Documentos Afectados</label>
                            <select name="dte_ids[]" id="dte_ids_borradores" class="form-select" multiple>
                                <option value="">Seleccione empresa primero...</option>
                            </select>
                            <small class="text-muted">
                                Seleccione los documentos que serán incluidos en esta contingencia.
                                <br>
                                <span class="badge bg-danger">Necesita</span> = DTE rechazado que necesita contingencia
                                <br>
                                <span class="badge bg-warning">Borrador</span> = DTE en cola (pendiente de envío)
                                <br>
                                <span class="badge bg-info">Sin DTE</span> = Venta sin DTE generado
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>
                        Crear Contingencia con Borradores
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


