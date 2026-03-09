/**
 * Sales Dynamic Module - Módulo de Ventas Dinámicas
 * Integra toda la lógica del módulo de ventas original en una interfaz dinámica
 */

// Variables globales
let currentProductId = null;
let currentProductData = null;

$(document).ready(function() {
    // Inicialización del módulo

    // Verificar que los elementos principales existan

    // Inicializar Select2 lo antes posible para asegurar render
    // Inicializando Select2
    initializeSelect2();

    // Verificar si hay un borrador cargado
    var valcorrdoc = $("#valcorr").val();
    var valdraftdoc = $("#valdraft").val();

        // Verificar localStorage para drafts persistentes
    var localStorageDraft = localStorage.getItem('current_sale_draft');
    var localStorageType = localStorage.getItem('current_sale_type');
    // Antes de decidir qué cargar:
    //CAMBIO TOTALMENTE NUEVO
    const params = new URLSearchParams(window.location.search);
    const newParam = params.get('new');
    const corrParam = params.get('corr') || '';
    let isNew = newParam === 'true' || newParam === '1' || newParam === 'yes';
    let hasCorrValue = (valcorrdoc && valcorrdoc !== "") || (corrParam && corrParam !== "");

    // Si desde la URL viene un corr y el hidden está vacío, sincronizar
    if (!valcorrdoc && corrParam) {
        $("#valcorr").val(corrParam);
        valcorrdoc = corrParam;
        hasCorrValue = true;
    }

    // Si ya existe un correlativo (ya hay venta), no tratarlo como nuevo aunque venga ?new=...
    if (hasCorrValue) {
        isNew = false;
    }

    if ((isNew || params.has('new')) && !hasCorrValue) {
        localStorage.removeItem('current_sale_draft');
        localStorage.removeItem('current_sale_type');
        $("#valdraft").val("false");
    } else if (params.has('new') && hasCorrValue) {
        // Si ya existe un corr, remover ?new=... para evitar que se trate como nuevo en cada refresh
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('new');
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }
    //FIN CAMBIO TOTALMENTE NUEVO

    if (!isNew && hasCorrValue && valdraftdoc == "true") {
        draftdocument(valcorrdoc, valdraftdoc);
    } else if (!isNew && localStorageDraft && !params.has('new')) {
        // Si el tipo de documento no está almacenado, usar el del formulario
        if (!localStorageType) {
            localStorageType = $('#typedocument').val();
            localStorage.setItem('current_sale_type', localStorageType);
        }
        $("#corr").val(localStorageDraft);
        $("#valcorr").val(localStorageDraft);
        $("#typedocument").val(localStorageType);
        loadDraftData(localStorageDraft);
    } else if (!isNew && hasCorrValue) {
        // Fallback: usar el corr que viene del servidor aunque valdraft sea false
        localStorage.setItem('current_sale_draft', valcorrdoc);
        localStorage.setItem('current_sale_type', $('#typedocument').val());
        loadDraftData(valcorrdoc);
    } else {
        // LÓGICA ORIGINAL: Solo crear correlativo para nuevas ventas
        var typedocument = $('#typedocument').val();
        createcorrsale(typedocument);
    }

    // Inicializar con un pequeño delay para asegurar que el DOM esté listo
    setTimeout(function() {
        // Inicializando SalesDynamic
        initializeSalesDynamic();

        // Verificar que los elementos del DOM existan

        // Verificar el contenido actual de las etiquetas
        toggleIvaPercibidoField();
        toggleIvaRetenidoVisibility();
    }, 100);

    // Verificar sistema de precios múltiples después de un delay
    setTimeout(function() {
        // Verificando sistema de precios múltiples
        checkMultiplePricesSystem();
    }, 500);

});


/**
 * Controla la visibilidad del campo "IVA Percibido" dependiendo del tipo de contribuyente de la empresa.
 * Solo debe mostrarse cuando la empresa emisora es Gran Contribuyente.
 */
function toggleIvaPercibidoField() {
    var container = $("#ivaPercibidoContainer");
    if (!container.length) {
        return;
    }

    var isGranContribuyente = ($("#typecontribuyente").val() || "").toUpperCase() === "GRA";

    if (isGranContribuyente) {
        container.show();
    } else {
        container.hide();
        $("#ivarete").val(0);
    }
}

/**
 * Controla la visibilidad de la fila "IVA Retenido" dependiendo de si el cliente es agente de retención.
 */
function toggleIvaRetenidoVisibility() {
    var row = $("#ivaRetenidoRow");
    var field = $("#ivaRetenidoFieldContainer");

    var isAgenteRetencion = ($("#cliente_agente_retencion").val() || "0") === "1";

    if (isAgenteRetencion) {
        if (row.length) row.show();
        if (field.length) field.show();
        refreshIvaRetenidoPreview(true);
    } else {
        if (row.length) row.hide();
        if (field.length) field.hide();
        updateIvaRetenidoValue(0);
    }
}

function updateIvaRetenidoValue(amount) {
    var input = $("#ivaretencion");
    if (input.length) {
        var value = parseFloat(amount) || 0;
        input.val(value.toFixed(8));
    }
}

/**
 * Recalcula una vista previa del IVA retenido considerando los productos agregados
 * y opcionalmente la línea que todavía no se ha agregado.
 *
 * @param {boolean} includePendingLine
 */
function refreshIvaRetenidoPreview(includePendingLine = false) {
    var isAgenteRetencion = ($("#cliente_agente_retencion").val() || "0") === "1";
    var typedoc = $('#typedocument').val();
    var typesale = $('#typesale').val();

    if (!isAgenteRetencion || (typedoc !== '3' && typedoc !== '6') || typesale !== 'gravada') {
        updateIvaRetenidoValue(0);
        return;
    }

    // Solo calcular el IVA Retenido del producto pendiente (no el total acumulado)
    if (includePendingLine) {
        var pendingTotal = (parseFloat($('#cantidad').val()) || 0) * (parseFloat($('#precio').val()) || 0);
        var pendingGravada = pendingTotal;

        if (typedoc === '6') {
            pendingGravada = pendingTotal / 1.13; // Quitar IVA si es factura
        }

        // Mostrar solo el 1% del producto pendiente (no el total de la venta)
        var retencion_producto = pendingGravada * 0.01;
        updateIvaRetenidoValue(retencion_producto);
    } else {
        updateIvaRetenidoValue(0);
    }
}

/**
 * Marca la venta actual como existente (no nueva) y actualiza URL/localStorage
 */
function markSaleAsExisting() {
    var corr = $("#corr").val();
    if (!corr || corr === "") {
        return;
    }

    $("#valdraft").val("true");
    localStorage.setItem('current_sale_draft', corr);
    localStorage.setItem('current_sale_type', $('#typedocument').val());

    try {
        var url = new URL(window.location.href);
        url.searchParams.delete('new');
        url.searchParams.set('corr', corr);
        url.searchParams.set('draft', 'true');
        url.searchParams.set('typedocument', $('#typedocument').val());
        url.searchParams.set('operation', 'edit');
        window.history.replaceState({}, '', url.toString());
    } catch (e) {}
}



/**
 * Función consolidada para actualizar TODOS los cuadros de información
 * Esta función reemplaza a todas las demás para evitar duplicaciones
 */
function updateAllInformationCards(productData, unitCode) {
    // Actualizando cuadros de información

    if (!productData || !productData.product) {
        // No hay datos de producto para actualizar
        return;
    }

        const product = productData.product;
    const stock = productData.stock;
    const measureType = stock?.measure_type || 'unit';

    // 1. ACTUALIZAR TARJETA DE CONVERSIÓN DE UNIDADES
    if (measureType === 'weight') {
        // Producto por peso (sacos, libras)
        const price = parseFloat(product.price) || 0;
        const weightPerUnit = parseFloat(product.weight_per_unit) || 0;

        $('#catalog-price-sack').text('$' + price.toFixed(8));
        $('#catalog-weight-total').text(weightPerUnit.toFixed(4) + ' libras');
        $('#catalog-price-pound').text('$' + (weightPerUnit > 0 ? (price / weightPerUnit).toFixed(8) : '0.00000000'));

        // Actualizar etiquetas para productos por peso
        $('#catalog-price-sack-label').text('Precio del Saco');
        $('#catalog-weight-total-label').text('Libras por Saco');
        $('#catalog-price-pound-label').text('Precio por Libra');
    } else if (measureType === 'volume') {
        // Producto por volumen (depósitos, litros)
        const price = parseFloat(product.price) || 0;
        const volumePerUnit = parseFloat(product.volume_per_unit) || 0;

        $('#catalog-price-sack').text('$' + price.toFixed(8));
        $('#catalog-weight-total').text(volumePerUnit.toFixed(4) + ' litros');
        $('#catalog-price-pound').text('$' + (volumePerUnit > 0 ? (price / volumePerUnit).toFixed(8) : '0.00000000'));

        // Actualizar etiquetas para productos por volumen
        $('#catalog-price-sack-label').text('Precio del Contenedor');
        $('#catalog-weight-total-label').text('Litros por Contenedor');
        $('#catalog-price-pound-label').text('Precio por Litro');
    } else {
        // Producto por unidad
        const price = parseFloat(product.price) || 0;

        $('#catalog-price-sack').text('$' + price.toFixed(8));
        $('#catalog-weight-total').text('0.0000 unidades');
        $('#catalog-price-pound').text('$' + price.toFixed(8));

        // Actualizar etiquetas para productos por unidad
        $('#catalog-price-sack-label').text('Precio por Unidad');
        $('#catalog-weight-total-label').text('Unidades');
        $('#catalog-price-pound-label').text('Precio por Unidad');
    }

    // 2. ACTUALIZAR TARJETA DE STOCK DISPONIBLE
    const stockQuantity = parseFloat(stock?.base_quantity) || 0;
    const stockUnit = stock?.unit_name || 'unidades';

    // Verificar si es producto farmacéutico - siempre mostrar en unidades base
    const productId = $('#productid').val();
    let isPharmaceutical = false;
    let pastillasPerBlister = 1;
    let blistersPerCaja = 1;

    // Obtener datos farmacéuticos si están disponibles
    if (productId && productData.product) {
        // Intentar obtener desde los datos del producto si están disponibles
        if (productData.product.pastillas_per_blister || productData.product.blisters_per_caja) {
            isPharmaceutical = true;
            pastillasPerBlister = productData.product.pastillas_per_blister || 1;
            blistersPerCaja = productData.product.blisters_per_caja || 1;
        }
    }

    // Para productos farmacéuticos, siempre mostrar en "unidades" (pastillas)
    // Para otros productos, mostrar según el tipo
    if (isPharmaceutical) {
        // Stock disponible siempre en unidades (pastillas)
        $('#catalog-stock-available').text(stockQuantity.toFixed(4) + ' unidades');
    } else if (measureType === 'weight') {
        // Producto por peso (sacos, libras, kg)
        $('#catalog-stock-available').text(stockQuantity.toFixed(4) + ' ' + stockUnit);
    } else if (measureType === 'volume') {
        // Producto por volumen (depósitos, litros, ml)
        $('#catalog-stock-available').text(stockQuantity.toFixed(4) + ' ' + stockUnit);
    } else {
        // Producto por unidad
        $('#catalog-stock-available').text(stockQuantity.toFixed(4) + ' unidades');
    }

    let stockStatusFixed = (stock?.base_quantity || 0) > 0 ?
        '<span class="badge bg-success">Disponible</span>' :
        '<span class="badge bg-danger">Sin stock</span>';
    $('#catalog-stock-status').html(stockStatusFixed);

    // 3. ACTUALIZAR TARJETA DE VALIDACIONES
    const cantidadAVender = parseFloat($('#cantidad').val()) || 0;
    const selectedUnitCode = unitCode || $('#unit-select').val() || '';
    const selectedUnitOption = $('#unit-select option:selected');
    const conversionFactor = parseFloat(selectedUnitOption.attr('data-conversion-factor') || selectedUnitOption.attr('data-factor') || '1');

    // Obtener información del producto para verificar si es farmacéutico
    const productIdForValidation = $('#productid').val();
    let isPharmaceuticalForValidation = false;
    let pastillasPerBlisterForValidation = 1;
    let blistersPerCajaForValidation = 1;

    // Intentar obtener datos farmacéuticos desde los datos del producto o campos ocultos
    if (productData.product && (productData.product.pastillas_per_blister || productData.product.blisters_per_caja)) {
        isPharmaceuticalForValidation = true;
        pastillasPerBlisterForValidation = productData.product.pastillas_per_blister || 1;
        blistersPerCajaForValidation = productData.product.blisters_per_caja || 1;
    } else if (productIdForValidation) {
        // Si no están en productData, intentar desde campos ocultos
        const pastillasPerBlisterVal = $('#pastillas_per_blister').val();
        const blistersPerCajaVal = $('#blisters_per_caja').val();

        if (pastillasPerBlisterVal || blistersPerCajaVal) {
            isPharmaceuticalForValidation = true;
            pastillasPerBlisterForValidation = parseFloat(pastillasPerBlisterVal) || 1;
            blistersPerCajaForValidation = parseFloat(blistersPerCajaVal) || 1;
        }
    }

    // Calcular stock después considerando conversiones
    let stockDespues = stockQuantity;
    let stockDespuesEnUnidades = 0;

    if (isPharmaceuticalForValidation && (selectedUnitCode === 'PASTILLA' || selectedUnitCode === 'BLISTER' || selectedUnitCode === 'CAJA')) {
        // Para productos farmacéuticos, el stock base está en pastillas
        // Calcular cuántas pastillas se van a vender
        let pastillasAVender = 0;
        if (selectedUnitCode === 'PASTILLA') {
            pastillasAVender = cantidadAVender;
        } else if (selectedUnitCode === 'BLISTER') {
            pastillasAVender = cantidadAVender * pastillasPerBlisterForValidation;
        } else if (selectedUnitCode === 'CAJA') {
            pastillasAVender = cantidadAVender * blistersPerCajaForValidation * pastillasPerBlisterForValidation;
        }

        // El stock base está en pastillas, restar directamente
        stockDespuesEnUnidades = Math.max(0, stockQuantity - pastillasAVender);

        // Convertir stock después a la unidad seleccionada para mostrar
        if (selectedUnitCode === 'PASTILLA') {
            stockDespues = stockDespuesEnUnidades;
        } else if (selectedUnitCode === 'BLISTER') {
            stockDespues = pastillasPerBlisterForValidation > 0 ? stockDespuesEnUnidades / pastillasPerBlisterForValidation : 0;
        } else if (selectedUnitCode === 'CAJA') {
            const totalPastillasPerCaja = blistersPerCajaForValidation * pastillasPerBlisterForValidation;
            stockDespues = totalPastillasPerCaja > 0 ? stockDespuesEnUnidades / totalPastillasPerCaja : 0;
        }
    } else {
        // Para productos no farmacéuticos, usar el factor de conversión
        const cantidadEnUnidadesBase = cantidadAVender * conversionFactor;
        stockDespuesEnUnidades = Math.max(0, stockQuantity - cantidadEnUnidadesBase);
        stockDespues = conversionFactor > 0 ? stockDespuesEnUnidades / conversionFactor : stockDespuesEnUnidades;
    }

    const selectedUnitName = selectedUnitOption.text() || stockUnit;

    // Stock actual siempre en unidades base (pastillas para farmacéuticos, unidades para otros)
    if (isPharmaceuticalForValidation) {
        $('#catalog-validation-current').text(stockQuantity.toFixed(4) + ' unidades');
    } else {
        $('#catalog-validation-current').text(stockQuantity.toFixed(4) + ' ' + stockUnit);
    }

    $('#catalog-validation-sell').text(cantidadAVender.toFixed(2));
    $('#catalog-validation-after').text(stockDespues.toFixed(4) + ' ' + selectedUnitName);

    // Mostrar stock después en unidades base (pastillas para farmacéuticos, unidades para otros)
    if (isPharmaceuticalForValidation) {
        $('#catalog-validation-lbs').text(stockDespuesEnUnidades.toFixed(4) + ' pastillas');
        $('#catalog-validation-lbs-label').text('En pastillas:');
    } else if (measureType === 'weight') {
        const totalInLbs = parseFloat(stock?.total_in_lbs) || 0;
        $('#catalog-validation-lbs').text(totalInLbs.toFixed(4) + ' libras');
        $('#catalog-validation-lbs-label').text('En libras:');
    } else if (measureType === 'volume') {
        const totalInLiters = parseFloat(stock?.total_in_liters) || 0;
        $('#catalog-validation-lbs').text(totalInLiters.toFixed(4) + ' litros');
        $('#catalog-validation-lbs-label').text('En litros:');
    } else {
        $('#catalog-validation-lbs').text(stockDespuesEnUnidades.toFixed(4) + ' unidades');
        $('#catalog-validation-lbs-label').text('En unidades:');
    }

        // Cuadros de información actualizados
}

/**
 * Función consolidada para calcular conversiones y stock después de venta
 * Reemplaza todas las funciones conflictivas de sales-units.js
 */
