/**
 * Page sale List
 */

'use strict';

// Función helper para extraer mensajes de respuestas del backend (SCOPE GLOBAL)
function getResponseMessage(response, defaultMessage = 'Error desconocido') {
    if (typeof response === 'object' && response !== null) {
        // Si es un objeto con propiedad message
        if (response.message) {
            return response.message;
        }
        // Si es un objeto con propiedad descripcionMsg (respuestas de Hacienda)
        if (response.descripcionMsg) {
            return response.descripcionMsg;
        }
        // Si es un objeto con propiedad text
        if (response.text) {
            return response.text;
        }
        // Si es un objeto con propiedad error
        if (response.error) {
            return response.error;
        }
    }

    // Si es una cadena de texto
    if (typeof response === 'string') {
        return response;
    }

    // Si es un número o boolean, convertirlo a string
    if (typeof response === 'number' || typeof response === 'boolean') {
        return response.toString();
    }

    return defaultMessage;
}

// Configurar AJAX para esta página específicamente
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest'
    },
    xhrFields: {
        withCredentials: true
    }
});

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }




  // Variable declaration for table - solo la principal, no las de borradores
  var dt_sale_table = $('.datatables-sale').not('.draft-table').not('#draft-invoices-table').not('[data-exclude-datatables="true"]');

  // Debug: Verificar si se encontró la tabla

  // Client datatable
  if (dt_sale_table.length) {
    var dt_sale = dt_sale_table.DataTable({
      columnDefs: [
        {
          // Columna de Acciones - no ordenable, no buscable, no responsive
          searchable: false,
          orderable: false,
          responsivePriority: 0,
          responsive: false, // Desactivar responsive para la columna de acciones
          targets: 0
        },
        {
          // Columna de FECHA - ordenable por fecha usando data-order (timestamp)
          targets: 2,
          type: 'num' // Usar tipo numérico para ordenar timestamps
        },
        {
          // Columna de NOTAS - prioridad responsive
            responsivePriority: 1,
            targets: 8
          }
      ],
      order: [[2, 'desc']], // Ordenar por FECHA (columna 2) descendente
      dom:
        '<"row me-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar'
      },
      // Buttons with Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle mx-3',
          text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be print
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('sale-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                //customize print view for dark
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('sale-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('sale-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-code-2 me-2"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('sale-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('sale-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        },
        {
          text: '<i class="ti ti-report-money ti-tada me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">Nueva Venta</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'modal',
            'data-bs-target': '#selectDocumentModal'
          }
        }
      ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles venta ' + data[0] + ' ' + data[1];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            // Verificar si la fila tiene el atributo data-no-modal
            var rowNode = api.row(rowIdx).node();
            if (rowNode && $(rowNode).attr('data-no-modal') === 'true') {
              // No mostrar el modal para Notas de Crédito o Débito
              return false;
            }

            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      // Prevenir que se abra el modal al hacer clic en el botón de expansión para filas con data-no-modal
      drawCallback: function(settings) {
        var api = this.api();
        // Ocultar y desactivar el botón de control responsive para filas con data-no-modal
        api.rows().every(function() {
          var row = this.node();
          if ($(row).attr('data-no-modal') === 'true') {
            var $controlCell = $(row).find('td.control');
            $controlCell.hide();
            // Desactivar completamente el responsive para esta fila
            $controlCell.off('click');
            // Marcar la fila como no responsive
            $(row).addClass('dtr-disabled');
          }
        });
      }
    });

    // Prevenir que se abra el modal al hacer clic en cualquier botón de acciones
    $(document).on('click', '.datatables-sale tbody td:first-child a, .datatables-sale tbody td:first-child button, .datatables-sale tbody td:first-child .btn', function(e) {
      e.stopPropagation();
      e.stopImmediatePropagation();
    });

    // Prevenir que se abra el modal al hacer clic en el botón de expansión
    $(document).on('click', '.datatables-sale tbody tr[data-no-modal="true"] td.control', function(e) {
      e.stopPropagation();
      e.preventDefault();
      e.stopImmediatePropagation();
      return false;
    });

    // Prevenir que DataTables responsive procese estas filas
    $(document).on('click', '.datatables-sale tbody tr[data-no-modal="true"]', function(e) {
      // Si el clic es en el botón de control o cerca de él, prevenir completamente
      if ($(e.target).closest('td.control').length || $(e.target).hasClass('dtr-control')) {
        e.stopPropagation();
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
      // Solo prevenir si el clic no es en un botón o enlace
      if (!$(e.target).closest('a, button, .btn').length && !$(e.target).closest('td.control').length) {
        e.stopPropagation();
      }
    });

    // Prevenir que el modal responsive se abra cuando se hace clic en cualquier elemento dentro de la columna de acciones
    $(document).on('click', '.datatables-sale tbody tr', function(e) {
      // Si el clic es en la columna de acciones (primera columna) o en cualquier botón/enlace, prevenir el modal
      if ($(e.target).closest('td:first-child').length ||
          $(e.target).closest('a, button, .btn, .dropdown-menu, .dropdown-item, .btn-group').length) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        // Prevenir que DataTables responsive procese este clic
        return false;
      }
    });

    // Prevenir específicamente el evento responsive de DataTables cuando se hace clic en botones
    $(document).on('click', '.datatables-sale tbody td:first-child *', function(e) {
      e.stopPropagation();
      e.stopImmediatePropagation();
    });

    // Desactivar completamente el responsive para filas con data-no-modal después de cada draw
    dt_sale.on('draw.dt', function() {
      dt_sale.rows().every(function() {
        var row = this.node();
        if ($(row).attr('data-no-modal') === 'true') {
          // Desactivar el responsive para esta fila específica
          dt_sale.responsive.disable(this);
          // Ocultar el botón de control
          $(row).find('td.control').hide().off('click');
        }
      });
    });

  } else {
  }

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);

});


