/**
 * Módulo de Pre-Ventas para Menudeo
 * Optimizado para uso con pistola de código de barras
 */

class PreSalesManager {
    constructor() {
        this.currentSaleId = null;
        this.currentCompanyId = null;
        this.currentProduct = null;
        this.sessionStartTime = null;
        this.sessionTimer = null;
        this.sessionMonitor = null;
        this.warningShown = false;
        this.expiredAlertShown = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadClients();
        this.initializeProductSearch();
        this.focusBarcodeInput();
    }

    setupEventListeners() {
        // Barcode input events
        $('#barcode-input').on('keydown', (e) => {
            if (e.keyCode === 13) { // Enter key
                e.preventDefault();
                this.searchProduct();
            }
        });

        // Product search select2 events
        $('#product-search').on('change', (e) => {
            const selectedProductId = $(e.target).val();
            if (selectedProductId && selectedProductId !== "") {
                this.searchProductById(selectedProductId);
            }
        });

        // Quantity input events
        $('#quantity-input').on('input', () => {
            this.updateProductTotal();
        });

        // Price input events
        $('#product-price-edit').on('input', () => {
            this.updateProductTotal();
        });

        // Keyboard shortcuts
        $(document).on('keydown', (e) => {
            // Ctrl + N: Nueva sesión
            if (e.ctrlKey && e.keyCode === 78) {
                e.preventDefault();
                this.startNewSession();
            }

            // Ctrl + F: Finalizar venta
            if (e.ctrlKey && e.keyCode === 70) {
                e.preventDefault();
                this.finalizeSale();
            }

            // Ctrl + C: Cancelar sesión
            if (e.ctrlKey && e.keyCode === 67) {
                e.preventDefault();
                this.cancelSession();
            }
        });

        // Inicializar Select2 para productos
        this.initializeProductSelect2();
    }

    initializeProductSelect2() {
        // Función para formatear el estado del select2 (igual que en ventas)
        const formatState = (state) => {
            if (state.id === '' || state.id === '0') {
                return state.text;
            }
            // Verificar que state.title existe y no es undefined
            const imageSrc = state.title && state.title !== 'undefined' ? state.title : 'default.png';
            const $state = $(
                '<span><img src="' + window.presalesConfig.baseUrl + '/assets/img/products/' + imageSrc + '" class="imagen-producto-select2" /> ' + state.text + '</span>'
            );
            return $state;
        };

        // Inicializar Select2
        const $select = $('#product-search');
        if ($select.length) {
            $select.wrap('<div class="position-relative"></div>').select2({
                placeholder: "Seleccione un producto",
                dropdownParent: $select.parent(),
                templateResult: formatState,
                templateSelection: formatState
            });

            // Cargar todos los productos
            this.loadAllProducts();
        }
    }

    loadAllProducts() {
        $.ajax({
            url: window.presalesConfig.baseUrl + '/product/getproductall',
            method: 'GET',
            success: (response) => {
                const $select = $('#product-search');
                $select.empty().append('<option value="">Seleccione un producto</option>');

                response.forEach((product) => {
                    const optionText = product.name.toUpperCase() + ' | Descripción: ' + product.description + ' | Proveedor: ' + product.nameprovider;
                    $select.append(
                        '<option value="' + product.id + '" title="' + (product.image || 'default.png') + '">' + optionText + '</option>'
                    );
                });

                // Configurar el evento change
                $select.off('change').on('change', (e) => {
                    const selectedValue = $(e.target).val();
                    if (selectedValue && selectedValue !== '') {
                        this.searchProductById(selectedValue);
                    }
                });
            },
            error: (xhr) => {
                this.showAlert('Error', 'Error al cargar la lista de productos', 'error');
            }
        });
    }