function calculateProductConversionsAndStock(productId, unitCode, quantity) {
    if (!productId || !unitCode) return;

        // Obtener datos del producto desde los campos ocultos
    const productData = {
        sale_type: $('#measure_type').val() || 'unit',
        volume_per_unit: parseFloat($('#volume_per_unit').val()) || 0,
        weight_per_unit: parseFloat($('#weight_per_unit').val()) || 0,
        price: parseFloat($('#precio').val()) || 0
    };

    const stockData = {
        base_quantity: parseFloat($('#stock_quantity').val()) || 0,
        total_in_liters: parseFloat($('#total_in_liters').val()) || 0,
        total_in_lbs: parseFloat($('#total_in_liters').val()) || 0
    };

    // 1. ACTUALIZAR TARJETA DE CONVERSIÓN
    if (productData.sale_type === 'volume') {
        // Producto por volumen
        const volumePerUnit = productData.volume_per_unit || 25;
        const pricePerContainer = productData.price || 0;
        const pricePerLiter = volumePerUnit > 0 ? pricePerContainer / volumePerUnit : 0;

        $('#catalog-price-sack').text('$' + pricePerContainer.toFixed(2));
        $('#catalog-weight-total').text(volumePerUnit.toFixed(4) + ' litros');
        $('#catalog-price-pound').text('$' + pricePerLiter.toFixed(2));

        $('#catalog-price-sack-label').text('Precio del Contenedor');
        $('#catalog-weight-total-label').text('Litros por Contenedor');
                $('#catalog-price-pound-label').text('Precio por Litro');

    } else if (productData.sale_type === 'weight') {
        // Producto por peso
        const weightPerUnit = productData.weight_per_unit || 100;
        const pricePerSack = productData.price || 0;
        const pricePerPound = weightPerUnit > 0 ? pricePerSack / weightPerUnit : 0;

        $('#catalog-price-sack').text('$' + pricePerSack.toFixed(2));
        $('#catalog-weight-total').text(weightPerUnit.toFixed(4) + ' libras');
        $('#catalog-price-pound').text('$' + pricePerPound.toFixed(2));

        $('#catalog-price-sack-label').text('Precio del Saco');
        $('#catalog-weight-total-label').text('Libras por Saco');
        $('#catalog-price-pound-label').text('Precio por Libra');

    } else {
        // Producto por unidad
        $('#catalog-price-sack').text('$' + productData.price.toFixed(2));
        $('#catalog-weight-total').text('0.0000 unidades');
        $('#catalog-price-pound').text('$' + productData.price.toFixed(2));

        $('#catalog-price-sack-label').text('Precio por Unidad');
        $('#catalog-weight-total-label').text('Unidades');
        $('#catalog-price-pound-label').text('Precio por Unidad');
    }

    // 2. CALCULAR STOCK DESPUÉS DE LA VENTA
    const selectedUnitOption = $('#unit-select option:selected');
    const conversionFactor = parseFloat(selectedUnitOption.attr('data-conversion-factor') || selectedUnitOption.attr('data-factor') || '1');

    // Verificar si es producto farmacéutico
    let isPharmaceutical = false;
    let pastillasPerBlister = 1;
    let blistersPerCaja = 1;

    // Obtener datos farmacéuticos desde campos ocultos o hacer petición AJAX
    if (productId) {
        $.ajax({
            url: '/sale/getproductbyid/' + productId,
            method: 'GET',
            async: false, // Síncrono para obtener datos inmediatamente
            success: function(response) {
                if (response.success && response.data && response.data.product) {
                    const product = response.data.product;
                    if (product.pastillas_per_blister || product.blisters_per_caja) {
                        isPharmaceutical = true;
                        pastillasPerBlister = product.pastillas_per_blister || 1;
                        blistersPerCaja = product.blisters_per_caja || 1;
                    }
                }
            }
        });
    }

    let stockDespues = stockData.base_quantity;
    let stockEnMedida = 0;
    let stockDespuesEnUnidades = 0;

    if (isPharmaceutical && (unitCode === 'PASTILLA' || unitCode === 'BLISTER' || unitCode === 'CAJA')) {
        // Para productos farmacéuticos, el stock base está en pastillas
        let pastillasAVender = 0;
        if (unitCode === 'PASTILLA') {
            pastillasAVender = quantity;
        } else if (unitCode === 'BLISTER') {
            pastillasAVender = quantity * pastillasPerBlister;
        } else if (unitCode === 'CAJA') {
            pastillasAVender = quantity * blistersPerCaja * pastillasPerBlister;
        }

        // El stock base está en pastillas
        stockDespuesEnUnidades = Math.max(0, stockData.base_quantity - pastillasAVender);

        // Convertir a la unidad seleccionada para mostrar
        if (unitCode === 'PASTILLA') {
            stockDespues = stockDespuesEnUnidades;
        } else if (unitCode === 'BLISTER') {
            stockDespues = pastillasPerBlister > 0 ? stockDespuesEnUnidades / pastillasPerBlister : 0;
        } else if (unitCode === 'CAJA') {
            const totalPastillasPerCaja = blistersPerCaja * pastillasPerBlister;
            stockDespues = totalPastillasPerCaja > 0 ? stockDespuesEnUnidades / totalPastillasPerCaja : 0;
        }
        stockEnMedida = stockDespuesEnUnidades;
    } else if (productData.sale_type === 'volume' && unitCode === '23') {
        // Producto de volumen, unidad litro
        const volumePerUnit = productData.volume_per_unit || 25;
        const quantityInContainers = quantity / volumePerUnit;
        stockDespues = Math.max(0, stockData.base_quantity - quantityInContainers);
        stockEnMedida = stockDespues * volumePerUnit;
        stockDespuesEnUnidades = stockDespues;

    } else if (productData.sale_type === 'weight' && unitCode === '36') {
        // Producto de peso, unidad libra
        const weightPerUnit = productData.weight_per_unit || 100;
        const quantityInSacos = quantity / weightPerUnit;
        stockDespues = Math.max(0, stockData.base_quantity - quantityInSacos);
        stockEnMedida = stockDespues * weightPerUnit;
        stockDespuesEnUnidades = stockDespues;

    } else {
        // Otras unidades o productos por unidad
        const cantidadEnUnidadesBase = quantity * conversionFactor;
        stockDespuesEnUnidades = Math.max(0, stockData.base_quantity - cantidadEnUnidadesBase);
        stockDespues = conversionFactor > 0 ? stockDespuesEnUnidades / conversionFactor : stockDespuesEnUnidades;
        stockEnMedida = stockDespuesEnUnidades;
    }

    // 3. ACTUALIZAR TARJETA DE VALIDACIONES
    const selectedUnitName = selectedUnitOption.text() || 'unidades';
    $('#catalog-validation-sell').text(quantity.toFixed(2));
    $('#catalog-validation-after').text(stockDespues.toFixed(4) + ' ' + selectedUnitName);

    if (isPharmaceutical) {
        $('#catalog-validation-lbs').text(stockEnMedida.toFixed(4) + ' pastillas');
        $('#catalog-validation-lbs-label').text('En pastillas:');
    } else if (productData.sale_type === 'volume') {
        $('#catalog-validation-lbs').text(stockEnMedida.toFixed(4) + ' litros');
        $('#catalog-validation-lbs-label').text('En litros:');
    } else if (productData.sale_type === 'weight') {
        $('#catalog-validation-lbs').text(stockEnMedida.toFixed(4) + ' libras');
        $('#catalog-validation-lbs-label').text('En libras:');
    } else {
        $('#catalog-validation-lbs').text(stockEnMedida.toFixed(4) + ' unidades');
        $('#catalog-validation-lbs-label').text('En unidades:');
    }

}

/**
 * Limpiar localStorage y crear nueva venta
 */
function clearDraftAndCreateNew() {

    // Limpiar localStorage
    localStorage.removeItem('current_sale_draft');
    localStorage.removeItem('current_sale_type');

    // Limpiar campos completamente
    $("#corr").val("").prop('disabled', false);
    $("#valcorr").val("").prop('disabled', false);

    // Limpiar tabla de productos
    $("#tblproduct tbody").empty();

    // Limpiar totales
    $("#sumas").val("0");
    $("#sumasl").html("$0.00");
    $("#ventatotal").val("0");
    $("#ventatotall").html("$0.00");

    // Limpiar campos del formulario
    clearProductData();


    // Crear nuevo correlativo
    var typedocument = $('#typedocument').val();
    createcorrsale(typedocument);
}

/**
 * Remover producto de la venta
 */
function removeProduct(productId) {
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
                url: '/sale/remove-product',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    product_id: productId,
                    sale_id: $('#corr').val()
                },
                success: function(response) {
                    if (response.success) {
                        var corr = $('#valcorr').val();
                        var document = $('#typedocument').val();

                        // Recalcular totales
                        totalamount();
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            title: '¡Producto Removido!',
                            text: response.message || 'Producto removido exitosamente',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Recargar la tabla de productos
                        loadDraftProducts($('#corr').val());
                        window.location.href =
                                    "create-dynamic?corr=" + corr + "&draft=true&typedocument=" + document +"&operation=edit";

                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error al remover producto',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al remover producto',
                        icon: 'error'
                    });
                }
            });
        }
    });
}

/**
 * Crear correlativo de venta - Función del módulo original
 */
function createcorrsale(typedocument="") {

    //crear correlativo temp de factura
    let salida = false;
    var valicorr = $("#valcorr").val();


    // Verificar si realmente no hay correlativo (nueva venta)
    if (!valicorr || valicorr === "" || valicorr === "null" || valicorr === null || valicorr === "undefined") {

        // LIMPIAR localStorage ANTES de crear nuevo correlativo
        localStorage.removeItem('current_sale_draft');
        localStorage.removeItem('current_sale_type');

        // Solo crear nuevo correlativo si NO hay valcorr (nueva venta)

        $.ajax({
            url: "/sale/newcorrsale/" +  typedocument,
            method: "GET",
            async: false,
                            success: function (response) {
                    if (response && $.isNumeric(response.sale_id)) {
                        // Asignar el correlativo al campo
                        $("#corr").val(response.sale_id);
                        $("#valcorr").val(response.sale_id);
                        $("#valdraft").val("true"); //nuevo cambio para evitar crear otro draft al refrescar

                        localStorage.setItem('current_sale_draft', response.sale_id);
                        localStorage.setItem('current_sale_type', typedocument);
                        markSaleAsExisting();

                        // Mostrar mensaje de draft creado
                        Swal.fire({
                            title: 'Draft Creado',
                            text: 'Se ha creado un nuevo draft de venta. Puedes refrescar la página sin perder tu trabajo.',
                            icon: 'success',
                            confirmButtonText: 'Entendido'
                        });

                        // No recargar la página, solo actualizar el campo
                    } else {
                        Swal.fire("Error", "No se pudo crear el correlativo", "error");
                    }
                },
            error: function(xhr, status, error) {

                Swal.fire("Error", "No se pudo crear el correlativo", "error");
            }
        });
    } else {
        // Ya hay un valcorr (retomar venta), no crear nuevo correlativo

        $("#corr").val(valicorr);
        salida = true;
    }

    return salida;
}

/**
 * Mostrar drafts disponibles del usuario
 */
