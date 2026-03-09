'use strict';

// Definir baseUrl si no está definida (evitar redeclaración)
window.baseUrl = window.baseUrl || window.location.origin + '/';

// Configurar token CSRF para todas las peticiones AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    xhrFields: {
        withCredentials: true
    }
});

$(function () {
    let dt_table = $('.datatables-inventory');

    // Función para cambiar el estado
    window.toggleState = function(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas cambiar el estado de este inventario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url: window.baseUrl + 'inve/toggle-state/' + id,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            dt_table.DataTable().ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al cambiar el estado del inventario',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                });
            }
        });
    };

    // DataTable with export buttons
    if (dt_table.length) {
        const dt_inventory = dt_table.DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                emptyTable: "No hay datos disponibles",
                zeroRecords: "No se encontraron registros coincidentes",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                error: "Error al cargar los datos",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                lengthMenu: "Mostrar _MENU_ registros",
                search: "Buscar:",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-label-secondary dropdown-toggle mx-3',
                    text: '<i class="ti ti-download me-1 ti-xs"></i> <span class="align-middle">Exportar</span>',
                    buttons: [
                        {
                            extend: 'print',
                            text: '<i class="ti ti-printer me-2" ></i>Imprimir',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] },
                            customize: function (win) {
                                $(win.document.body)
                                    .css('font-size', '10pt')
                                    .prepend('<img src="' + window.baseUrl + 'assets/img/logo.png" style="position:absolute; top:0; left:0;" />');
                                $(win.document.body)
                                    .find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="ti ti-file-spreadsheet me-2"></i>Csv',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="ti ti-file-text me-2"></i>Pdf',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] }
                        }
                    ]
                }
            ],
            processing: true,
            serverSide: false,
            ajax: {
                url: window.baseUrl + 'inve/list',
                cache: true,
                xhrFields: {
                    withCredentials: true
                },
                error: function (xhr, error, thrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al cargar los datos del inventario',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            },
            responsive: false,
            scrollX: true,
            columns: [
                {
                    data: null,
                    title: 'ACCIONES',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center">
                                <a href="javascript:editinventory(${row.id});" class="text-body">
                                    <i class="mx-2 ti ti-edit ti-sm"></i>
                                </a>
                                <a href="javascript:showExpirationTracking(${row.product_id});" class="text-body" title="Ver seguimiento de vencimiento">
                                    <i class="mx-2 ti ti-calendar-time ti-sm"></i>
                                </a>
                                <div class="dropdown">
                                    <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="mx-2 ti ti-dots-vertical ti-sm"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="javascript:toggleState(${row.id});" class="dropdown-item">
                                            <i class="ti ti-toggle-right ti-sm me-2"></i>
                                            <span class="align-middle">Cambiar Estado</span>
                                        </a>
                                        <a href="javascript:deleteinventory(${row.id});" class="dropdown-item text-danger">
                                            <i class="ti ti-trash ti-sm me-2"></i>
                                            <span class="align-middle">Eliminar</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                { data: 'code', title: 'CÓDIGO' },
                { data: 'name', title: 'NOMBRE' },
                { data: 'description', title: 'DESCRIPCIÓN' },
                {
                    data: 'price',
                    title: 'PRECIO',
                    render: function(data) {
                        return '$ ' + parseFloat(data).toFixed(2);
                    }
                },
                { data: 'type', title: 'TIPO' },
                { data: 'provider_name', title: 'PROVEEDOR' },
                {
                    data: 'quantity',
                    title: 'CANTIDAD',
                    type: 'num', // Tipo numérico para ordenamiento
                    orderData: [7], // Ordenar por columna cantidad (índice 7 con acciones en 0)
                    render: function(data, type, row) {
                        // Para ordenamiento y filtrado, siempre usar quantity_raw o extraer valor numérico
                        if (type === 'type' || type === 'sort' || type === 'filter') {
                            if (row.quantity_raw !== undefined && row.quantity_raw !== null) {
                                return parseFloat(row.quantity_raw) || 0;
                            }
                            // Intentar extraer de HTML si existe
                            if (typeof data === 'string' && data.includes('data-quantity')) {
                                const match = data.match(/data-quantity=["']?([^"'\s>]+)/);
                                if (match && match[1]) {
                                    return parseFloat(match[1]) || 0;
                                }
                            }
                            // Intentar parsear directamente
                            const parsed = parseFloat(data);
                            return isNaN(parsed) ? 0 : parsed;
                        }
                        
                        // Para display
                        // Detectar si data es HTML
                        const isHtml = typeof data === 'string' && (data.trim().charAt(0) === '<' || data.includes('data-quantity'));
                        
                        if (isHtml) {
                            // Obtener valor numérico para comparaciones
                            let numericValue = 0;
                            if (row.quantity_raw !== undefined && row.quantity_raw !== null) {
                                numericValue = parseFloat(row.quantity_raw) || 0;
                            } else {
                                const match = data.match(/data-quantity=["']?([^"'\s>]+)/);
                                if (match && match[1]) {
                                    numericValue = parseFloat(match[1]) || 0;
                                }
                            }
                            
                            // Aplicar estilo de advertencia si es necesario
                            const minStock = parseFloat(row.minimum_stock) || 0;
                            if (numericValue <= minStock) {
                                // Agregar clase de advertencia
                                if (data.includes('<div')) {
                                    return data.replace(/<div([^>]*)>/, '<div$1 class="text-danger fw-bold">');
                                } else if (data.includes('<span')) {
                                    return data.replace(/<span([^>]*)>/, '<span$1 class="text-danger fw-bold">');
                                }
                            }
                            return data;
                        }
                        
                        // Si no es HTML, debería ser un número
                        let numericValue = 0;
                        if (row.quantity_raw !== undefined && row.quantity_raw !== null) {
                            numericValue = parseFloat(row.quantity_raw) || 0;
                        } else {
                            const parsed = parseFloat(data);
                            numericValue = isNaN(parsed) ? 0 : parsed;
                        }
                        
                        const formattedQuantity = numericValue.toFixed(4);
                        const minStock = parseFloat(row.minimum_stock) || 0;
                        const warningClass = numericValue <= minStock ? 'text-danger fw-bold' : '';
                        return `<span class="${warningClass}" data-quantity="${numericValue}">${formattedQuantity}</span>`;
                    }
                },
                {
                    data: 'minimum_stock',
                    title: 'STOCK MÍNIMO',
                    render: function(data, type, row) {
                        return parseFloat(data).toFixed(4);
                    }
                },
                { data: 'location', title: 'UBICACIÓN' },
                {
                    data: 'active',
                    title: 'ESTADO',
                    render: function(data) {
                        const isActive = data === 'Activo' || data === true;
                        const badge = isActive
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>';
                        return badge;
                    }
                },
                {
                    data: 'expiration_status',
                    title: 'ESTADO VENCIMIENTO',
                    render: function(data, type, row) {
                        if (!data || data === 'no_expiration') {
                            return '<span class="expiration-status expiration-none">Sin Fecha</span>';
                        }

                        let statusClass = 'expiration-ok';
                        let statusText = 'OK';

                        switch(data) {
                            case 'expired':
                                statusClass = 'expiration-expired';
                                statusText = 'Vencido';
                                break;
                            case 'critical':
                                statusClass = 'expiration-critical';
                                statusText = 'Crítico';
                                break;
                            case 'warning':
                                statusClass = 'expiration-warning';
                                statusText = 'Advertencia';
                                break;
                            case 'ok':
                                statusClass = 'expiration-ok';
                                statusText = 'OK';
                                break;
                        }

                        return `<span class="expiration-status ${statusClass}">${statusText}</span>`;
                    }
                }
            ],
            order: [[2, 'asc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100]
        });
    }
});

// Función para mostrar el seguimiento de vencimiento de un producto
window.showExpirationTracking = function(productId) {
    $.ajax({
        url: window.baseUrl + 'inve/expiration-tracking/' + productId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#expiration-content').html(response.html);
                $('#expirationTrackingModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al cargar el seguimiento de vencimiento',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al cargar el seguimiento de vencimiento',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
};

// Función para mostrar el reporte de vencimiento
window.showExpirationReport = function() {
    $.ajax({
        url: window.baseUrl + 'inve/expiration-report',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#expiration-report-content').html(response.html);
                $('#expirationReportModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al cargar el reporte de vencimiento',
                    customClass: {
                        confirmButton: 'btn btn-danger'
                    }
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al cargar el reporte de vencimiento',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
};

// Función para editar inventario
window.editinventory = function(id) {
    // Limpiar el formulario antes de llenarlo
    $('#editinventoryForm')[0].reset();
    $('#edit_inventoryid').val('');
    $('#edit_quantity').val('');
    $('#edit_minimum_stock').val('');
    $('#edit_location').val('');

    // Guardar el ID del inventario en un campo oculto
    $('#edit_inventoryid').val(id);

    $.ajax({
        url: window.baseUrl + 'inve/edit/' + id,
        type: 'GET',
        success: function(response) {
            // Llenar el formulario de edición con los datos
            if (response.inventory) {
                $('#edit_productid').val(response.inventory.product_id);
                $('#edit_quantity').val(response.inventory.quantity);
                $('#edit_minimum_stock').val(response.inventory.minimum_stock);
                $('#edit_location').val(response.inventory.location);

                // Mostrar información del producto
                if (response.product) {
                    $('#edit_product_name').text(response.product.name);
                    $('#edit_current_stock').text(parseFloat(response.inventory.base_quantity || response.inventory.quantity).toFixed(4));
                }

                // CARGAR UNIDADES DE MEDIDA PARA EL PRODUCTO
                if (response.inventory.product_id && typeof window.loadEditUnits === 'function') {
                    window.loadEditUnits(response.inventory.product_id, response.inventory.base_unit_id);
                } else if (response.inventory.product_id) {
                    // Cargar unidades manualmente si la función no está disponible
                    $.getJSON('/sale/getproductbyid/' + response.inventory.product_id, function(resp) {
                        if (resp && resp.success && resp.data.units) {
                            // Determinar qué unidades mostrar según el tipo de producto
                            let allowedUnits = ['59','36','99']; // Por defecto: Unidad, Libra, Dólar

                            const product = resp.data.product;
                            
                            // Verificar si es un producto farmacéutico
                            if (product && (product.pastillas_per_blister || product.blisters_per_caja)) {
                                // Para productos farmacéuticos, cargar solo unidades farmacéuticas
                                allowedUnits = ['PASTILLA','BLISTER','CAJA'];
                            } else if (product && product.sale_type) {
                                switch(product.sale_type) {
                                    case 'volume':
                                        allowedUnits = ['59','23','99']; // Unidad, Litro, Dólar
                                        break;
                                    case 'weight':
                                        allowedUnits = ['59','36','99']; // Unidad, Libra, Dólar
                                        break;
                                    case 'unit':
                                        allowedUnits = ['59','36','99']; // Unidad, Libra, Dólar
                                        break;
                                }
                            }

                            const units = resp.data.units.filter(u => allowedUnits.includes(u.unit_code));

                            const sel = $('#edit_unit-select');
                            sel.empty().append('<option value="">Seleccionar unidad...</option>');
                            units.forEach(u => {
                                const prettyName = u.unit_code === '59' ? 'Unidad' :
                                                 u.unit_code === '36' ? 'Libra' :
                                                 u.unit_code === '23' ? 'Litro' :
                                                 u.unit_code === '99' ? 'Dólar' :
                                                 u.unit_code === 'PASTILLA' ? 'Pastilla' :
                                                 u.unit_code === 'BLISTER' ? 'Blister' :
                                                 u.unit_code === 'CAJA' ? 'Caja' : u.unit_name;
                                sel.append(`<option value="${u.unit_code}" data-id="${u.unit_id}" data-factor="${u.conversion_factor}">${prettyName}</option>`);
                            });

                            // Seleccionar la unidad guardada si existe
                            if (response.inventory.base_unit_id) {
                                // Primero buscar por unit_id exacto
                                let savedUnit = units.find(u => u.unit_id == response.inventory.base_unit_id);

                                // Si no se encuentra, buscar por el código de unidad correspondiente
                                if (!savedUnit) {
                                    // Obtener el código de unidad correspondiente al ID guardado
                                    const unitCodes = {
                                        '2': '36',      // Libra
                                        '11': '23',     // Litro
                                        '28': '59',     // Unidad
                                        '34': '99',     // Otra
                                        '36': 'PASTILLA', // Pastilla (ID 36)
                                        '39': 'BLISTER',  // Blister (ID 39)
                                        '40': 'CAJA'      // Caja (ID 40)
                                    };
                                    const unitCode = unitCodes[response.inventory.base_unit_id];
                                    if (unitCode) {
                                        savedUnit = units.find(u => u.unit_code === unitCode);
                                    }
                                }

                                if (savedUnit) {
                                    sel.val(savedUnit.unit_code).trigger('change');
                                    $('#edit_selected-unit-id').val(savedUnit.unit_id);
                                    $('#edit_conversion-factor').val(savedUnit.conversion_factor);
                                } else {
                                    // Si no encuentra la unidad guardada, seleccionar la primera disponible
                                    if (units.length) {
                                        sel.val(units[0].unit_code).trigger('change');
                                    }
                                }
                            } else {
                                // Si no hay unidad guardada, seleccionar la primera disponible
                                if (units.length) {
                                    sel.val(units[0].unit_code).trigger('change');
                                }
                            }
                        }
                    });
                }
            }
            // Abrir el modal de edición solo después de llenar los campos
            $('#editinventoryModal').modal('show');
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al cargar los datos del inventario',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
};

// Manejar el submit del formulario de edición
$('#editinventoryForm').on('submit', function(e) {
    e.preventDefault();
    var id = $('#edit_inventoryid').val();
    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el ID del inventario a editar',
            customClass: { confirmButton: 'btn btn-danger' }
        });
        return;
    }
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    var data = {
        quantity: $('#edit_quantity').val(),
        minimum_stock: $('#edit_minimum_stock').val(),
        location: $('#edit_location').val(),
        'unit-select': $('#edit_unit-select').val(),
        'selected-unit-id': $('#edit_selected-unit-id').val(),
        'conversion-factor': $('#edit_conversion-factor').val(),
        _token: csrfToken,
        _method: 'PUT'
    };
    $.ajax({
        url: window.baseUrl + 'inve/edit/' + id,
        type: 'PUT',
        data: data,
        success: function(response) {
            $('#editinventoryModal').modal('hide');
            $('.datatables-inventory').DataTable().ajax.reload();
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: response.message,
                customClass: {
                    confirmButton: 'btn btn-success'
                }
            });
        },
        error: function(xhr) {
            let msg = 'Hubo un error al actualizar el inventario';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg,
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    });
});

// Función para eliminar inventario
window.deleteinventory = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: window.baseUrl + 'inve/edit/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.datatables-inventory').DataTable().ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'El inventario ha sido eliminado correctamente',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al eliminar el inventario',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        }
    });
};
