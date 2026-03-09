$(function () {
    'use strict';

    var dt_debit_notes_table = $('.datatables-debit-notes'),
        select2 = $('.select2');

    // DataTable with buttons
    if (dt_debit_notes_table.length) {
        var dt_debit_notes = dt_debit_notes_table.DataTable({
            ajax: {
                url: '/debit-notes/data',
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
                            '<div class="form-check"> <input class="form-check-input dt-checkboxes" type="checkbox" value="" id="checkbox' +
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
                        return '<span class="fw-semibold text-success">$' + parseFloat(full.total).toFixed(2) + '</span>';
                    }
                },
                {
                    targets: 6,
                    render: function (data, type, full, meta) {
                        var badgeClass = 'bg-secondary';
                        if (full.estado === 'ACTIVO') {
                            badgeClass = 'bg-success';
                        } else if (full.estado === 'ANULADO') {
                            badgeClass = 'bg-danger';
                        } else if (full.estado === 'PENDIENTE') {
                            badgeClass = 'bg-warning';
                        }
                        return '<span class="badge ' + badgeClass + '">' + full.estado + '</span>';
                    }
                },
                {
                    targets: 7,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + (full.motivo || 'N/A') + '</span>';
                    }
                },
                {
                    targets: -1,
                    title: 'Acciones',
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full, meta) {
                        return (
                            '<div class="d-flex align-items-center">' +
                            '<a href="/debit-notes/' + full.id + '" class="btn btn-sm btn-outline-info me-1" title="Ver">' +
                            '<i class="ti ti-eye"></i>' +
                            '</a>' +
                            '<a href="/debit-notes/' + full.id + '/print" class="btn btn-sm btn-outline-secondary me-1" target="_blank" title="Imprimir">' +
                            '<i class="ti ti-printer"></i>' +
                            '</a>' +
                            '<div class="btn-group">' +
                            '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">' +
                            '<i class="ti ti-dots-vertical"></i>' +
                            '</button>' +
                            '<div class="dropdown-menu">' +
                            (full.estado === 'ACTIVO' ?
                                '<a href="/debit-notes/' + full.id + '/edit" class="dropdown-item">' +
                                '<i class="ti ti-edit me-2"></i>Editar' +
                                '</a>' +
                                '<a href="javascript:void(0)" onclick="sendEmail(' + full.id + ')" class="dropdown-item">' +
                                '<i class="ti ti-mail me-2"></i>Enviar por correo' +
                                '</a>' +
                                '<div class="dropdown-divider"></div>' +
                                '<a href="javascript:void(0)" onclick="deleteDebitNote(' + full.id + ')" class="dropdown-item text-danger">' +
                                '<i class="ti ti-trash me-2"></i>Eliminar' +
                                '</a>' : '') +
                            '</div>' +
                            '</div>' +
                            '</div>'
                        );
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
                            return 'Detalles de la Nota de Débito #' + data.correlativo;
                        }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        var data = $.map(columns, function (col, i) {
                            return col.columnIndex !== 0 && col.columnIndex !== -1
                                ? '<tr data-dt-row="' +
                                    col.rowIdx +
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
            }
        });
    }

    // Select2
    if (select2.length) {
        select2.wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Seleccionar...',
            dropdownParent: select2.parent()
        });
    }
});
