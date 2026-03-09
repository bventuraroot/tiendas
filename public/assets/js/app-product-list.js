/**
 * Page User List
 */

'use strict';

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

  // Variable declaration for table
  var dt_user_table = $('.datatables-products'),
    select2 = $('.select2provider');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Proveedor',
      dropdownParent: $this.parent()
    });
  }

  var select2cfiscal = $('.select2cfiscal');

  if (select2cfiscal.length) {
    var $this = select2cfiscal;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar C. Fiscal',
      dropdownParent: $this.parent()
    });
  }

  var select2type = $('.select2type');

  if (select2type.length) {
    var $this = select2type;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Tipo',
      dropdownParent: $this.parent()
    });
  }

  var select2marca = $('.select2marca');

  if (select2marca.length) {
    var $this = select2marca;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Marca',
      dropdownParent: $this.parent()
    });
  }

  var select2marcaredit = $('.select2marcaredit');

  if (select2marcaredit.length) {
    var $this = select2marcaredit;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Marca',
      dropdownParent: $this.parent()
    });
  }

  var select2provideredit = $('.select2provideredit');

  if (select2provideredit.length) {
    var $this = select2provideredit;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Proveedor',
      dropdownParent: $this.parent()
    });
  }

  var select2laboratory = $('.select2laboratory');

  if (select2laboratory.length) {
    var $this = select2laboratory;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Laboratorio',
      dropdownParent: $this.parent()
    });
  }

  var select2laboratoryedit = $('.select2laboratoryedit');

  if (select2laboratoryedit.length) {
    var $this = select2laboratoryedit;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Seleccionar Laboratorio',
      dropdownParent: $this.parent()
    });
  }

  // Client datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      columnDefs: [
        {
          // Acciones - Primera columna, siempre visible
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 0
        },
        {
          // Imagen - Prioridad alta
          responsivePriority: 2,
          targets: 1,
          render: function (data, type, full, meta) {
            if (type === 'display') {
              // Extraer la URL de la imagen del HTML
              var imgMatch = data.match(/src="([^"]+)"/);
              if (imgMatch && imgMatch[1]) {
                return data; // Retornar el HTML completo con la imagen
              }
              // Si no hay imagen, mostrar ícono
              return '<i class="ti ti-photo ti-lg text-muted"></i>';
            }
            return data;
          }
        },
        {
          // Código - Prioridad alta
          responsivePriority: 3,
          targets: 2
        },
        {
          // Nombre - Prioridad alta
          responsivePriority: 4,
          targets: 3
        },
        {
          // Precio - Prioridad media
          responsivePriority: 5,
          targets: 4
        },
        {
          // Proveedor - Prioridad media
          responsivePriority: 6,
          targets: 5
        },
        {
          // Marca - Prioridad media
          responsivePriority: 7,
          targets: 6
        },
        {
          // C. Fiscal - Prioridad baja
          responsivePriority: 8,
          targets: 7
        },
        {
          // Tipo - Prioridad baja
          responsivePriority: 9,
          targets: 8
        },
        {
          // Categoría - Prioridad media
          responsivePriority: 10,
          targets: 9
        },
        {
          // Estado - Prioridad baja
          responsivePriority: 11,
          targets: 10
        }
      ],
      order: [[2, 'desc']],
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
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                // prevent avatar to be print
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
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
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
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
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
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
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
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
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
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
          text: '<i class="ti ti-plus me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">Agregar Producto</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'modal',
            'data-bs-target': '#addProductModal'
          }
        }
      ],
      // Sin responsive: mostrar toda la información sin icono de expandir
      responsive: false
    });
  }

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
