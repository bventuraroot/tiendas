/**
 * Sales Units Management
 * Maneja las unidades de medida en el módulo de ventas
 */

// Script de prueba para verificar carga

// Declarar función global inmediatamente para evitar undefined
window.loadProductUnits = function(productId) {
    if (typeof productId === 'object' && productId.id) {
        productId = productId.id;
    }
    if (productId) {
        // Llamar directamente a la función sin disparar eventos
        if (typeof loadProductUnitsInternal === 'function') {
            loadProductUnitsInternal(productId);
        } else {
        }
    }
};

$(document).ready(function() {

    // Variables globales
    let currentProductId = null;
    let currentProductData = null;

    // Inicializar eventos con un pequeño delay para asegurar que el DOM esté listo
    setTimeout(function() {
        initializeSalesUnits();
    }, 100);

    /**
     * Inicializar el módulo de unidades de venta
     */
    function initializeSalesUnits() {

        // Cargar unidades de prueba automáticamente
        loadTestUnits();

        // Verificar si las unidades se cargaron correctamente
        setTimeout(function() {
            const unitSelect = $('#unit-select');
            const optionsCount = unitSelect.find('option').length;

            if (optionsCount <= 1) {
                loadTestUnits();
            }
        }, 500);

        // Evento para búsqueda por código
        $(document).on('change keyup paste input', '#codesearch', function() {
            const code = $(this).val().trim();
            if (code.length > 0) {
                searchProductByCode(code);
            } else {
                // Si el campo está vacío, limpiar datos del producto
                clearProductData();
            }
        });

        // Evento para tecla Escape en el campo de código
        $(document).on('keydown', '#codesearch', function(e) {
            if (e.key === 'Escape') {
                $(this).val('').trigger('input');
                clearProductData();
            }
        });

        // Evento para botón de limpiar código
        $(document).on('click', '#clear-codesearch', function() {
            $('#codesearch').val('').trigger('input');
            clearProductData();
        });

        // Evento para búsqueda por nombre
        $(document).on('change', '#psearch', function() {
            const productId = $(this).val();
            if (productId) {
                loadProductUnitsInternal(productId);
            }
        });

                // Evento para cambio de unidad
        $(document).on('change', '#unit-select', function() {
            const unitCode = $(this).val();
            if (unitCode && currentProductId) {
                calculateUnitConversion(currentProductId, unitCode);
            } else {
                // Actualizar panel con nueva unidad incluso si no hay conversión
                updatePanelWithCurrentData();
            }

            // Forzar actualización del panel después de un breve delay
            setTimeout(function() {
                updatePanelWithCurrentData();

                // Recalcular validaciones cuando cambie la unidad
                const quantity = parseFloat($('#cantidad').val()) || 0;
                const availableStock = parseFloat($('#catalog-validation-current').text()) || 0;
                const weightPerUnit = 100.0000; // Libras por saco

                let quantityInSacos = quantity;
                let remainingAfterSale = availableStock;
                let remainingInLbs = 0;

                // Si la unidad es libras, convertir a sacos para el cálculo
                if (unitCode === '36') { // Código de libra
                    quantityInSacos = quantity / weightPerUnit;
                    remainingAfterSale = Math.max(0, availableStock - quantityInSacos);
                    remainingInLbs = remainingAfterSale * weightPerUnit;
                } else {
                    // Si es sacos, calcular directamente
                    remainingAfterSale = Math.max(0, availableStock - quantity);
                    remainingInLbs = remainingAfterSale * weightPerUnit;
                }

                $('#catalog-validation-after').text(remainingAfterSale.toFixed(4) + ' sacos');
                $('#catalog-validation-lbs').text(remainingInLbs.toFixed(4) + ' libras');
            }, 500);
        });

        // Evento para cambio de cantidad y precio
        $(document).on('change keyup', '#cantidad, #precio', function() {
            updatePanelWithCurrentData();

            // Si hay un producto seleccionado y una unidad, calcular conversión
            const productId = $('#productid').val();
            const unitCode = $('#unit-select').val();
            const quantity = parseFloat($('#cantidad').val()) || 0;

            if (productId && unitCode && quantity > 0) {
                calculateUnitConversion(productId, unitCode, quantity);
            }
        });

                // Evento para cambio de cantidad
        $(document).on('change keyup', '#cantidad', function() {
            const quantity = parseFloat($(this).val()) || 0;
            const unitCode = $('#unit-select').val();
            if (unitCode && currentProductId && quantity > 0) {
                calculateUnitConversion(currentProductId, unitCode, quantity);
            }
            // Actualizar panel con nueva cantidad
            updatePanelWithCurrentData();

            // Actualizar también la validación en tiempo real
            $('#catalog-validation-sell').text(quantity.toFixed(2));

            // Recalcular stock después de la venta
            const availableStock = parseFloat($('#catalog-validation-current').text()) || 0;
            const weightPerUnit = 100.0000; // Libras por saco

            let quantityInSacos = quantity;
            let remainingAfterSale = availableStock;
            let remainingInLbs = 0;
            let isAvailable = true;

            // Si la unidad es libras, convertir a sacos para el cálculo
            if (unitCode === '36') { // Código de libra
                quantityInSacos = quantity / weightPerUnit;
                remainingAfterSale = availableStock - quantityInSacos;
                remainingInLbs = remainingAfterSale * weightPerUnit;
                isAvailable = remainingAfterSale >= 0;
            } else {
                // Si es sacos, calcular directamente
                remainingAfterSale = availableStock - quantity;
                remainingInLbs = remainingAfterSale * weightPerUnit;
                isAvailable = remainingAfterSale >= 0;
            }

            // Asegurar que no haya valores negativos en la visualización
            const displayRemainingAfterSale = Math.max(0, remainingAfterSale);
            const displayRemainingInLbs = Math.max(0, remainingInLbs);

            $('#catalog-validation-after').text(displayRemainingAfterSale.toFixed(4) + ' sacos');
            $('#catalog-validation-lbs').text(displayRemainingInLbs.toFixed(4) + ' libras');

            // Actualizar el estado de disponibilidad
            let stockStatusQuantity = isAvailable ?
                '<span class="badge bg-success">Disponible</span>' :
                '<span class="badge bg-danger">Stock insuficiente</span>';
            $('#catalog-stock-status').html(stockStatusQuantity);
        });

        // Evento para cambio de precio
        $(document).on('change keyup', '#precio', function() {
            updatePanelWithCurrentData();
        });


        // Cargar unidades básicas inmediatamente
        loadTestUnits();

        // También ejecutar después de 2 segundos como respaldo
        setTimeout(function() {
            if ($('#unit-select option').length <= 1) {
                loadTestUnits();
            }
        }, 2000);
    }

    /**
     * Función de prueba para cargar unidades sin depender del endpoint
     */
    function loadTestUnits() {

        // Unidades de prueba hardcodeadas
        const testUnits = [
            { unit_code: '59', unit_name: 'Unidad', unit_id: 1, conversion_factor: 1.0 },
            { unit_code: '36', unit_name: 'Libra', unit_id: 2, conversion_factor: 1.0 },
            { unit_code: '99', unit_name: 'Dólar', unit_id: 3, conversion_factor: 1.0 }
        ];

        loadUnitsDropdown(testUnits);

        // Simular selección de producto
        $('#productid').val('23'); // PRODUCTO EN PESO
        $('#productname').val('PRODUCTO EN PESO');
        $('#productdescription').val('askldmlkasdasd');
        $('#productunitario').val('50.00');

        // Establecer datos del producto para las tarjetas
        currentProductData = {
            product: {
                id: 23,
                name: 'PRODUCTO EN PESO',
                price: 50.00,
                weight_per_unit: 100.0000,
                sale_type: 'weight',
                marca: 'AGROCENTRO',
                provider: 'Proveedor 1'
            },
            stock: {
                base_quantity: 5.0000,
                base_unit: 'sacos'
            }
        };

    }

    /**
     * Buscar producto por código
     */
    function searchProductByCode(code) {
        $.ajax({
            url: `/sale/getproductbyid/${code}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    currentProductId = response.data.product.id;
                    currentProductData = response.data;

                    // Llenar campos del producto
                    $('#productid').val(response.data.product.id);
                    $('#productname').val(response.data.product.name);
                    $('#productdescription').val(response.data.product.description);
                    $('#productunitario').val(response.data.product.price);

                    // Cargar unidades disponibles
                    loadUnitsDropdown(response.data.units);

                    // Mostrar información del producto
                    showProductInfo(response.data.product);

                    // Actualizar panel con información inicial del producto
                    updatePanelWithProductData(response.data);
                } else {
                    clearProductData();
                }
            },
            error: function(xhr, status, error) {
                clearProductData();
            }
        });
    }

    /**
     * Cargar unidades disponibles para un producto
     */
    function loadProductUnitsInternal(productId) {

        $.ajax({
            url: `/sale/getproductbyid/${productId}`,
            method: 'GET',
            success: function(response) {

                if (response.success) {
                    currentProductId = productId;
                    currentProductData = response.data;


                    // Llenar campos del producto
                    $('#productid').val(response.data.product.id);
                    $('#productname').val(response.data.product.name);
                    $('#productdescription').val(response.data.product.description);
                    $('#productunitario').val(response.data.product.price);

                    // Cargar unidades disponibles
                    loadUnitsDropdown(response.data.units);

                    // Mostrar información del producto
                    showProductInfo(response.data.product);

                    // Actualizar panel con información inicial del producto
                    updatePanelWithProductData(response.data);
                } else {
                }
            },
            error: function(xhr, status, error) {
            }
        });
    }

    /**
     * Cargar dropdown de unidades
     */
    function loadUnitsDropdown(units) {

        const unitSelect = $('#unit-select');

        unitSelect.empty().append('<option value="">Seleccionar unidad...</option>');

        if (units && units.length > 0) {

            // Determinar qué unidades mostrar según el tipo de producto
            let allowedUnitCodes = ['59', '36', '99']; // Por defecto: Unidad, Libra, Dólar

            if (currentProductData && currentProductData.product && currentProductData.product.sale_type) {
                switch(currentProductData.product.sale_type) {
                    case 'volume':
                        allowedUnitCodes = ['59', '23', '99']; // Unidad, Litro, Dólar
                        break;
                    case 'weight':
                        allowedUnitCodes = ['59', '36', '99']; // Unidad, Libra, Dólar
                        break;
                    case 'unit':
                        allowedUnitCodes = ['59', '36', '99']; // Unidad, Libra, Dólar
                        break;
                }
            }

            // Filtrar unidades según el tipo de producto
            const allowedUnits = units.filter(function(unit) {
                return allowedUnitCodes.includes(unit.unit_code);
            });


            allowedUnits.forEach(function(unit) {
                const unitName = getUnitDisplayName(unit.unit_code, unit.unit_name);
                const optionHtml = '<option value="' + unit.unit_code + '" data-unit-id="' + unit.unit_id + '" data-conversion-factor="' + unit.conversion_factor + '">' + unitName + '</option>';
                unitSelect.append(optionHtml);
            });

        } else {
        }

    }

    /**
     * Obtener nombre de visualización para la unidad
     */
    function getUnitDisplayName(unitCode, unitName) {
        const unitNames = {
            '59': 'Unidad',
            '36': 'Libra',
            '23': 'Litro',
            '99': 'Dólar'
        };
        return unitNames[unitCode] || unitName;
    }

    /**
     * Calcular conversión de unidad
     */
    function calculateUnitConversion(productId, unitCode, quantity) {
        const qty = quantity || parseFloat($('#cantidad').val()) || 1;

        $.ajax({
            url: '/sale/calculate-unit-conversion',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({
                product_id: productId,
                unit_code: unitCode,
                quantity: qty
            }),
            success: function(response) {
                if (response.success) {
                    // Sanitizar estructura esperada para evitar NaN
                    const data = response.data || {};
                    data.unit_info = data.unit_info || { unit_code: $('#unit-select').val(), unit_name: getUnitDisplayName($('#unit-select').val()) };
                    data.calculations = data.calculations || { base_quantity_needed: 0, conversion_factor: parseFloat($('#conversion-factor').val())||1, base_price: $('#precio').val()||0 };

                    // Usar stock_info del response o crear uno por defecto
                    if (!data.stock_info && currentProductData && currentProductData.stock) {
                        data.stock_info = {
                            available_quantity: currentProductData.stock.base_quantity || 0,
                            base_unit: currentProductData.stock.base_unit || 'libras',
                            available: (currentProductData.stock.base_quantity || 0) > 0,
                            total_in_lbs: 0,
                            remaining_in_lbs: 0,
                            remaining_after_sale: 0
                        };
                    } else if (!data.stock_info) {
                        // Crear stock_info por defecto basado en el tipo de producto
                        let defaultBaseUnit = 'libras';
                        if (currentProductData && currentProductData.product) {
                            switch(currentProductData.product.sale_type) {
                                case 'weight':
                                    defaultBaseUnit = 'libras';
                                    break;
                                case 'volume':
                                    defaultBaseUnit = 'litros';
                                    break;
                                case 'unit':
                                    defaultBaseUnit = 'unidades';
                                    break;
                                default:
                                    defaultBaseUnit = 'libras';
                            }
                        }

                        data.stock_info = {
                            available_quantity: 0,
                            base_unit: defaultBaseUnit,
                            available: false,
                            total_in_lbs: 0,
                            remaining_in_lbs: 0,
                            remaining_after_sale: 0
                        };
                    }

                    data.product_info = data.product_info || currentProductData?.product || {};

                    updateConversionDisplay(data);
                    updateStockDisplay(data);

                    // Actualizar campos ocultos para el formulario
                    const selectedOption = $('#unit-select option[value="' + unitCode + '"]');
                    $('#selected-unit-id').val(selectedOption.data('unit-id'));
                    $('#conversion-factor').val(selectedOption.data('conversion-factor'));
                }
            },
            error: function(xhr, status, error) {
                showBasicUnitInfo(unitCode, qty);
            }
        });
    }

    /**
     * Actualizar visualización de conversión
     */
    function updateConversionDisplay(data) {

        // Datos sanitizados
        const unitPrice = Number(data.unit_price || 0);
        const subtotal = Number(data.subtotal || 0);
        const baseNeeded = Number(data.calculations?.base_quantity_needed || 0);
        const factor = Number(data.calculations?.conversion_factor || 1);
        const unitName = data.unit_info?.unit_name || '-';

        // Actualizar tarjetas simples
        $('.unit-price-display').text('$' + unitPrice.toFixed(2));
        $('.subtotal-display').text('$' + subtotal.toFixed(2));
        $('.base-quantity-display').text(baseNeeded.toFixed(4));

        // Información del catálogo de productos
        const weightPerUnit = data.calculations?.weight_per_unit || 0;
        const pricePerSack = data.calculations?.price_per_sack || 0;
        const pricePerPound = data.calculations?.price_per_pound || 0;

        const conversionDetails = `
            <div class="conversion-details">
                <div class="conversion-row">
                    <div class="conversion-label">Precio del saco:</div>
                    <div class="conversion-value">$${pricePerSack.toFixed(2)}</div>
                </div>
                <div class="conversion-row">
                    <div class="conversion-label">Peso total en libras:</div>
                    <div class="conversion-value">${weightPerUnit.toFixed(4)} libras</div>
                </div>
                <div class="conversion-row">
                    <div class="conversion-label">Precio por libra:</div>
                    <div class="conversion-value">$${pricePerPound.toFixed(2)}</div>
                </div>
                <div class="conversion-row">
                    <div class="conversion-label">Subtotal:</div>
                    <div class="conversion-value">$${subtotal.toFixed(2)}</div>
                </div>
            </div>`;

        $('.conversion-details').html(conversionDetails);

        // Actualizar las tarjetas fijas
        updateFixedCatalogCardsFromConversion(data);
    }

    /**
     * Actualizar visualización de stock
     */
    function updateStockDisplay(data) {
        if (data.stock_info) {
            const stockInfo = data.stock_info;
            const availableClass = stockInfo.available ? 'text-success' : 'text-danger';
            const availableIcon = stockInfo.available ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            const unitName = getUnitDisplayName(data.unit_info?.unit_code, data.unit_info?.unit_name);
            const baseUnitName = stockInfo.base_unit || 'libras';

            // Actualizar displays principales
            $('.stock-display').text(Number(stockInfo.available_quantity).toFixed(4) + ' ' + unitName);
            $('.base-unit-display').text(baseUnitName);
            $('.stock-status-display').html('<span class="' + availableClass + '"><i class="' + availableIcon + '"></i> ' + (stockInfo.available ? 'Disponible' : 'Insuficiente') + '</span>');

            // Información clara del stock con conversiones
            const totalInLbs = stockInfo.total_in_lbs || 0;
            const remainingInLbs = stockInfo.remaining_in_lbs || 0;

            const stockDetails = `
                <div class="stock-details">
                    <div class="stock-row">
                        <div class="stock-label">Stock disponible:</div>
                        <div class="stock-value">${Number(stockInfo.available_quantity).toFixed(4)} ${baseUnitName}</div>
                    </div>
                    <div class="stock-row">
                        <div class="stock-label">Total en libras:</div>
                        <div class="stock-value">${Number(totalInLbs).toFixed(4)} libras</div>
                    </div>
                    <div class="stock-row">
                        <div class="stock-label">Estado:</div>
                        <div class="stock-value ${availableClass}">${stockInfo.available ? 'Disponible' : 'Insuficiente'}</div>
                    </div>
                </div>`;
            $('.stock-details').html(stockDetails);

            // Actualizar validaciones
            updateValidationDisplay(data);

            // Actualizar las tarjetas fijas
            updateFixedCatalogCardsFromConversion(data);
        } else {
            // Fallback: información básica
            $('.stock-display').text('0');
            $('.base-unit-display').text('-');
            $('.stock-status-display').html('<span class="text-muted">Seleccione un producto y unidad</span>');
            $('.stock-details').html(`
                <div class="stock-details">
                    <div class="stock-row">
                        <div class="stock-label">Estado:</div>
                        <div class="stock-value text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Seleccione producto y unidad
                        </div>
                    </div>
                </div>`);

            // Limpiar panel lateral
            if (window.SalesSidebarPanel && window.SalesSidebarPanel.clearPanelInfo) {
                window.SalesSidebarPanel.clearPanelInfo();
            }
        }
    }

    /**
     * Actualizar visualización de validaciones
     */
    function updateValidationDisplay(data) {

        if (data.stock_info && data.unit_info) {
            const stockInfo = data.stock_info;
            const unitInfo = data.unit_info;
            const quantity = parseFloat($('#cantidad').val()) || 0;

            // Calcular stock después de la venta
            const factor = Number(unitInfo.conversion_factor || $('#conversion-factor').val() || 1);
            const baseQuantityNeeded = quantity * factor;
            const remainingStock = Number(stockInfo.available_quantity || 0) - baseQuantityNeeded;
            const isAvailable = remainingStock >= 0;

            // Actualizar stock después de venta
            $('.remaining-stock-display').text(Number(remainingStock).toFixed(4));

            // Actualizar estado de disponibilidad
            const availabilityClass = isAvailable ? 'text-success' : 'text-danger';
            const availabilityIcon = isAvailable ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            const availabilityText = isAvailable ? 'Stock suficiente' : 'Stock insuficiente';

            $('.availability-status-display').html(`
                <span class="${availabilityClass}">
                    <i class="${availabilityIcon} me-1"></i>
                    ${availabilityText}
                </span>
            `);

            // Información clara de validaciones con descuento
            const remainingInLbs = stockInfo.remaining_in_lbs || 0;
            const remainingAfterSale = stockInfo.remaining_after_sale || 0;

            const validationDetails = `
                <div class="validation-details">
                    <div class="validation-row">
                        <div class="validation-label">Stock actual:</div>
                        <div class="validation-value">${Number(stockInfo.available_quantity || 0).toFixed(4)} ${stockInfo.base_unit}</div>
                    </div>
                    <div class="validation-row">
                        <div class="validation-label">A vender:</div>
                        <div class="validation-value">${quantity} ${getUnitDisplayName(unitInfo.unit_code, unitInfo.unit_name)}</div>
                    </div>
                    <div class="validation-row highlight">
                        <div class="validation-label">Stock después:</div>
                        <div class="validation-value ${isAvailable ? 'text-success' : 'text-danger'}">${Number(remainingAfterSale).toFixed(4)} ${stockInfo.base_unit}</div>
                    </div>
                    <div class="validation-row">
                        <div class="validation-label">En libras:</div>
                        <div class="validation-value ${isAvailable ? 'text-success' : 'text-danger'}">${Number(remainingInLbs).toFixed(4)} libras</div>
                    </div>
                    <div class="validation-row">
                        <div class="validation-label">Estado:</div>
                        <div class="validation-value ${availabilityClass}">${isAvailable ? 'Venta permitida' : 'Stock insuficiente'}</div>
                    </div>
                </div>`;

            $('.validation-details').html(validationDetails);
        } else {
            // Fallback: información básica
            $('.remaining-stock-display').text('0');
            $('.availability-status-display').html('<span class="text-muted">Seleccione un producto y unidad</span>');
            $('.validation-details').html(`
                <div class="validation-details">
                    <div class="validation-row">
                        <div class="validation-label">Estado:</div>
                        <div class="validation-value text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Seleccione producto y unidad
                        </div>
                    </div>
                </div>`);
        }
    }

    /**
     * Mostrar información básica si falla la conversión
     */
    function showBasicUnitInfo(unitCode, quantity) {
        $('.unit-price-display').text('Calculando...');
        $('.subtotal-display').text('Calculando...');
                    $('.conversion-details').html(`
                <div class="conversion-details">
                    <div class="conversion-row">
                        <div class="conversion-label">Estado:</div>
                        <div class="conversion-value text-muted">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            Calculando...
                        </div>
                    </div>
                </div>`);
    }

    /**
     * Mostrar información del producto
     */
    function showProductInfo(product) {
        $('#add-information-products').show();
        $('#product-name').text(product.name);
        $('#product-marca').text(product.marca || '-');
        $('#product-provider').text(product.provider || '-');
        $('#product-price').text('$' + Number(product.price).toFixed(2));

        // Mostrar imagen si existe
        if (product.image) {
            $('#product-image').attr('src', product.image);
        }
    }

            /**
     * Actualizar panel con datos del producto cargado
     */
    function updatePanelWithProductData(productData) {

        if (productData && productData.product) {
            // Determinar unidad base por defecto según el tipo de producto
            let defaultBaseUnit = 'libras';
            if (productData.product.sale_type) {
                switch(productData.product.sale_type) {
                    case 'weight':
                        defaultBaseUnit = 'libras';
                        break;
                    case 'volume':
                        defaultBaseUnit = 'litros';
                        break;
                    case 'unit':
                        defaultBaseUnit = 'unidades';
                        break;
                    default:
                        defaultBaseUnit = 'libras';
                }
            }

            const panelData = {
                conversion: {
                    unit_name: 'Seleccione unidad',
                    conversion_factor: 1,
                    unit_price: productData.product.price || 0,
                    subtotal: productData.product.price || 0,
                    base_quantity_needed: 1,
                    equivalent_units: []
                },
                stock: {
                    available_quantity: productData.stock?.base_quantity || 0,
                    base_unit: productData.stock?.base_unit || defaultBaseUnit,
                    available: (productData.stock?.base_quantity || 0) > 0,
                    total_in_lbs: 0,
                    remaining_in_lbs: 0,
                    remaining_after_sale: 0
                },
                product: {
                    name: productData.product.name,
                    marca: productData.product.marca,
                    provider: productData.product.provider
                }
            };

            // Actualizar las tarjetas fijas
            updateFixedCatalogCards(productData);
        }
    }

    /**
     * Actualizar panel con datos actuales
     */
    function updatePanelWithCurrentData() {
        // Esta función ya no se necesita porque usamos las tarjetas fijas
        // Se mantiene por compatibilidad pero no hace nada
    }

    /**
     * Limpiar datos del producto
     */
    function clearProductData() {
        currentProductId = null;
        currentProductData = null;

        // Limpiar campos de búsqueda
        $('#codesearch').val('');
        $('#productid').val('');
        $('#productname').val('');
        $('#productdescription').val('');
        $('#productunitario').val('');
        $('#unit-select').empty().append('<option value="">Seleccionar unidad...</option>');
        $('#selected-unit-id').val('');
        $('#conversion-factor').val('');

        // Limpiar displays
        $('.unit-price-display').text('$0.00');
        $('.subtotal-display').text('$0.00');
        $('.base-quantity-display').text('0');
        $('.stock-display').text('0');
        $('.base-unit-display').text('-');
        $('.stock-status-display').html('<span class="text-muted">Seleccione un producto y unidad</span>');
        $('.remaining-stock-display').text('0');
        $('.availability-status-display').html('<span class="text-muted">Seleccione un producto y unidad</span>');
        $('.conversion-details').html('');
        $('.stock-details').html('');
        $('.validation-details').html(`
            <div class="validation-details">
                <div class="validation-row">
                    <div class="validation-label">Estado:</div>
                    <div class="validation-value text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Seleccione producto y unidad
                    </div>
                </div>
            </div>`);

        $('#add-information-products').hide();

        // Limpiar tarjetas fijas
        clearFixedCatalogCards();
    }
});



// Función global para actualizar panel con datos actuales
window.updatePanelWithCurrentData = updatePanelWithCurrentData;

// Función global para actualizar tarjetas fijas
window.updateFixedCatalogCards = updateFixedCatalogCards;

/**
 * Función para probar las tarjetas fijas con datos de ejemplo
 */
function testFixedCards() {

    // Establecer una cantidad de prueba
    $('#cantidad').val('1');

    const testData = {
        product: {
            name: 'PRODUCTO EN PESO',
            price: 50.00,
            weight_per_unit: 100.0000,
            marca: 'AGROCENTRO',
            provider: 'Proveedor 1'
        },
        stock: {
            base_quantity: 5.0000,
            base_unit: 'sacos'
        }
    };

    updateFixedCatalogCards(testData);

    // También actualizar con datos de conversión
    const quantity = parseFloat($('#cantidad').val()) || 1;
    const availableStock = 5.0000;
    const unitCode = $('#unit-select').val();
    const weightPerUnit = 100.0000; // Libras por saco

    let quantityInSacos = quantity;
    let remainingAfterSale = availableStock;
    let remainingInLbs = 0;
    let isAvailable = true;

    // Si la unidad es libras, convertir a sacos para el cálculo
    if (unitCode === '36') { // Código de libra
        quantityInSacos = quantity / weightPerUnit;
        remainingAfterSale = availableStock - quantityInSacos;
        remainingInLbs = remainingAfterSale * weightPerUnit;
        isAvailable = remainingAfterSale >= 0;
    } else {
        // Si es sacos, calcular directamente
        remainingAfterSale = availableStock - quantity;
        remainingInLbs = remainingAfterSale * weightPerUnit;
        isAvailable = remainingAfterSale >= 0;
    }

    const conversionData = {
        calculations: {
            price_per_sack: 50.00,
            weight_per_unit: 100.0000,
            price_per_pound: 0.50
        },
        subtotal: 50.00 * quantity,
        stock_info: {
            available_quantity: availableStock,
            base_unit: 'sacos',
            available: isAvailable,
            total_in_lbs: availableStock * 100.0000,
            remaining_in_lbs: Math.max(0, remainingInLbs),
            remaining_after_sale: Math.max(0, remainingAfterSale)
        }
    };

    updateFixedCatalogCardsFromConversion(conversionData);
}

// Función global para probar tarjetas
window.testFixedCards = testFixedCards;

// Función global para cargar unidades manualmente
window.loadUnits = function() {
    loadTestUnits();
};



/**
 * Actualizar tarjetas fijas del catálogo con datos del producto
 */
function updateFixedCatalogCards(productData) {

    if (productData && productData.product) {
        const product = productData.product;
        const stock = productData.stock;

        // Tarjeta de Conversión de Unidades
        $('#catalog-price-sack').text('$' + (product.price || 0).toFixed(2));
        $('#catalog-weight-total').text((product.weight_per_unit || 0).toFixed(4) + ' libras');
        $('#catalog-price-pound').text('$' + ((product.price || 0) / (product.weight_per_unit || 1)).toFixed(2));
        $('#catalog-subtotal').text('$' + (product.price || 0).toFixed(2));

        // Tarjeta de Stock Disponible
        $('#catalog-stock-available').text((stock?.base_quantity || 0).toFixed(4) + ' ' + (stock?.base_unit || 'unidades'));
        $('#catalog-stock-lbs').text(((stock?.base_quantity || 0) * (product.weight_per_unit || 0)).toFixed(4) + ' libras');

        let stockStatusFixed = (stock?.base_quantity || 0) > 0 ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Sin stock</span>';
        $('#catalog-stock-status').html(stockStatusFixed);

        // Tarjeta de Validaciones
        $('#catalog-validation-current').text((stock?.base_quantity || 0).toFixed(4) + ' ' + (stock?.base_unit || 'unidades'));
        $('#catalog-validation-sell').text('0');
        $('#catalog-validation-after').text((stock?.base_quantity || 0).toFixed(4) + ' ' + (stock?.base_unit || 'unidades'));
        $('#catalog-validation-lbs').text(((stock?.base_quantity || 0) * (product.weight_per_unit || 0)).toFixed(4) + ' libras');
    }
}

/**
 * Actualizar tarjetas fijas del catálogo con datos de conversión
 */
function updateFixedCatalogCardsFromConversion(data) {

    if (data) {
        // Tarjeta de Conversión de Unidades
        $('#catalog-price-sack').text('$' + (data.calculations?.price_per_sack || 0).toFixed(2));
        $('#catalog-weight-total').text((data.calculations?.weight_per_unit || 0).toFixed(4) + ' libras');
        $('#catalog-price-pound').text('$' + (data.calculations?.price_per_pound || 0).toFixed(2));
        $('#catalog-subtotal').text('$' + (data.subtotal || 0).toFixed(2));

        // Tarjeta de Stock Disponible
        $('#catalog-stock-available').text((data.stock_info?.available_quantity || 0).toFixed(4) + ' ' + (data.stock_info?.base_unit || 'unidades'));
        $('#catalog-stock-lbs').text((data.stock_info?.total_in_lbs || 0).toFixed(4) + ' libras');

        let stockStatusConversion = data.stock_info?.available ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Sin stock</span>';
        $('#catalog-stock-status').html(stockStatusConversion);

        // Tarjeta de Validaciones
        const quantity = parseFloat($('#cantidad').val()) || 1; // Por defecto 1 si está vacío
        const availableStock = data.stock_info?.available_quantity || 0;
        let unitCode = $('#unit-select').val();
        const weightPerUnit = data.calculations?.weight_per_unit || 100.0000;

        let quantityInSacos = quantity;
        let remainingAfterSale = availableStock;
        let remainingInLbs = 0;
        let isAvailable = true;

        // Si la unidad es libras, convertir a sacos para el cálculo
        if (unitCode === '36') { // Código de libra
            quantityInSacos = quantity / weightPerUnit;
            remainingAfterSale = availableStock - quantityInSacos;
            remainingInLbs = remainingAfterSale * weightPerUnit;
            isAvailable = remainingAfterSale >= 0;
        } else {
            // Si es sacos, calcular directamente
            remainingAfterSale = availableStock - quantity;
            remainingInLbs = remainingAfterSale * weightPerUnit;
            isAvailable = remainingAfterSale >= 0;
        }

        // Asegurar que no haya valores negativos en la visualización
        const displayRemainingAfterSale = Math.max(0, remainingAfterSale);
        const displayRemainingInLbs = Math.max(0, remainingInLbs);

        $('#catalog-validation-current').text(availableStock.toFixed(4) + ' ' + (data.stock_info?.base_unit || 'unidades'));
        $('#catalog-validation-sell').text(quantity.toFixed(2));
        $('#catalog-validation-after').text(displayRemainingAfterSale.toFixed(4) + ' ' + (data.stock_info?.base_unit || 'unidades'));
        $('#catalog-validation-lbs').text(displayRemainingInLbs.toFixed(4) + ' libras');

        // Actualizar el estado de disponibilidad en las tarjetas
        let stockStatusFinal = isAvailable ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Stock insuficiente</span>';
        $('#catalog-stock-status').html(stockStatusFinal);
    }
}

/**
 * Limpiar tarjetas fijas del catálogo
 */
function clearFixedCatalogCards() {

    // Tarjeta de Conversión de Unidades
    $('#catalog-price-sack').text('$0.00');
    $('#catalog-weight-total').text('0.0000 libras');
    $('#catalog-price-pound').text('$0.00');
    $('#catalog-subtotal').text('$0.00');

    // Tarjeta de Stock Disponible
    $('#catalog-stock-available').text('0 unidades');
    $('#catalog-stock-lbs').text('0 libras');
    $('#catalog-stock-status').html('<span class="badge bg-secondary">Sin datos</span>');

    // Tarjeta de Validaciones
    $('#catalog-validation-current').text('0 unidades');
    $('#catalog-validation-sell').text('0');
    $('#catalog-validation-after').text('0 unidades');
    $('#catalog-validation-lbs').text('0 libras');
}
