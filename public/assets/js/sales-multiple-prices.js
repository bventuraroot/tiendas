/**
 * Sistema de Precios Múltiples para Ventas
 * Integración con el módulo de ventas para manejar precios por unidad y tipo de cliente
 * Sistema NO INTRUSIVO - Solo se activa cuando hay precios múltiples configurados
 */

// Sistema de Precios Múltiples para Ventas
// Integración con el módulo de ventas para manejar precios por unidad y tipo de cliente

class SalesMultiplePrices {
    constructor() {
        this.currentProductId = null;
        this.currentUnitId = null;
        this.currentPriceType = 'regular';
        this.priceCache = new Map();
        this.isInitialized = false;
        this.isActive = false; // Flag para saber si el sistema está activo
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializePriceSelector();
        this.isInitialized = true;
    }

    bindEvents() {


        // Evento cuando se selecciona un producto (Select2)
        $(document).on('select2:select', '#psearch, .select2psearch', (e) => {
            const productId = e.params.data.id;
            if (productId && productId !== '0') {
                // Verificar si tiene precios múltiples primero
                this.checkProductForMultiplePrices(productId);
            } else {
                this.deactivateMultiplePrices();
            }
        });

        // Evento adicional para cambio normal del select (fallback)
        $(document).on('change', '#psearch', (e) => {
            const productId = $(e.target).val();
            if (productId && productId !== '0') {
                // Verificar si tiene precios múltiples primero
                this.checkProductForMultiplePrices(productId);
            } else {
                this.deactivateMultiplePrices();
            }
        });

        // Evento cuando se cambia la unidad de precios múltiples
        $(document).on('change', '#multiple-prices-unit-select', (e) => {
            const unitId = $(e.target).val();
            if (unitId && this.currentProductId && this.isActive) {
                this.loadPriceForUnit(unitId);
            }
        });

        // Evento cuando se cambia el tipo de precio (solo si está activo)
        $(document).on('change', '#price-type-select', (e) => {
            if (this.isActive) {
                this.currentPriceType = $(e.target).val();
                this.updatePriceDisplay();
            }
        });

        // Evento cuando se cambia la cantidad (solo si está activo)
        $(document).on('change', '#cantidad', (e) => {
            if (this.isActive) {
                this.updateTotalPrice();
            }
        });


    }

    /**
     * Verificar si un producto tiene precios múltiples configurados
     */
        async checkProductForMultiplePrices(productId) {

        try {
            this.currentProductId = productId;

            const url = `/product-prices/product/${productId}/has-prices`;

            const hasPricesResponse = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });


            if (!hasPricesResponse.ok) {
                this.deactivateMultiplePrices();
                return;
            }

            const hasPricesData = await hasPricesResponse.json();

