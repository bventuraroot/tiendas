/**
 * Page Purchase List - Módulo completamente reescrito
 */

'use strict';

// ========================================
// VARIABLES GLOBALES
// ========================================

let products = [];
let selectedProducts = [];
let productRowIndex = 0;
let currentRowIndex = undefined;

// Variables para edición
let editSelectedProducts = [];
let editProductRowIndex = 0;

// ========================================
// INICIALIZACIÓN DATATABLES Y SELECT2
// ========================================

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

  // Select2 configurations
  $('.select2purchase').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Periodo',
      dropdownParent: $(this).parent()
 });
  });

  $('.select2purchaseedit').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Periodo',
      dropdownParent: $(this).parent()
 });
  });

  $('.select2company').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Empresa',
      dropdownParent: $(this).parent()
 });
  });

  $('.select2companyedit').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Empresa',
      dropdownParent: $(this).parent()
 });
  });

  $('.select2provider').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Proveedor',
      dropdownParent: $(this).parent()
 });
  });

  $('.select2provideredit').each(function() {
    $(this).wrap('<div class="position-relative"></div>').select2({
   placeholder: 'Seleccionar Proveedor',
      dropdownParent: $(this).parent()
 });
  });

  // DataTable configuration
  var dt_purchase_table = $('.datatables-purchase');
  if (dt_purchase_table.length) {
    var dt_purchase = dt_purchase_table.DataTable({
      columnDefs: [
        {
          responsivePriority: 1,
          targets: 0 // Acciones siempre visible
        },
        {
          responsivePriority: 2,
          targets: 3 // Fecha con alta prioridad
        }
      ],
      order: [[3, 'desc']], // Columna FECHA (índice 3): más recientes primero
      scrollX: true,
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
        searchPlaceholder: 'Buscar',
        emptyTable: 'No hay datos disponibles en la tabla'
      },
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
              exportOptions: { columns: [1, 2, 3, 4, 5] }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5] }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5] }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-code-2 me-2"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5] }
            }
          ]
        },
        {
          text: '<i class="ti ti-report-money ti-tada me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">Nueva Compra</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'modal',
            'data-bs-target': '#addPurchaseModal'
          }
        }
      ],
      responsive: false
    });
  }

  // Filter form control to default size
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});

// ========================================
// INICIALIZACIÓN DEL SISTEMA DE PRODUCTOS
// ========================================

$(document).ready(function() {

    // Reset variables globales
    products = [];
    selectedProducts = [];
    productRowIndex = 0;
    currentRowIndex = undefined;
    editSelectedProducts = [];
    editProductRowIndex = 0;

    // Verificar elementos necesarios
    if (!$('#productModal').length) {
        return;
    }

    if (!$('#productSelectionTable').length) {
        return;
    }

    // Inicializar
    initializeProductSystem();

});

function initializeProductSystem() {
    // Cargar productos
    loadProducts();

    // Configurar eventos
    setupEventListeners();

    // No crear fila inicial - el usuario debe hacer click en "Agregar Producto"
}

function setupEventListeners() {
    // Botón agregar producto
    $('#addProductBtn').off('click').on('click', function() {
        currentRowIndex = undefined; // Nueva compra
        $('#productModal').modal('show');
    });

    // Búsqueda de productos
    $('#productSearch').off('input').on('input', filterProducts);

    // Botón agregar producto en edición
    $('#addEditProductBtn').off('click').on('click', function() {
        addEditProductRow();
    });

    // Formulario nueva compra (index.blade.php)
    $('#addpurchaseForm').off('submit').on('submit', handleFormSubmit);

    // Formulario crear compra (create.blade.php)
    $('#purchaseForm').off('submit').on('submit', handleCreateFormSubmit);

    // Formulario editar compra (index.blade.php)
    $('#updatepurchaseForm').off('submit').on('submit', handleUpdateFormSubmit);

    // Eventos del modal de nueva compra
    $('#addPurchaseModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        resetPurchaseForm();
    });

    $('#addPurchaseModal').off('show.bs.modal').on('show.bs.modal', function() {
        initializePurchaseForm();
    });

    // Eventos del modal de edición
    $('#updatePurchaseModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        editSelectedProducts = [];
        editProductRowIndex = 0;
    });

    $('#updatePurchaseModal').off('show.bs.modal').on('show.bs.modal', function() {
    });
}

// ========================================
// GESTIÓN DE PRODUCTOS
// ========================================