    searchProductById(productId) {
        if (!productId || !this.currentSaleId) {
            this.showAlert('Error', 'Debe iniciar una sesión primero', 'error');
            return;
        }

        $.ajax({
            url: window.presalesConfig.baseUrl + '/product/getproductid/' + btoa(productId),
            method: 'GET',
            success: (response) => {
                if (response && response.length > 0) {
                    const product = response[0];
                    this.currentProduct = {
                        id: product.id,
                        code: product.code,
                        name: product.name,
                        description: product.description,
                        price: product.price,
                        stock: product.stock || 0,
                        image: product.image || 'default.png'
                    };

                    this.showProductInfo();
                    $('#add-product-btn').prop('disabled', false);
                    $('#product-search').val('').trigger('change');
                }
            },
            error: (xhr) => {
                this.showAlert('Error', 'Error al buscar el producto', 'error');
            }
        });
    }

    focusBarcodeInput() {
        $('#barcode-input').focus();
    }

    initializeProductSearch() {
        // Inicializar select2 para búsqueda de productos
        var selectProductSearch = $("#product-search");

        if (selectProductSearch.length) {
            selectProductSearch.wrap('<div class="position-relative"></div>').select2({
                placeholder: "Seleccione un producto",
                dropdownParent: selectProductSearch.parent(),
                templateResult: this.formatProductState
            });

            // Cargar productos disponibles
            this.loadProducts();
        }
    }

    formatProductState(state) {
        if (state.id === "" || state.id === "0") {
            return state.text;
        }

        var imageSrc = state.title && state.title !== 'undefined' ? state.title : 'default.png';
        var $state = $(
            '<span><img src="/assets/img/products/' + imageSrc + '" class="imagen-producto-select2" /> ' + state.text + '</span>'
        );
        return $state;
    }

    loadProducts() {
        $.ajax({
            url: "/product/getproductall",
            method: "GET",
            success: (response) => {
                $("#product-search").append('<option value="">Seleccione un producto</option>');
                $.each(response, function (index, value) {
                    $("#product-search").append(
                        '<option value="' + value.id + '" title="' + value.image + '">' +
                        value.name.toUpperCase() + " | Marca: " + value.marcaname + " | Proveedor: " + value.nameprovider +
                        "</option>"
                    );
                });
            },
            error: (xhr) => {
            }
        });
    }

    startNewSession() {
        // Iniciar sesión automáticamente con configuración predeterminada
        const companyId = window.presalesConfig.companyId || 1; // Usar empresa del usuario o empresa 1 por defecto
        const clientId = 15; // Cliente predeterminado: "CLIENTES VARIOS"
        const acuenta = 'Venta al menudeo';

        this.loadOrCreateSession(companyId, clientId, acuenta);
    }

    loadOrCreateSession(companyId, clientId, acuenta) {
        // Método para cargar sesión existente o crear una nueva
        $.ajax({
            url: window.presalesConfig.routes.startSession,
            method: 'POST',
            data: {
                company_id: companyId,
                client_id: clientId,
                acuenta: acuenta,
                force_new: false, // NO forzar nueva sesión, cargar existente si hay
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    // Nueva sesión creada
                    this.currentSaleId = response.sale_id;
                    this.currentCompanyId = companyId;
                    this.sessionStartTime = new Date();

                    $('#startSessionModal').modal('hide');
                    this.showSessionInfo();
                    this.showAlert('Éxito', response.message, 'success');

                    // Iniciar monitoreo de sesión
                    this.startSessionMonitoring();
                } else {
                    // Sesión existente encontrada
                    if (response.existing_session_id) {
                        // Cargar la sesión existente
                        this.currentSaleId = response.existing_session_id;
                        this.currentCompanyId = companyId;
                        this.sessionStartTime = new Date();

                        $('#startSessionModal').modal('hide');
                        this.showSessionInfo();
                        this.showAlert('Info', 'Sesión existente cargada', 'info');

                        // Cargar los productos de la sesión existente
                        this.loadSaleDetails();
                        // Iniciar monitoreo de sesión
                        this.startSessionMonitoring();
                    } else {
                        this.showAlert('Error', response?.message || 'Error al iniciar sesión', 'error');
                    }
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;

                if (xhr.status === 409 && response?.existing_session_id) {
                    // Sesión existente encontrada (status 409)
                    this.currentSaleId = response.existing_session_id;
                    this.currentCompanyId = companyId;
                    this.sessionStartTime = new Date();

                    $('#startSessionModal').modal('hide');
                    this.showSessionInfo();
                    this.showAlert('Info', 'Sesión existente cargada automáticamente', 'info');

                    // Cargar los productos de la sesión existente
                    this.loadSaleDetails();
                    // Iniciar monitoreo de sesión
                    this.startSessionMonitoring();
                } else {
                    this.showAlert('Error', response?.message || 'Error al iniciar sesión', 'error');
                }
            }
        });
    }