// Función para cancelar venta (versión mejorada)
function cancelsale(saleId) {
    // Validar que saleId sea un número válido
    if (!saleId || isNaN(saleId)) {
        Swal.fire({
            title: 'Error',
            text: 'ID de venta inválido',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

      swalWithBootstrapButtons.fire({
        title: 'Anular?',
        text: "Esta accion no tiene retorno",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, Anular!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/sale/destroy/"+btoa(saleId),
                method: "GET",
                success: function(response){
                        if(response.success === true || response.res == 1){
                            Swal.fire({
                                title: '¡Éxito!',
                                text: getResponseMessage(response, 'Documento invalidado correctamente'),
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then((result) => {
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                        });
                        } else if(response.success === false || response.res == 0){
                            Swal.fire(
                                'Error',
                                getResponseMessage(response, 'Error al invalidar el documento'),
                                'error'
                            );
                        } else {
                            // Fallback para respuestas con formato antiguo
                            Swal.fire(
                                'Problemas!',
                                getResponseMessage(response, 'Error al procesar la invalidación'),
                                'error'
                            );
                        }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Error al invalidar el documento';

                    // Si el servidor retornó JSON con mensaje
                    if (xhr.responseJSON) {
                        errorMessage = getResponseMessage(xhr.responseJSON, 'Error al invalidar el documento');
                    } else if (xhr.status === 400) {
                        errorMessage = 'Error de validación en el servidor';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error interno del servidor';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Error de conexión. Verifique su internet.';
                    }

                    Swal.fire(
                        'Error',
                        errorMessage,
                        'error'
                    );
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna accion :)',
            'error'
            );
        }
    });
   }


// Función para crear nota de crédito
function ncr(saleId) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "Nota de Crédito?",
            text: "Esta accion no tiene retorno",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Si, Crear!",
            cancelButtonText: "No, espera!",
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: "/sale/ncr/"+btoa(saleId),
                        method: "GET",
                        success: function (response) {
                            if (response.res == 1) {
                                resolve(response);
                            } else if (response.res == 0) {
                                reject("Algo salió mal");
                            }
                        },
                        error: function() {
                            reject("Error en la petición");
                        }
                    });
                });
            },
        })
        .then((result) => {
            if (result.value) {
                Swal.fire({
                    title: 'Nota de Crédito Creado Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "/sale/index";
                    }
                });
            }
        })
        .catch((error) => {
            swalWithBootstrapButtons.fire(
                "Problemas!",
                'Algo sucedio y no se creo la nota de crédito, favor comunicarse con el administrador.',
                "error"
            );
        });
}


  function printsale(corr){
    var url = 'impdoc/'+corr;
    window.open(url, '_blank');
  }