function loadProducts() {

    $.ajax({
        url: '/purchase/products',
        method: 'GET',
        success: function(response) {
        if (response.success) {
            products = response.products;
                renderProductSelectionTable();
        } else {
                showError('Error al cargar productos: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showError('Error al cargar productos. Revisa la consola.');
        }
    });
}

function renderProductSelectionTable() {
    const tbody = $('#productSelectionTable tbody');
    tbody.empty();

    if (products.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center">No hay productos disponibles</td></tr>');
        return;
    }

    products.forEach(product => {
        const row = `
            <tr>
                <td>${product.code || 'N/A'}</td>
                <td>${product.name}</td>
                <td>${product.provider ? product.provider.razonsocial : 'N/A'}</td>
                <td>$${parseFloat(product.price || 0).toFixed(2)} <small class="text-muted">(Precio venta)</small></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary select-product-btn"
                            data-product-id="${product.id}">
                        <i class="ti ti-plus"></i> Seleccionar
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Event listeners para botones de selección
    $('.select-product-btn').off('click').on('click', function() {
        const productId = parseInt($(this).data('product-id'));
        selectProduct(productId);
    });

    // Eventos del modal de productos
    $('#productModal').off('show.bs.modal').on('show.bs.modal', function() {
    });

    $('#productModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        currentRowIndex = undefined;
    });
}

function filterProducts() {
    const searchTerm = $('#productSearch').val().toLowerCase();
    const rows = $('#productSelectionTable tbody tr');

    rows.each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(searchTerm));
    });
}

function selectProduct(productId) {

    const product = products.find(p => p.id === productId);
    if (!product) {
        showError('Producto no encontrado');
        return;
    }


    // Detectar modo
    const isEditMode = $('#updatePurchaseModal').is(':visible');

    if (isEditMode) {
        handleEditModeSelection(product);
        } else {
        handleNewModeSelection(product);
        }

    // Cerrar modal y mostrar confirmación
        $('#productModal').modal('hide');

    // Mostrar información sobre utilidad potencial
    const salePrice = parseFloat(product.price || 0);
    showSuccess(`"${product.name}" agregado. Precio venta: $${salePrice.toFixed(2)}. Ingresa el costo de compra para calcular utilidad.`);
}

function handleNewModeSelection(product) {
    if (currentRowIndex !== undefined) {
        updateProductRow(currentRowIndex, product);
    } else {
        addProductRowWithData(product);
    }
}

function handleEditModeSelection(product) {
    addEditProductRow(product);
}

// ========================================
// GESTIÓN DE FILAS DE PRODUCTOS
// ========================================

function addEmptyProductRow() {

    const isIndexView = isIndexViewStructure();
    const rowHtml = generateEmptyRowHtml(productRowIndex, isIndexView);

    $('#productsTableBody').append(rowHtml);
    productRowIndex++;

}

function addProductRowWithData(product) {

    const isIndexView = isIndexViewStructure();
    const rowHtml = generateProductRowHtml(productRowIndex, product, isIndexView);

    $('#productsTableBody').append(rowHtml);

    // Esperar a que el DOM esté listo antes de cargar las unidades (delay para modales y reflow)
    const rowIndexToLoad = productRowIndex;
    setTimeout(() => {
        loadProductUnits(rowIndexToLoad, product.id);
    }, 200);

    // Registrar producto seleccionado - usar precio como costo inicial, pero permitir edición
    selectedProducts[productRowIndex] = {
        product_id: parseInt(product.id),
        quantity: 1,
        unit_price: parseFloat(product.price || 0), // Costo inicial basado en precio, pero editable
        unit_code: null,
        unit_id: null,
        conversion_factor: 1,
        expiration_date: null,
        notes: null
    };

    // Calcular totales
    calculateRowTotal(productRowIndex);
    productRowIndex++;

}

function updateProductRow(rowIndex, product) {

    const row = $(`#productRow_${rowIndex}`);

    // Verificar que la fila existe
    if (row.length === 0) {
        console.warn(`updateProductRow: No se encontró la fila ${rowIndex}`);
        return;
    }

    // Actualizar campos
    row.find('.product-name').val(product.name);
    row.find('.product-id').val(product.id);
    row.find('.unit-price').val(product.price);

    // Cargar unidades disponibles para este producto (delay para que el DOM esté listo)
    setTimeout(() => {
        loadProductUnits(rowIndex, product.id);
    }, 150);

    // Registrar producto seleccionado
    const quantity = parseInt(row.find('.quantity').val()) || 1;
    selectedProducts[rowIndex] = {
        product_id: parseInt(product.id),
        quantity: quantity,
        unit_price: parseFloat(product.price || 0),
        unit_code: null,
        unit_id: null,
        conversion_factor: 1,
        expiration_date: row.find('.expiration-date').val() || null,
        notes: row.find('.notes').val() || null
    };

    // Calcular totales
    calculateRowTotal(rowIndex);

}

/**
 * Obtener el selector de unidades de una fila (por id o por posición en la tabla)
 */
function getUnitSelectForRow(rowIndex) {
    let unitSelect = $(`#productRow_${rowIndex}`).find('.unit-select');
    if (unitSelect.length === 0) {
        unitSelect = $('#productsTableBody tr').eq(rowIndex).find('.unit-select');
    }
    if (unitSelect.length === 0) {
        unitSelect = $(`tr[id="productRow_${rowIndex}"]`).find('.unit-select');
    }
    return unitSelect;
}

/**
 * Cargar unidades disponibles para un producto
 */
function loadProductUnits(rowIndex, productId) {
    if (!productId) {
        console.warn('loadProductUnits: No se proporcionó productId');
        return;
    }

    const unitSelect = getUnitSelectForRow(rowIndex);

    if (unitSelect.length === 0) {
        console.warn(`loadProductUnits: No se encontró el selector de unidades para la fila ${rowIndex}`);
        return;
    }

    // Mostrar indicador de carga
    unitSelect.prop('disabled', true).html('<option>Cargando unidades...</option>');

    $.ajax({
        url: `/sale/getproductbyid/${productId}`,
        method: 'GET',
        success: function(response) {
            // Limpiar opciones existentes
            unitSelect.empty().append('<option value="">Seleccionar unidad...</option>');
            
            if (response.success && response.data && response.data.units && response.data.units.length > 0) {
                let units = response.data.units;
                const product = response.data.product || {};

                // En COMPRAS: solo unidades que aplican a farmacia
                if (product.pastillas_per_blister || product.blisters_per_caja) {
                    // Producto parametrizado (pastilla/blister/caja): solo Caja
                    units = units.filter(u => u.unit_code === 'CAJA');
                } else {
                    // Producto no parametrizado: solo Unidad (no Kilogramo, Libra, Dólar, etc.)
                    units = units.filter(u => u.unit_code === '59');
                    if (units.length === 0) {
                        units = [{ unit_code: '59', unit_id: (response.data.units.find(u => u.unit_code === '59') || {}).unit_id || '', unit_name: 'Unidad', conversion_factor: 1 }];
                    }
                }

                if (units.length > 0) {
                    units.forEach(unit => {
                        const unitName = getUnitDisplayName(unit.unit_code, unit.unit_name);
                        unitSelect.append(`<option value="${unit.unit_code}" data-id="${unit.unit_id || ''}" data-factor="${unit.conversion_factor || 1}">${unitName}</option>`);
                    });
                    const defaultUnit = units.find(u => u.unit_code === 'CAJA') || units.find(u => u.unit_code === '59') || units.find(u => u.is_default) || units[0];
                    unitSelect.val(defaultUnit.unit_code).trigger('change');
                } else {
                    unitSelect.append(`<option value="59" data-id="" data-factor="1">Unidad</option>`).val('59').trigger('change');
                }
            } else {
                unitSelect.append(`<option value="59" data-id="" data-factor="1">Unidad</option>`).val('59').trigger('change');
            }
            
            unitSelect.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error(`Error cargando unidades para producto ${productId}:`, error);
            // En caso de error, agregar "Unidad" por defecto
            unitSelect.empty()
                .append('<option value="">Seleccionar unidad...</option>')
                .append(`<option value="59" data-id="" data-factor="1">Unidad</option>`)
                .prop('disabled', false);
        }
    });
}

function loadEditProductUnits(rowIndex, productId, selectedUnitCode = null, selectedUnitId = null, conversionFactor = 1) {
    if (!productId) {
        console.warn('loadEditProductUnits: No se proporcionó productId');
        return;
    }

    const unitSelect = $(`#editProductRow_${rowIndex} .unit-select`);
    
    // Verificar que el selector existe
    if (unitSelect.length === 0) {
        console.warn(`loadEditProductUnits: No se encontró el selector de unidades para la fila ${rowIndex}`);
        return;
    }

    // Mostrar indicador de carga
    unitSelect.prop('disabled', true).html('<option>Cargando unidades...</option>');

    $.ajax({
        url: `/sale/getproductbyid/${productId}`,
        method: 'GET',
        success: function(response) {
            // Limpiar opciones existentes
            unitSelect.empty().append('<option value="">Seleccionar unidad...</option>');
            
            if (response.success && response.data && response.data.units && response.data.units.length > 0) {
                let units = response.data.units;
                const product = response.data.product || {};

                // En COMPRAS: solo unidades que aplican a farmacia
                if (product.pastillas_per_blister || product.blisters_per_caja) {
                    units = units.filter(u => u.unit_code === 'CAJA');
                } else {
                    units = units.filter(u => u.unit_code === '59');
                    if (units.length === 0) {
                        units = [{ unit_code: '59', unit_id: (response.data.units.find(u => u.unit_code === '59') || {}).unit_id || '', unit_name: 'Unidad', conversion_factor: 1 }];
                    }
                }

                if (units.length > 0) {
                    units.forEach(unit => {
                        const unitName = getUnitDisplayName(unit.unit_code, unit.unit_name);
                        unitSelect.append(`<option value="${unit.unit_code}" data-id="${unit.unit_id || ''}" data-factor="${unit.conversion_factor || 1}">${unitName}</option>`);
                    });
                    if (selectedUnitCode) {
                        unitSelect.val(selectedUnitCode).trigger('change');
                    } else if (selectedUnitId) {
                        const option = unitSelect.find(`option[data-id="${selectedUnitId}"]`);
                        if (option.length) unitSelect.val(option.val()).trigger('change');
                    } else {
                        const defaultUnit = units.find(u => u.unit_code === 'CAJA') || units.find(u => u.unit_code === '59') || units.find(u => u.is_default) || units[0];
                        if (defaultUnit) unitSelect.val(defaultUnit.unit_code).trigger('change');
                    }
                    const selectedOption = unitSelect.find('option:selected');
                    if (selectedOption.length && (selectedUnitCode || selectedUnitId)) {
                        const row = $(`#editProductRow_${rowIndex}`);
                        row.find('.selected-unit-id').val(selectedOption.data('id') || selectedUnitId);
                        row.find('.conversion-factor').val(selectedOption.data('factor') || conversionFactor);
                    }
                } else {
                    unitSelect.append(`<option value="59" data-id="" data-factor="1">Unidad</option>`).val('59').trigger('change');
                }
            } else {
                unitSelect.append(`<option value="59" data-id="" data-factor="1">Unidad</option>`).val('59').trigger('change');
            }
            
            unitSelect.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error(`Error cargando unidades para producto ${productId}:`, error);
            // En caso de error, agregar "Unidad" por defecto
            unitSelect.empty()
                .append('<option value="">Seleccionar unidad...</option>')
                .append(`<option value="59" data-id="" data-factor="1">Unidad</option>`)
                .prop('disabled', false);
        }
    });
}

/**
 * Obtener nombre de visualización para unidad
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

function removeProductRow(rowIndex) {

    $(`#productRow_${rowIndex}`).remove();
    delete selectedProducts[rowIndex];
    calculateAllTotals();

    // Asegurar que siempre haya una fila vacía
    setTimeout(() => {
        if ($('#productsTableBody tr').length === 0) {
            addEmptyProductRow();
        }
    }, 100);
}

function showProductModal(rowIndex) {
    currentRowIndex = rowIndex;
    $('#productModal').modal('show');
}

// ========================================
// GENERACIÓN DE HTML
// ========================================

function generateEmptyRowHtml(index, isIndexView) {
    if (isIndexView) {
        return `
            <tr id="productRow_${index}">
                <td>
                    <input type="text" class="form-control product-name" readonly
                           placeholder="Haga clic para seleccionar producto"
                           onclick="showProductModal(${index})">
                    <input type="hidden" class="product-id" value="">
                </td>
                <td>
                    <select class="form-select unit-select" onchange="updateSelectedProduct(${index})">
                        <option value="">Seleccionar unidad...</option>
                    </select>
                    <input type="hidden" class="selected-unit-id" value="">
                    <input type="hidden" class="conversion-factor" value="1">
                </td>
                <td>
                    <input type="number" class="form-control quantity" min="1" value="1"
                           onchange="calculateRowTotal(${index})" placeholder="Cantidad">
                </td>
                <td>
                    <input type="number" class="form-control unit-price" min="0" step="0.00001" value="0.00000"
                           onchange="calculateRowTotal(${index})" placeholder="Costo de compra" style="width: 140px;">
                </td>
                <td><span class="subtotal">$0.0000</span></td>
                <td>
                    <input type="date" class="form-control expiration-date"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${index})">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    } else {
        return `
            <tr id="productRow_${index}">
                <td>
                    <input type="text" class="form-control product-name" readonly
                           placeholder="Haga clic para seleccionar producto"
                           onclick="showProductModal(${index})">
                    <input type="hidden" class="product-id" value="">
                </td>
                <td>
                    <select class="form-select unit-select" onchange="updateSelectedProduct(${index})">
                        <option value="">Seleccionar unidad...</option>
                    </select>
                    <input type="hidden" class="selected-unit-id" value="">
                    <input type="hidden" class="conversion-factor" value="1">
                </td>
                <td>
                    <input type="number" class="form-control quantity" min="1" value="1"
                           onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control unit-price" min="0" step="0.00001" value="0.00000"
                           onchange="calculateRowTotal(${index})">
                </td>
                <td><span class="subtotal">$0.0000</span></td>
                <td><span class="iva">$0.0000</span></td>
                <td><span class="total">$0.0000</span></td>
                <td>
                    <input type="date" class="form-control expiration-date"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <input type="text" class="form-control batch-number"
                           onchange="updateSelectedProduct(${index})">
                </td>
            <td>
                    <input type="text" class="form-control notes"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${index})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }
}

function generateProductRowHtml(index, product, isIndexView) {
    if (isIndexView) {
        return `
            <tr id="productRow_${index}">
                <td>
                    <input type="text" class="form-control product-name" readonly
                           value="${product.name}"
                           onclick="showProductModal(${index})">
                    <input type="hidden" class="product-id" value="${product.id}">
                </td>
                <td>
                    <select class="form-select unit-select" onchange="updateSelectedProduct(${index})">
                        <option value="">Seleccionar unidad...</option>
                    </select>
                    <input type="hidden" class="selected-unit-id" value="">
                    <input type="hidden" class="conversion-factor" value="1">
                </td>
                <td>
                    <input type="number" class="form-control quantity" min="1" value="1"
                           onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control unit-price" min="0" step="0.00001"
                           value="${product.price || 0}"
                           onchange="calculateRowTotal(${index})" style="width: 140px;">
                </td>
                <td><span class="subtotal">$0.0000</span></td>
                <td>
                    <input type="date" class="form-control expiration-date"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${index})">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    } else {
        return `
            <tr id="productRow_${index}">
                <td>
                    <input type="text" class="form-control product-name" readonly
                           value="${product.name}"
                           onclick="showProductModal(${index})">
                    <input type="hidden" class="product-id" value="${product.id}">
                </td>
                <td>
                    <select class="form-select unit-select" onchange="updateSelectedProduct(${index})">
                        <option value="">Seleccionar unidad...</option>
                    </select>
                    <input type="hidden" class="selected-unit-id" value="">
                    <input type="hidden" class="conversion-factor" value="1">
                </td>
                <td>
                    <input type="number" class="form-control quantity" min="1" value="1"
                           onchange="calculateRowTotal(${index})">
                </td>
                <td>
                    <input type="number" class="form-control unit-price" min="0" step="0.00001"
                           value="${product.price || 0}"
                           onchange="calculateRowTotal(${index})">
                </td>
                <td><span class="subtotal">$0.0000</span></td>
                <td><span class="iva">$0.0000</span></td>
                <td><span class="total">$0.0000</span></td>
                <td>
                    <input type="date" class="form-control expiration-date"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <input type="text" class="form-control batch-number"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <input type="text" class="form-control notes"
                           onchange="updateSelectedProduct(${index})">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${index})">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }
}

// ========================================
// CÁLCULOS Y TOTALES
// ========================================

function calculateRowTotal(rowIndex) {

    const row = $(`#productRow_${rowIndex}`);
    const quantity = parseInt(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;


    const subtotal = quantity * unitPrice;
    const iva = subtotal * 0.13;
    const total = subtotal + iva;

    // Actualizar displays con 5 decimales
    row.find('.subtotal').text(`$${subtotal.toFixed(5)}`);

    if (row.find('.iva').length) {
        row.find('.iva').text(`$${iva.toFixed(5)}`);
    }

    if (row.find('.total').length) {
        row.find('.total').text(`$${total.toFixed(5)}`);
    }

    // Actualizar solo quantity y unit_price en selectedProducts (evita ciclo con updateSelectedProduct)
    if (selectedProducts[rowIndex]) {
        selectedProducts[rowIndex].quantity = quantity;
        selectedProducts[rowIndex].unit_price = unitPrice;
    }

    // Calcular totales generales
    calculateAllTotals();

}

function updateSelectedProduct(rowIndex) {

    const row = $(`#productRow_${rowIndex}`);

    const productId = parseInt(row.find('.product-id').val());
    if (!productId) {
        return; // Fila vacía
    }

    const quantity = parseInt(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
    const expirationDate = row.find('.expiration-date').val() || null;
    const notes = row.find('.notes').val() || null;

    // Obtener información de unidad seleccionada
    const unitSelect = row.find('.unit-select');
    const selectedUnitCode = unitSelect.val();
    const selectedOption = unitSelect.find('option:selected');
    const unitId = selectedOption.data('id') || null;
    const conversionFactor = parseFloat(selectedOption.data('factor')) || 1;

    // Actualizar campos ocultos
    row.find('.selected-unit-id').val(unitId);
    row.find('.conversion-factor').val(conversionFactor);

    // Mostrar preview de conversión para productos farmacéuticos
    let conversionPreview = row.find('.conversion-preview');
    if (conversionPreview.length === 0) {
        // Crear elemento de preview si no existe
        conversionPreview = $('<small class="conversion-preview text-muted d-block mt-1"></small>');
        unitSelect.after(conversionPreview);
    }

    // Si es una unidad farmacéutica y hay cantidad, mostrar conversión
    if (selectedUnitCode && (selectedUnitCode === 'BLISTER' || selectedUnitCode === 'CAJA') && quantity > 0) {
        const baseQuantity = quantity * conversionFactor;
        conversionPreview.text(`≈ ${baseQuantity.toLocaleString()} pastillas`).show();
    } else if (selectedUnitCode === 'PASTILLA' && quantity > 0) {
        conversionPreview.text(`${quantity.toLocaleString()} pastillas`).show();
    } else {
        conversionPreview.hide();
    }

    selectedProducts[rowIndex] = {
        product_id: productId,
        quantity: quantity,
        unit_price: unitPrice,
        unit_code: selectedUnitCode,
        unit_id: unitId,
        conversion_factor: conversionFactor,
        expiration_date: expirationDate,
        notes: notes
    };

    // Recalcular total de la fila cuando cambia la unidad o cantidad
    calculateRowTotal(rowIndex);

}

function calculateAllTotals() {
    let subtotal = 0;
    let totalIva = 0;
    let totalAmount = 0;

    Object.values(selectedProducts).forEach(product => {
        if (product && product.product_id) {
        const rowSubtotal = product.quantity * product.unit_price;
        const rowIva = rowSubtotal * 0.13;

        subtotal += rowSubtotal;
        totalIva += rowIva;
            totalAmount += rowSubtotal + rowIva;
        }
    });

    const isIndexView = isIndexViewStructure();

    if (isIndexView) {
        // Para modal "Ingresar compra" - actualizar campos con 5 decimales
        $('#gravada').val(subtotal.toFixed(5));
        $('#iva').val(totalIva.toFixed(5));
        calculateTotals(); // Función existente para calcular total general
    } else {
        // Para vista create - actualizar displays con 5 decimales
        $('#subtotal').text(`$${subtotal.toFixed(5)}`);
        $('#totalIva').text(`$${totalIva.toFixed(5)}`);
        $('#totalAmount').text(`$${totalAmount.toFixed(5)}`);
    }

}

function calculateTotals() {
    // Delegar al archivo forms-purchase.js
    if (typeof suma === 'function') {
        suma();
    }
}

// Función global para el botón "Calcular Totales"
function calculateTotalsFromProducts() {

    // Primero calcular totales desde productos
    calculateAllTotals();

    // Luego recalcular el total final considerando que el IVA Retenido se resta
    // Esto asegura que el botón "Calcular Totales" sea el único que maneje correctamente el IVA Retenido
    if (typeof suma === 'function') {
        suma();
    }

}

// ========================================
// GESTIÓN DEL FORMULARIO
// ========================================

function resetPurchaseForm() {

    // Limpiar tabla de productos
    $('#productsTableBody').empty();

    // Reset variables
    selectedProducts = [];
    productRowIndex = 0;
    currentRowIndex = undefined;

    // Limpiar campos del formulario
    $('#addpurchaseForm')[0].reset();

    // Reset campos de totales
    $('#exenta, #gravada, #iva, #contrans, #fovial, #iretenido, #others, #total').val('0.00');

    // Reset Select2 si existen
    $('.select2purchase, .select2company, .select2provider').val(null).trigger('change');

}

function initializePurchaseForm() {

    // Asegurar que las variables estén limpias
    selectedProducts = [];
    productRowIndex = 0;
    currentRowIndex = undefined;

    // No crear fila inicial - el usuario debe usar el botón "Agregar Producto"

}

// ========================================
// UTILIDADES
// ========================================

function formatDateForInput(dateString) {
    if (!dateString) return '';

    try {
        // Si la fecha ya viene en formato Y-m-d, usarla directamente
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            return dateString;
        }

        // Si viene como objeto Date o string ISO, convertir
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        // Usar la fecha local en lugar de UTC para evitar problemas de zona horaria
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        const result = `${year}-${month}-${day}`; // Formato YYYY-MM-DD
        return result;
    } catch (error) {
        return '';
    }
}

function formatDateForDisplay(dateString) {
    if (!dateString) return 'N/A';

    try {
        // Si la fecha ya viene en formato Y-m-d, usarla directamente
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            const [year, month, day] = dateString.split('-');
            return `${day}/${month}/${year}`;
        }

        // Si viene como objeto Date o string ISO, convertir
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';

        // Usar fecha local para evitar problemas de zona horaria
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}/${month}/${year}`; // Formato DD/MM/YYYY
    } catch (error) {
        return 'N/A';
    }
}

function isIndexViewStructure() {
    const columnCount = $('#productsTable thead tr th').length;
    return columnCount === 7; // 7 columnas = index.blade.php (modal compra sin lote), 10 = create.blade.php (página completa)
}

function showError(message) {
    Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'Ok'
    });
}

function showSuccess(message) {
    Swal.fire({
        title: 'Producto Agregado',
        text: message,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

// ========================================
// MANEJO DE FORMULARIOS
// ========================================

function handleFormSubmit(e) {
    e.preventDefault();

    // Validar tipo de documento
    const documentValue = $('#document').val();
    if (!documentValue || documentValue === '' || documentValue === 'Elije una opcion') {
        showError('Debe seleccionar un tipo de documento.');
        $('#document').focus();
        return;
    }

    // Verificar que hay productos
    const validProducts = Object.values(selectedProducts).filter(p => p && p.product_id);
    if (validProducts.length === 0) {
        showError('Debe agregar al menos un producto. Haga click en "Agregar Producto" para comenzar.');
        return;
    }

    submitForm();
}

function handleCreateFormSubmit(e) {
    e.preventDefault();

    // Validar tipo de documento
    const documentValue = $('#document').val();
    if (!documentValue || documentValue === '' || documentValue === 'Elije una opcion') {
        showError('Debe seleccionar un tipo de documento.');
        $('#document').focus();
        return;
    }

    const validProducts = Object.values(selectedProducts).filter(p => p && p.product_id);
    if (validProducts.length === 0) {
        showError('Debe agregar al menos un producto. Haga click en "Agregar Producto" para comenzar.');
        return;
    }

    // Preparar datos
    const details = validProducts.map(product => ({
        product_id: product.product_id,
        quantity: parseInt(product.quantity),
        unit_price: parseFloat(product.unit_price),
        unit_code: product.unit_code || '',
        unit_id: product.unit_id || '',
        conversion_factor: product.conversion_factor || 1.0000,
        expiration_date: product.expiration_date || null,
        batch_number: '', // Se genera automáticamente en el backend
        notes: product.notes || null
    }));

    $('#detailsInput').val(JSON.stringify(details));
    submitForm();
}

function handleUpdateFormSubmit(e) {
    e.preventDefault();

    // Obtener datos del formulario
    const formData = new FormData($('#updatepurchaseForm')[0]);

    // Agregar productos editados si existen
    if (Object.keys(editSelectedProducts).length > 0) {
        const validEditProducts = Object.values(editSelectedProducts).filter(p => p && p.product_id);


        validEditProducts.forEach((product, index) => {
            formData.append(`edit_details[${index}][product_id]`, product.product_id);
            formData.append(`edit_details[${index}][quantity]`, product.quantity);
            formData.append(`edit_details[${index}][unit_price]`, product.unit_price);
            formData.append(`edit_details[${index}][unit_code]`, product.unit_code || '');
            formData.append(`edit_details[${index}][unit_id]`, product.unit_id || '');
            formData.append(`edit_details[${index}][conversion_factor]`, product.conversion_factor || '1.0000');
            formData.append(`edit_details[${index}][expiration_date]`, product.expiration_date || '');
            formData.append(`edit_details[${index}][batch_number]`, product.batch_number || '');
            formData.append(`edit_details[${index}][notes]`, product.notes || '');


            // Log específico para fecha de expiración
            if (product.expiration_date) {
            }
        });

    }

    // Debug: Mostrar datos que se van a enviar
    for (let pair of formData.entries()) {
    }

    // Enviar con AJAX
    $.ajax({
        url: $('#updatepurchaseForm').attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Marcar como AJAX
        },
        success: function(response) {

            if (response.success) {
                // Cerrar modal
                $('#updatePurchaseModal').modal('hide');

                // Mostrar mensaje de éxito
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.message || 'Compra actualizada correctamente',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                }).then(() => {
                    // Recargar la página para actualizar la lista
                    location.reload();
                });

            } else {
                showError(response.message || 'Error al actualizar la compra');
            }
        },
        error: function(xhr) {

            let message = 'Error al actualizar la compra';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    message = errorData.message || message;
                } catch (e) {
                    // Si no es JSON válido, usar mensaje por defecto
                }
            }

            showError(message);
        }
    });
}

function submitForm() {
    const formData = new FormData($('#addpurchaseForm')[0] || $('#purchaseForm')[0]);

    // Debug: Verificar productos antes de enviar

    const validProducts = Object.values(selectedProducts).filter(p => p && p.product_id);

    // Agregar productos si es formulario simple
    if ($('#addpurchaseForm').length) {
        let productIndex = 0;
        Object.values(selectedProducts).forEach((product) => {
        if (product && product.product_id) {

                formData.append(`details[${productIndex}][product_id]`, product.product_id);
                formData.append(`details[${productIndex}][quantity]`, product.quantity);
                formData.append(`details[${productIndex}][unit_price]`, product.unit_price);
                formData.append(`details[${productIndex}][unit_code]`, product.unit_code || '');
                formData.append(`details[${productIndex}][unit_id]`, product.unit_id || '');
                formData.append(`details[${productIndex}][conversion_factor]`, product.conversion_factor || '1.0000');
                formData.append(`details[${productIndex}][expiration_date]`, product.expiration_date || '');
                formData.append(`details[${productIndex}][batch_number]`, ''); // Se genera automáticamente en el backend
                formData.append(`details[${productIndex}][notes]`, product.notes || '');

                productIndex++;
            }
        });

    }

    // Debug: Verificar FormData
    for (let pair of formData.entries()) {
    }

    const form = $('#addpurchaseForm').length ? $('#addpurchaseForm') : $('#purchaseForm');

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Cerrar modal antes de mostrar confirmación
                $('#addPurchaseModal').modal('hide');

                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Compra creada correctamente',
                    icon: 'success',
                    confirmButtonText: 'Ok'
                }).then(() => {
                    // Recargar la página para actualizar la lista
                        location.reload();
                });

            } else {
                showError(response.message || 'Error al crear la compra');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error al crear la compra';

            // No cerrar el modal en caso de error para que el usuario pueda corregir
            showError(message);
        }
    });
}

// ========================================
// FUNCIONES PARA EDICIÓN (COMPATIBILIDAD)
// ========================================

function addEditProductRow(product = null) {
    if (!product) {
        showEditProductModal(editProductRowIndex);
        return;
    }

    const row = `
        <tr id="editProductRow_${editProductRowIndex}">
            <td>
                <input type="text" class="form-control product-name" readonly
                       value="${product.name}"
                       onclick="showEditProductModal(${editProductRowIndex})">
                <input type="hidden" class="product-id" value="${product.id}">
            </td>
            <td>
                <select class="form-select unit-select" onchange="updateEditSelectedProduct(${editProductRowIndex})">
                    <option value="">Seleccionar unidad...</option>
                </select>
                <input type="hidden" class="selected-unit-id" value="">
                <input type="hidden" class="conversion-factor" value="1">
            </td>
            <td>
                <input type="number" class="form-control quantity" min="1" value="1"
                       onchange="calculateEditRowTotal(${editProductRowIndex})">
            </td>
            <td>
                <input type="number" class="form-control unit-price" min="0" step="0.00001"
                       value="${parseFloat(product.price || 0).toFixed(5)}"
                       onchange="calculateEditRowTotal(${editProductRowIndex})" onblur="formatUnitPrice(${editProductRowIndex})">
            </td>
            <td><span class="subtotal">$0.0000</span></td>
            <td>
                <input type="date" class="form-control expiration-date"
                       onchange="updateEditSelectedProduct(${editProductRowIndex})">
            </td>
            <td>
                <input type="text" class="form-control batch-number"
                       onchange="updateEditSelectedProduct(${editProductRowIndex})">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeEditProductRow(${editProductRowIndex})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#editProductsTableBody').append(row);

    // Actualizar producto seleccionado inmediatamente
    if (product) {
        // Cargar unidades del producto (sin unidad preseleccionada para nuevos productos)
        loadEditProductUnits(editProductRowIndex, product.id);
        // No actualizar inmediatamente, se hará cuando se seleccione una unidad
    }

    editProductRowIndex++;
}

function showEditProductModal(rowIndex) {
    currentRowIndex = rowIndex;
    $('#productModal').modal('show');
}

function calculateEditRowTotal(rowIndex) {
    const row = $(`#editProductRow_${rowIndex}`);
    const quantity = parseInt(row.find('.quantity').val()) || 0;
    const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
    const subtotal = quantity * unitPrice;

    row.find('.subtotal').text(`$${subtotal.toFixed(5)}`);
    updateEditSelectedProduct(rowIndex);
    calculateEditTotalsFromProducts();
}

function formatUnitPrice(rowIndex) {
    const row = $(`#editProductRow_${rowIndex}`);
    const unitPriceField = row.find('.unit-price');
    const currentValue = parseFloat(unitPriceField.val()) || 0;

    // Formatear a 5 decimales
    unitPriceField.val(currentValue.toFixed(5));

    // Recalcular totales
    calculateEditRowTotal(rowIndex);
}

function updateEditSelectedProduct(rowIndex) {
    const row = $(`#editProductRow_${rowIndex}`);
    const expirationDate = row.find('.expiration-date').val() || null;

    // Obtener información de unidad seleccionada
    const unitSelect = row.find('.unit-select');
    const selectedUnitCode = unitSelect.val() || null;
    const selectedOption = unitSelect.find('option:selected');
    const unitId = selectedOption.data('id') || null;
    const conversionFactor = parseFloat(selectedOption.data('factor')) || 1;

    // Actualizar campos ocultos
    row.find('.selected-unit-id').val(unitId);
    row.find('.conversion-factor').val(conversionFactor);

    editSelectedProducts[rowIndex] = {
        product_id: parseInt(row.find('.product-id').val()),
        quantity: parseInt(row.find('.quantity').val()) || 0,
        unit_price: parseFloat(row.find('.unit-price').val()) || 0,
        unit_code: selectedUnitCode,
        unit_id: unitId,
        conversion_factor: conversionFactor,
        expiration_date: expirationDate,
        batch_number: row.find('.batch-number').val() || null,
        notes: null
    };


    // Log específico para fecha de expiración
    if (expirationDate) {
    }
}

function removeEditProductRow(rowIndex) {
    $(`#editProductRow_${rowIndex}`).remove();
    delete editSelectedProducts[rowIndex];
    calculateEditTotalsFromProducts();
}

function calculateEditTotalsFromProducts() {

    let subtotal = 0;
    let totalIva = 0;

    Object.values(editSelectedProducts).forEach(product => {
        if (product && product.product_id) {
        const rowSubtotal = product.quantity * product.unit_price;
        subtotal += rowSubtotal;
            totalIva += rowSubtotal * 0.13;
        }
    });


    // Actualizar campos automáticamente con 5 decimales
        $('#gravadaedit').val(subtotal.toFixed(5));
        $('#ivaedit').val(totalIva.toFixed(5));

    // Recalcular total general - esto también manejará correctamente el IVA Retenido
    calculateEditTotals();

}

function calculateEditTotals() {
    // Delegar al archivo forms-purchase.js
    if (typeof sumaedit === 'function') {
        sumaedit();
    }
}

// ========================================
// FUNCIONES AUXILIARES (COMPATIBILIDAD)
// ========================================

function loadPurchaseDetails(purchaseId) {
    const encodedId = btoa(purchaseId);
    $.get(`/purchase/details/${encodedId}`, function(response) {
        if (response.success) {

            // Limpiar datos anteriores
            editSelectedProducts = [];
            editProductRowIndex = 0;
            $('#editProductsTableBody').empty();

            // Formatear fecha del comprobante
            const formattedDate = formatDateForInput(response.purchase.date);
            $('#dateedit').val(formattedDate);

            // Cargar proveedor y empresa con Select2
            if (response.purchase.provider_id) {
                $('#provideredit').val(response.purchase.provider_id).trigger('change');
            }
            if (response.purchase.company_id) {
                $('#companyedit').val(response.purchase.company_id).trigger('change');
            }
            if (response.purchase.document_id) {
                $('#documentedit').val(response.purchase.document_id).trigger('change');
            }
            if (response.purchase.periodo) {
                $('#periodedit').val(response.purchase.periodo).trigger('change');
            }

            // Cargar otros campos del formulario con 5 decimales
            $('#numberedit').val(response.purchase.number || '');
            $('#exentaedit').val(parseFloat(response.purchase.exenta || 0).toFixed(5));
            $('#gravadaedit').val(parseFloat(response.purchase.gravada || 0).toFixed(5));
            $('#ivaedit').val(parseFloat(response.purchase.iva || 0).toFixed(5));
            $('#contransedit').val(parseFloat(response.purchase.contrns || 0).toFixed(5));
            $('#fovialedit').val(parseFloat(response.purchase.fovial || 0).toFixed(5));
            $('#iretenidoedit').val(parseFloat(response.purchase.iretenido || 0).toFixed(5));
            $('#othersedit').val(parseFloat(response.purchase.otros || 0).toFixed(5));
            $('#totaledit').val(parseFloat(response.purchase.total || 0).toFixed(5));

            // Cargar productos
            response.details.forEach(detail => {
                const product = {
                    id: detail.product_id,
                    name: detail.product.name,
                    price: detail.unit_price
                };

                addEditProductRow(product);

                const row = $(`#editProductRow_${editProductRowIndex - 1}`);
                row.find('.quantity').val(detail.quantity);
                const unitPriceValue = parseFloat(detail.unit_price || 0).toFixed(5);
                row.find('.unit-price').val(unitPriceValue);

                // Cargar unidades y seleccionar la correcta
                const currentRowIndex = editProductRowIndex - 1;
                loadEditProductUnits(
                    currentRowIndex, 
                    detail.product_id,
                    detail.unit_code || null,
                    detail.unit_id || null,
                    detail.conversion_factor || 1
                );

                // Formatear fecha de expiración
                const formattedExpDate = formatDateForInput(detail.expiration_date);
                row.find('.expiration-date').val(formattedExpDate);

                row.find('.batch-number').val(detail.batch_number);

                // Actualizar el array editSelectedProducts con los valores cargados
                // Esperar a que se carguen las unidades antes de actualizar
                setTimeout(() => {
                    updateEditSelectedProduct(currentRowIndex);
                    calculateEditRowTotal(currentRowIndex);
                }, 800); // Esperar a que se carguen y seleccionen las unidades
            });

        } else {
            showError('Error al cargar los detalles de la compra: ' + response.message);
        }
    }).fail(function(xhr, status, error) {
        showError('Error al cargar los detalles de la compra');
    });
}

function viewPurchaseDetails(purchaseId) {
    const encodedId = btoa(purchaseId);
    $.get(`/purchase/details/${encodedId}`, function(response) {
        if (response.success) {
            $('#viewNumber').text(response.purchase.number || 'N/A');
            $('#viewDate').text(formatDateForDisplay(response.purchase.date));
            $('#viewDocumentType').text(response.purchase.document_type_name || 'N/A');
            $('#viewProvider').text(response.purchase.provider ? response.purchase.provider.razonsocial : 'N/A');
            $('#viewCompany').text(response.purchase.company ? response.purchase.company.name : 'N/A');
            const paymentTypeLabel = response.purchase.payment_type === 'credito' ? 'Crédito' : 'Contado';
            $('#viewPaymentType').text(paymentTypeLabel);
            if (response.purchase.payment_type === 'credito') {
                $('#viewCreditDaysRow').show();
                const days = response.purchase.credit_days ? response.purchase.credit_days + ' días' : 'N/A';
                $('#viewCreditDays').text(days);
            } else {
                $('#viewCreditDaysRow').hide();
                $('#viewCreditDays').text('-');
            }
            $('#viewExenta').text('$' + (response.purchase.exenta || '0.00'));
            $('#viewGravada').text('$' + (response.purchase.gravada || '0.00'));
            $('#viewIva').text('$' + (response.purchase.iva || '0.00'));
            $('#viewIretenido').text('$' + (response.purchase.iretenido || '0.00'));
            $('#viewTotal').text('$' + (response.purchase.total || '0.00'));
            $('#viewPaymentDueDate').text(response.purchase.payment_due_date ? formatDateForDisplay(response.purchase.payment_due_date) : 'N/A');

            const tbody = $('#viewProductsTableBody');
            tbody.empty();

            if (response.details && response.details.length > 0) {
                response.details.forEach(detail => {
                    const subtotal = detail.quantity * detail.unit_price;
                    // Obtener el nombre de la unidad de medida
                    const unitName = detail.unit ? detail.unit.unit_name : (detail.unit_code || 'N/A');
                    const row = `
                        <tr>
                            <td>
                                <strong>${detail.product ? detail.product.name : 'N/A'}</strong>
                                <br><small class="text-muted">${detail.product ? detail.product.code : 'N/A'}</small>
                            </td>
                            <td>${detail.quantity}</td>
                            <td>${unitName}</td>
                            <td>$${detail.unit_price}</td>
                            <td>$${subtotal.toFixed(5)}</td>
                            <td>${formatDateForDisplay(detail.expiration_date)}</td>
                            <td>${detail.batch_number || 'N/A'}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr><td colspan="7" class="text-center">No hay productos en esta compra</td></tr>');
            }

            $('#viewPurchaseModal').modal('show');
        } else {
            alert('Error al cargar los detalles de la compra: ' + (response.message || 'Error desconocido'));
        }
    }).fail(function(xhr, status, error) {
        alert('Error al cargar los detalles de la compra. Revisa la consola para más información.');
    });
}

// ========================================
// FUNCIONES PARA VISTAS ESPECÍFICAS
// ========================================

function loadProviders() {

    $.ajax({
        url: '/provider/getproviders',
        method: 'GET',
        success: function(response) {

            const select = $('#providerFilter');
            if (select.length) {
                select.empty();
                select.append('<option value="">Todos</option>');

                // La respuesta es un array directo
                if (Array.isArray(response)) {
                    response.forEach(provider => {
                        select.append(`<option value="${provider.id}">${provider.razonsocial}</option>`);
                    });
                }
            }
        },
        error: function(xhr, status, error) {
        }
    });
}

// Funciones legacy
function retomarsale(corr, document) {
    window.location.href = "create?corr=" + corr + "&draft=true&typedocument=" + document + "&operation=delete";
}

function printsale(corr) {
    window.open('impdoc/' + corr, '_blank');
}

function checkExpiredProducts() {

    $.get('/purchase/expired-products', function(response) {

        if (response.success && response.data.length > 0) {
            Swal.fire({
                title: 'Productos Vencidos',
                text: `Hay ${response.data.length} productos vencidos en el inventario`,
                icon: 'warning',
                confirmButtonText: 'Ver Detalles',
                showCancelButton: true,
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Navegar en la misma pestaña
                    window.location.href = '/purchase/expiring-products-view';
                }
            });
        } else {
            Swal.fire({
                title: 'Sin Productos Vencidos',
                text: 'No hay productos vencidos en el inventario',
                icon: 'success',
                confirmButtonText: 'Ok'
            });
        }
    }).fail(function(xhr, status, error) {

        Swal.fire({
            title: 'Error',
            text: 'Error al verificar productos vencidos. Revisa la consola para más información.',
            icon: 'error',
            confirmButtonText: 'Ok'
        });
    });
}

// Nueva función para productos vencidos en la misma página
function showExpiredProductsInSamePage() {
    window.location.href = '/purchase/expiring-products-view';
}

// Función de test para debug
function testDebug() {

    // Test 1: Verificar productos

    // Test 2: Verificar elementos DOM

    // Test 3: Verificar rutas

    $.get('/purchase/products', function(response) {
    }).fail(function() {
    });

    $.get('/purchase/expiring-products', function(response) {
    }).fail(function() {
    });

        // Test 4: Simular agregar producto
    if (products.length > 0) {
        const testProduct = products[0];

        // Simular selección
        selectedProducts[999] = {
            product_id: testProduct.id,
            quantity: 5,
            unit_price: parseFloat(testProduct.price || 0),
            expiration_date: null,
            batch_number: null,
            notes: 'Test'
        };

        delete selectedProducts[999]; // Limpiar
    }

    // Test 5: Verificar actualizaciones de cantidad
    const existingRows = $('#productsTableBody tr');
    if (existingRows.length > 0) {
        const firstRow = existingRows.first();
        const rowId = firstRow.attr('id');
        if (rowId) {
            const rowIndex = rowId.replace('productRow_', '');

            // Simular cambio de cantidad
            const quantityInput = firstRow.find('.quantity');
            if (quantityInput.length) {
                const originalQuantity = quantityInput.val();

                quantityInput.val(10);
                quantityInput.trigger('change');

                setTimeout(() => {
                }, 100);
            } else {
            }
        }
    } else {
    }

    Swal.fire({
        title: 'Test Completado',
        text: 'Revisa la consola para ver los resultados del test',
        icon: 'info',
        confirmButtonText: 'Ok'
    });
}

// Función específica para test de cantidades
function testQuantityUpdate() {

    // Verificar si hay productos en la tabla
    const existingRows = $('#productsTableBody tr');

    if (existingRows.length === 0) {
        Swal.fire({
            title: 'Sin productos',
            text: 'Agrega un producto primero para probar la actualización de cantidades',
            icon: 'warning',
            confirmButtonText: 'Ok'
        });
        return;
    }

    // Probar en cada fila existente
    existingRows.each(function(index) {
        const row = $(this);
        const rowId = row.attr('id');

        if (rowId) {
            const rowIndex = rowId.replace('productRow_', '');

            // Obtener elementos
            const productId = row.find('.product-id').val();
            const quantityInput = row.find('.quantity');
            const priceInput = row.find('.unit-price');


            // Estado anterior

            // Cambiar cantidad
            const newQuantity = 15 + index; // Diferente para cada fila
            quantityInput.val(newQuantity);

            // Disparar evento change manualmente
            quantityInput.trigger('change');

            // Verificar después del cambio
            setTimeout(() => {
            }, 50);
        }
    });

    // Mostrar resumen después de un momento
    setTimeout(() => {

        Swal.fire({
            title: 'Test de Cantidades Completado',
            text: 'Revisa la consola para ver los detalles del test',
            icon: 'success',
            confirmButtonText: 'Ok'
        });
    }, 200);
}

// Las funciones de reporte de utilidades se moverán al módulo de reportes

// Función de test para edición
function testEditSubmit() {

    const form = $('#updatepurchaseForm');
    if (form.length === 0) {
        return;
    }


    // Verificar campos principales
    const fields = ['idedit', 'numberedit', 'dateedit', 'provideredit', 'companyedit'];
    fields.forEach(field => {
        const element = $(`#${field}`);
    });

    // Verificar totales
    const totals = ['exentaedit', 'gravadaedit', 'ivaedit', 'totaledit'];
    totals.forEach(total => {
        const element = $(`#${total}`);
    });

    // Verificar productos editados

    Swal.fire({
        title: 'Test de Edición Completado',
        text: 'Revisa la consola para ver los detalles',
        icon: 'info',
        confirmButtonText: 'Ok'
    });
}