    forceNewSession() {
        // Forzar creación de nueva sesión cancelando la actual
        const companyId = window.presalesConfig.companyId || 1;
        const clientId = 15; // Cliente predeterminado: "CLIENTES VARIOS"
        const acuenta = 'Venta al menudeo';

        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se cancelará la sesión actual y se creará una nueva. Se perderán todos los productos agregados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear nueva sesión',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                this.forceCreateNewSession(companyId, clientId, acuenta);
            }
        });
    }

    forceCreateNewSession(companyId, clientId, acuenta) {
        // Método para forzar la creación de una nueva sesión cancelando la anterior
        $.ajax({
            url: window.presalesConfig.routes.cancelSession,
            method: 'POST',
            data: {
                force: true, // Indicar que es forzado
                _token: window.presalesConfig.csrfToken
            },
            success: () => {
                // Después de cancelar, crear nueva sesión
                this.createNewSession(companyId, clientId, acuenta);
            },
            error: () => {
                // Si falla cancelar, intentar crear nueva sesión de cualquier modo
                this.createNewSession(companyId, clientId, acuenta);
            }
        });
    }

    createNewSession(companyId, clientId, acuenta) {
        // Método para crear una nueva sesión sin verificaciones previas
        $.ajax({
            url: window.presalesConfig.routes.startSession,
            method: 'POST',
            data: {
                company_id: companyId,
                client_id: clientId,
                acuenta: acuenta,
                force_new: true, // Indicar que debe forzar nueva sesión
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.currentSaleId = response.sale_id;
                    this.currentCompanyId = companyId;
                    this.sessionStartTime = new Date();

                    $('#startSessionModal').modal('hide');
                    this.showSessionInfo();
                    this.showAlert('Éxito', 'Nueva sesión creada correctamente', 'success');

                    // Limpiar modal
                    $('#modal-company-select').val('');
                    $('#modal-client-select').val('').trigger('change');
                    $('#modal-acuenta').val('');

                    // Iniciar monitoreo de sesión
                    this.startSessionMonitoring();
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                this.showAlert('Error', response?.message || 'Error al crear nueva sesión', 'error');
            }
        });
    }

    confirmStartSession() {
        const companyId = $('#modal-company-select').val();
        const clientId = $('#modal-client-select').val();
        const acuenta = $('#modal-acuenta').val();

        if (!companyId) {
            this.showAlert('Error', 'Debe seleccionar una empresa', 'error');
            return;
        }

        $.ajax({
            url: window.presalesConfig.routes.startSession,
            method: 'POST',
            data: {
                company_id: companyId,
                client_id: clientId,
                acuenta: acuenta,
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.currentSaleId = response.sale_id;
                    this.currentCompanyId = companyId;
                    this.sessionStartTime = new Date();

                    $('#startSessionModal').modal('hide');
                    this.showSessionInfo();
                    this.showAlert('Éxito', response.message, 'success');

                    // Limpiar modal
                    $('#modal-company-select').val('');
                    $('#modal-client-select').val('').trigger('change');
                    $('#modal-acuenta').val('');

                    // Iniciar monitoreo de sesión
                    this.startSessionMonitoring();
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                if (xhr.status === 409 && response.existing_session_id) {
                    // Sesión existente encontrada
                    // Verificar si la sesión está expirada antes de preguntar
                    if (response.is_expired === true) {
                        // Si está expirada, mostrar mensaje y crear nueva automáticamente
                        this.showAlert('Info', 'Tu sesión anterior había expirado. Creando nueva sesión...', 'info');
                        // Intentar crear nueva sesión automáticamente después de un momento
                        setTimeout(() => {
                            this.confirmStartSession();
                        }, 1000);
                        return;
                    }

                    // Si la sesión está vigente, preguntar si desea continuar
                    const sessionTime = response.session_time || 'hace un momento';
                    const sessionAge = response.session_age_minutes || 0;

                    Swal.fire({
                        title: 'Sesión activa encontrada',
                        text: `Ya tienes una sesión activa iniciada ${sessionTime} (${sessionAge} minutos). ¿Deseas continuar con esa sesión?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Continuar sesión',
                        cancelButtonText: 'Crear nueva',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Continuar con la sesión existente
                            this.currentSaleId = response.existing_session_id;
                            this.currentCompanyId = companyId;
                            this.sessionStartTime = new Date();

                            $('#startSessionModal').modal('hide');
                            this.showSessionInfo();
                            this.showAlert('Éxito', 'Continuando con sesión existente', 'success');

                            // Limpiar modal
                            $('#modal-company-select').val('');
                            $('#modal-client-select').val('').trigger('change');
                            $('#modal-acuenta').val('');

                            // Cargar los productos de la sesión existente
                            this.loadSaleDetails();
                            // Iniciar monitoreo de sesión
                            this.startSessionMonitoring();
                        } else {
                            // Cancelar la sesión existente y crear una nueva
                            this.forceCreateNewSession(companyId, clientId, acuenta);
                        }
                    });
                } else {
                    this.showAlert('Error', response?.message || 'Error al iniciar sesión', 'error');
                }
            }
        });
    }

    showSessionInfo() {
        $('#session-info').show();
        $('#session-id').text('#' + this.currentSaleId);
        this.updateSessionTime();

        // Actualizar cada segundo
        this.sessionTimer = setInterval(() => {
            this.updateSessionTime();
        }, 1000);
    }

    updateSessionTime() {
        if (this.sessionStartTime) {
            const now = new Date();
            const diff = Math.floor((now - this.sessionStartTime) / 1000);
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            $('#session-time').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
        }
    }

    searchProduct() {
        const code = $('#barcode-input').val().trim();
        if (!code) return;

        if (!this.currentSaleId) {
            this.showAlert('Error', 'Debe iniciar una sesión primero', 'error');
            return;
        }

        $.ajax({
            url: window.presalesConfig.routes.searchProduct,
            method: 'POST',
            data: {
                code: code,
                company_id: this.currentCompanyId,
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    // Producto único encontrado
                    this.currentProduct = response.product;
                    this.showProductInfo();
                    $('#add-product-btn').prop('disabled', false);
                    $('#barcode-input').val('').focus();
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                if (xhr.status === 404 && response?.is_expired === true) {
                    // Sesión expirada durante la búsqueda
                    this.showSessionExpired();
                } else {
                    this.showAlert('Error', response?.message || 'Producto no encontrado', 'error');
                    $('#barcode-input').val('').focus();
                }
            }
        });
    }

    showProductInfo() {
        if (!this.currentProduct) return;

        $('#product-name').text(this.currentProduct.name);
        $('#product-description').text(this.currentProduct.description);
        $('#product-code').text(this.currentProduct.code);
        $('#product-price').text('$' + parseFloat(this.currentProduct.price).toFixed(2));
        $('#product-stock').text(this.currentProduct.stock);
        $('#product-image').attr('src', window.presalesConfig.baseUrl + '/assets/img/products/' + this.currentProduct.image);

        this.updateProductTotal();
        $('#product-info').show();
    }

    updateProductTotal() {
        if (!this.currentProduct) return;

        const quantity = parseInt($('#quantity-input').val()) || 1;
        const total = this.currentProduct.price * quantity;
        $('#product-total').text('$' + total.toFixed(2));
    }

    addProduct() {
        if (!this.currentProduct || !this.currentSaleId) return;

        const quantity = parseInt($('#quantity-input').val()) || 1;
        const price = this.currentProduct.price;

        $.ajax({
            url: window.presalesConfig.routes.addProduct,
            method: 'POST',
            data: {
                sale_id: this.currentSaleId,
                product_id: this.currentProduct.id,
                quantity: quantity,
                price: price,
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.currentProduct = null;
                    $('#product-info').hide();
                    $('#add-product-btn').prop('disabled', true);
                    $('#quantity-input').val(1);
                    this.loadSaleDetails();
                    this.showAlert('Éxito', response.message, 'success');
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                if (xhr.status === 404 && response?.is_expired === true) {
                    // Sesión expirada durante la adición de producto
                    this.showSessionExpired();
                } else {
                    this.showAlert('Error', response?.message || 'Error al agregar producto', 'error');
                }
            }
        });
    }

    loadSaleDetails() {
        if (!this.currentSaleId) return;

        $.ajax({
            url: window.presalesConfig.routes.getDetails,
            method: 'POST',
            data: {
                sale_id: this.currentSaleId,
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.updateSaleItemsTable(response.details);
                    this.updateTotals(response.totals);
                    this.updateButtons();
                }
            },
            error: (xhr) => {
                const response = xhr.responseJSON;
                if (xhr.status === 404 && response?.is_expired === true) {
                    // Sesión expirada mientras se cargaban los detalles
                    this.showSessionExpired();
                } else {
                    // No mostrar error al usuario a menos que sea crítico
                }
            }
        });
    }

    updateSaleItemsTable(details) {
        const tbody = $('#sale-items-body');
        tbody.empty();

        if (details.length === 0) {
            tbody.append(`
                <tr id="no-items-row">
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-shopping-cart fs-1 mb-2"></i>
                        <br>
                        No hay productos en la venta
                    </td>
                </tr>
            `);
            return;
        }

        details.forEach((detail) => {
            const row = `
                <tr>
                    <td>${detail.amountp}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${window.presalesConfig.baseUrl}/assets/img/products/${detail.product.image || 'default.png'}"
                                 class="rounded me-2"
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <div>
                                <div class="fw-bold">${detail.product.name}</div>
                                <small class="text-muted">${detail.product.code}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">$${parseFloat(detail.priceunit).toFixed(2)}</td>
                    <td class="text-end">$${parseFloat(detail.pricesale + detail.nosujeta + detail.exempt).toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="window.preSalesManager.removeProduct(${detail.id})">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    updateTotals(totals) {
        $('#subtotal-amount').text('$' + parseFloat(totals.subtotal).toFixed(2));
        // $('#iva-amount').text('$' + parseFloat(totals.iva).toFixed(2)); // IVA comentado
        $('#nosujeta-amount').text('$' + parseFloat(totals.nosujeta).toFixed(2));
        $('#exempt-amount').text('$' + parseFloat(totals.exempt).toFixed(2));
        $('#total-amount').text('$' + parseFloat(totals.total).toFixed(2));
    }

    updateButtons() {
        const hasItems = $('#sale-items-body tr').length > 1; // Más de la fila "no items"
        $('#finalize-btn').prop('disabled', !hasItems);
        // $('#print-btn').prop('disabled', !hasItems); // Botón de imprimir comentado
    }

    removeProduct(detailId) {
        Swal.fire({
            title: '¿Está seguro?',
            text: '¿Desea remover este producto de la venta?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, remover',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.presalesConfig.routes.removeProduct,
                    method: 'POST',
                    data: {
                        detail_id: detailId,
                        _token: window.presalesConfig.csrfToken
                    },
                    success: (response) => {
                        if (response.success) {
                            this.loadSaleDetails();
                            this.showAlert('Éxito', response.message, 'success');
                        }
                    },
                    error: (xhr) => {
                        this.showAlert('Error', xhr.responseJSON?.message || 'Error al remover producto', 'error');
                    }
                });
            }
        });
    }

    finalizeSale() {
        if (!this.currentSaleId) return;

        const typedocumentId = $('#typedocument-select').val();
        const clientId = $('#client-select').val();
        const acuenta = $('#acuenta-input').val() || 'Venta al menudeo';
        const waytopay = $('#payment-method').val();

        if (!typedocumentId) {
            this.showAlert('Error', 'Debe seleccionar un tipo de documento', 'error');
            return;
        }

        if (!clientId) {
            this.showAlert('Error', 'Debe seleccionar un cliente', 'error');
            return;
        }

        $.ajax({
            url: window.presalesConfig.routes.finalize,
            method: 'POST',
            data: {
                sale_id: this.currentSaleId,
                typedocument_id: typedocumentId,
                client_id: clientId,
                acuenta: acuenta,
                waytopay: waytopay,
                _token: window.presalesConfig.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    Swal.fire({
                        title: '¡Borrador Creado!',
                        text: `Borrador de factura creado exitosamente. Total: $${parseFloat(response.total).toFixed(2)}. El número de correlativo se asignará al finalizar la factura.`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then(() => {
                        // Automáticamente resetear la sesión para permitir crear una nueva
                        this.resetSession();
                    });
                }
            },
            error: (xhr) => {
                this.showAlert('Error', xhr.responseJSON?.message || 'Error al crear borrador', 'error');
            }
        });
    }

    cancelSession() {
        if (!this.currentSaleId) return;

        Swal.fire({
            title: '¿Está seguro?',
            text: '¿Desea cancelar la sesión actual? Se perderán todos los productos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.presalesConfig.routes.cancel,
                    method: 'POST',
                    data: {
                        sale_id: this.currentSaleId,
                        _token: window.presalesConfig.csrfToken
                    },
                    success: (response) => {
                        if (response.success) {
                            this.resetSession();
                            this.showAlert('Éxito', response.message, 'success');
                        }
                    },
                    error: (xhr) => {
                        this.showAlert('Error', xhr.responseJSON?.message || 'Error al cancelar sesión', 'error');
                    }
                });
            }
        });
    }

    resetSession() {
        this.currentSaleId = null;
        this.currentCompanyId = null;
        this.currentProduct = null;
        this.sessionStartTime = null;

        if (this.sessionTimer) {
            clearInterval(this.sessionTimer);
            this.sessionTimer = null;
        }

        $('#session-info').hide();
        $('#product-info').hide();
        $('#sale-items-body').html(`
            <tr id="no-items-row">
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="ti ti-shopping-cart fs-1 mb-2"></i>
                    <br>
                    No hay productos en la venta
                </td>
            </tr>
        `);

        this.updateTotals({
            subtotal: 0,
            iva: 0,
            nosujeta: 0,
            exempt: 0,
            total: 0
        });

        this.updateButtons();
        this.focusBarcodeInput();
    }

    showDailyStats() {
        $.ajax({
            url: window.presalesConfig.routes.dailyStats,
            method: 'GET',
            success: (response) => {
                if (response.success) {
                    $('#total-sales').text(response.stats.total_sales || 0);
                    $('#menudeo-sales').text(response.stats.menudeo_sales || 0);
                    $('#total-amount-stats').text('$' + parseFloat(response.stats.total_amount || 0).toFixed(2));
                    $('#dailyStatsModal').modal('show');
                }
            }
        });
    }

    loadClients() {
        $.ajax({
            url: window.presalesConfig.routes.clients,
            method: 'GET',
            success: (response) => {
                if (response.success) {
                    const clientSelect = $('#client-select, #modal-client-select');
                    clientSelect.empty();
                    clientSelect.append('<option value="">Seleccione un cliente</option>');
                    clientSelect.append('<option selected value="15">CLIENTES VARIOS</option>');
                }
            }
        });
    }

    printReceipt() {
        if (!this.currentSaleId) return;

        // Abrir ventana de impresión
        window.open(`${window.presalesConfig.routes.printReceipt}?sale_id=${this.currentSaleId}`, '_blank');
    }

    showAlert(title, message, type) {
        Swal.fire(title, message, type);
    }

    /**
     * Iniciar monitoreo de sesión para verificar expiración
     */
    startSessionMonitoring() {
        // Verificar cada 5 minutos
        this.sessionMonitor = setInterval(() => {
            this.checkSessionStatus();
        }, 5 * 60 * 1000); // 5 minutos

        // Verificar inmediatamente
        this.checkSessionStatus();
    }

    /**
     * Verificar el estado de la sesión actual
     */
    checkSessionStatus() {
        if (!this.currentSaleId) return;

        $.ajax({
            url: window.presalesConfig.routes.sessionInfo,
            method: 'GET',
            data: {
                session_id: this.currentSaleId
            },
            success: (response) => {
                if (response.success) {
                    this.updateSessionInfo(response);

                    // Mostrar advertencia si la sesión está por expirar
                    if (response.expires_in_minutes <= 30 && response.expires_in_minutes > 0) {
                        this.showSessionWarning(response.expires_in_minutes);
                    }

                    // Solo mostrar alerta si el backend marca is_expired como true
                    if (response.is_expired === true) {
                        this.showSessionExpired();
                    }
                }
            },
            error: (xhr) => {
                if (xhr.status === 404) {
                    const response = xhr.responseJSON;
                    if (response && response.is_expired === true) {
                        // Sesión expirada confirmada por el backend
                        this.showSessionExpired();
                    } else {
                        // Sesión no encontrada por otra razón
                        this.showAlert('Error', 'No se pudo verificar el estado de la sesión', 'warning');
                        this.resetSession();
                    }
                } else {
                    // Otros errores de red
                }
            }
        });
    }

    /**
     * Actualizar información de la sesión en la interfaz
     */
    updateSessionInfo(response) {
        $('#session-age').text(response.session_age_minutes + ' min');
        $('#session-created').text(response.created_at_formatted);

        if (response.is_expired === true) {
            $('#session-status').text('EXPIRADA').removeClass('badge-success').addClass('badge-danger');
        } else if (response.expires_in_minutes <= 30) {
            $('#session-status').text('POR EXPIRAR').addClass('badge-warning').removeClass('badge-success badge-danger');
        } else {
            $('#session-status').text('ACTIVA').removeClass('badge-warning badge-danger').addClass('badge-success');
        }
    }

    /**
     * Mostrar advertencia de sesión por expirar
     */
    showSessionWarning(minutesLeft) {
        if (this.warningShown) return; // Evitar múltiples advertencias

        this.warningShown = true;
        Swal.fire({
            title: 'Sesión por expirar',
            text: `Tu sesión expirará en ${minutesLeft} minutos. ¿Deseas extender la sesión?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Extender',
            cancelButtonText: 'Finalizar ahora'
        }).then((result) => {
            this.warningShown = false;
            if (result.isConfirmed) {
                // Aquí podrías implementar lógica para extender la sesión
                this.showAlert('Info', 'La sesión se mantendrá activa por 4 horas más', 'info');
            } else {
                this.finalizeSale();
            }
        });
    }

    /**
     * Mostrar alerta de sesión expirada
     */
    showSessionExpired() {
        // Evitar mostrar múltiples alertas
        if (this.expiredAlertShown) return;
        this.expiredAlertShown = true;

        Swal.fire({
            title: 'Sesión expirada',
            text: 'Tu sesión ha expirado después de 2 horas de inactividad. Puedes iniciar una nueva sesión.',
            icon: 'warning',
            confirmButtonText: 'Nueva sesión',
            allowOutsideClick: false
        }).then(() => {
            this.resetSession();
            // Permitir que se pueda mostrar la alerta nuevamente en el futuro
            this.expiredAlertShown = false;
            // Automáticamente abrir el modal para nueva sesión
            this.startNewSession();
        });
    }

    /**
     * Limpiar monitoreo de sesión
     */
    stopSessionMonitoring() {
        if (this.sessionMonitor) {
            clearInterval(this.sessionMonitor);
            this.sessionMonitor = null;
        }
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.preSalesManager = new PreSalesManager();

    // Exponer funciones globales para compatibilidad
    window.startNewSession = () => preSalesManager.startNewSession();
    window.forceNewSession = () => preSalesManager.forceNewSession();
    window.confirmStartSession = () => preSalesManager.confirmStartSession();
    window.searchProduct = () => preSalesManager.searchProduct();
    window.addProduct = () => preSalesManager.addProduct();
    window.removeProduct = (id) => preSalesManager.removeProduct(id);
    window.finalizeSale = () => preSalesManager.finalizeSale();
    window.cancelSession = () => preSalesManager.cancelSession();
    window.showDailyStats = () => preSalesManager.showDailyStats();
    window.printReceipt = () => preSalesManager.printReceipt();
});
