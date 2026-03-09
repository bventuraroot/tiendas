$(function () {
    "use strict";

    var dt_credit_notes_table = $('.datatables-credit-notes'),
        credit_notes_view = 'credit-notes-list';

    // DataTable with buttons
    if (dt_credit_notes_table.length) {
        var dt_credit_notes = dt_credit_notes_table.DataTable({
            ajax: {
                url: '/credit-notes/data',
                type: 'GET'
            },
            columns: [
                { data: '' },
                { data: 'correlativo' },
                { data: 'fecha' },
                { data: 'cliente' },
                { data: 'empresa' },
                { data: 'total' },
                { data: 'estado' },
                { data: 'motivo' },
                { data: 'acciones' }
            ],
            columnDefs: [
                {
                    targets: 0,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full, meta) {
                        return (
                            '<div class="form-check"> <input class="form-check-input dt-checkboxes" type="checkbox" value="' +
                            full.id +
                            '" id="checkbox' +
                            full.id +
                            '" /><label class="form-check-label" for="checkbox' +
                            full.id +
                            '"></label></div>'
                        );
                    }
                },
                {
                    targets: 1,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-semibold">' + full.correlativo + '</span>';
                    }
                },
                {
                    targets: 2,
                    render: function (data, type, full, meta) {
                        return '<span class="text-nowrap">' + full.fecha + '</span>';
                    }
                },
                {
                    targets: 3,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + full.cliente + '</span>';
                    }
                },
                {
                    targets: 4,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + full.empresa + '</span>';
                    }
                },
                {
                    targets: 5,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-semibold">' + full.total + '</span>';
                    }
                },
                {
                    targets: 6,
                    render: function (data, type, full, meta) {
                        return full.estado;
                    }
                },
                {
                    targets: 7,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + full.motivo + '</span>';
                    }
                },
                {
                    targets: 8,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full, meta) {
                        return full.acciones;
                    }
                }
            ],
            order: [[1, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: 7,
            lengthMenu: [7, 10, 25, 50, 75, 100],
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Detalles de la Nota de Crédito: ' + data.correlativo;
                        }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        var data = $.map(columns, function (col, i) {
                            return col.columnIndex !== 8
                                ? '<tr data-dt-row="' +
                                      col.rowIdx +
                                      '" data-dt-column="' +
                                      col.columnIndex +
                                      '"> <td>' +
                                      col.title +
                                      ':</td> <td>' +
                                      col.data +
                                      '</td></tr>'
                                : '';
                        }).join('');

                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                    }
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    }

    // Filter form control to default size
    // ? setTimeout used for multilingual table initialization
    setTimeout(() => {
        $('.dataTables_filter .form-control').removeClass('form-control-sm');
        $('.dataTables_length .form-select').removeClass('form-select-sm');
    }, 300);
});
