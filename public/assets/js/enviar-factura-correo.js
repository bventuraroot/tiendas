/**
 * Funcionalidad para enviar facturas por correo electrónico
 * Usa la configuración existente del .env
 */

// Verificar que jQuery esté disponible
if (typeof $ === 'undefined') {
}

// Verificar que SweetAlert2 esté disponible
if (typeof Swal === 'undefined') {
}

// Función global para enviar factura por correo
function enviarFacturaPorCorreo(facturaId, correoCliente = '', numeroFactura = '') {

    Swal.fire({
        title: 'Enviar Factura por Correo',
        html: `
            <div class="text-start">
                <label for="email-factura" class="form-label">Correo Electrónico:</label>
                <input type="email" id="email-factura" class="form-control"
                       placeholder="correo@ejemplo.com" value="${correoCliente}">

                <label for="nombre-cliente" class="form-label mt-3">Nombre del Cliente (opcional):</label>
                <input type="text" id="nombre-cliente" class="form-control"
                       placeholder="Nombre del cliente">

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        La factura se enviará como PDF adjunto
                    </small>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const email = document.getElementById('email-factura').value;
            const nombreCliente = document.getElementById('nombre-cliente').value;

            if (!email) {
                Swal.showValidationMessage('El correo electrónico es requerido');
                return false;
            }

            if (!validarEmail(email)) {
                Swal.showValidationMessage('El formato del correo no es válido');
                return false;
            }

            return { email, nombreCliente };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            enviarFactura(facturaId, result.value.email, result.value.nombreCliente, numeroFactura);
        }
    });
}

/**
 * Valida el formato del email
 */
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Envía la factura por correo
 */
function enviarFactura(facturaId, email, nombreCliente, numeroFactura) {

    // Mostrar loading
    Swal.fire({
        title: 'Enviando factura...',
        text: 'Por favor espere mientras se genera y envía el PDF',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Realizar petición AJAX
    $.ajax({
        url: '/sale/enviar-factura-correo',
        type: 'POST',
        data: {
            id_factura: facturaId,
            email: email,
            nombre_cliente: nombreCliente,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: (response) => {
            if (response.success) {
                Swal.fire({
                    title: '¡Factura Enviada!',
                    html: `
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3">La factura ha sido enviada exitosamente a:</p>
                            <strong class="text-primary">${email}</strong>
                            <br><br>
                            <small class="text-muted">
                                Factura: ${response.data?.numero_factura || numeroFactura}<br>
                                Empresa: ${response.data?.empresa || ''}
                            </small>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Error al enviar la factura',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: (xhr) => {
            let errorMessage = 'Error al enviar la factura';

            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    // Errores de validación
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                } else if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
            }

            Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {

    // Verificar que las dependencias estén disponibles
    if (typeof $ === 'undefined') {
        return;
    }

    if (typeof Swal === 'undefined') {
        return;
    }


    // Event listener para botones de envío de correo
    $(document).on('click', '.btn-enviar-correo', function(e) {
        e.preventDefault();

        const facturaId = $(this).data('factura-id');
        const correoCliente = $(this).data('correo-cliente') || '';
        const numeroFactura = $(this).data('numero-factura') || '';


        enviarFacturaPorCorreo(facturaId, correoCliente, numeroFactura);
    });


    // Verificar que los botones existan
    const botones = $('.btn-enviar-correo');
});

// Fallback: si jQuery no está disponible inmediatamente, intentar después de 1 segundo
setTimeout(function() {
    if (typeof $ !== 'undefined' && typeof window.enviarFacturaCorreoInicializado === 'undefined') {
        window.enviarFacturaCorreoInicializado = true;

        $(document).on('click', '.btn-enviar-correo', function(e) {
            e.preventDefault();

            const facturaId = $(this).data('factura-id');
            const correoCliente = $(this).data('correo-cliente') || '';
            const numeroFactura = $(this).data('numero-factura') || '';

            enviarFacturaPorCorreo(facturaId, correoCliente, numeroFactura);
        });

    }
}, 1000);

// Función global para usar desde otros archivos
window.enviarFacturaPorCorreo = enviarFacturaPorCorreo;
