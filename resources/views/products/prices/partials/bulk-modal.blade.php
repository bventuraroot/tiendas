<!-- Modal para Precios Masivos -->
<div class="modal fade" id="bulkPriceModal" tabindex="-1" aria-labelledby="bulkPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkPriceModalLabel">
                    <i class="fas fa-layer-group me-2"></i>
                    Agregar Precios Masivos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="bulkPriceForm" onsubmit="event.preventDefault(); createBulkPrices();">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-1"></i>
                            Instrucciones
                        </h6>
                        <p class="mb-0">
                            Complete los precios para las unidades que desee configurar.
                            Puede marcar uno como precio por defecto.
                        </p>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Unidad</th>
                                            <th>Precio Regular</th>
                                            <th>Precio de Costo</th>
                                            <th>Precio al Por Mayor</th>
                                            <th>Precio al Detalle</th>
                                            <th>Precio Especial</th>
                                            <th>Por Defecto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($units as $unit)
                                            <tr>
                                                <td>
                                                    <strong>{{ $unit->unit_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $unit->unit_code }}</small>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="prices[{{ $loop->index }}][price]"
                                                               step="0.0001"
                                                               min="0"
                                                               placeholder="0.0000">
                                                    </div>
                                                    <input type="hidden" name="prices[{{ $loop->index }}][unit_id]" value="{{ $unit->id }}">
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="prices[{{ $loop->index }}][cost_price]"
                                                               step="0.0001"
                                                               min="0"
                                                               placeholder="0.0000">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="prices[{{ $loop->index }}][wholesale_price]"
                                                               step="0.0001"
                                                               min="0"
                                                               placeholder="0.0000">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="prices[{{ $loop->index }}][retail_price]"
                                                               step="0.0001"
                                                               min="0"
                                                               placeholder="0.0000">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number"
                                                               class="form-control"
                                                               name="prices[{{ $loop->index }}][special_price]"
                                                               step="0.0001"
                                                               min="0"
                                                               placeholder="0.0000">
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               name="default_price"
                                                               value="{{ $loop->index }}"
                                                               id="default_{{ $loop->index }}">
                                                        <label class="form-check-label" for="default_{{ $loop->index }}">
                                                            <i class="fas fa-star text-warning"></i>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Plantillas de Precios -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-muted">
                                <i class="fas fa-magic me-1"></i>
                                Plantillas de Precios
                            </h6>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyTemplate('weight')">
                                    <i class="fas fa-weight me-1"></i>
                                    Productos por Peso
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="applyTemplate('volume')">
                                    <i class="fas fa-tint me-1"></i>
                                    Productos por Volumen
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="applyTemplate('unit')">
                                    <i class="fas fa-cube me-1"></i>
                                    Productos por Unidad
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllPrices()">
                                    <i class="fas fa-eraser me-1"></i>
                                    Limpiar Todo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-success" id="summaryInfo" style="display: none;">
                                <h6 class="alert-heading">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Resumen de Precios
                                </h6>
                                <div id="summaryDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        Guardar Todos los Precios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Plantillas de precios
