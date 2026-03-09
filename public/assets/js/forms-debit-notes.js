$(function () {
    'use strict';

    // Variables
    var form = $('#debitNoteForm');
    var select2 = $('.select2');

    // Form Validation
    if (form.length) {
        form.on('submit', function (e) {
            e.preventDefault();

            // Validar que al menos un producto esté seleccionado
            var checkedProducts = $('.product-checkbox:checked').length;

            if (checkedProducts === 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar al menos un producto para la nota de débito.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }

            // Validar que los productos seleccionados tengan cantidad y precio válidos
            var isValid = true;
            $('.product-checkbox:checked').each(function() {
                var row = $(this).closest('tr');
                var cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
                var precio = parseFloat(row.find('input[name*="[precio]"]').val()) || 0;

                if (cantidad <= 0 || precio < 0) {
                    isValid = false;
                    return false;
                }
            });

            if (!isValid) {
                Swal.fire({
                    title: 'Error',
                    text: 'Todos los productos deben tener cantidad y precio válidos.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }

            // Mostrar loading
            showLoading();

            // Enviar por AJAX
            $.ajax({
                url: form.attr('action'),
                method: form.attr('method') || 'POST',
                data: form.serialize(),
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(resp) {
                    hideLoading();
                    if (resp === '1' || resp === 1 || (resp && resp.success)) {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Creación y presentación con hacienda de nota de débito exitosa',
                            icon: 'success',
                            confirmButtonText: 'Continuar'
                        }).then(function(){
                            var redirectUrlInput = document.getElementById('redirectToSales');
                            if (redirectUrlInput && redirectUrlInput.value) {
                                window.location.href = redirectUrlInput.value;
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        showError('Error', 'No se pudo completar la creación y presentación con hacienda de nota de débito.');
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    var msg = 'Ocurrió un error al procesar la creación y presentación con hacienda de nota de débito.';
                    var details = '';

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            details = '<ul>';
                            for (var field in xhr.responseJSON.errors) {
                                if (xhr.responseJSON.errors.hasOwnProperty(field)) {
                                    details += '<li>' + field + ': ' + xhr.responseJSON.errors[field].join(', ') + '</li>';
                                }
                            }
                            details += '</ul>';
                        }
                        if (xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                    } else if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                msg = response.message;
                            }
                        } catch (e) {
                            // Si no es JSON válido, usar el texto de respuesta
                            msg = 'Error del servidor: ' + xhr.responseText.substring(0, 200);
                        }
                    }

                    // Manejo específico de errores de Hacienda
                    if (xhr.responseJSON && xhr.responseJSON.hacienda_error) {
                        msg = 'Error de Hacienda: ' + xhr.responseJSON.error;
                        if (xhr.responseJSON.codigo) {
                            msg += ' (Código: ' + xhr.responseJSON.codigo + ')';
                        }
                        if (xhr.responseJSON.observaciones) {
                            msg += ' - Observaciones: ' + xhr.responseJSON.observaciones;
                        }
                    } else if (msg.includes('DOCUMENTO NO CUMPLE ESQUEMA JSON')) {
                        msg = 'Error de Hacienda: El documento no cumple con el esquema JSON requerido. Verifique la estructura de los datos.';
                    } else if (msg.includes('HACIENDA_REJECTED')) {
                        msg = 'Error de Hacienda: ' + msg;
                    }

                    if (xhr.status === 422) {
                        msg = 'Error de validación. Verifique que todos los campos estén completos.';
                    } else if (xhr.status === 500) {
                        msg = 'Error interno del servidor. Contacte al administrador.';
                    } else if (xhr.status === 404) {
                        msg = 'Recurso no encontrado. Verifique que la venta original existe.';
                    } else if (xhr.status === 403) {
                        msg = 'No tiene permisos para realizar esta acción.';
                    }

                    showError('Error (' + xhr.status + ')', msg + details);
                }
            });
            return false;
        });
    }

    // Select2
    if (select2.length) {
        select2.wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Seleccionar...',
            dropdownParent: select2.parent()
        });
    }

    // Función para calcular totales
    function calculateTotals() {
        let subtotalGravado = 0;
        let subtotalExento = 0;
        let subtotalNoSujeto = 0;
        let iva = 0;

        $('.product-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
            // precio siempre es el precio unitario (readonly)
            const precio = parseFloat(row.data('original-price')) || parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
            const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
            const subtotal = cantidad * precio;

            if (tipoVenta === 'gravada') {
                subtotalGravado += subtotal;
                iva += subtotal * 0.13;
            } else if (tipoVenta === 'exenta') {
                subtotalExento += subtotal;
            } else if (tipoVenta === 'nosujeta') {
                subtotalNoSujeto += subtotal;
            }
        });

        const total = subtotalGravado + subtotalExento + subtotalNoSujeto + iva;

        $('#subtotalGravado').val(subtotalGravado.toFixed(2));
        $('#iva').val(iva.toFixed(2));
        $('#subtotalExento').val(subtotalExento.toFixed(2));
        $('#subtotalNoSujeto').val(subtotalNoSujeto.toFixed(2));
        $('#total').val(total.toFixed(2));
    }

    // Función para calcular IVA de una fila
    function calculateRowIVA(row) {
        const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
        // precio siempre es el precio unitario (readonly)
        const precio = parseFloat(row.data('original-price')) || parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
        const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
        const subtotal = cantidad * precio;

        let iva = 0;
        if (tipoVenta === 'gravada') {
            iva = subtotal * 0.13;
        }
        row.find('.iva-display').text('$' + iva.toFixed(2));
    }

    // Event listeners para calcular totales
    $(document).on('input', 'input[name*="[cantidad]"], input[name*="[precio]"], select[name*="[tipo_venta]"]', function() {
        const row = $(this).closest('tr');
        calculateRowIVA(row);
        calculateTotals();
    });

    $(document).on('change', '.product-checkbox', function() {
        calculateTotals();
    });

    // Seleccionar todos los productos
    $('#selectAll').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
        calculateTotals();
    });

    // Validación en tiempo real
    $(document).on('input', 'input[name*="[cantidad]"], input[name*="[precio]"]', function() {
        var value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val(0);
        }
    });

    // Calcular totales iniciales
    calculateTotals();

    // Funciones de utilidad
    function showLoading() {
        Swal.fire({
            title: 'Procesando...',
            text: 'Creando nota de débito y enviando a Hacienda',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    function hideLoading() {
        Swal.close();
    }

    function showError(title, message) {
        Swal.fire({
            title: title,
            html: message,
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    }
});