// Función para enviar correo
function EnviarCorreo(id_factura, correo, numero) {
    (async () => {
        const _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const { value: email } = await Swal.fire({
            title: 'Mandar comprobante por Correo',
            input: 'email',
            inputLabel: 'Correo a Enviar',
            inputPlaceholder: 'Introduzca el Correo',
            inputValue: correo
        });

        if (email) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando correo...',
                text: 'Por favor espere mientras se genera y envía el PDF',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Primero consultar si la venta tiene DTE para decidir el endpoint
            const baseUrl = window.location.origin;
            fetch(baseUrl + `/sale/check-dte/${id_factura}`)
                .then(r => r.json())
                .then(dte => {
                    const hasDte = !!(dte && dte.hasDte);
                    const url = hasDte ? (baseUrl + "/sale/envia_correo") : (baseUrl + "/sale/enviar_correo_offline");

                    $.ajax({
                        url: url,
                        type: hasDte ? 'GET' : 'POST',
                        data: {
                            id_factura: id_factura,
                            email: email,
                            numero: numero,
                            nombre_cliente: '',
                            _token: _token
                        },
                        success: function(response, status) {
                            if (response === '' || response?.success) {
                                Swal.fire({
                                    title: '¡Correo Enviado!',
                                    html: `
                                        <p>Comprobante enviado exitosamente a:</p>
                                        <strong>${email}</strong>
                                        <br><br>
                                        <small class="text-muted">Factura: ${response?.data?.numero_factura || numero}</small>
                                    `,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: getResponseMessage(response, 'Error al enviar el correo'),
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = 'Error al enviar el correo';

                            if (xhr.responseJSON) {
                                errorMessage = getResponseMessage(xhr.responseJSON, 'Error al enviar el correo');
                            } else if (xhr.status === 404) {
                                errorMessage = 'Función no encontrada. Verifique la configuración.';
                            } else if (xhr.status === 405) {
                                errorMessage = 'Método no permitido. Contacte al administrador.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Error interno del servidor.';
                            } else if (xhr.status === 0) {
                                errorMessage = 'Error de conexión. Verifique su internet.';
                            }

                            Swal.fire({
                                title: 'Error de Envío',
                                html: `
                                    <p>${errorMessage}</p>
                                    <hr>
                                    <small class="text-muted">Código: ${xhr.status}</small>
                                `,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                })
                .catch(() => {
                    // Si falla la verificación, usar offline como fallback
                    $.ajax({
                        url: baseUrl + "/sale/enviar_correo_offline",
                        type: 'POST',
                        data: { id_factura, email, numero, nombre_cliente: '', _token: _token },
                        success: function() { Swal.fire('¡Correo Enviado!', '', 'success'); },
                        error: function() { Swal.fire('Error de Envío', 'No se pudo enviar el correo', 'error'); }
                    });
                });
        }
    })();
}

// Función para retomar venta (versión mejorada)
function retomarsale(saleId, typesale, tipoDte = '', typedocumentId = 0) {
    // Debug: Log de parámetros recibidos

    // Determinar la redirección según el tipo de documento
    let url = '';

    // Si es una nota de crédito (tipoDte = '05')
    if (tipoDte === '05') {
        url = `/credit-notes/edit/${saleId}`;
    }
    // Si es una nota de débito (tipoDte = '06')
    else if (tipoDte === '06') {
        url = `/debit-notes/edit/${saleId}`;
    }
    // Para otros tipos de documentos (facturas, créditos fiscales, etc.)
    else {
        if (typesale === 2 || typesale === 3) {
            // Es un borrador, redirigir directamente a create-dynamic
            url = `/sale/create-dynamic?corr=${saleId}&draft=true&typedocument=${typedocumentId}&operation=edit`;
        } else {
            // Documento finalizado, redirigir a create-dynamic también
            url = `/sale/create-dynamic?corr=${saleId}&typedocument=${typedocumentId}&operation=edit`;
        }
    }

    // Debug: Log de la URL final

    // Redirigir a la URL determinada
    window.location.href = url;
}

// Inicialización cuando el documento esté listo
$(document).ready(function() {
    // Inicializar filtros
    initializeFilters();

    // Manejar dropdowns en la tabla
    initializeTableDropdowns();
});

// Función para inicializar los dropdowns de la tabla
function initializeTableDropdowns() {
    // Delegar eventos para dropdowns que se crean dinámicamente
    $(document).on('click', '.dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Cerrar otros dropdowns abiertos
        $('.dropdown-menu').removeClass('show');
        $('.dropdown-toggle').attr('aria-expanded', 'false');

        // Abrir el dropdown actual
        const dropdown = $(this).next('.dropdown-menu');
        dropdown.toggleClass('show');
        $(this).attr('aria-expanded', dropdown.hasClass('show'));
    });

    // Cerrar dropdowns al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').removeClass('show');
            $('.dropdown-toggle').attr('aria-expanded', 'false');
        }
    });

    // Prevenir que el dropdown se cierre al hacer clic en él
    $(document).on('click', '.dropdown-menu', function(e) {
        e.stopPropagation();
    });
}

// Función para inicializar los filtros
function initializeFilters() {
    // Auto-submit del formulario cuando cambien los filtros (opcional)
    $('select[name="tipo_documento"], select[name="cliente_id"]').on('change', function() {
        // Opcional: auto-submit cuando cambie un filtro
        // $('#filters-form').submit();
    });

    // Validación de fechas
    $('input[name="fecha_desde"], input[name="fecha_hasta"]').on('change', function() {
        validateDateRange();
    });

    // Limpiar filtros
    $('.btn-clear-filters').on('click', function(e) {
        e.preventDefault();
        clearFilters();
    });
}

// Función para validar el rango de fechas
function validateDateRange() {
    const fechaDesde = $('input[name="fecha_desde"]').val();
    const fechaHasta = $('input[name="fecha_hasta"]').val();

    if (fechaDesde && fechaHasta) {
        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            Swal.fire({
                title: 'Error en fechas',
                text: 'La fecha desde no puede ser mayor que la fecha hasta',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $('input[name="fecha_hasta"]').val('');
        }
    }
}

// Función para limpiar todos los filtros
function clearFilters() {
    $('input[name="fecha_desde"]').val('');
    $('input[name="fecha_hasta"]').val('');
    $('select[name="tipo_documento"]').val('');
    $('input[name="correlativo"]').val('');
    $('select[name="cliente_id"]').val('');

    // Redirigir a la página sin filtros
    window.location.href = window.location.pathname;
}

// Función para aplicar filtros rápidos (opcional)
function applyQuickFilter(filterType, value) {
    switch(filterType) {
        case 'today':
            const today = new Date().toISOString().split('T')[0];
            $('input[name="fecha_desde"]').val(today);
            $('input[name="fecha_hasta"]').val(today);
            break;
        case 'thisMonth':
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
            $('input[name="fecha_desde"]').val(firstDay);
            $('input[name="fecha_hasta"]').val(lastDay);
            break;
        case 'lastMonth':
            const lastMonth = new Date();
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            const firstDayLastMonth = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1).toISOString().split('T')[0];
            const lastDayLastMonth = new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0).toISOString().split('T')[0];
            $('input[name="fecha_desde"]').val(firstDayLastMonth);
            $('input[name="fecha_hasta"]').val(lastDayLastMonth);
            break;
    }

    // Aplicar el filtro
    $('form[method="GET"]').submit();
}

// ==================== FUNCIONES DE BORRADORES DE FACTURA ====================

// Cargar borradores de factura pendientes (desde preventas)
function loadDraftInvoices() {
    const section = document.getElementById('draft-invoices-section');
    const tbody = document.getElementById('draft-invoices-body');

    if (section.style.display === 'none') {
        section.style.display = 'block';
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-muted">
                    <i class="ti ti-loader fs-1"></i>
                    <br>
                    Cargando borradores...
                </td>
            </tr>
        `;

        // Cargar borradores desde la API
        $.get('/presales/drafts')
            .done(function(response) {
                if (response.success && response.drafts && response.drafts.length > 0) {
                    tbody.innerHTML = response.drafts.map(draft => {
                        const date = new Date(draft.created_at).toLocaleDateString('es-ES');
                        const userName = draft.user ? draft.user.name : 'Usuario';

                        return `
                            <tr>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info"
                                            onclick="viewDraftDetails(${draft.id})"
                                            title="Ver detalles">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                                <td>${draft.id}</td>
                                <td>${draft.client_name || 'Sin cliente'}</td>
                                <td>${draft.company_name || 'Sin empresa'}</td>
                                <td>${draft.document_type || 'Factura'}</td>
                                <td>$${parseFloat(draft.total || 0).toFixed(2)}</td>
                                <td>${date}</td>
                                <td>${userName}</td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-success me-1"
                                            onclick="completeDraftInvoice(${draft.id}, ${draft.typedocument_id})"
                                            title="Completar factura">
                                        <i class="ti ti-check me-1"></i>
                                        Completar
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="viewDraftDetails(${draft.id})"
                                            title="Ver detalles">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="ti ti-inbox fs-1"></i>
                                <br>
                                No hay borradores pendientes
                            </td>
                        </tr>
                    `;
                }
            })
            .fail(function(xhr, status, error) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-danger">
                            <i class="ti ti-alert-triangle fs-1"></i>
                            <br>
                            Error cargando borradores
                        </td>
                    </tr>
                `;
            });
    } else {
        section.style.display = 'none';
    }
}