function applyTemplate(type) {
    const rows = document.querySelectorAll('#bulkPriceModal tbody tr');

    rows.forEach((row, index) => {
        const unitCode = row.querySelector('td:first-child small').textContent;
        const priceInput = row.querySelector('input[name*="[price]"]');
        const costInput = row.querySelector('input[name*="[cost_price]"]');
        const wholesaleInput = row.querySelector('input[name*="[wholesale_price]"]');
        const retailInput = row.querySelector('input[name*="[retail_price]"]');
        const specialInput = row.querySelector('input[name*="[special_price]"]');

        // Limpiar valores
        priceInput.value = '';
        costInput.value = '';
        wholesaleInput.value = '';
        retailInput.value = '';
        specialInput.value = '';

        // Aplicar plantilla según el tipo
        switch(type) {
            case 'weight':
                if (unitCode === '36') { // Libra
                    priceInput.value = '0.85';
                    costInput.value = '0.65';
                    wholesaleInput.value = '0.75';
                    retailInput.value = '0.95';
                } else if (unitCode === '59') { // Unidad (Saco)
                    priceInput.value = '55.00';
                    costInput.value = '45.00';
                    wholesaleInput.value = '50.00';
                    retailInput.value = '60.00';
                } else if (unitCode === '34') { // Kilogramo
                    priceInput.value = '1.87';
                    costInput.value = '1.43';
                    wholesaleInput.value = '1.65';
                    retailInput.value = '2.09';
                }
                break;

            case 'volume':
                if (unitCode === '23') { // Litro
                    priceInput.value = '2.50';
                    costInput.value = '1.80';
                    wholesaleInput.value = '2.20';
                    retailInput.value = '2.80';
                } else if (unitCode === '22') { // Galón
                    priceInput.value = '9.45';
                    costInput.value = '6.80';
                    wholesaleInput.value = '8.30';
                    retailInput.value = '10.60';
                } else if (unitCode === '26') { // Mililitro
                    priceInput.value = '0.0025';
                    costInput.value = '0.0018';
                    wholesaleInput.value = '0.0022';
                    retailInput.value = '0.0028';
                }
                break;

            case 'unit':
                if (unitCode === '59') { // Unidad
                    priceInput.value = '25.00';
                    costInput.value = '18.00';
                    wholesaleInput.value = '22.00';
                    retailInput.value = '28.00';
                } else if (unitCode === '58') { // Docena
                    priceInput.value = '300.00';
                    costInput.value = '216.00';
                    wholesaleInput.value = '264.00';
                    retailInput.value = '336.00';
                }
                break;
        }
    });

    updateSummary();
}

// Limpiar todos los precios
function clearAllPrices() {
    const inputs = document.querySelectorAll('#bulkPriceModal input[type="number"]');
    inputs.forEach(input => {
        input.value = '';
    });

    const radios = document.querySelectorAll('#bulkPriceModal input[type="radio"]');
    radios.forEach(radio => {
        radio.checked = false;
    });

    document.getElementById('summaryInfo').style.display = 'none';
}

// Actualizar resumen
function updateSummary() {
    const rows = document.querySelectorAll('#bulkPriceModal tbody tr');
    let totalPrices = 0;
    let totalCost = 0;
    let totalWholesale = 0;
    let totalRetail = 0;
    let totalSpecial = 0;

    rows.forEach(row => {
        const priceInput = row.querySelector('input[name*="[price]"]');
        const costInput = row.querySelector('input[name*="[cost_price]"]');
        const wholesaleInput = row.querySelector('input[name*="[wholesale_price]"]');
        const retailInput = row.querySelector('input[name*="[retail_price]"]');
        const specialInput = row.querySelector('input[name*="[special_price]"]');

        if (priceInput.value) totalPrices++;
        if (costInput.value) totalCost++;
        if (wholesaleInput.value) totalWholesale++;
        if (retailInput.value) totalRetail++;
        if (specialInput.value) totalSpecial++;
    });

    const summaryDetails = document.getElementById('summaryDetails');
    summaryDetails.innerHTML = `
        <strong>Precios configurados:</strong> ${totalPrices}<br>
        <strong>Precios de costo:</strong> ${totalCost}<br>
        <strong>Precios al por mayor:</strong> ${totalWholesale}<br>
        <strong>Precios al detalle:</strong> ${totalRetail}<br>
        <strong>Precios especiales:</strong> ${totalSpecial}
    `;

    document.getElementById('summaryInfo').style.display = 'block';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#bulkPriceModal input[type="number"]');
    inputs.forEach(input => {
        input.addEventListener('input', updateSummary);
    });

    const radios = document.querySelectorAll('#bulkPriceModal input[type="radio"]');
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Marcar el precio por defecto en el formulario
            const index = this.value;
            const defaultInput = document.querySelector(`input[name="prices[${index}][is_default]"]`);
            if (!defaultInput) {
                const newInput = document.createElement('input');
                newInput.type = 'hidden';
                newInput.name = `prices[${index}][is_default]`;
                newInput.value = '1';
                this.closest('td').appendChild(newInput);
            }
        });
    });
});
</script>