function showUserDrafts() {
    $.ajax({
        url: '/sale/user-drafts',
        method: 'GET',
        success: function(response) {
            if (response.success && response.drafts.length > 0) {
                let draftsHtml = '<div class="drafts-list">';
                response.drafts.forEach(function(draft) {
                    const hasProducts = draft.has_products ? '<span class="badge bg-success">Con productos</span>' : '<span class="badge bg-warning">Sin productos</span>';
                    draftsHtml += `
                        <div class="draft-item mb-2 p-2 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${draft.typedocument_name}</strong> - ${draft.company_name}<br>
                                    <small class="text-muted">Fecha: ${draft.date}</small>
                                    ${hasProducts}
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary" onclick="loadDraft(${draft.id})">
                                        <i class="ti ti-edit me-1"></i>Cargar
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                draftsHtml += '</div>';

                Swal.fire({
                    title: 'Drafts Disponibles',
                    html: draftsHtml,
                    width: '600px',
                    showConfirmButton: false,
                    showCloseButton: true
                });
            } else {
                Swal.fire('Drafts', 'No tienes drafts disponibles', 'info');
            }
        },
        error: function() {
            Swal.fire('Error', 'No se pudieron cargar los drafts', 'error');
        }
    });
}

/**
 * Cargar un draft específico
 */
function loadDraft(draftId) {
    // Cerrar el modal de drafts
    Swal.close();

    // Cargar el draft
    $("#corr").val(draftId);
    $("#valcorr").val(draftId);

    // Cargar los datos del draft usando la función existente
    draftdocument(draftId, true);

    // Cargar los productos del draft si los tiene
    loadDraftProducts(draftId);

    // Mostrar mensaje de confirmación
    Swal.fire({
        title: 'Draft Cargado',
        text: 'El draft se ha cargado correctamente. Puedes continuar editando.',
        icon: 'success',
        showCancelButton: false,
        showDenyButton: false,
        showCloseButton: false,
        confirmButtonText: "Ok",
        confirmButtonColor: "#3085d6",
        allowOutsideClick: false,
        allowEscapeKey: false,
        buttonsStyling: true,
        focusConfirm: true
    });
}

/**
 * Cargar productos de un draft
 */
function loadDraftProducts(draftId) {
    $.ajax({
        url: "/sale/getdraftproducts/" + btoa(draftId),
        method: "GET",
        success: function(response) {
            if (response.success && response.products && response.products.length > 0) {
                // Limpiar tabla actual
                $("#tblproduct tbody").empty();

                // Agregar productos del draft
                response.products.forEach(function(product, index) {
                    // DEBUG: Verificar datos del producto

                    // Construir descripción completa: nombre + unidad (como en agregarp)
                    let descriptionbyproduct = product.product_name;
                    if (product.unit_name && product.unit_name !== 'Unidad') {
                        descriptionbyproduct += " - " + product.unit_name;
                    } else {
                        descriptionbyproduct += " " + (product.marca_name || product.marca || '');
                    }

                    // DEBUG: Validar específicamente product.gravada

                    // Crear fila de producto con el formato correcto de la tabla
                    let row = `
                        <tr data-product-id="${product.product_id}">
                            <td>(${parseFloat(product.quantity).toFixed(2)})</td>
                            <td>${descriptionbyproduct}</td>
                            <td class="es aqui">$${parseFloat(parseFloat(product.unit_price || 0)).toFixed(2)}</td>
                            <td>$${parseFloat(product.nosujeta || 0).toFixed(2)}</td>
                            <td>$${parseFloat(product.exempt || 0).toFixed(2)}</td>
                            <td>$${parseFloat(product.gravada || 0).toFixed(2)}</td>
                            <td>$${parseFloat(product.total).toFixed(2)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-remove-product" onclick="removeProduct(${product.product_id})">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $("#tblproduct tbody").append(row);
                });

                // Habilitar botón de finalizar si hay productos
                updateFinalizeButton();

                // Actualizar los cuadros de información con el primer producto del draft
                if (response.products.length > 0) {
                    const firstProduct = response.products[0];

                    // Llenar campos del formulario con datos del primer producto
                    $('#productid').val(firstProduct.product_id);
                    $('#productname').val(firstProduct.product_name);
                    $('#marca').val(firstProduct.marca_name || firstProduct.marca || '');
                    $('#precio').val(firstProduct.unit_price);
                    $('#cantidad').val(parseFloat(firstProduct.quantity).toFixed(2));
                    $('#total').val(firstProduct.total);

                    // Cargar stock y actualizar cuadros
                    loadProductStock(firstProduct.product_id);
                }

                // RESTAURADO: Calcular totales después de cargar productos del draft
                calculateDraftTotals(response.products);

                // loadDraftProducts completado exitosamente
                //window.location.href = "create-dynamic?corr=" + corr + "&draft=true&typedocument=" + document +"&operation=edit";
            } else {
                // Respuesta sin productos o inválida
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Calcular totales del draft cargado (como en el módulo anterior)
 */
function calculateDraftTotals(products) {
    var typedoc = $('#typedocument').val();
    var sumas = 0;
    var iva13l = 0;
    var rentarete10 = 0;
    var ivaretenido = 0;
    var ventasnosujetas = 0;
    var ventasexentas = 0;
    var ventatotal = 0;

    // Calcular totales acumulativos de todos los productos (como funcionaba antes)
    products.forEach(function(product, index) {

        // SOLUCIÓN: Como los campos de categoría están en 0, usar el total del producto
        var total = parseFloat(product.total || 0);
        var iva_amount = parseFloat(product.iva_amount || 0); // detained13 (IVA Percibido)
        var detained = parseFloat(product.detained || 0); // detained (IVA Retenido) - este es el TOTAL acumulado, no individual
        var nosujeta = parseFloat(product.nosujeta || 0); // Ventas no sujetas
        var exempt = parseFloat(product.exempt || 0); // Ventas exentas

        // NO sumar detained porque es el total acumulado guardado en el último producto
        // Solo tomar el detained del último producto (que contiene el total)
        if (detained > 0) {
            ivaretenido = detained; // Usar directamente el valor (no sumar)
        }

        // Sumar ventas no sujetas y exentas
        ventasnosujetas += nosujeta;
        ventasexentas += exempt;

        // Calcular IVA según tipo de documento (como en tu factura que funciona)
        if (typedoc == '6') { // FACTURA (id=6): NO se calcula IVA
            sumas += total; // Agregar precio tal como viene
            iva13l += 0; // IVA = 0 para facturas
        } else if (typedoc == '3') { // CRÉDITO FISCAL (id=3): Sumar IVA Percibido
            sumas += total; // Agregar precio tal como viene
            iva13l += iva_amount; // Sumar IVA Percibido (detained13)
        } else { // Otros tipos
            sumas += total; // Agregar precio tal como viene
            iva13l += iva_amount; // Sumar IVA Percibido si existe
        }

    });

    // Actualizar campos ocultos (como funcionaba antes)
    $("#sumas").val(sumas);
    $("#13iva").val(iva13l);

    // Actualizar display en la tabla
    $("#sumasl").html(sumas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#13ival").html(iva13l.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    // Actualizar IVA Retenido acumulado de los productos
    // El ivaretenido ya viene calculado desde la BD (es el total acumulado)
    // NO recalcular la retención del agente porque ya está incluida en detained
    $("#ivaretenidol").html(ivaretenido.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#ivareto").val(ivaretenido);
    $("#ivaretenido").val(ivaretenido);

    // Actualizar ventas no sujetas y exentas
    $("#ventasnosujetas").val(ventasnosujetas);
    $("#ventasnosujetasl").html(ventasnosujetas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#ventasexentas").val(ventasexentas);
    $("#ventasexentasl").html(ventasexentas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    // Calcular total final (como en tu factura que funciona)
    if (typedoc == '6') { // FACTURA (id=6): total = solo sumas (sin IVA) - IVA retenido
        ventatotal = sumas - ivaretenido;
    } else { // CRÉDITO FISCAL (id=3) y otros: total = sumas + IVA - IVA retenido
        ventatotal = sumas + iva13l - ivaretenido;
    }

    // RESTAURADO: Actualizar campos ocultos para facturación electrónica (como en sales.create)
    $("#ventatotal").val(ventatotal);
    $("#ventatotall").html(ventatotal.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#ventatotallhidden").val(ventatotal);


    // Actualizar botón de finalizar
    updateFinalizeButton();
}

/**
 * Cargar datos del draft desde localStorage
 */
function loadDraftData(draftId) {

    // Cargar información básica del draft
    $.ajax({
        url: "/sale/getdatadocbycorr/" + btoa(draftId),
        method: "GET",
        success: function (response) {
            if (response && response.id) {

                // Configurar empresa
                $('#company').empty();
                $("#company").append(
                    '<option value="' + response.id + '">' + response.name.toUpperCase() + "</option>"
                );
                $('#company').prop('disabled', true);

                // Configurar correlativo
                $('#corr').prop('disabled', true);
                $("#corr").val(draftId);

                // Configurar tipo de documento
                $("#typedocument").val(response.typedocument_id);
                $("#typecontribuyente").val(response.tipoContribuyente);
                toggleIvaPercibidoField();
                $("#iva").val(response.iva);
                $("#iva_entre").val(response.iva_entre);
                $("#typecontribuyenteclient").val(response.client_contribuyente);

                // Configurar fecha
                $('#date').prop('disabled', true);
                var dateValue = response.date;
                if (dateValue && dateValue !== null && dateValue !== '') {
                    var formattedDate = dateValue.split(' ')[0];
                    if (formattedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        $("#date").val(formattedDate);
                    } else {
                        $("#date").val(new Date().toISOString().split('T')[0]);
                    }
                } else {
                    $("#date").val(new Date().toISOString().split('T')[0]);
                }

                // Configurar cliente si existe
                if (response.client_id != null && response.client_firstname != 'N/A') {
                    $("#client").empty();
                    $("#client").append(
                        '<option value="' + response.client_id + '">' +
                        response.client_firstname + ' ' + response.client_secondname + "</option>"
                    );
                    $('#client').prop('disabled', false);
                } else if (response.client_firstname == 'N/A') {
                    $("#client").empty();
                    $("#client").append(
                        '<option value="' + response.client_id + '">' + response.comercial_name + "</option>"
                    );
                    $('#client').prop('disabled', false);
                } else {
                    // Cargar clientes de la empresa
                    getclientbycompanyurl(response.id);
                }

                // Configurar forma de pago
                if (response.waytopay != null) {
                    $("#fpago option[value=" + response.waytopay + "]").attr("selected", true);
                }
                $("#acuenta").val(response.acuenta);

                // Cargar productos del draft
                loadDraftProducts(draftId);

                markSaleAsExisting();

                // Actualizar estado del botón de finalizar después de cargar datos
                setTimeout(updateFinalizeButton, 500);

            } else {
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Cargar documento borrador - Función del módulo original
 */
function draftdocument(corr, draft) {
    if (draft) {
        $.ajax({
            url: "/sale/getdatadocbycorr/" + btoa(corr),
            method: "GET",
            async: false,
            success: function (response) {
                $.each(response, function (index, value) {
                    //campo de company
                    $('#company').empty();
                    $("#company").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name.toUpperCase() +
                            "</option>"
                    );
                    // Mantener la empresa visible pero bloqueada (igual al módulo original)
                    $('#company').prop('disabled', true);
                    $('#corr').prop('disabled', true);
                    $("#typedocument").val(value.typedocument_id);
                    $("#typecontribuyente").val(value.tipoContribuyente);
                    toggleIvaPercibidoField();
                    $("#iva").val(value.iva);
                    $("#iva_entre").val(value.iva_entre);
                    $("#typecontribuyenteclient").val(value.client_contribuyente);
                    $('#date').prop('disabled', true);
                    $("#corr").val(corr);

                    // Formatear la fecha correctamente para el input type="date"
                    var dateValue = value.date;
                    if (dateValue && dateValue !== null && dateValue !== '') {
                        var formattedDate = dateValue.split(' ')[0];
                        if (formattedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            $("#date").val(formattedDate);
                        } else {
                            $("#date").val(new Date().toISOString().split('T')[0]);
                        }
                    } else {
                        $("#date").val(new Date().toISOString().split('T')[0]);
                    }

                    //campo cliente
                    $("#client").empty();
                    if(value.client_id != null && value.client_firstname!='N/A'){
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.client_firstname +' '+ value.client_secondname +
                                "</option>"
                        );
                        // Permitir cambiar el cliente al recargar (pedido del usuario)
                        $('#client').prop('disabled', false);
                    }else if(value.client_firstname=='N/A') {
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.comercial_name +
                                "</option>"
                        );
                        $('#client').prop('disabled', false);
                    }else{
                        var getsclient =  getclientbycompanyurl(value.id);
                    }

                    if(value.waytopay != null){
                        $("#fpago option[value="+ value.waytopay +"]").attr("selected",true);
                        // Mostrar/ocultar campo de autorización según forma de pago
                        if(value.waytopay == 3){
                            $('#card-authorization-container').show();
                        } else {
                            $('#card-authorization-container').hide();
                        }
                    }
                    // Cargar número de autorización si existe
                    if(value.card_authorization_number){
                        $("#card_authorization_number").val(value.card_authorization_number);
                    }
                    $("#acuenta").val(value.acuenta);

                    // CARGAR LOS PRODUCTOS
                    if (typeof loadDraftProducts === 'function') {
                        loadDraftProducts(corr);
                    } else {
                    }
                });
            },
            failure: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
            error: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
        });
    }
}

/**
 * Inicializar el módulo de ventas dinámicas
 */
function initializeSalesDynamic() {
    // Inicializando SalesDynamic

    // Select2 ya inicializado en document.ready

    // Cargar datos iniciales
    loadInitialData();

    // Configurar eventos
    setupEventListeners();

    // Auto-seleccionar empresa si solo hay una
    autoSelectCompany();


    // Evento para actualizar validaciones cuando cambie la cantidad
    $('#cantidad').on('change', function() {
        const productId = $('#productid').val();
        const unitCode = $('#unit-select').val();
        if (productId && productId !== '' && unitCode) {
            // Actualizar preview de conversión cuando cambie la cantidad
            updateUnitConversionPreview(productId, unitCode);

            // Obtener datos completos del producto para mantener contexto
            $.ajax({
                url: '/sale/getproductbyid/' + productId,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data && response.data.product) {
                        const product = response.data.product;
                        const stock = response.data.stock || {};

                        // Actualizar campos ocultos con datos farmacéuticos
                        if (product.pastillas_per_blister) {
                            $('#pastillas_per_blister').val(product.pastillas_per_blister);
                        }
                        if (product.blisters_per_caja) {
                            $('#blisters_per_caja').val(product.blisters_per_caja);
                        }

                        const currentProductData = {
                            product: {
                                name: $('#productname').val(),
                                price: parseFloat($('#precio').val()) || 0,
                                weight_per_unit: product.weight_per_unit || 0,
                                volume_per_unit: product.volume_per_unit || 0,
                                pastillas_per_blister: product.pastillas_per_blister || 0,
                                blisters_per_caja: product.blisters_per_caja || 0,
                                marca: $('#marca').val(),
                                provider: $('#provider').val() || ''
                            },
                            stock: {
                                base_quantity: parseFloat($('#stock_quantity').val()) || stock.available_quantity || 0,
                                unit_name: stock.unit_name || 'unidades',
                                total_in_lbs: parseFloat($('#total_in_lbs').val()) || stock.total_in_lbs || 0,
                                total_in_liters: parseFloat($('#total_in_liters').val()) || stock.total_in_liters || 0,
                                total_in_ml: parseFloat($('#total_in_ml').val()) || stock.total_in_ml || 0,
                                measure_type: $('#measure_type').val() || stock.measure_type || 'unit'
                            }
                        };

                        // Actualizar los cuadros con la nueva cantidad y unidad seleccionada
                        updateAllInformationCards(currentProductData, unitCode);

                        // También actualizar el cálculo de conversiones
                        const cantidad = parseFloat($('#cantidad').val()) || 1;
                        calculateProductConversionsAndStock(productId, unitCode, cantidad);
                    }
                }
            });
        }
    });

        // Evento para cuando se seleccione un producto (consolidado)
    $(document).on('change', '#psearch', function() {
        // Si el cambio viene de búsqueda por código, no llamar searchproduct (ya se llenó todo y se evita borrar el código)
        if (window.fromCodeSearch) {
            window.fromCodeSearch = false;
            return;
        }
        const productId = $(this).val();

        if (productId && productId !== '0') {
            // Producto seleccionado
            searchproduct(productId);

            // Cargar unidades del producto si la función está disponible
            if (typeof loadProductUnitsInternal === 'function') {
                loadProductUnitsInternal(productId);
            } else {
                // Cargar unidades básicas directamente
                loadBasicUnits(productId);
            }
        }

        // Verificar si el sistema de precios múltiples está disponible
        if (window.salesMultiplePrices) {

        }
    });

        // Evento para cuando se seleccione una unidad de medida
    $('#unit-select').on('change', function() {
        const productId = $('#productid').val();
        const unitCode = $(this).val();
        const unitName = $(this).find('option:selected').text();

        // Mostrar preview de conversión farmacéutica si aplica
        updateUnitConversionPreview(productId, unitCode);

        if (productId && productId !== '' && unitCode && unitCode !== '') {
            // Unidad de medida cambiada

            // Sistema de precios múltiples disponible

            // Actualizar los cuadros de información con la nueva unidad
            const currentProductData = {
                product: {
                    name: $('#productname').val(),
                    price: parseFloat($('#precio').val()) || 0,
                    weight_per_unit: parseFloat($('#weight_per_unit').val()) || 0,
                    volume_per_unit: parseFloat($('#volume_per_unit').val()) || 0,
                    marca: $('#marca').val(),
                    provider: $('#provider').val() || ''
                },
                stock: {
                    base_quantity: parseFloat($('#stock_quantity').val()) || 0,
                    unit_name: unitName,
                    total_in_lbs: parseFloat($('#total_in_lbs').val()) || 0,
                    total_in_liters: parseFloat($('#total_in_liters').val()) || 0,
                    total_in_ml: parseFloat($('#total_in_ml').val()) || 0,
                    measure_type: $('#measure_type').val() || 'unit'
                }
            };


                                    // Usar la función consolidada con la nueva unidad
            updateAllInformationCards(currentProductData, unitCode);

                        // Llamar a la función consolidada para cálculos de conversión y stock
            const cantidad = parseFloat($('#cantidad').val()) || 1;
            calculateProductConversionsAndStock(productId, unitCode, cantidad);

            // RESTAURAR precios múltiples cuando cambia la unidad
            if (window.salesMultiplePrices && typeof window.salesMultiplePrices.checkUnitForMultiplePrices === 'function') {
                // Establecer el producto actual antes de verificar precios múltiples
                const currentProductId = $('#productid').val();
                if (currentProductId) {
                    window.salesMultiplePrices.currentProductId = currentProductId;
                    window.salesMultiplePrices.checkUnitForMultiplePrices(unitCode);
                }
            }

        }
    });

    // Sistema de precios múltiples ya está funcionando correctamente

    // Actualizar estado inicial del botón de finalizar
    updateFinalizeButton();
}

/**
 * Inicializar Select2 para los dropdowns
 */
function initializeSelect2() {
    // Inicializando Select2

    // Select2 para empresa
    var selectdcompany = $(".select2company");
    if (selectdcompany.length) {
        var $this = selectdcompany;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar empresa",
            dropdownParent: $this.parent(),
        });
    }

    // Select2 para cliente
    var selectdclient = $(".select2client");
    if (selectdclient.length) {
        var $this = selectdclient;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar cliente",
            dropdownParent: $this.parent(),
        });
    }

    // Select2 para productos
    var selectdpsearch = $(".select2psearch");
    if (selectdpsearch.length) {
        var $this = selectdpsearch;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar Producto",
            dropdownParent: $this.parent(),
            templateResult: formatState
        });
    }
}

/**
 * Formatear estado para Select2 de productos
 */
function formatState(state) {
    if (state.id == 0) {
        return state.text;
    }
    var imageSrc = state.title && state.title !== 'undefined' ? state.title : 'default.png';
    var $state = $(
        '<span><img src="../assets/img/products/' + imageSrc + '" class="imagen-producto-select2" /> ' + state.text + '</span>'
    );
    return $state;
}

/**
 * Cargar datos iniciales
 */
function loadInitialData() {

    // Cargar empresas disponibles
    loadCompanies();

    // Cargar productos disponibles
    loadProducts();
}

/**
 * Cargar empresas disponibles
 */
function loadCompanies() {

    $.ajax({
        url: "/company/getcompanies",
        method: "GET",
        success: function (response) {

            const companySelect = $('#company');
            if (companySelect.length > 0) {
                companySelect.empty();
                companySelect.append('<option value="">Seleccione empresa</option>');

                if (response && response.length > 0) {
                    response.forEach(function(company) {
                        companySelect.append(
                            '<option value="' + company.id + '">' + company.name + '</option>'
                        );
                    });

                } else {
                }
            } else {
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Cargar productos disponibles
 */
function loadProducts() {
            // Iniciando carga de productos

    $.ajax({
        url: "/product/getproductall",
        method: "GET",
        success: function (response) {
            // Productos cargados exitosamente

            $("#psearch").empty().append('<option value="0">Seleccione un producto</option>');

            $.each(response, function (index, value) {
                $("#psearch").append(
                    '<option value="' +
                        value.id +
                        '" title="' + value.image + '">' +
                        value.name.toUpperCase() + "| Descripción: " + value.description + "| Proveedor: " + value.nameprovider +
                        "</option>"
                );
            });

            // Dropdown de productos llenado
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Auto-seleccionar empresa y generar correlativo
 */
function autoSelectCompany() {
    const companySelect = $('#company');


    // Si el campo está oculto, usar el valor que ya tiene
    if (companySelect.is(':hidden')) {
        const companyId = companySelect.val();


        if (companyId && companyId !== '') {
            aviablenext(companyId);

            // Generar correlativo automáticamente
            var typedocument = $('#typedocument').val();
            createcorrsale(typedocument);
        } else {
        }
    } else {
        // Campo visible - lógica original
        const options = companySelect.find('option').not('[value=""]');

        if (options.length > 0) {
            const firstCompanyId = options.first().val();
            companySelect.val(firstCompanyId).trigger('change');
            aviablenext(firstCompanyId);

            // Generar correlativo automáticamente
            var typedocument = $('#typedocument').val();
            createcorrsale(typedocument);
        } else {
        }
    }
}

/**
 * Variables globales para manejo de código de barras
 */
var barcodeScanTimeout = null;
var codesearchLastInputTime = 0;
var codesearchIsTyping = false;

/**
 * Configurar eventos
 */
function setupEventListeners() {
    // Evento para búsqueda por código - Soporta entrada manual y escaneo de código de barras
    $(document).on('keypress', '#codesearch', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            clearTimeout(barcodeScanTimeout);
            codesearchIsTyping = false;
            const code = $(this).val().trim();
            if (code.length > 0) {
                searchproductcode(code);
            } else {
                clearProductData();
            }
        }
    });

    // Detectar escritura manual vs escaneo de código de barras
    $(document).on('input', '#codesearch', function() {
        const code = $(this).val().trim();
        const currentTime = Date.now();
        const timeSinceLastInput = codesearchLastInputTime > 0 ? currentTime - codesearchLastInputTime : 0;

        // Si el tiempo entre caracteres es muy corto (< 50ms), probablemente es un escaneo
        if (timeSinceLastInput > 0 && timeSinceLastInput < 50) {
            codesearchIsTyping = false;
        } else if (timeSinceLastInput > 150) {
            codesearchIsTyping = true; // Usuario escribiendo manualmente
        }

        codesearchLastInputTime = currentTime;

        // Limpiar timeout anterior
        clearTimeout(barcodeScanTimeout);

        if (code.length === 0) {
            clearProductData();
            codesearchIsTyping = false;
            return;
        }

        // Si no está escribiendo (escaneo rápido) y tiene al menos 8 caracteres (típico de código de barras), buscar automáticamente
        // Para entrada manual, el usuario debe presionar Enter
        if (!codesearchIsTyping && code.length >= 8) {
            barcodeScanTimeout = setTimeout(function() {
                // Si después de 150ms no hay más entrada, probablemente terminó el escaneo
                const currentCode = $('#codesearch').val().trim();
                if (currentCode.length >= 8) {
                    searchproductcode(currentCode);
                }
            }, 150);
        }
    });

    // Manejar cuando el campo pierde el foco (blur) - no buscar automáticamente, solo limpiar si está vacío
    $(document).on('blur', '#codesearch', function() {
        clearTimeout(barcodeScanTimeout);
        const code = $(this).val().trim();
        // No buscar automáticamente al perder foco, permitir que el usuario controle con Enter
        // Solo limpiar si está vacío
        if (code.length === 0) {
            clearProductData();
        }
        codesearchIsTyping = false;
    });

    // Evento para tecla Escape en el campo de código
    $(document).on('keydown', '#codesearch', function(e) {
        if (e.key === 'Escape') {
            clearTimeout(barcodeScanTimeout);
            codesearchIsTyping = false;
            $(this).val('');
            clearProductData();
        }
    });

    // Evento para botón de limpiar código
    $(document).on('click', '#clear-codesearch', function() {
        clearTimeout(barcodeScanTimeout);
        codesearchIsTyping = false;
        $('#codesearch').val('');
        clearProductData();
    });

    // Evento para búsqueda por nombre - ELIMINADO (duplicado con el evento de la línea 531)
}

/**
 * Función aviablenext del módulo original
 */
function aviablenext(idcompany) {

    // Cargar clientes para la empresa seleccionada
    getclientbycompanyurl(idcompany);

    // Si ya hay cliente seleccionado en el DOM y lo queremos preservar editable
    $('#client').prop('disabled', false);

    // Obtener tipo de contribuyente de la empresa
    $.ajax({
        url: "/company/gettypecontri/" + btoa(idcompany),
        method: "GET",
        success: function (response) {
            $("#typecontribuyente").val(response.tipoContribuyente);
            toggleIvaPercibidoField();
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Cargar clientes por empresa
 */
function getclientbycompanyurl(idcompany) {
    var typedocument = $('#typedocument').val();
    var companyId = $('#company').val() || idcompany;


    $.ajax({
        url: "/sale/clients?document_type=" + typedocument + "&company_id=" + companyId,
        method: "GET",
        success: function (response) {


            // Limpiar opciones existentes antes de agregar nuevas
            $("#client").empty();
            $("#client").append('<option value="0">Seleccione un cliente</option>');

            var currentVal = $('#client').val();
            var typedocument = $('#typedocument').val();
            var availableClients = 0;
            var totalClients = response.length;

            $.each(response, function (index, value) {


                // Para el nuevo endpoint, todos los clientes ya vienen filtrados según el tipo de documento
                // No necesitamos validación adicional aquí
                availableClients++;

                    // Crear texto de búsqueda con información adicional
                    var searchText = '';

                    if(value.tpersona=='J'){
                        // Persona jurídica
                        var nombre = value.name_contribuyente ? value.name_contribuyente.toUpperCase() : 'SIN NOMBRE';
                        var nit = value.nit && value.nit !== 'N/A' ? value.nit : 'SIN NIT';
                        var ncr = value.ncr && value.ncr !== 'N/A' ? value.ncr : 'SIN NCR';

                        searchText = nombre + ' | NIT: ' + nit + ' | NCR: ' + ncr;

                        $("#client").append(
                            '<option value="' +
                                value.id +
                                '" data-type="J" data-contribuyente="' + (value.tipoContribuyente || '') + '" data-nit="' + (value.nit || '') + '" data-ncr="' + (value.ncr || '') + '">' +
                                searchText +
                                "</option>"
                        );
                    }else if (value.tpersona=='N'){
                        // Persona natural
                        var nombre = '';
                        if (value.firstname && value.firstlastname) {
                            nombre = value.firstname.toUpperCase() + ' ' + value.firstlastname.toUpperCase();
                        } else if (value.firstname) {
                            nombre = value.firstname.toUpperCase();
                        } else {
                            nombre = 'SIN NOMBRE';
                        }

                        var dui = value.nit && value.nit !== 'N/A' ? value.nit : 'SIN DUI';
                        var ncr = value.ncr && value.ncr !== 'N/A' ? value.ncr : 'SIN NCR';

                        searchText = nombre + ' | DUI: ' + dui + ' | NCR: ' + ncr;

                        $("#client").append(
                            '<option value="' +
                                value.id +
                                '" data-type="N" data-contribuyente="' + (value.tipoContribuyente || '') + '" data-dui="' + (value.nit || '') + '" data-ncr="' + (value.ncr || '') + '">' +
                                searchText +
                                "</option>"
                        );
                    }
            });

            // Mostrar mensaje informativo sobre restricciones
            if (typedocument == '3') {
                showClientValidationMessage(
                    `Para Crédito Fiscal se muestran ${availableClients} clientes (solo contribuyentes)`,
                    'info'
                );
            } else if (typedocument == '8') {
                showClientValidationMessage(
                    `Para Sujeto Excluido se muestran ${availableClients} clientes (todos los tipos)`,
                    'info'
                );
            } else {
                showClientValidationMessage('', '');
            }
            // Reseleccionar cliente previo si existe
            if (currentVal && $("#client option[value='"+currentVal+"']").length) {
                $('#client').val(currentVal).trigger('change');
            }

            // Actualizar estado del botón de finalizar
            updateFinalizeButton();
            // Asegurar select2 activo incluso si el contenedor estaba oculto
            if (!$('#client').data('select2')) {
                $("#client").wrap('<div class="position-relative"></div>').select2({
                    placeholder: "Seleccionar cliente",
                    dropdownParent: $("#client").parent(),
                });
            }
        },
        error: function(xhr, status, error) {
            $("#client").empty();
            $("#client").append('<option value="0">Error cargando clientes</option>');
        }
    });
}

/**
 * Validar tipo de contribuyente del cliente
 */
function valtrypecontri(idcliente) {
    if (!idcliente || idcliente === '0') {
        $('#client-info').hide();
        $("#cliente_agente_retencion").val("0");
        toggleIvaRetenidoVisibility();
        return;
    }

    // Validación inmediata en UI: Crédito Fiscal solo para Natural Contribuyente o Jurídico (con NRC)
    var typedoc = $('#typedocument').val();

    // Obtener información completa del cliente
    $.ajax({
        url: "/client/gettypecontri/" + btoa(idcliente),
        method: "GET",
        success: function (response) {
            $("#typecontribuyenteclient").val(response.tipoContribuyente);

            if (typedoc == '3') {
                var isJuridico = (response.tpersona === 'J');
                var isNaturalContribuyente = (response.tpersona === 'N' && (String(response.contribuyente) === '1'));
                var hasNrc = response.ncr && response.ncr !== 'N/A' && String(response.ncr).trim() !== '';

                if ((!isJuridico && !isNaturalContribuyente) || !hasNrc) {
                    // Resetear selección y mostrar mensaje
                    $('#client').val('0').trigger('change');
                    $('#client-info').hide();
                    $('#acuenta').val('');

                    showClientValidationMessage(
                        'Para Crédito Fiscal debe seleccionar un cliente Jurídico o Natural Contribuyente con NRC válido.',
                        'danger'
                    );

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Cliente no válido para Crédito Fiscal',
                            text: 'Seleccione un cliente Jurídico o Natural Contribuyente con NRC válido.',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                    }

                    // No continuar con guardado ni actualización de retenciones
                    $("#cliente_agente_retencion").val("0");
                    toggleIvaRetenidoVisibility();
                    updateFinalizeButton();
                    return;
                } else {
                    // Limpiar aviso si estaba
                    showClientValidationMessage('', '');
                }
            }

            // Guardar si el cliente es agente de retención para usar en cálculos
            if (response.agente_retencion == "1") {
                $("#cliente_agente_retencion").val("1");
            } else {
                $("#cliente_agente_retencion").val("0");
            }
            toggleIvaRetenidoVisibility();

            // Mostrar información detallada del cliente
            showClientInfo(response);

            // Actualizar estado del botón de finalizar
            updateFinalizeButton();
        },
        error: function (error) {

        }
    });

    // Guardar el cliente en la venta inmediatamente
    var corrValue = $("#corr").val();
    if (corrValue && corrValue !== '' && idcliente && idcliente !== '0') {
        $.ajax({
            url: "/sale/updateclient/" + corrValue + "/" + idcliente,
            method: "GET",
            success: function (response) {
                if (response.success) {

                } else {

                }
            },
            error: function (error) {

            }
        });
    }
}

/**
 * Validar forma de pago
 */
function valfpago(fpago) {
    // Validar que se haya seleccionado una forma de pago válida
    if (fpago === '0' || fpago === '' || fpago === null) {
        return false;
    }

    // Mostrar/ocultar campo de autorización según forma de pago
    if (fpago === '3') {
        // Tarjeta - mostrar campo de autorización
        $('#card-authorization-container').slideDown(200);
        $('#card_authorization_number').focus();
    } else {
        // Otra forma de pago - ocultar y limpiar campo
        $('#card-authorization-container').slideUp(200);
        $('#card_authorization_number').val('');
    }

    // Obtener el ID de la venta actual
    var saleId = $('#corr').val();

    if (!saleId || saleId === '' || saleId === 'null') {
        return true; // Continuar aunque no haya saleId aún (puede ser un borrador nuevo)
    }

    // Obtener el número de autorización si es tarjeta
    var authorizationNumber = fpago === '3' ? $('#card_authorization_number').val() : null;

    // Actualizar la forma de pago en la base de datos
    $.ajax({
        url: '/sale/update-payment-method',
        method: 'POST',
        data: {
            sale_id: saleId,
            payment_method: fpago,
            card_authorization_number: authorizationNumber,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Mostrar notificación de éxito (comentado para no ser intrusivo)
                // if (typeof Swal !== 'undefined') {
                //     Swal.fire({
                //         icon: 'success',
                //         title: 'Actualizado',
                //         text: response.message,
                //         timer: 2000,
                //         showConfirmButton: false,
                //         toast: true,
                //         position: 'top-end'
                //     });
                // }
            } else {
                // Mostrar notificación de error
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Error al actualizar forma de pago',
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            // Mostrar notificación de error
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al actualizar forma de pago',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        }
    });

    return true;
}

/**
 * Buscar producto por código - Función del módulo original
 */
function searchproductcode(codeproduct) {

    //Get products by id avaibles
    var typedoc = $('#typedocument').val();
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var iva = parseFloat($("#iva").val());
    var iva_entre = parseFloat($("#iva_entre").val()) || 1.13; // Valor por defecto si no está definido
    var retencion=0.00;
    var pricevalue;
    var calculoIVA13;

    $.ajax({
        url: "/product/getproductcode/" + btoa(encodeURIComponent(codeproduct)),
        method: "GET",
        success: function (response) {
            if (!response || response.length === 0) {
                Swal.fire({
                    title: 'Producto no encontrado',
                    text: `No se encontró ningún producto con el código "${codeproduct}"`,
                    icon: 'warning',
                    confirmButtonText: 'Entendido'
                });
                return;
            }


            $.each(response, function (index, value) {


                if(typedoc=='6' || typedoc=='8'){
                    pricevalue = parseFloat(value.price);
                }else{
                    if($('#isnewsale').val() == 1){

                        pricevalue2 = parseFloat(value.price/1.13);
                    }
                    pricevalue = parseFloat(value.price/iva_entre);
                }

                // Marcar que el cambio viene de búsqueda por código para no disparar searchproduct (evita que se borre el código)
                window.fromCodeSearch = true;
                $("#psearch").val(value.id).trigger("change.select2");
                $("#codesearch").val(value.code || '');
                $("#precio").val(pricevalue.toFixed(8));



                $("#productname").val(value.productname);
                $("#marca").val(value.marcaname);
                $("#productid").val(value.id);
                $("#productdescription").val(value.description);
                $("#productunitario").val(value.id);

                $("#add-information-products").css("display", "");
                $("#product-image").attr("src", '../assets/img/products/' + value.image);
                $("#product-name").html(value.productname);
                $("#product-marca").html(value.marcaname);
                $("#product-provider").html(value.provider);
                $("#product-price").html(pricevalue.toFixed(8));

                // Cargar unidades del producto (farmacéuticas si aplica)
                if (typeof loadProductUnits === 'function') {
                    loadProductUnits(value.id);
                } else if (typeof window.loadProductUnits === 'function') {
                    window.loadProductUnits(value.id);
                }

                // Cargar información de stock del producto
                if (typeof loadProductStock === 'function') {
                    loadProductStock(value.id);
                }

                //validar si es gran contribuyente el cliente vs la empresa
                if (typecontricompany == "GRA") {
                    if (typecontriclient == "GRA") {
                        retencion = 0.01;
                    } else if (
                        typecontriclient == "MED" ||
                        typecontriclient == "PEQ" ||
                        typecontriclient == "OTR"
                    ) {
                        retencion = 0.00;
                    }
                }
                if(typecontriclient==""){
                    retencion = 0.0;
                }
                if(typedoc=='6' || typedoc=='8'){
                    $("#ivarete13").val(0);
                }else{
                    $("#ivarete13").val(parseFloat(pricevalue.toFixed(2) * iva).toFixed(2));
                }
                $("#ivarete").val(
                    parseFloat(pricevalue.toFixed(8) * retencion).toFixed(8)
                );
                if(typedoc=='8'){
                    // CORREGIDO: Usar precio unitario actual del campo × cantidad para el cálculo
                    var cantidad = parseFloat($("#cantidad").val()) || 1;
                    var precioActual = parseFloat($("#precio").val()) || parseFloat(pricevalue);
                    var subtotal = precioActual * cantidad;
                    $("#rentarete").val(
                        parseFloat(subtotal * 0.10).toFixed(8)
                    );
                }
                // Solo llenar precio_sin_iva si el tipo de documento es 3 (Crédito Fiscal)
                if(typedoc == '3' && $('#typesale').val() === 'gravada') {
                    $("#precio_sin_iva").val(pricevalue2.toFixed(8));

                    calculoIVA13 = parseFloat(value.price) - parseFloat(pricevalue2);
                        $("#ivarete13").val(calculoIVA13.toFixed(8));
                }
            });
            var updateamounts = totalamount();
        },
        error: function(xhr, status, error) {
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al buscar el producto. Por favor, inténtalo de nuevo.',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
    });
}

/**
 * Buscar producto por ID - Función del módulo original
 */
function searchproduct(idpro) {
    // Iniciando búsqueda de producto

    if(idpro==9){
        $("#add-information-tickets").css("display", "");
    }else{
        $("#add-information-tickets").css("display", "none");
    }

    //Get products by id avaibles
    var typedoc = $('#typedocument').val();
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var iva = parseFloat($("#iva").val());
    var iva_entre = parseFloat($("#iva_entre").val()) || 1.13; // Valor por defecto si no está definido
    var retencion=0.00;
    var pricevalue;
    var pricevalue2;

    $.ajax({
        url: "/product/getproductid/" + btoa(idpro),
        method: "GET",
        success: function (response) {
            // Respuesta del servidor recibida

            if (response && response.length > 0) {
                $.each(response, function (index, value) {
                    // Procesando producto

                    if(typedoc=='6' || typedoc=='8'){
                        pricevalue = parseFloat(value.price);
                    }else{
                        if($('#isnewsale').val() == 1){

                            pricevalue2 = parseFloat(value.price/1.13);
                        }
                        pricevalue = parseFloat(value.price/iva_entre);
                    }

                    // Llenar automáticamente el código del producto cuando se selecciona desde el combobox
                    $("#codesearch").val(value.code || '');

                    $("#precio").val(pricevalue.toFixed(8));

                    $("#productname").val(value.productname);
                    $("#marca").val(value.marcaname);
                    $("#productid").val(value.id);
                                        $("#productdescription").val(value.description);
                    $("#productunitario").val(value.id);

                    // Campos establecidos correctamente

                    // Mostrar información del producto
                    $("#product-image").attr('src', '../assets/img/products/' + (value.image || 'default.png'));
                    $("#product-name").text(value.productname);
                    $("#product-marca").text(value.marcaname);
                    $("#product-provider").text(value.provider || 'N/A');
                    $("#product-price").text('$ ' + pricevalue.toFixed(8));
                    $("#add-information-products").show();

                    //validar si es gran contribuyente el cliente vs la empresa
                    if (typecontricompany == "GRA") {
                        if (typecontriclient == "GRA") {
                            retencion = 0.01;
                        } else if (
                            typecontriclient == "MED" ||
                            typecontriclient == "PEQ" ||
                            typecontriclient == "OTR"
                        ) {
                            retencion = 0.00;
                        }
                    }
                    if(typecontriclient==""){
                        retencion = 0.0;
                    }
                    if(typedoc=='6' || typedoc=='8'){
                        $("#ivarete13").val(0);
                    }else{
                        $("#ivarete13").val(parseFloat(pricevalue.toFixed(8) * iva).toFixed(8));
                    }
                    $("#ivarete").val(
                        parseFloat(pricevalue.toFixed(8) * retencion).toFixed(8)
                    );
                    if(typedoc=='8'){
                        // CORREGIDO: Usar precio unitario actual del campo × cantidad para el cálculo
                        var cantidad = parseFloat($("#cantidad").val()) || 1;
                        var precioActual = parseFloat($("#precio").val()) || parseFloat(pricevalue);
                        var subtotal = precioActual * cantidad;
                        $("#rentarete").val(
                            parseFloat(subtotal * 0.10).toFixed(8)
                        );
                    }

                    // Cargar unidades del producto después de cargar los datos
                    if (typeof loadProductUnits === 'function') {
                        loadProductUnits(value.id);
                    } else if (typeof window.loadProductUnits === 'function') {
                        window.loadProductUnits(value.id);
                    }
                    // Solo llenar precio_sin_iva si el tipo de documento es 3 (Crédito Fiscal)
                    if(typedoc == '3' && $('#typesale').val() === 'gravada') {
                        $("#precio_sin_iva").val(pricevalue2.toFixed(8));

                        calculoIVA13 = parseFloat(value.price) - parseFloat(pricevalue2);
                            $("#ivarete13").val(calculoIVA13.toFixed(8));
                    }
                });

                var updateamounts = totalamount();

                // Cargar unidades del producto (farmacéuticas si aplica)
                if (typeof loadProductUnits === 'function') {
                    loadProductUnits(idpro);
                } else if (typeof window.loadProductUnits === 'function') {
                    window.loadProductUnits(idpro);
                }

                // Cargar información de stock del producto
                loadProductStock(idpro);

                // Comentado: No actualizar tarjetas aquí, se hará en loadProductStock
                // if (window.updateFixedCatalogCards) {
                //     const productData = {
                //         product: {
                //             name: response[0]?.productname || '',
                //             price: pricevalue,
                //             weight_per_unit: response[0]?.weight_per_unit || 0,
                //             volume_per_unit: response[0]?.volume_per_unit || 0,
                //             marca: response[0]?.marcaname || '',
                //             provider: response[0]?.provider || ''
                //         },
                //         stock: {
                //             base_quantity: 0, // Se actualizará con loadProductStock
                //             base_unit: 'unidades',
                //             measure_type: response[0]?.sale_type || 'unit'
                //         }
                //     };

                //     window.updateFixedCatalogCards(productData);
                // }

                // Verificar precios múltiples para este producto
                if (window.salesMultiplePrices) {
                    // Establecer el producto actual antes de verificar precios múltiples
                    window.salesMultiplePrices.currentProductId = idpro;
                    window.salesMultiplePrices.checkProductForMultiplePrices(idpro);
                }

                // Limpiar campos de unidad y precio - COMENTADO TEMPORALMENTE
                // $("#unit-select").empty().append('<option value="">Seleccionar...</option>');
                // $("#precio").val('0.00');
                // $("#total").val('0.00');

                // Producto cargado exitosamente
            } else {
                Swal.fire("Error", "No se pudo obtener información del producto.", "error");
            }
        },
        error: function(xhr, status, error) {
            Swal.fire("Error", "Error al obtener información del producto.", "error");
        }
    });
}

/**
 * Calcular total del producto
 */
function totalamount() {
    const cantidad = parseFloat($('#cantidad').val()) || 1;
    const precio = parseFloat($('#precio').val()) || 0;
    const total = cantidad * precio;
    const typedoc = $('#typedocument').val();

    if(typedoc == '3' && $('#typesale').val() === 'gravada') {
        const ivaEntre = 1.13;

        total_sin_iva = parseFloat(total/ivaEntre);
        totaliva13 = total - total_sin_iva;

        /*if($('#isnewsale').val() == 1){
            $('#precio_sin_iva').val(totaliva13.toFixed(8));
            $('#ivarete13').val(total_sin_iva.toFixed(8));
        }else{
            $('#precio_sin_iva').val(total_sin_iva.toFixed(8));
            $('#ivarete13').val(totaliva13.toFixed(8));
        }*/
            $('#precio_sin_iva').val(total_sin_iva.toFixed(8));
            $('#ivarete13').val(totaliva13.toFixed(8));
    }

    // Recalcular IVA Retenido (detained) - SIEMPRE se calcula, independientemente del tipo de documento
    // El campo detained debe guardarse siempre, incluso si es 0
    let retencion = 0.00;

    // Solo calcular retención si es tipo de venta gravada
    if ($('#typesale').val() === 'gravada') {
        const typecontricompany = $("#typecontribuyente").val();
        const typecontriclient = $("#typecontribuyenteclient").val();

        // Calcular retención según tipo de contribuyente
        if (typecontricompany == "GRA") {
            if (typecontriclient == "GRA") {
                retencion = 0.01; // 1% de retención
            } else if (
                typecontriclient == "MED" ||
                typecontriclient == "PEQ" ||
                typecontriclient == "OTR"
            ) {
                retencion = 0.00;
            }
        }

        if (typecontriclient == "") {
            retencion = 0.0;
        }
    }

    // SIEMPRE calcular y establecer ivarete (puede ser 0, pero debe estar calculado)
    const ivarete_unitario = parseFloat(precio * retencion).toFixed(8);
    $("#ivarete").val(ivarete_unitario);

    $('#total').val(total.toFixed(8));
    refreshIvaRetenidoPreview(true);

}

/**
 * Cambiar tipo de venta
 */
function changetypesale(type) {
    var price = $("#precio").val();
    var cantidad = $("#cantidad").val() || 1;
    var typedoc = $('#typedocument').val();
    var iva = parseFloat($("#iva").val());


    switch(type){
        case 'gravada':
            if(typedoc=='6' || typedoc=='8'){
                $('#ivarete13').val(parseFloat(0));
            }else{
                $('#ivarete13').val(parseFloat(price*iva).toFixed(8));
            }

            // Lógica de Roma Copies: Sujeto Excluido (tipo 8) SÍ genera renta, Factura (tipo 6) NO genera renta
            if(typedoc=='8'){
                // Para Sujeto Excluido (tipo 8), SÍ se aplica retención de renta del 10%
                // CORREGIDO: Usar precio unitario actual del campo × cantidad para el cálculo
                var precioActual = parseFloat($("#precio").val()) || parseFloat(price);
                var subtotal = precioActual * parseFloat(cantidad);
                var rentaCalculada = parseFloat(subtotal * 0.10).toFixed(8);
                $('#rentarete').val(rentaCalculada);
            } else if(typedoc=='6'){
                // Para Factura normal (tipo 6), NO se aplica retención de renta
                $('#rentarete').val(parseFloat(0).toFixed(8));
            }
            break;
        case 'exenta':
            $('#ivarete13').val(0.00);
            $('#ivarete').val(0.00);
            $('#rentarete').val(0.00);
            $('#precio_sin_iva').val(0.00);
            break;
        case 'nosujeta':
            $('#ivarete13').val(0.00);
            $('#ivarete').val(0.00);
            $('#rentarete').val(0.00);
            $('#precio_sin_iva').val(0.00);
            break;
    }

    // Solo calcular el total, no sobrescribir la renta
    const cantidadTotal = parseFloat($("#cantidad").val()) || 1;
    const precioTotal = parseFloat($("#precio").val()) || 0;
    const total = cantidadTotal * precioTotal;
    $('#total').val(total.toFixed(8));

    refreshIvaRetenidoPreview(true);
}

/**
 * Función agregarp del módulo original
 */
// Flag eliminado para simplificar la lógica

function agregarp() {
    // Función agregarp iniciada

    // Verificar que hay correlativo antes de continuar
    var corrid = $("#corr").val();

    if (!corrid || corrid == "" || corrid == "null") {
        Swal.fire("Error", "No hay correlativo disponible. Por favor recarga la página.", "error");
        return;
    }

    // Verificar que hay cliente seleccionado
    var clientid = $("#client").val();
    if (!clientid || clientid == "0" || clientid == "null") {
        Swal.fire("Error", "Debe seleccionar un cliente antes de agregar productos.", "error");
        return;
    }

    // Verificar que hay producto seleccionado
    var productid = $("#productid").val();

    if (!productid || productid == "" || productid == "null") {
        Swal.fire("Error", "Debe seleccionar un producto.", "error");
        return;
    }

    var reserva = $('#reserva').val();
    var ruta = $('#ruta').val();
    var destino = $('#destino').val();
    var linea = $('#linea').val();
    var canal = $('#Canal').val();
    var fee = parseFloat($("#fee").val()) || 0.00;
    var fee2 = parseFloat($("#fee2").val()) || 0.00;

    // Validar si el producto es 9 y los campos son obligatorios
    if (productid == 9) {
        if (!reserva || !ruta || !destino || !linea || !canal) {
            swal.fire("Favor complete la información del producto");

            return;
        }
    } else {
        // Si el producto no es 9, enviar valores vacíos
        reserva = "null";
        ruta = "null";
        destino = "0";
        linea = "0";
        canal = "null";
    }

    // Validación de producto 9 completada

    var typedoc = $('#typedocument').val(); //tipo de documento
    var clientid = $("#client").val(); //id del cliente
    var corrid = $("#corr").val(); //correlativo
    var acuenta = ($("#acuenta").val()==""?'SIN VALOR DEFINIDO':$("#acuenta").val()); //el nombre del cliente
    var fpago = $("#fpago").val(); //forma de pago
    var productname = $("#productname").val(); //nombre del producto
    var marca = $("#marca").val(); //marca del producto
    if(typedoc == '3'){
        var priceunitario = parseFloat(($("#precio").val()/1.13).toFixed(8));
    }
    var price = (typedoc == '3') ? priceunitario : parseFloat($("#precio").val());  //precio unitario del producto
    // Validación condicional para price_sin_iva basada en typedoc
    var price_sin_iva = (typedoc == '3') ? parseFloat($("#precio_sin_iva").val()) : parseFloat($("#precio").val()); //precio unitario del producto sin iva
    var ivarete13 = parseFloat($("#ivarete13").val()); //iva 13% normal
    var ivapercibido = parseFloat($("#ivapercibido").val()) || 0; //iva percibido (cuando empresa es gran contribuyente)
    var rentarete = parseFloat($("#rentarete").val())||0.00; //renta retenido
    var cantidad = parseFloat($("#cantidad").val()); //cantidad del producto
    var type = $("#typesale").val(); //tipo de venta gravada, exenta, no sujeta.

    // Calcular IVA Retenido (detained) - solo cuando cliente es agente de retención y tipo de venta es gravada
    // Este se calcula automáticamente: 1% sobre precio × cantidad (solo si ventas gravadas > $120)
    // Por ahora, el cálculo se hace a nivel de totales, no por producto individual
    // Para el producto individual, detained será 0 (se calcula al final sobre el total)
    var ivarete = 0; // IVA Retenido se calcula al final sobre el total de ventas gravadas
    var productdescription = $("#productdescription").val(); //descripción del producto

    // Variables extraídas
    // Unidades de medida: leer de #unit-select o .unit-select (creado por sales-units.js)
    var unitCode = $('#unit-select').val() || $('.unit-select').val() || '';

    if (!unitCode) {
        // No se seleccionó unidad de medida
        // Intentar cargar unidades si aún no existen
        if (typeof window.loadProductUnits === 'function' && productid) {
            window.loadProductUnits(productid);
        }
        Swal.fire("Información", "Seleccione la unidad de medida antes de agregar.", "warning");

        // Reset del flag en caso de validación fallida
        isAddingProduct = false;
        return;
    }

    // Unidad de medida seleccionada
    var pricegravada = 0;
    var priceexenta = 0;
    var pricenosujeta = 0;
    var sumas = parseFloat($("#sumas").val());
    var iva13 = parseFloat($("#13iva").val());
    var rentarete10 = parseFloat($("#rentaretenido").val());
    var ivaretenido = parseFloat($("#ivaretenido").val());
    var ventasnosujetas = parseFloat($("#ventasnosujetas").val());
    var ventasexentas = parseFloat($("#ventasexentas").val());
    var ventatotal = parseFloat($("#ventatotal").val());
    var descriptionbyproduct;
    var sumasl = 0;
    var ivaretenidol = 0;
    var iva13l = 0;
    var renta10l = 0;
    var ventasnosujetasl = 0;
    var ventasexentasl = 0;
    var ventatotall = 0;
    var iva13temp = 0;
    var renta10temp = 0;
    var totaltempgravado = 0;
    var priceunitariofee = 0;

    if (type == "gravada") {
        if(typedoc==3){
            pricegravada = parseFloat((price_sin_iva)+fee);
        }else{
            pricegravada = parseFloat((price * cantidad)+fee);
        }
        //pricegravada = parseFloat((price * cantidad)+fee);
        totaltempgravado = parseFloat(pricegravada);
        if(typedoc==6 || typedoc==8){
            iva13temp = 0.00;
        }else if(typedoc==3){
            iva13temp = parseFloat($("#ivarete13").val()).toFixed(8);
        }
    } else if (type == "exenta") {
        priceexenta = parseFloat(price * cantidad);
        iva13temp = 0;
    } else if (type == "nosujeta") {
        pricenosujeta = parseFloat(price * cantidad);
        iva13temp = 0;
    }

    // Cálculos de precios completados

    // Calcular total
    var total = parseFloat($("#total").val());

    // Agregar a la tabla con todas las columnas
    var newRow = `
        <tr>
            <td class="text-center">(${cantidad})</td>
            <td>${productname}</td>
            <td class="text-end">$${price.toFixed(8)}</td>
            <td class="text-end">$${pricenosujeta.toFixed(8)}</td>
            <td class="text-end">$${priceexenta.toFixed(8)}</td>
            <td class="text-end">$${pricegravada.toFixed(8)}</td>
            <td class="text-end">$${total.toFixed(8)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-remove-product" onclick="eliminarpro(this)">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    `;

    // Comentado: No agregar fila hasta que el AJAX sea exitoso
    // $("#tblproduct tbody").append(newRow);

    // Comentado: No limpiar campos hasta que el AJAX sea exitoso
    // clearProductData();

    // Comentado: No actualizar totales hasta que el AJAX sea exitoso
    // updateTotals();

    // Comentado: No actualizar botón hasta que el AJAX sea exitoso
    // updateFinalizeButton();

    // Comentado: No mostrar mensaje hasta que el AJAX sea exitoso
    // showNotification('Producto agregado correctamente', 'success');

    if(typedoc=='8'){
        iva13temp = 0.00;
    }

    // Calcular IVA Retenido (detained) ANTES de enviar al backend
    // Incluir el producto que se está agregando en el cálculo
    var ivaretenido_calculado = parseFloat($("#ivaretenido").val()) || 0;
    var es_agente_retencion = $("#cliente_agente_retencion").val() == "1";

    if (es_agente_retencion && (typedoc == '3' || typedoc == '6') && type === 'gravada') {
        // Calcular ventas gravadas actuales + el producto que se está agregando
        var ventas_gravadas_totales = 0;

        // Sumar ventas gravadas de productos ya agregados
        $("#tblproduct tbody tr").each(function() {
            var gravadasText = $(this).find("td:eq(5)").text();
            var gravadas = parseFloat(gravadasText.replace(/[$,]/g, '')) || 0;
            if (typedoc == '6') {
                ventas_gravadas_totales += gravadas / 1.13; // Facturas: quitar IVA
            } else {
                ventas_gravadas_totales += gravadas; // CCF: ya sin IVA
            }
        });

        // Agregar el producto que se está por agregar
        var gravada_nueva = pricegravada;
        if (typedoc == '6') {
            gravada_nueva = pricegravada / 1.13; // Facturas: quitar IVA
        }
        ventas_gravadas_totales += gravada_nueva;

        // Calcular retención del 1% si supera $120
        if (ventas_gravadas_totales > 120.00) {
            ivaretenido_calculado = parseFloat(ventas_gravadas_totales * 0.01);
        } else {
            ivaretenido_calculado = 0;
        }
    }

    // Usar el valor calculado para enviar al backend (detained)
    // Siempre usar el valor calculado del IVA Retenido total
    ivarete = ivaretenido_calculado;

    renta10temp = parseFloat(rentarete*cantidad).toFixed(8);
    var totaltemp = parseFloat(parseFloat(pricegravada) + parseFloat(priceexenta) + parseFloat(pricenosujeta));
    var ventatotaltotal =  parseFloat(ventatotal);
    priceunitariofee = price + (fee/cantidad);
    var totaltemptotal = parseFloat(
    ($.isNumeric(pricegravada)? pricegravada: 0) +
    ($.isNumeric(priceexenta)? priceexenta: 0) +
    ($.isNumeric(pricenosujeta)? pricenosujeta: 0) +
    ($.isNumeric(iva13temp)? parseFloat(iva13temp): 0) -
    ($.isNumeric(renta10temp)? parseFloat(renta10temp): 0) -
    ($.isNumeric(ivarete)? ivarete: 0));

    // Obtener nombre de la unidad seleccionada para incluir en la descripción
    var unitSelected = $('#unit-select option:selected');
    var unitDisplayName = unitSelected.text() || '';
    if (unitDisplayName && unitDisplayName !== 'Seleccionar...' && unitDisplayName !== 'Seleccionar unidad...') {
        descriptionbyproduct = productname + " - " + unitDisplayName;
    } else {
        descriptionbyproduct = productname + " " + marca;
    }

    // Iniciando llamada AJAX
            $.ajax({
            url: "sale-units/add-product",
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        data: JSON.stringify({
            sale_id: corrid,
            typedoc: typedoc,
            product_id: productid,
            unit_code: unitCode,
            quantity: cantidad,
            //base_price: price,
            priceunit: price,
            base_price: price_sin_iva,
            client_id: clientid,
            acuenta: acuenta,
            waytopay: fpago,
            price_nosujeta: pricenosujeta,
            price_exenta: priceexenta,
            price_gravada: pricegravada,
            iva_rete13: ivarete13,
            iva_percibido: ivapercibido, // IVA Percibido (cuando empresa es gran contribuyente)
            renta: rentarete,
            iva_rete: ivarete // IVA Retenido (cuando cliente es agente de retención, se calcula al final)
        }),
        beforeSend: function() {
            // Enviando datos AJAX
        },
        success: function (response) {
            // Respuesta AJAX recibida

            if (response.success) {
                var row =
                    '<tr id="pro' +
                    response.data.sale_detail_id +
                    '"><td>' +
                    cantidad +
                    "</td><td>" +
                    descriptionbyproduct +
                    "</td><td>" +
                    priceunitariofee.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    pricenosujeta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    priceexenta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td class='es aqui'>" +
                    pricegravada.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="text-center">' +
                    totaltemp.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="quitar_documents"><button class="btn btn-remove-product" type="button" onclick="eliminarpro(' +
                    response.data.sale_detail_id +
                    ')"><span class="ti ti-trash"></span></button></td></tr>';
                $("#tblproduct tbody").append(row);

                // Leer el valor actual de sumas del campo oculto
                const currentSumas = parseFloat($("#sumas").val()) || 0;
                sumasl = currentSumas + totaltemp;


                $("#sumasl").html(
                    sumasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#sumas").val(sumasl);

                // RESTAURADO: Lógica de cálculos de IVA (como en el módulo anterior)
                if(typedoc==6 || typedoc==8){
                    iva13l=0.00;
                }else if(typedoc==3){
                    //calculo de iva 13%
                    iva13l = parseFloat(parseFloat(iva13) + parseFloat(iva13temp));
                }
                $("#13ival").html(
                    iva13l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#13iva").val(iva13l);

                if($("#typedocument").val() == '8'){
                    //calculo de retenido 10%
                    renta10l = parseFloat(parseFloat(renta10temp) + parseFloat(rentarete10));
                    $("#10rental").html(
                        renta10l.toLocaleString("en-US", {
                            style: "currency",
                            currency: "USD",
                        })
                    );
                    $("#rentaretenido").val(renta10l);
                }
                // Calcular IVA Retenido (detained) - solo retención del agente cuando aplica
                // NO sumar ivaretenido anterior porque se recalcula desde cero sobre todas las ventas gravadas
                var es_agente_retencion = $("#cliente_agente_retencion").val() == "1";
                ivaretenidol = 0; // Inicializar en 0

                if (es_agente_retencion && (typedoc == '3' || typedoc == '6')) {
                    var ventas_gravadas = 0;

                    // Sumar todas las ventas gravadas de la tabla (incluyendo el producto recién agregado)
                    $("#tblproduct tbody tr").each(function() {
                        var gravadasText = $(this).find("td:eq(5)").text(); // Columna de ventas gravadas
                        var gravadas = parseFloat(gravadasText.replace(/[$,]/g, '')) || 0;

                        if (typedoc == '6') {
                            // Para Facturas: quitar IVA porque los montos incluyen IVA
                            var gravadasSinIva = gravadas / 1.13;
                            ventas_gravadas += gravadasSinIva;
                        } else {
                            // Para CCF: los montos ya están sin IVA
                            ventas_gravadas += gravadas;
                        }
                    });

                    // IMPORTANTE: Solo aplicar retención del 1% si las ventas gravadas superan $120
                    var retencion_agente = 0;
                    if (ventas_gravadas > 120.00) {
                        retencion_agente = parseFloat(ventas_gravadas * 0.01);
                    }

                    // Asignar directamente el valor calculado (no sumar, porque es el total sobre todas las ventas)
                    ivaretenidol = retencion_agente;
                    $("#retencion_agente").val(retencion_agente); // Guardar solo para referencia
                    updateIvaRetenidoValue(retencion_agente);
                } else {
                    ivaretenidol = 0;
                    $("#retencion_agente").val(0);
                    updateIvaRetenidoValue(0);
                }

                $("#ivaretenidol").html(
                    ivaretenidol.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ivareto").val(ivaretenidol);
                // Actualizar el campo oculto #ivaretenido con el total acumulado para que se guarde en detained
                $("#ivaretenido").val(ivaretenidol);
                // Acumular ventas no sujetas
                const currentVentasNoSujetas = parseFloat($("#ventasnosujetas").val()) || 0;
                ventasnosujetasl = currentVentasNoSujetas + pricenosujeta;
                $("#ventasnosujetasl").html(
                    ventasnosujetasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasnosujetas").val(ventasnosujetasl);

                // Acumular ventas exentas
                const currentVentasExentas = parseFloat($("#ventasexentas").val()) || 0;
                ventasexentasl = currentVentasExentas + priceexenta;
                $("#ventasexentasl").html(
                    ventasexentasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasexentas").val(ventasexentasl);

                // Calcular total final (como en el módulo anterior) - RESTANDO IVA RETENIDO
                if (typedoc == '6') { // FACTURA (id=6): total = sumas - IVA retenido
                    ventatotall = sumasl - ivaretenidol;
                } else if (typedoc == '8') { // FACTURA DE SUJETO EXCLUIDO (id=8): total = sumas - renta 10%
                    ventatotall = sumasl - renta10l;
                } else if (typedoc == '3') { // CRÉDITO FISCAL (id=3): total = sumas + IVA - IVA retenido
                    ventatotall = sumasl + iva13l - ivaretenidol;
                } else { // Otros tipos: total = sumas - retenciones
                    ventatotall = sumasl - ivaretenidol;
                }


                $("#ventatotall").html(
                    parseFloat(ventatotall).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );

                $('#ventatotallhidden').val(ventatotall);
                                $("#ventatotal").val(ventatotall);




                // Totales actualizados

                // Actualizar el botón de finalizar después de agregar producto
                updateFinalizeButton();

                // Limpiar campos después de agregar

                clearProductData();


                showNotification('Producto agregado correctamente', 'success');

                markSaleAsExisting();

                // Reset del flag para permitir futuras ejecuciones
            } else if (response == 0) {
                Swal.fire("Error", "No se pudo agregar el producto", "error");

                // Reset del flag en caso de respuesta = 0

                // Actualizar el botón de finalizar en caso de error
                updateFinalizeButton();
            }
        },
        error: function(xhr, status, error) {

            try {
                var errorResponse = JSON.parse(xhr.responseText);
                Swal.fire("Error", errorResponse.message || "Error al agregar producto", "error");
            } catch(e) {
                Swal.fire("Error", "Error al agregar producto: " + error, "error");
            }

            // Reset del flag en caso de error

            // Actualizar el botón de finalizar en caso de error
            updateFinalizeButton();
        }
    });

            // Función agregarp completada
}

/**
 * Eliminar producto de la venta
 */
function eliminarpro(idsaledetail) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "destroysaledetail/" + btoa(idsaledetail),
                method: "GET",
                success: function (response) {

                    if (response.res == "1") {

                        // Eliminar la fila de la tabla usando el ID correcto
                        const rowToRemove = $("#pro" + idsaledetail);
                        if (rowToRemove.length > 0) {
                            rowToRemove.remove();
                        } else {
                        }

                        Swal.fire({
                            title: '¡Producto Eliminado!',
                            text: response.message || 'Producto eliminado correctamente',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Recalcular totales después de eliminar
                        recalculateTotalsAfterDelete();

                        // Actualizar estado del botón de finalizar
                        updateFinalizeButton();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error al eliminar producto',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    let errorMessage = 'Error al eliminar producto';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage = 'Error de conexión con el servidor';
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
    });
}

/**
 * Recalcular totales después de eliminar un producto
 */
function recalculateTotalsAfterDelete() {

    // Obtener todas las filas de productos restantes
    const productRows = $("#tblproduct tbody tr");
    const typedoc = $('#typedocument').val();

    let sumas = 0;
    let iva13l = 0;
    let renta10l = 0;
    let ivaretenidol = 0;
    let ventasnosujetas = 0;
    let ventasexentas = 0;
    let ventasgravadas = 0;

    // Sumar los totales de todos los productos restantes
    productRows.each(function() {
        const totalText = $(this).find('td:eq(6)').text(); // Columna TOTAL
        const nosujetaText = $(this).find('td:eq(3)').text(); // Columna NO SUJETA
        const exentaText = $(this).find('td:eq(4)').text(); // Columna EXENTA
        const gravadaText = $(this).find('td:eq(5)').text(); // Columna GRAVADA

        const total = parseFloat(totalText.replace(/[^0-9.-]+/g, '')) || 0;
        const nosujeta = parseFloat(nosujetaText.replace(/[^0-9.-]+/g, '')) || 0;
        const exenta = parseFloat(exentaText.replace(/[^0-9.-]+/g, '')) || 0;
        const gravada = parseFloat(gravadaText.replace(/[^0-9.-]+/g, '')) || 0;

        sumas += total;
        ventasnosujetas += nosujeta;
        ventasexentas += exenta;
        ventasgravadas += gravada;
    });


    // Calcular IVA según tipo de documento
    if (typedoc == '3') {
        iva13l = ventasgravadas * 0.13;
    }

    // Agregar retención 1% del agente al IVA retenido si aplica
    var es_agente_retencion = $("#cliente_agente_retencion").val() == "1";
    if (es_agente_retencion && (typedoc == '3' || typedoc == '6')) {
        var ventas_gravadas_siniva = ventasgravadas;

        if (typedoc == '6') {
            // Para Facturas: quitar IVA porque los montos incluyen IVA
            ventas_gravadas_siniva = ventasgravadas / 1.13;
        }

        // IMPORTANTE: Solo aplicar retención del 1% si las ventas gravadas superan $120
        var retencion_agente = 0;
        if (ventas_gravadas_siniva > 120.00) {
            retencion_agente = parseFloat(ventas_gravadas_siniva * 0.01);
        } else {
        }

        ivaretenidol += retencion_agente;
        $("#retencion_agente").val(retencion_agente);
        updateIvaRetenidoValue(retencion_agente);
    } else {
        $("#retencion_agente").val(0);
        updateIvaRetenidoValue(0);
    }

    // Actualizar campos de totales
    $("#sumas").val(sumas);
    $("#sumasl").html(sumas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    $("#13iva").val(iva13l);
    $("#13ival").html(iva13l.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    $("#ivaretenidol").html(ivaretenidol.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#ivareto").val(ivaretenidol);

    $("#ventasnosujetas").val(ventasnosujetas);
    $("#ventasnosujetasl").html(ventasnosujetas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    $("#ventasexentas").val(ventasexentas);
    $("#ventasexentasl").html(ventasexentas.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));

    // Calcular total final según tipo de documento - RESTANDO IVA RETENIDO
    let ventatotal = 0;

    if (typedoc == '6') { // FACTURA (id=6): total = sumas - IVA retenido
        ventatotal = sumas - ivaretenidol;
    } else if (typedoc == '8') { // FACTURA DE SUJETO EXCLUIDO (id=8): total = sumas - renta 10%
        ventatotal = sumas - renta10l;
    } else if (typedoc == '3') { // CRÉDITO FISCAL (id=3): total = sumas + IVA - IVA retenido
        ventatotal = sumas + iva13l - ivaretenidol;
    } else { // Otros tipos: total = sumas - retenciones
        ventatotal = sumas - ivaretenidol;
    }

    // Actualizar total final
    $("#ventatotal").val(ventatotal);
    $("#ventatotall").html(ventatotal.toLocaleString("en-US", {
        style: "currency",
        currency: "USD",
    }));
    $("#ventatotallhidden").val(ventatotal);

}

/**
 * Limpiar datos del producto
 */
function clearProductData() {
    currentProductData = null;
    $('#add-information-products').hide();
    $('#productid').val('');
    $('#productname').val('');
    $('#productdescription').val('');
    $('#productunitario').val('');
    $('#marca').val('');
    $('#precio').val('');
    $('#cantidad').val(1);
    $('#total').val('');
    $('#ivarete13').val('');
    $('#ivarete').val('');
    $('#ivapercibido').val(''); // Limpiar IVA Percibido
    $('#ivaretencion').val(''); // Limpiar IVA Retenido (campo visible)
    $('#rentarete').val('');
    $('#psearch').val('0').trigger('change');
}

/**
 * Actualizar totales de la venta (FUNCIÓN ELIMINADA - Usar lógica de acumulación)
 * Esta función estaba causando problemas de cálculo
 */
function updateTotals() {
}

/**
 * Actualizar estado del botón de finalizar venta
 */
function updateFinalizeButton() {
    const productRows = $("#tblproduct tbody tr").length;
    const hasProducts = productRows > 0;
    const hasClient = $('#client').val() && $('#client').val() !== '0';


    $("#finalize-btn").prop('disabled', !hasProducts || !hasClient);

    // Cambiar texto del botón según el estado
    if (hasProducts && hasClient) {
        $("#finalize-btn").text('Finalizar Venta');
        $("#finalize-btn").removeClass('btn-secondary').addClass('btn-success');
    } else {
        $("#finalize-btn").text('Finalizar Venta (Faltan datos)');
        $("#finalize-btn").removeClass('btn-success').addClass('btn-secondary');
    }
}

/**
 * Finalizar venta
 * PROTEGIDO CONTRA DOBLE ENVÍO
 */
function finalizeSale() {
    // PROTECCIÓN CONTRA DOBLE ENVÍO: Prevenir ejecución si ya se está procesando
    if (isCreatingDocument) {
        showNotification('El documento ya se está creando. Por favor espere...', 'warning');
        return;
    }

    const clientId = $('#client').val();
    const companyId = $('#company').val();
    const paymentMethod = $('#fpago').val();
    const productRows = $("#tblproduct tbody tr").length;

    if (!clientId || clientId === '0') {
        showNotification('Debe seleccionar un cliente', 'warning');
        return;
    }

    if (!companyId || companyId === '0') {
        showNotification('Debe seleccionar una empresa', 'warning');
        return;
    }

    if (!paymentMethod || paymentMethod === '0') {
        showNotification('Debe seleccionar una forma de pago', 'warning');
        return;
    }

    // Validar número de autorización si es pago con tarjeta
    if (paymentMethod === '3') {
        const authorizationNumber = $('#card_authorization_number').val();
        if (!authorizationNumber || authorizationNumber.trim() === '') {
            showNotification('Debe ingresar el número de autorización del POS', 'warning');
            $('#card_authorization_number').focus();
            return;
        }
    }

    // Verificar que hay productos en la venta
    if (productRows === 0) {
        showNotification('Debe agregar al menos un producto', 'warning');
        return;
    }

    // Antes de finalizar, actualizar la retención del agente en la base de datos
    updateRetencionAgenteBeforeFinalize();

    // Actualizar el número de autorización antes de finalizar
    updateAuthorizationNumberBeforeFinalize();

    Swal.fire({
        title: '¿Finalizar venta?',
        text: `Se generará una venta por ${$("#ventatotall").text()}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // PROTECCIÓN CONTRA DOBLE ENVÍO: Verificar nuevamente antes de crear documento
            if (isCreatingDocument) {
                showNotification('El documento ya se está creando. Por favor espere...', 'warning');
                return;
            }
            // RESTAURADO: Usar createdocument como en el módulo anterior
            createDocument();
        }
    });
}

/**
 * Actualizar la retención del agente en la base de datos antes de finalizar
 */
function updateRetencionAgenteBeforeFinalize() {
    var saleId = $('#corr').val();
    var retencionAgente = parseFloat($('#retencion_agente').val()) || 0;

    if (!saleId || saleId === '') {
        return;
    }


    $.ajax({
        url: '/sale/update-retencion-agente',
        method: 'POST',
        data: {
            sale_id: saleId,
            retencion_agente: retencionAgente,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
            } else {
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Actualizar el número de autorización antes de finalizar
 */
function updateAuthorizationNumberBeforeFinalize() {
    var saleId = $('#corr').val();
    var paymentMethod = $('#fpago').val();
    var authorizationNumber = paymentMethod === '3' ? $('#card_authorization_number').val() : null;

    if (!saleId || saleId === '') {
        return;
    }

    if (paymentMethod === '3' && authorizationNumber) {
        $.ajax({
            url: '/sale/update-payment-method',
            method: 'POST',
            data: {
                sale_id: saleId,
                payment_method: paymentMethod,
                card_authorization_number: authorizationNumber,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Silencioso, solo para guardar
            },
            error: function(xhr, status, error) {
                // Silencioso
            }
        });
    }
}

/**
 * Crear documento usando createdocument (como en el módulo anterior)
 * PROTEGIDO CONTRA DOBLE ENVÍO
 */
// Variable para prevenir múltiples ejecuciones simultáneas
var isCreatingDocument = false;

function createDocument() {
    // PROTECCIÓN CONTRA DOBLE ENVÍO: Prevenir ejecución si ya se está procesando
    if (isCreatingDocument) {
        showNotification('El documento ya se está creando. Por favor espere...', 'warning');
        return;
    }

    var corr = $('#corr').val();

    // Validar que hay correlativo
    if (!corr || corr === '') {
        showNotification('No hay correlativo disponible', 'error');
        return;
    }

    // Verificar que el total esté correcto antes de finalizar
    var totalFrontend = $('#ventatotal').val() || $('#ventatotall').text().replace('$', '').replace(',', '') || 0;
    // Validar que el total sea mayor a 0
    if (parseFloat(totalFrontend) <= 0) {
        showNotification('El total de la venta debe ser mayor a 0', 'error');
        return;
    }

    // PROTECCIÓN CONTRA DOBLE ENVÍO: Marcar como procesando
    isCreatingDocument = true;

    // Deshabilitar botón de finalizar venta
    var finalizeBtn = $('#finalize-btn');
    var originalBtnHtml = finalizeBtn.html();
    var originalBtnDisabled = finalizeBtn.prop('disabled');
    
    finalizeBtn.prop('disabled', true);
    finalizeBtn.html('<i class="ti ti-loader-2 me-1 fa-spin"></i>Creando documento...');

    // Generar token de idempotencia
    var idempotencyKey = typeof generateIdempotencyKey === 'function' 
        ? generateIdempotencyKey() 
        : 'idempotency_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    // Mostrar loader
    Swal.fire({
        title: 'Creando documento...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    // Llamar a createdocument como en el módulo anterior
    // Usar el mismo total que se validó anteriormente
    // Agregar "0" al inicio porque createdocument elimina el primer carácter
    var totalamount = "0" + totalFrontend;

    $.ajax({
        url: "createdocument/" + btoa(corr) + '/' + totalamount,
        method: "GET",
        headers: {
            'X-Idempotency-Key': idempotencyKey
        },
        data: {
            _idempotency_key: idempotencyKey
        },
        success: function (response) {
            Swal.close();
            
            // PROTECCIÓN CONTRA DOBLE ENVÍO: Resetear flag de procesamiento
            isCreatingDocument = false;
            
            if (response.res == 1) {
                // Documento creado exitosamente
                Swal.fire({
                    title: "¡DTE Creado correctamente!",
                    text: "El documento se ha generado exitosamente. Imprimiendo ticket...",
                    icon: "success",
                    showCancelButton: false,
                    showDenyButton: false,
                    showCloseButton: false,
                    confirmButtonText: "Ok",
                    confirmButtonColor: "#3085d6",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    buttonsStyling: true,
                    focusConfirm: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                }).then(() => {
                    // Priorizar abrir la misma vista de ticket estándar que usa sale.index
                    if (response.ticket_url) {
                        const unifiedUrl = response.ticket_url;
                        window.open(unifiedUrl, "_blank");
                        setTimeout(function() {
                            window.location.href = "index";
                        }, 1500);
                        return;
                    }

                    // Si no hay ticket_url, construir la URL manualmente usando la misma ruta que sale.index
                    const baseUrl = window.location.origin;
                    const fallbackUrl = baseUrl + "/sale/ticket/" + response.sale_id + "?autoprint=true&auto_close=true";
                    window.open(fallbackUrl, "_blank");
                    setTimeout(function() {
                        window.location.href = "index";
                    }, 2000);
                });
                
                // No re-habilitar el botón porque se va a redirigir
            } else if (response.res == 0) {
                // PROTECCIÓN CONTRA DOBLE ENVÍO: Resetear flag y re-habilitar botón en caso de error
                isCreatingDocument = false;
                finalizeBtn.prop('disabled', originalBtnDisabled);
                finalizeBtn.html(originalBtnHtml);
                
                // Mostrar error específico del DTE si está disponible
                let errorMessage = "Algo salió mal al crear el documento";
                let errorTitle = "Error al Crear DTE";


                // Verificar si hay información específica del error del DTE
                if (response.dte_response) {
                    try {
                        const dteResponse = typeof response.dte_response === 'string'
                            ? JSON.parse(response.dte_response)
                            : response.dte_response;


                        if (dteResponse.codEstado && dteResponse.estado && dteResponse.descripcionMsg) {
                            errorTitle = `Error DTE - ${dteResponse.estado}`;
                            errorMessage = `${dteResponse.descripcionMsg}`;

                            if (dteResponse.observacionesMsg) {
                                errorMessage += `\n\nObservaciones: ${dteResponse.observacionesMsg}`;
                            }

                            if (dteResponse.nuEnvios) {
                                errorMessage += `\n\nNúmero de envíos: ${dteResponse.nuEnvios}`;
                            }

                        } else {
                        }
                    } catch (e) {
                    }
                }

                // También verificar si hay mensaje de error directo
                if (response.message) {
                    errorMessage = response.message;
                }

                // Si es un error de Hacienda específico, usar información adicional
                if (response.error_type === 'hacienda_rejected') {
                    errorTitle = "Error de Hacienda";
                    if (response.codigo) {
                        errorMessage += `\n\nCódigo: ${response.codigo}`;
                    }
                    if (response.observaciones) {
                        errorMessage += `\n\nObservaciones: ${response.observaciones}`;
                    }
                }


                Swal.fire({
                    title: errorTitle,
                    text: errorMessage,
                    icon: "error",
                    confirmButtonText: "Entendido",
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'swal-wide'
                    }
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();

            // PROTECCIÓN CONTRA DOBLE ENVÍO: Resetear flag y re-habilitar botón en caso de error
            isCreatingDocument = false;
            finalizeBtn.prop('disabled', originalBtnDisabled);
            finalizeBtn.html(originalBtnHtml);

            let errorMessage = "Error al crear el documento: " + error;
            let errorTitle = "Error";

            // Verificar si es un error de doble envío (409 Conflict)
            if (xhr.status === 409) {
                errorTitle = "Acción Duplicada";
                errorMessage = "Esta venta ya está siendo procesada. Por favor espere o recargue la página.";
            }

            // Intentar parsear la respuesta del servidor
            try {
                const response = JSON.parse(xhr.responseText);

                if (response.message) {
                    errorMessage = response.message;
                }
                if (response.error_type) {
                    errorTitle = `Error - ${response.error_type}`;
                }
                
                // Si es error de doble envío desde el backend
                if (response.error === 'duplicate_submission') {
                    errorTitle = "Acción Duplicada";
                    errorMessage = response.message || "Esta acción ya fue procesada. Por favor, no recargue la página.";
                }

                // Si hay información específica del error
                if (response.dte_response) {
                    const dteResponse = typeof response.dte_response === 'string'
                        ? JSON.parse(response.dte_response)
                        : response.dte_response;

                    if (dteResponse.codEstado && dteResponse.estado && dteResponse.descripcionMsg) {
                        errorTitle = `Error DTE - ${dteResponse.estado}`;
                        errorMessage = `${dteResponse.descripcionMsg}`;

                        if (dteResponse.observacionesMsg) {
                            errorMessage += `\n\nObservaciones: ${dteResponse.observacionesMsg}`;
                        }

                        if (dteResponse.nuEnvios) {
                            errorMessage += `\n\nNúmero de envíos: ${dteResponse.nuEnvios}`;
                        }
                    }
                }

            } catch (parseError) {
            }

            Swal.fire({
                title: errorTitle,
                text: errorMessage,
                icon: "error",
                confirmButtonText: "Entendido",
                allowOutsideClick: false,
                customClass: {
                    popup: 'swal-wide'
                }
            });
        }
    });
}

/**
 * Procesar venta (FUNCIÓN MANTENIDA PARA COMPATIBILIDAD)
 */
function processSale() {
    const saleData = {
        company_id: $('#company').val(),
        client_id: $('#company').val(),
        typedocument: $('#typedocument').val(),
        date: $('#date').val(),
        corr: $('#corr').val(),
        fpago: $('#fpago').val(),
        acuenta: $('#acuenta').val(),
        is_draft: false
    };

    $.ajax({
        url: window.salesDynamicConfig.routes.processSale,
        method: 'POST',
        data: saleData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                showNotification('Venta procesada correctamente', 'success');
                resetSale();

                // Opcional: abrir el documento generado
                if (response.document_url) {
                    window.open(response.document_url, '_blank');
                }
            } else {
                showNotification(response.message || 'Error al procesar la venta', 'error');
            }
        },
        error: function (xhr, status, error) {

            showNotification('Error al procesar la venta', 'error');
        }
    });
}

/**
 * Guardar como borrador
 */
function saveAsDraft() {
    const draftData = {
        company_id: $('#company').val(),
        client_id: $('#client').val(),
        typedocument: $('#typedocument').val(),
        date: $('#date').val(),
        corr: $('#corr').val(),
        fpago: $('#fpago').val(),
        acuenta: $('#acuenta').val(),
        is_draft: true
    };

    $.ajax({
        url: window.salesDynamicConfig.routes.saveDraft,
        method: 'POST',
        data: draftData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                showNotification('Borrador guardado correctamente', 'success');
            } else {
                showNotification(response.message || 'Error al guardar borrador', 'error');
            }
        },
        error: function (xhr, status, error) {

            showNotification('Error al guardar borrador', 'error');
        }
    });
}

/**
 * Imprimir ticket
 */
function printTicket() {
    const productRows = $("#tblproduct tbody tr").length;
    if (productRows === 0) {
        showNotification('No hay productos para imprimir', 'warning');
        return;
    }

    // Generar contenido del ticket
    const ticketContent = generateTicketContent();

    // Abrir ventana de impresión
    const printWindow = window.open('', '_blank');
    printWindow.document.write(ticketContent);
    printWindow.document.close();
    printWindow.print();
}

/**
 * Generar contenido del ticket
 */
function generateTicketContent() {
    const clientName = $('#acuenta').val() || 'Cliente General';
    const date = new Date().toLocaleString();
    const total = $("#ventatotall").text();

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket de Venta</title>
            <style>
                body { font-family: monospace; font-size: 16px; }
                .ticket { width: 300px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 1px solid #000; padding: 12px 0; }
                .items { width: 100%; border-collapse: collapse; }
                .items th, .items td { padding: 8px; text-align: left; font-size: 14px; }
                .totals { margin-top: 12px; border-top: 1px solid #000; padding-top: 12px; font-size: 16px; }
            </style>
        </head>
        <body>
            <div class="ticket">
                <div class="header">
                    <h3>AGROSERVICIO MILAGRO DE DIOS</h3>
                    <p>Ticket de Venta</p>
                    <p>${date}</p>
                    <p>Cliente: ${clientName}</p>
                </div>

                <div class="totals">
                    <p><strong>TOTAL: ${total}</strong></p>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <p>¡Gracias por su compra!</p>
                </div>
            </div>
        </body>
        </html>
    `;
}

/**
 * Cargar borrador
 */
function loadDraft(draftId) {
    // Implementar carga de borrador
    showNotification('Carga de borrador en desarrollo', 'info');
}

/**
 * Resetear venta
 */
function resetSale() {
    $('#client').val('').trigger('change');
    $('#acuenta').val('');
    $('#fpago').val('0');
    clearProductData();
    $('#tblproduct tbody').empty();
    $('#sumasl').html('$ 0.00');
    $('#13ival').html('$ 0.00');
    $('#10rental').html('$ 0.00');
    $('#ivaretenidol').html('$0.00');
    $('#ventasnosujetasl').html('$0.00');
    $('#ventasexentasl').html('$0.00');
    $('#ventatotall').html('$ 0.00');
    $('#sumas').val(0);
    $('#13iva').val(0);
    $('#rentaretenido').val(0);
    $('#ivaretenido').val(0);
    $('#ventasnosujetas').val(0);
    $('#ventasexentas').val(0);
    $('#ventatotal').val(0);
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        alert(message);
    }
}

/**
 * Mostrar mensaje de validación de cliente
 */
function showClientValidationMessage(message, type = 'warning') {
    const messageElement = $('#client-validation-message');
    const textElement = $('#validation-text');

    if (message) {
        textElement.text(message);
        messageElement.removeClass('text-warning text-danger text-info').addClass('text-' + type);
        messageElement.show();
    } else {
        messageElement.hide();
    }
}

/**
 * Mostrar información del cliente en el nuevo formato más detallado y elegante
 */
function showClientInfo(clientData) {
    if (clientData) {
        // Información personal
        var fullName = '';
        if (clientData.tpersona === 'J') {
            fullName = clientData.name_contribuyente || clientData.comercial_name || 'SIN NOMBRE';
        } else {
            fullName = (clientData.firstname || '') + ' ' + (clientData.firstlastname || '') + ' ' + (clientData.secondname || '') + ' ' + (clientData.secondlastname || '');
        }

        $('#client-name').text(fullName.trim() || '-');
        $('#client-type').text(clientData.tpersona === 'J' ? 'Jurídica' : 'Natural');
        $('#client-nit').text(clientData.nit || clientData.dui || '-');

        // Información fiscal
        var contribuyenteText = '';
        switch(clientData.tipoContribuyente) {
            case 'CONT': contribuyenteText = 'Contribuyente'; break;
            case 'EXON': contribuyenteText = 'Exonerado'; break;
            case 'NO_CONT': contribuyenteText = 'No Contribuyente'; break;
            default: contribuyenteText = clientData.tipoContribuyente || '-';
        }
        $('#client-contribuyente').text(contribuyenteText);

        // Dirección
        var address = '';
        if (clientData.address) {
            address = clientData.address;
            if (clientData.city) address += ', ' + clientData.city;
            if (clientData.state) address += ', ' + clientData.state;
        }
        $('#client-address').text(address || '-');

        // Contacto
        $('#client-phone').text(clientData.phone || clientData.mobile || '-');
        $('#client-email').text(clientData.email || '-');

        // Mostrar la información
        $('#client-info').show();

        // Actualizar el tipo de contribuyente en el hidden input
        $('#typecontribuyenteclient').val(clientData.tipoContribuyente || '');

        // Actualizar el campo oculto "Venta a cuenta de"
        $('#acuenta').val(fullName.trim() || '');
    } else {
        $('#client-info').hide();
        $('#acuenta').val('');
    }
}

// Funciones globales para compatibilidad
window.createcorrsale = createcorrsale;
window.draftdocument = draftdocument;
window.aviablenext = aviablenext;
window.getclientbycompanyurl = getclientbycompanyurl;
window.valtrypecontri = valtrypecontri;
window.valfpago = valfpago;
window.searchproductcode = searchproductcode;
window.searchproduct = searchproduct;
window.totalamount = totalamount;
window.changetypesale = changetypesale;
window.agregarp = agregarp;
window.eliminarpro = eliminarpro;
    window.finalizeSale = finalizeSale;
    window.saveAsDraft = saveAsDraft;
    window.printTicket = printTicket;
    window.createDocument = createDocument;


// Funciones para tarjetas de catálogo - COMENTADAS para evitar conflictos
// window.updateFixedCatalogCards = updateFixedCatalogCards;
// window.updateFixedCatalogCardsFromConversion = updateFixedCatalogCardsFromConversion;
// window.clearFixedCatalogCards = clearFixedCatalogCards;

// Funciones adicionales
window.loadProductUnits = loadProductUnits;
window.updateUnitConversionPreview = updateUnitConversionPreview;
// window.loadProductStock = loadProductStock; // COMENTADA para evitar llamadas externas
window.getUnitDisplayName = getUnitDisplayName;
window.showUserDrafts = showUserDrafts;
window.loadDraft = loadDraft;
window.loadDraftProducts = loadDraftProducts;


/**
 * Actualizar preview de conversión farmacéutica
 */
function updateUnitConversionPreview(productId, unitCode) {
    if (!productId || !unitCode) {
        $('#unit-conversion-preview').hide();
        return;
    }

    // Obtener información del producto para verificar si es farmacéutico
    $.ajax({
        url: '/sale/getproductbyid/' + productId,
        method: 'GET',
        success: function(response) {
            if (response.success && response.data && response.data.product) {
                const product = response.data.product;
                const quantity = parseFloat($('#cantidad').val()) || 1;

                // Solo mostrar preview para productos farmacéuticos
                if (product.pastillas_per_blister || product.blisters_per_caja) {
                    let previewText = '';
                    const pastillasPerBlister = product.pastillas_per_blister || 1;
                    const blistersPerCaja = product.blisters_per_caja || 1;

                    if (unitCode === 'PASTILLA') {
                        previewText = `Se descontarán ${quantity} pastilla(s) del inventario`;
                    } else if (unitCode === 'BLISTER') {
                        const totalPastillas = quantity * pastillasPerBlister;
                        previewText = `${quantity} Blister(s) × ${pastillasPerBlister} Pastilla/Blister = ${totalPastillas} pastilla(s) se descontarán del inventario`;
                    } else if (unitCode === 'CAJA') {
                        const totalPastillas = quantity * blistersPerCaja * pastillasPerBlister;
                        previewText = `${quantity} Caja(s) × ${blistersPerCaja} Blister/Caja × ${pastillasPerBlister} Pastilla/Blister = ${totalPastillas} pastilla(s) se descontarán del inventario`;
                    }

                    if (previewText) {
                        $('#unit-conversion-preview').text(previewText).show();
                    } else {
                        $('#unit-conversion-preview').hide();
                    }
                } else {
                    $('#unit-conversion-preview').hide();
                }
            } else {
                $('#unit-conversion-preview').hide();
            }
        },
        error: function() {
            $('#unit-conversion-preview').hide();
        }
    });
}

/**
 * Cargar unidades de medida del producto
 */
function loadProductUnits(productId) {
    $.ajax({
        url: '/sale/getproductbyid/' + productId,
        method: 'GET',
        success: function(response) {
            if (response.success && response.data && response.data.units) {
                const unitSelect = $('#unit-select');
                unitSelect.empty();
                unitSelect.append('<option value="">Seleccionar...</option>');

                // Filtrar unidades según el tipo de producto
                let allowedUnitCodes = ['59']; // Por defecto: solo Unidad

                // Verificar si es un producto farmacéutico
                if (response.data.product && (response.data.product.pastillas_per_blister || response.data.product.blisters_per_caja)) {
                    // Para productos farmacéuticos, cargar solo unidades farmacéuticas
                    allowedUnitCodes = ['PASTILLA', 'BLISTER', 'CAJA'];
                } else if (response.data.product && response.data.product.sale_type) {
                    switch(response.data.product.sale_type) {
                        case 'volume':
                            allowedUnitCodes = ['59', '23', '99']; // Unidad, Litro, Dólar
                            break;
                        case 'weight':
                            allowedUnitCodes = ['59', '36', '99']; // Unidad, Libra, Dólar
                            break;
                        case 'unit':
                            allowedUnitCodes = ['59']; // Solo Unidad
                            break;
                    }
                }

                const allowedUnits = response.data.units.filter(function(unit) {
                    return allowedUnitCodes.includes(unit.unit_code);
                });

                allowedUnits.forEach(function(unit) {
                    const unitName = getUnitDisplayName(unit.unit_code, unit.unit_name);
                    unitSelect.append(`<option value="${unit.unit_code}" data-unit-id="${unit.unit_id}" data-conversion-factor="${unit.conversion_factor}">${unitName}</option>`);
                });

                // Seleccionar la primera opción de unidad (descontar del inventario)
                if (allowedUnits.length > 0) {
                    const firstUnitCode = allowedUnits[0].unit_code;
                    unitSelect.val(firstUnitCode).trigger('change');
                }

            } else {

            }
        },
        error: function(xhr, status, error) {

        }
    });
}

/**
 * Obtener nombre de visualización de la unidad
 */
function getUnitDisplayName(unitCode, unitName) {
    const unitNames = {
        '59': 'Unidad',
        '36': 'Libra',
        '23': 'Litro',
        '99': 'Dólar',
        'PASTILLA': 'Pastilla',
        'BLISTER': 'Blister',
        'CAJA': 'Caja'
    };
    return unitNames[unitCode] || unitName;
}

/**
 * Cargar información de stock del producto
 */
function loadProductStock(productId) {
    $.ajax({
        url: '/sale/getproductbyid/' + productId,
        method: 'GET',
        success: function(response) {
            if (response.success && response.data) {


                                    // Llenar campos ocultos con información del stock
                    $('#stock_quantity').val(response.data.stock?.available_quantity || 0);
                    $('#total_in_lbs').val(response.data.stock?.total_in_lbs || 0);
                    $('#total_in_liters').val(response.data.stock?.total_in_liters || 0);
                    $('#total_in_ml').val(response.data.stock?.total_in_ml || 0);
                    $('#measure_type').val(response.data.stock?.measure_type || 'unit');

                    // Guardar datos farmacéuticos en campos ocultos para acceso rápido
                    if (response.data.product.pastillas_per_blister) {
                        $('#pastillas_per_blister').val(response.data.product.pastillas_per_blister);
                    }
                    if (response.data.product.blisters_per_caja) {
                        $('#blisters_per_caja').val(response.data.product.blisters_per_caja);
                    }

                    // Actualizar tarjetas con información real del stock
                    const productData = {
                        product: {
                            name: response.data.product.name || $('#productname').val(),
                            price: parseFloat($('#precio').val()) || 0,
                            weight_per_unit: response.data.product.weight_per_unit || 100.0000,
                            volume_per_unit: response.data.product.volume_per_unit || 0,
                            pastillas_per_blister: response.data.product.pastillas_per_blister || 0,
                            blisters_per_caja: response.data.product.blisters_per_caja || 0,
                            marca: response.data.product.marca_name || $('#marca').val(),
                            provider: response.data.product.provider_name || ''
                        },
                        stock: {
                            base_quantity: response.data.stock?.available_quantity || 0,
                            unit_name: response.data.stock?.unit_name || 'unidades',
                            total_in_lbs: response.data.stock?.total_in_lbs || 0,
                            total_in_liters: response.data.stock?.total_in_liters || 0,
                            total_in_ml: response.data.stock?.total_in_ml || 0,
                            measure_type: response.data.stock?.measure_type || 'unit'
                        }
                    };

                    // Actualizar los cuadros de información con datos reales del stock
                    updateAllInformationCards(productData, null);

                    // Verificar precios múltiples para este producto
                    if (window.salesMultiplePrices) {
                        window.salesMultiplePrices.checkProductForMultiplePrices(productId);
                    }

                    // Comentado: No usar forceUpdateLabels (ya se actualiza en updateAllInformationCards)
                    // setTimeout(function() {
                    //     forceUpdateLabels(response.data.stock?.measure_type || 'unit');
                    // }, 500);
            } else {
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

/**
 * Procesar venta
 */
function processSale() {
    // Verificar que hay productos en la tabla
    if ($("#tblproduct tbody tr").length === 0) {
        Swal.fire("Error", "Debe agregar al menos un producto antes de procesar la venta.", "error");
        return;
    }

    // Recopilar datos de la venta
    var saleData = {
        corr: $("#corr").val(),
        client: $("#client").val(),
        typedocument: $("#typedocument").val(),
        date: $("#date").val(),
        acuenta: $("#acuenta").val(),
        fpago: $("#fpago").val(),
        sumas: $("#sumas").val(),
        iva13: $("#13iva").val(),
        ivaretenido: $("#ivaretenido").val(),
        rentaretenido: $("#rentaretenido").val(),
        ventasnosujetas: $("#ventasnosujetas").val(),
        ventasexentas: $("#ventasexentas").val(),
        ventatotal: $("#ventatotal").val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Enviar datos al servidor
    $.ajax({
        url: '/sale/process-sale',
        method: 'POST',
        data: saleData,
        success: function(response) {
            if (response.success) {
                // Limpiar localStorage cuando se finaliza la venta
                localStorage.removeItem('current_sale_draft');
                localStorage.removeItem('current_sale_type');

                Swal.fire({
                    title: '¡Venta Procesada!',
                    text: 'La venta se ha procesado correctamente.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Redirigir a la lista de ventas o imprimir
                    window.location.href = '/sale/index';
                });
            } else {
                Swal.fire("Error", response.message || "Error al procesar la venta", "error");
            }
        },
        error: function(xhr, status, error) {

            Swal.fire("Error", "Error al procesar la venta", "error");
        }
    });
}

/**
 * Actualizar tarjetas fijas del catálogo con datos de producto
 * COMENTADA para evitar conflictos con updateAllInformationCards
 */
/*
function updateFixedCatalogCards(productData) {

    if (productData && productData.product) {
        const product = productData.product;
        const stock = productData.stock;

        // Logs detallados para diagnosticar

        // Tarjeta de Conversión de Unidades
        const measureType = stock?.measure_type || 'unit';

                if (measureType === 'weight') {
            // Producto por peso (sacos, libras)
            $('#catalog-price-sack').text('$' + (product.price || 0).toFixed(2));
            $('#catalog-weight-total').text((product.weight_per_unit || 0).toFixed(4) + ' libras');
            $('#catalog-price-pound').text('$' + ((product.price || 0) / (product.weight_per_unit || 1)).toFixed(2));

            // Actualizar etiquetas para productos por peso
            $('#catalog-price-sack-label').text('Precio del Saco');
            $('#catalog-weight-total-label').text('Libras por Saco');
            $('#catalog-price-pound-label').text('Precio por Libra');
        } else if (measureType === 'volume') {

            // Producto por volumen (depósitos, litros)
            const volumePerUnit = parseFloat(product.volume_per_unit) || 0;
            $('#catalog-price-sack').text('$' + (product.price || 0).toFixed(2));
            $('#catalog-weight-total').text(volumePerUnit.toFixed(4) + ' litros');
            $('#catalog-price-pound').text('$' + ((product.price || 0) / (volumePerUnit || 1)).toFixed(2));

            // Actualizar etiquetas para productos por volumen
            $('#catalog-price-sack-label').text('Precio del Contenedor');
            $('#catalog-weight-total-label').text('Litros por Contenedor');
            $('#catalog-price-pound-label').text('Precio por Litro');
        } else {
            // Producto por unidad
            $('#catalog-price-sack').text('$' + (product.price || 0).toFixed(2));
            $('#catalog-weight-total').text('0.0000 unidades');
            $('#catalog-price-pound').text('$' + (product.price || 0).toFixed(2));

            // Actualizar etiquetas para productos por unidad
            $('#catalog-price-sack-label').text('Precio por Unidad');
            $('#catalog-weight-total-label').text('Unidades');
            $('#catalog-price-pound-label').text('Precio por Unidad');
        }

                // Tarjeta de Stock Disponible
        const stockQuantity = stock?.base_quantity || 0;
        const stockUnit = stock?.unit_name || 'unidades';
        const stockMeasureType = stock?.measure_type || 'unit';

        let stockMeasure = '';
        let stockMeasureAlt = '';

        if (stockMeasureType === 'weight') {
            // Producto por peso (sacos, libras, kg)
            stockMeasure = (stock?.total_in_lbs || 0).toFixed(4) + ' libras';
            stockMeasureAlt = (stock?.total_in_kg || 0).toFixed(4) + ' kg';
            $('#catalog-stock-lbs-label').text('Total en libras:');
        } else if (stockMeasureType === 'volume') {
            // Producto por volumen (depósitos, litros, ml)
            stockMeasure = (stock?.total_in_liters || 0).toFixed(4) + ' litros';
            stockMeasureAlt = (stock?.total_in_ml || 0).toFixed(4) + ' ml';
            $('#catalog-stock-lbs-label').text('Total en litros:');
        } else {
            // Producto por unidad
            stockMeasure = '0.0000 unidades';
            stockMeasureAlt = '0.0000 unidades';
            $('#catalog-stock-lbs-label').text('Total en unidades:');
        }


        $('#catalog-stock-available').text(stockQuantity.toFixed(4) + ' ' + stockUnit);
        $('#catalog-stock-lbs').text(stockMeasure);

        let stockStatusFixed = (stock?.base_quantity || 0) > 0 ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Sin stock</span>';
        $('#catalog-stock-status').html(stockStatusFixed);

        // Tarjeta de Validaciones
        const validationQuantity = stock?.base_quantity || 0;
        const validationUnit = stock?.unit_name || 'unidades';

        $('#catalog-validation-current').text(validationQuantity.toFixed(4) + ' ' + validationUnit);
        $('#catalog-validation-sell').text('0');
        $('#catalog-validation-after').text(validationQuantity.toFixed(4) + ' ' + validationUnit);

        // Mostrar medida según el tipo de venta
        if (stockMeasureType === 'weight') {
            $('#catalog-validation-lbs').text((stock?.total_in_lbs || 0).toFixed(4) + ' libras');
            $('#catalog-validation-lbs-label').text('En libras:');
        } else if (stockMeasureType === 'volume') {
            $('#catalog-validation-lbs').text((stock?.total_in_liters || 0).toFixed(4) + ' litros');
            $('#catalog-validation-lbs-label').text('En litros:');
        } else {
            $('#catalog-validation-lbs').text('0.0000 unidades');
            $('#catalog-validation-lbs-label').text('En unidades:');
        }
    }
}
*/

/**
 * Actualizar tarjetas fijas del catálogo con datos de conversión
 * COMENTADA para evitar conflictos con updateAllInformationCards
 */
/*
function updateFixedCatalogCardsFromConversion(data) {


    if (data) {
        // Tarjeta de Conversión de Unidades
        const measureType = data.stock_info?.measure_type || 'unit';

                if (measureType === 'weight') {
            // Producto por peso
            $('#catalog-price-sack').text('$' + (data.calculations?.price_per_sack || 0).toFixed(2));
            $('#catalog-weight-total').text((data.calculations?.weight_per_unit || 0).toFixed(4) + ' libras');
            $('#catalog-price-pound').text('$' + (data.calculations?.price_per_pound || 0).toFixed(2));

            // Actualizar etiquetas para productos por peso
            $('#catalog-price-sack-label').text('Precio del Saco');
            $('#catalog-weight-total-label').text('Libras por Saco');
            $('#catalog-price-pound-label').text('Precio por Libra');
        } else if (measureType === 'volume') {
            // Producto por volumen
            $('#catalog-price-sack').text('$' + (data.calculations?.price_per_sack || 0).toFixed(2));
            $('#catalog-weight-total').text((data.calculations?.volume_per_unit || 0).toFixed(4) + ' litros');
            $('#catalog-price-pound').text('$' + (data.calculations?.price_per_liter || 0).toFixed(2));

            // Actualizar etiquetas para productos por volumen
            $('#catalog-price-sack-label').text('Precio del Contenedor');
            $('#catalog-weight-total-label').text('Litros por Contenedor');
            $('#catalog-price-pound-label').text('Precio por Litro');
        } else {
            // Producto por unidad
            $('#catalog-price-sack').text('$' + (data.calculations?.base_price || 0).toFixed(2));
            $('#catalog-weight-total').text('0.0000 unidades');
            $('#catalog-price-pound').text('$' + (data.calculations?.base_price || 0).toFixed(2));

            // Actualizar etiquetas para productos por unidad
            $('#catalog-price-sack-label').text('Precio por Unidad');
            $('#catalog-weight-total-label').text('Unidades');
            $('#catalog-price-pound-label').text('Precio por Unidad');
        }

        // Tarjeta de Stock Disponible
        const conversionStockQuantity = data.stock_info?.available_quantity || 0;
        const conversionStockUnit = data.stock_info?.base_unit || 'unidades';
        const conversionMeasureType = data.stock_info?.measure_type || 'unit';

        let conversionStockMeasure = '';

        if (conversionMeasureType === 'weight') {
            conversionStockMeasure = (data.stock_info?.total_in_lbs || 0).toFixed(4) + ' libras';
            $('#catalog-stock-lbs-label').text('Total en libras:');
        } else if (conversionMeasureType === 'volume') {
            conversionStockMeasure = (data.stock_info?.total_in_liters || 0).toFixed(4) + ' litros';
            $('#catalog-stock-lbs-label').text('Total en litros:');
        } else {
            conversionStockMeasure = '0.0000 unidades';
            $('#catalog-stock-lbs-label').text('Total en unidades:');
        }

        $('#catalog-stock-available').text(conversionStockQuantity.toFixed(4) + ' ' + conversionStockUnit);
        $('#catalog-stock-lbs').text(conversionStockMeasure);

        let stockStatusConversion = data.stock_info?.available ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Sin stock</span>';
        $('#catalog-stock-status').html(stockStatusConversion);

        // Tarjeta de Validaciones
        const quantity = parseFloat($('#cantidad').val()) || 1;
        const availableStock = data.stock_info?.available_quantity || 0;
        let unitCode = $('#unit-select').val();
        const weightPerUnit = data.calculations?.weight_per_unit || 100.0000;

        let quantityInSacos = quantity;
        let remainingAfterSale = availableStock;
        let remainingInLbs = 0;
        let isAvailable = true;

        // Si la unidad es libras, convertir a sacos para el cálculo
        if (unitCode === '36') {
            quantityInSacos = quantity / weightPerUnit;
            remainingAfterSale = availableStock - quantityInSacos;
            remainingInLbs = remainingAfterSale * weightPerUnit;
            isAvailable = remainingAfterSale >= 0;
        } else {
            remainingAfterSale = availableStock - quantity;
            remainingInLbs = remainingAfterSale * weightPerUnit;
            isAvailable = remainingAfterSale >= 0;
        }

        const displayRemainingAfterSale = Math.max(0, remainingAfterSale);
        const displayRemainingInLbs = Math.max(0, remainingInLbs);

        $('#catalog-validation-current').text(availableStock.toFixed(4) + ' ' + (data.stock_info?.base_unit || 'unidades'));
        $('#catalog-validation-sell').text(quantity.toFixed(2));
        $('#catalog-validation-after').text(displayRemainingAfterSale.toFixed(4) + ' ' + (data.stock_info?.base_unit || 'unidades'));
        // Mostrar medida según el tipo de venta del producto
        if (conversionMeasureType === 'weight') {
            $('#catalog-validation-lbs').text(displayRemainingInLbs.toFixed(4) + ' libras');
            $('#catalog-validation-lbs-label').text('En libras:');
        } else if (conversionMeasureType === 'volume') {
            $('#catalog-validation-lbs').text((displayRemainingInLbs / 1000).toFixed(4) + ' litros');
            $('#catalog-validation-lbs-label').text('En litros:');
        } else {
            $('#catalog-validation-lbs').text(displayRemainingInLbs.toFixed(4) + ' unidades');
            $('#catalog-validation-lbs-label').text('En unidades:');
        }

        let stockStatusFinal = isAvailable ?
            '<span class="badge bg-success">Disponible</span>' :
            '<span class="badge bg-danger">Stock insuficiente</span>';
        $('#catalog-stock-status').html(stockStatusFinal);
    }
}
*/

/**
 * Limpiar tarjetas fijas del catálogo
 */
function clearFixedCatalogCards() {


    $('#catalog-price-sack').text('$0.00');
    $('#catalog-weight-total').text('0.0000 unidades');
    $('#catalog-price-pound').text('$0.00');
    $('#catalog-stock-available').text('0 unidades');
    $('#catalog-stock-lbs').text('0 unidades');
    $('#catalog-stock-status').html('<span class="badge bg-secondary">Sin datos</span>');
    $('#catalog-validation-current').text('0 unidades');
    $('#catalog-validation-sell').text('0');
    $('#catalog-validation-after').text('0 unidades');
    $('#catalog-validation-lbs').text('0 libras');
}







/**
 * Función de debug para cargar clientes manualmente
 */
function debugLoadClients() {

    const companyId = $('#company').val();


    if (companyId && companyId !== '') {
        getclientbycompanyurl(companyId);
    } else {

        alert('No hay ID de empresa seleccionada');
    }
}

/**
 * Verificar sistema de precios múltiples
 */
function checkMultiplePricesSystem() {
    // Esperar a que el sistema de precios múltiples esté listo
    const checkMultiplePrices = () => {
        if (typeof window.salesMultiplePrices !== 'undefined' && window.salesMultiplePrices.isReady()) {
            // Sistema de precios múltiples inicializado
        } else {
            // Esperando sistema de precios múltiples...
            // Reintentar en 100ms
            setTimeout(checkMultiplePrices, 100);
        }
    };

    // Iniciar verificación
    checkMultiplePrices();
}

/**
 * Cargar unidades básicas para un producto
 */
function loadBasicUnits(productId) {
    // Cargando unidades básicas para producto

    // Unidades básicas disponibles
    const basicUnits = [
        { code: '59', name: 'Unidad', id: 59, conversion_factor: 1 },
        { code: '36', name: 'Libra', id: 36, conversion_factor: 1 },
        { code: '23', name: 'Litro', id: 23, conversion_factor: 1 },
        { code: '99', name: 'Dólar', id: 99, conversion_factor: 1 }
    ];

    // Llenar dropdown de unidades
    const unitSelect = $('#unit-select');
    unitSelect.empty().append('<option value="">Seleccionar unidad...</option>');

        basicUnits.forEach(function(unit) {
        const optionHtml = '<option value="' + unit.code + '" data-unit-id="' + unit.id + '" data-conversion-factor="' + unit.conversion_factor + '">' + unit.name + '</option>';
        unitSelect.append(optionHtml);
    });

    // Unidades básicas cargadas
}