// Completar un borrador de factura (usando el patrón original)
function completeDraftInvoice(draftId, typeDocumentId) {
    Swal.fire({
        title: '¿Completar borrador de factura?',
        text: '¿Estás seguro de que deseas completar este borrador y emitir la factura electrónica?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, completar factura',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirigir a create-dynamic con los parámetros del borrador
            window.location.href = `/sale/create-dynamic?corr=${draftId}&draft=true&typedocument=${typeDocumentId}&operation=edit`;
        }
    });
}

// Ver detalles de un borrador
function viewDraftDetails(draftId) {
    fetch(`/sale/get-draft-preventa/${draftId}`)
        .then(response => response.json())
        .then(data => {
            const draft = data.draft;
            const details = data.details || [];

            let detailsHtml = '';
            if (details && details.length > 0) {
                detailsHtml = details.map(detail => {
                    const productName = detail.description || (detail.product ? detail.product.name : 'Producto eliminado');
                    const quantity = detail.quantity || 0;
                    const price = detail.price || 0;
                    const total = quantity * price;

                    return `
                        <tr>
                            <td>${productName}</td>
                            <td class="text-end">${quantity}</td>
                            <td class="text-end">$${price.toFixed(2)}</td>
                            <td class="text-end">$${total.toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                detailsHtml = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            No hay productos en este borrador
                        </td>
                    </tr>
                `;
            }

            const draftInfo = `
                <div class="mb-3">
                    <h6>Información del Borrador</h6>
                    <p><strong>ID:</strong> ${draft.id}</p>
                    <p><strong>Cliente:</strong> ${draft.client_name || 'Sin cliente'}</p>
                    <p><strong>Total:</strong> $${parseFloat(draft.total || 0).toFixed(2)}</p>
                    <p><strong>Fecha:</strong> ${new Date(draft.created_at).toLocaleString('es-ES')}</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${detailsHtml}
                        </tbody>
                    </table>
                </div>
            `;

            Swal.fire({
                title: 'Detalles del Borrador',
                html: draftInfo,
                width: '800px',
                showCancelButton: true,
                confirmButtonText: 'Completar Factura',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    completeDraftInvoice(draftId, draft.typedocument_id);
                }
            });
        })
        .catch(error => {
            Swal.fire('Error', 'No se pudieron cargar los detalles del borrador', 'error');
        });
}

// Actualizar contador de borradores
function updateDraftCount() {
    $.get('/presales/drafts-count')
        .done(function(response) {
            if (response.success) {
                const badge = document.getElementById('draft-count');
                if (badge) {
                    badge.textContent = response.count || 0;
                    badge.style.display = response.count > 0 ? 'inline' : 'none';
                }
            }
        })
        .fail(function() {
            // Silenciar errores para no interrumpir la experiencia del usuario
        });
}

// Cargar contador al inicializar la página
$(document).ready(function() {
    updateDraftCount();
});