            if (hasPricesData.data && hasPricesData.data.has_prices) {
                this.isActive = true;

                // Cargar precios disponibles para este producto
                await this.loadProductPrices(productId);
            } else {
                this.deactivateMultiplePrices();
            }
        } catch (error) {
            this.deactivateMultiplePrices();
        }
    }

    /**
     * Cargar precios disponibles para un producto
     */
        async loadProductPrices(productId) {

        try {
            const url = `/product-prices/product/${productId}/prices`;

            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });


            if (response.ok) {
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    // Crear selector de precios múltiples
                    this.createPriceTypeSelector(data.data);

                    // MODIFICACIÓN: Cargar selector de unidades de precios múltiples
                    this.loadMultiplePricesUnitSelector(data.data);
                } else {
                    this.deactivateMultiplePrices();
                }
            } else {
                this.deactivateMultiplePrices();
            }
        } catch (error) {
            this.deactivateMultiplePrices();
        }
    }

    /**
     * Cargar selector de unidades de precios múltiples (campo separado)
     */
    loadMultiplePricesUnitSelector(pricesData) {

        // Crear o actualizar el selector de unidades de precios múltiples
        let multiplePricesUnitSelect = $('#multiple-prices-unit-select');

        if (multiplePricesUnitSelect.length === 0) {
            // Crear el selector si no existe
            const unitSelectorHtml = `
                <div class="col-md-4" id="multiple-prices-unit-container">
                    <label class="form-label" for="multiple-prices-unit-select">Unidad para Precios Múltiples</label>
                    <select class="form-select" id="multiple-prices-unit-select" name="multiple-prices-unit-select">
                        <option value="">Seleccionar unidad...</option>
                    </select>
                </div>
            `;

            // Insertar después del selector de unidad original (antes del tipo de precio)
            const unitSelectContainer = $('#unit-select').closest('.col-md-4');
            if (unitSelectContainer.length > 0) {
                unitSelectContainer.after(unitSelectorHtml);
                multiplePricesUnitSelect = $('#multiple-prices-unit-select');
            } else {
                // Fallback: insertar después del contenedor de tipo de precio
                const priceTypeContainer = $('#price-type-container');
                if (priceTypeContainer.length > 0) {
                    priceTypeContainer.after(unitSelectorHtml);
                    multiplePricesUnitSelect = $('#multiple-prices-unit-select');
                } else {
                    return;
                }
            }
        }

        multiplePricesUnitSelect.empty();
        multiplePricesUnitSelect.append('<option value="">Seleccionar unidad...</option>');

        // Procesar las unidades que tienen precios configurados
        pricesData.forEach(priceInfo => {
            const unitName = this.getUnitDisplayName(priceInfo.unit_code, priceInfo.unit_name);
            const isDefault = priceInfo.is_default ? ' - Por Defecto' : '';

            const option = $('<option></option>')
                .val(priceInfo.unit_code)
                .text(`${unitName}${isDefault}`)
                .data('unit-id', priceInfo.unit_id)
                .data('conversion-factor', 1.0);

            multiplePricesUnitSelect.append(option);
        });

        // Seleccionar automáticamente la unidad por defecto si existe
        const defaultUnit = pricesData.find(price => price.is_default);
        if (defaultUnit) {
            multiplePricesUnitSelect.val(defaultUnit.unit_code).trigger('change');
        }

        // Mostrar el contenedor
        $('#multiple-prices-unit-container').show();
    }


    /**
     * Obtener nombre de visualización de la unidad
     */
    getUnitDisplayName(unitCode, unitName) {
        const unitNames = {
            '59': 'Unidad',
            '36': 'Libra',
            '23': 'Litro',
            '34': 'Kilogramo',
            '99': 'Otra',
            '3': 'Saco',
            '4': 'Galón',
            '5': 'Quintal'
        };
        return unitNames[unitCode] || unitName || unitCode;
    }

    /**
     * Verificar si una unidad específica tiene precios múltiples
     */
    async checkUnitForMultiplePrices(unitId) {
        if (!this.currentProductId || !this.isActive) return;

        try {
            // Usar directamente el unitId que se pasa como parámetro
            const realUnitId = unitId;

            if (!realUnitId) {
                this.hidePriceTypeSelector();
                return;
            }

            const url = `/product-prices/product/${this.currentProductId}/unit/${realUnitId}/price-types`;

            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });

                        if (response.ok) {
                const data = await response.json();

                if (data.success && Object.keys(data.data.price_types).length > 0) {
                    this.currentUnitId = realUnitId;
                    this.populatePriceTypeSelector(data.data.price_types);
                    this.updatePriceDisplay();
                } else {
                    this.hidePriceTypeSelector();
                }
            } else {
                this.hidePriceTypeSelector();
            }
        } catch (error) {
            this.hidePriceTypeSelector();
        }
    }

    /**
     * Desactivar el sistema de precios múltiples
     */
    deactivateMultiplePrices() {
        this.isActive = false;
        this.currentProductId = null;
        this.currentUnitId = null;
        this.hidePriceTypeSelector();
        this.hideMultiplePricesUnitSelector();
    }

    /**
     * Ocultar el selector de tipos de precio
     */
    hidePriceTypeSelector() {
        $('#price-type-container').hide();

        const priceInfo = $('#price-info-display');
        if (priceInfo.length > 0) {
            priceInfo.hide();
        }
    }

    /**
     * Mostrar el selector de tipos de precio
     */
    showPriceTypeSelector() {
        $('#price-type-container').show();
    }

    /**
     * Ocultar el selector de unidades de precios múltiples
     */
    hideMultiplePricesUnitSelector() {
        $('#multiple-prices-unit-container').hide();
    }

    /**
     * Obtener headers para las peticiones
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        // Agregar token CSRF si está disponible
        const token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        return headers;
    }

    /**
     * Cargar precio para una unidad específica (solo si está activo)
     */
    async loadPriceForUnit(unitId) {
        if (!this.currentProductId || !this.isActive) return;

        try {
            // Obtener el unit_code del elemento seleccionado en el selector de precios múltiples
            const multiplePricesUnitSelect = $('#multiple-prices-unit-select');
            const selectedOption = multiplePricesUnitSelect.find('option:selected');
            const unitCode = selectedOption.val();
            const realUnitId = selectedOption.data('unit-id');


            if (!unitCode || !realUnitId) {
                this.hidePriceTypeSelector();
                return;
            }

            this.currentUnitId = realUnitId;

            const url = `/product-prices/product/${this.currentProductId}/unit/${unitCode}/price-types`;

            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });


            if (!response.ok) {
                if (response.status === 404) {
                }
                this.hidePriceTypeSelector();
                return;
            }

            const data = await response.json();

            if (data.success && Object.keys(data.data.price_types).length > 0) {
                this.populatePriceTypeSelector(data.data.price_types);
                this.updatePriceDisplay();
                this.showPriceTypeSelector();
            } else {
                this.hidePriceTypeSelector();
            }
        } catch (error) {
            this.hidePriceTypeSelector();
        }
    }

    /**
     * Crear selector de tipos de precio
     */
    createPriceTypeSelector(pricesData) {
        // Crear o actualizar el selector de tipos de precio
        let priceTypeSelect = $('#price-type-select');

        if (priceTypeSelect.length === 0) {
            // Crear el selector si no existe
            const priceTypeHtml = `
                <div class="col-md-2">
                    <label class="form-label" for="price-type-select">Tipo de Precio</label>
                    <select class="form-select" id="price-type-select" name="price-type-select">
                        <option value="">Seleccionar tipo...</option>
                    </select>
                </div>
            `;

            // Insertar después del selector de unidad
            const unitSelectContainer = $('#unit-select').closest('.col-md-2');
            if (unitSelectContainer.length > 0) {
                unitSelectContainer.after(priceTypeHtml);
                priceTypeSelect = $('#price-type-select');
            } else {
                return;
            }
        }

        priceTypeSelect.empty();
        priceTypeSelect.append('<option value="">Seleccionar tipo...</option>');

        // Procesar los precios disponibles
        const availablePrices = [];

        pricesData.forEach(priceInfo => {
            const prices = priceInfo.prices;

            if (prices.regular) {
                availablePrices.push({
                    type: 'regular',
                    name: 'Precio Regular',
                    value: prices.regular,
                    description: 'Precio estándar del producto'
                });
            }

            if (prices.wholesale) {
                availablePrices.push({
                    type: 'wholesale',
                    name: 'Precio al Por Mayor',
                    value: prices.wholesale,
                    description: 'Precio para compras al por mayor'
                });
            }

            if (prices.retail) {
                availablePrices.push({
                    type: 'retail',
                    name: 'Precio al Detalle',
                    value: prices.retail,
                    description: 'Precio para ventas al detalle'
                });
            }

            if (prices.special) {
                availablePrices.push({
                    type: 'special',
                    name: 'Precio Especial',
                    value: prices.special,
                    description: 'Precio promocional o especial'
                });
            }
        });

        // Agregar opciones al selector
        availablePrices.forEach(price => {
            const option = $('<option></option>')
                .val(price.type)
                .text(`${price.name} - $${price.value}`)
                .data('price-value', price.value)
                .data('price-description', price.description);

            priceTypeSelect.append(option);
        });

        // Seleccionar precio regular por defecto
        if (availablePrices.find(p => p.type === 'regular')) {
            priceTypeSelect.val('regular').trigger('change');
        }

        // Mostrar el selector
        $('#price-type-container').show();
    }

    /**
     * Poblar selector de tipos de precio (método original para compatibilidad)
     */
    populatePriceTypeSelector(priceTypes) {
        // Crear o actualizar el selector de tipos de precio
        let priceTypeSelect = $('#price-type-select');

        if (priceTypeSelect.length === 0) {
            // Crear el selector si no existe
            const priceTypeHtml = `
                <div class="col-md-2">
                    <label class="form-label" for="price-type-select">Tipo de Precio</label>
                    <select class="form-select" id="price-type-select" name="price-type-select">
                        <option value="">Seleccionar tipo...</option>
                    </select>
                </div>
            `;

            // Insertar después del selector de unidad
            const unitSelectContainer = $('#unit-select').closest('.col-md-2');
            if (unitSelectContainer.length > 0) {
                unitSelectContainer.after(priceTypeHtml);
                priceTypeSelect = $('#price-type-select');
            } else {
                return;
            }
        }

        priceTypeSelect.empty();
        priceTypeSelect.append('<option value="">Seleccionar tipo...</option>');

        Object.entries(priceTypes).forEach(([type, info]) => {
            const option = $('<option></option>')
                .val(type)
                .text(`${info.name} - $${info.value}`)
                .data('price-value', info.value);

            priceTypeSelect.append(option);
        });

        // Seleccionar precio regular por defecto
        if (priceTypes.regular) {
            priceTypeSelect.val('regular').trigger('change');
        }

    }

    /**
     * Actualizar la visualización del precio
     */
    updatePriceDisplay() {
        if (!this.isActive) return;

        const priceTypeSelect = $('#price-type-select');
        const selectedOption = priceTypeSelect.find('option:selected');

        if (selectedOption.length > 0 && selectedOption.val()) {
            const priceValue = selectedOption.data('price-value');
            $('#precio').val(priceValue);
            this.updateTotalPrice();

            // Mostrar información del precio seleccionado
            this.showPriceInfo(selectedOption.text(), priceValue);
        }
    }

    /**
     * Mostrar información del precio seleccionado
     */
    showPriceInfo(priceTypeName, priceValue) {
        if (!this.isActive) return;

        // Crear o actualizar el elemento de información
        let priceInfo = $('#price-info-display');

        if (priceInfo.length === 0) {
            const priceInfoHtml = `
                <div class="col-12">
                    <div class="alert alert-info" id="price-info-display">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Precio Seleccionado:</strong> <span id="selected-price-info"></span>
                    </div>
                </div>
            `;

            // Insertar después de los selectores
            const priceTypeContainer = $('#price-type-select').closest('.col-md-2');
            if (priceTypeContainer.length > 0) {
                priceTypeContainer.parent().after(priceInfoHtml);
                priceInfo = $('#price-info-display');

            }
        }

        $('#selected-price-info').text(`${priceTypeName} - $${priceValue}`);
        priceInfo.show();
    }

    /**
     * Actualizar precio total
     */
    updateTotalPrice() {
        if (!this.isActive) return;

        const quantity = parseFloat($('#cantidad').val()) || 0;
        const unitPrice = parseFloat($('#precio').val()) || 0;
        const total = quantity * unitPrice;

        $('#total').val(total.toFixed(2));

        // Actualizar totales de la venta si existe la función
        if (typeof totalamount === 'function') {
            totalamount();
        }

    }

    /**
     * Calcular precio de venta con descuentos
     */
    async calculateSalePriceWithDiscounts(quantity, discountPercent = 0) {
        if (!this.currentProductId || !this.currentUnitId) {
            return null;
        }

        try {
            const response = await fetch('/product-prices/calculate-sale-price', {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    product_id: this.currentProductId,
                    unit_id: this.currentUnitId,
                    quantity: quantity,
                    client_type: this.currentPriceType
                })
            });

            const data = await response.json();

            if (data.success) {
                const saleData = data.data;
                const discount = (saleData.total_price * discountPercent) / 100;
                const finalPrice = saleData.total_price - discount;

                return {
                    unit_price: saleData.unit_price,
                    total_price: saleData.total_price,
                    discount: discount,
                    final_price: finalPrice,
                    unit_name: saleData.unit_name,
                    price_type: saleData.price_type
                };
            }
        } catch (error) {
        }

        return null;
    }

    /**
     * Obtener precio por defecto de un producto
     */
    async getDefaultPrice(productId) {
        try {
            const response = await fetch(`/product-prices/product/${productId}/default-price`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await response.json();

            if (data.success) {
                return data.data;
            }
        } catch (error) {
        }

        return null;
    }

    /**
     * Obtener el mejor precio disponible
     */
    async getBestPrice(productId, unitId = null) {
        try {
            let url = `/product-prices/product/${productId}/best-price`;
            if (unitId) {
                url += `?unit_id=${unitId}`;
            }

            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await response.json();

            if (data.success) {
                return data.data;
            }
        } catch (error) {
        }

        return null;
    }

    /**
     * Inicializar selector de precios
     */
    initializePriceSelector() {
        // Agregar estilos CSS para el selector de precios
        const style = `
            <style>
                .price-type-selector {
                    margin-bottom: 15px;
                }
                .price-info-display {
                    background-color: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 0.375rem;
                    padding: 10px;
                    margin-top: 10px;
                }
                .price-type-option {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .price-type-name {
                    font-weight: 500;
                }
                .price-type-value {
                    color: #28a745;
                    font-weight: bold;
                }
            </style>
        `;

        $('head').append(style);
    }

    /**
     * Limpiar datos del producto actual
     */
    clearCurrentProduct() {
        this.currentProductId = null;
        this.currentUnitId = null;
        this.currentPriceType = 'regular';

        $('#price-type-select').remove();
        $('#price-info-display').remove();
        $('#precio').val('');
        $('#total').val('');

        // Ocultar los contenedores de precios múltiples
        $('#price-type-container').hide();
        $('#multiple-prices-unit-container').hide();
    }

    /**
     * Verificar si está inicializado
     */
    isReady() {
        return this.isInitialized;
    }

    /**
     * Verificar si está activo
     */
    isSystemActive() {
        return this.isActive;
    }

    /**
     * Exportar métodos para uso global
     */
    static getInstance() {
        if (!window.salesMultiplePrices) {
            window.salesMultiplePrices = new SalesMultiplePrices();
        }
        return window.salesMultiplePrices;
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    const instance = SalesMultiplePrices.getInstance();
});

// Exportar para uso global
window.SalesMultiplePrices = SalesMultiplePrices;

// Método de prueba para verificar la funcionalidad
window.testMultiplePrices = function(productId = 25) {

    // Verificar si la instancia existe
    if (window.salesMultiplePrices) {
        window.salesMultiplePrices.checkProductForMultiplePrices(productId);
    } else {
    }
};



