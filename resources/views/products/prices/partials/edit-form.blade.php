<form id="editPriceForm" onsubmit="event.preventDefault(); updatePrice();">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <!-- Unidad de Medida -->
            <div class="col-md-6 mb-3">
                <label for="edit_unit_id" class="form-label">
                    <i class="fas fa-ruler me-1"></i>
                    Unidad de Medida <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="edit_unit_id" name="unit_id" required>
                    <option value="">Seleccione una unidad</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}"
                                data-code="{{ $unit->unit_code }}"
                                data-name="{{ $unit->unit_name }}"
                                {{ $productPrice->unit_id == $unit->id ? 'selected' : '' }}>
                            {{ $unit->unit_name }} ({{ $unit->unit_code }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Seleccione la unidad de medida para este precio</div>
            </div>

            <!-- Precio Regular -->
            <div class="col-md-6 mb-3">
                <label for="edit_price" class="form-label">
                    <i class="fas fa-dollar-sign me-1"></i>
                    Precio Regular <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit_price" name="price"
                           step="0.0001" min="0" required placeholder="0.0000" value="{{ $productPrice->price }}">
                </div>
                <div class="form-text">Precio estándar del producto</div>
            </div>

            <!-- Precio de Costo -->
            <div class="col-md-6 mb-3">
                <label for="edit_cost_price" class="form-label">
                    <i class="fas fa-shopping-cart me-1"></i>
                    Precio de Costo
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit_cost_price" name="cost_price"
                           step="0.0001" min="0" placeholder="0.0000" value="{{ $productPrice->cost_price }}">
                </div>
                <div class="form-text">Precio de compra del producto</div>
            </div>

            <!-- Precio al Por Mayor -->
            <div class="col-md-6 mb-3">
                <label for="edit_wholesale_price" class="form-label">
                    <i class="fas fa-boxes me-1"></i>
                    Precio al Por Mayor
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit_wholesale_price" name="wholesale_price"
                           step="0.0001" min="0" placeholder="0.0000" value="{{ $productPrice->wholesale_price }}">
                </div>
                <div class="form-text">Precio para compras al por mayor</div>
            </div>

            <!-- Precio al Detalle -->
            <div class="col-md-6 mb-3">
                <label for="edit_retail_price" class="form-label">
                    <i class="fas fa-store me-1"></i>
                    Precio al Detalle
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit_retail_price" name="retail_price"
                           step="0.0001" min="0" placeholder="0.0000" value="{{ $productPrice->retail_price }}">
                </div>
                <div class="form-text">Precio para ventas al detalle</div>
            </div>

            <!-- Precio Especial -->
            <div class="col-md-6 mb-3">
                <label for="edit_special_price" class="form-label">
                    <i class="fas fa-star me-1"></i>
                    Precio Especial
                </label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit_special_price" name="special_price"
                           step="0.0001" min="0" placeholder="0.0000" value="{{ $productPrice->special_price }}">
                </div>
                <div class="form-text">Precio promocional o especial</div>
            </div>

            <!-- Opciones -->
            <div class="col-12 mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default" value="1" {{ $productPrice->is_default ? 'checked' : '' }}>
                            <label class="form-check-label" for="edit_is_default">
                                <i class="fas fa-star me-1"></i>
                                Marcar como precio por defecto
                            </label>
                            <div class="form-text">Este será el precio principal del producto</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1" {{ $productPrice->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="edit_is_active">
                                <i class="fas fa-check-circle me-1"></i>
                                Precio activo
                            </label>
                            <div class="form-text">Permitir el uso de este precio</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="col-12 mb-3">
                <label for="edit_notes" class="form-label">
                    <i class="fas fa-sticky-note me-1"></i>
                    Notas Adicionales
                </label>
                <textarea class="form-control" id="edit_notes" name="notes" rows="3"
                          placeholder="Información adicional sobre este precio...">{{ $productPrice->notes }}</textarea>
            </div>
        </div>

        <!-- Información de Margen -->
        <div class="alert alert-info" id="editMarginInfo" style="display: none;">
            <h6 class="alert-heading">
                <i class="fas fa-chart-line me-1"></i>
                Información de Margen
            </h6>
            <div id="editMarginDetails"></div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>
            Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            Actualizar Precio
        </button>
    </div>
</form>

<script>
// Calcular margen automáticamente para el formulario de edición
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('edit_price');
    const costInput = document.getElementById('edit_cost_price');
    const marginInfo = document.getElementById('editMarginInfo');
    const marginDetails = document.getElementById('editMarginDetails');

    function calculateEditMargin() {
        const price = parseFloat(priceInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;

        if (price > 0 && cost > 0) {
            const profit = price - cost;
            const margin = (profit / cost) * 100;

            marginDetails.innerHTML = `
                <strong>Precio de Venta:</strong> $${price.toFixed(2)}<br>
                <strong>Precio de Costo:</strong> $${cost.toFixed(2)}<br>
                <strong>Ganancia:</strong> $${profit.toFixed(2)}<br>
                <strong>Margen:</strong> <span class="badge ${margin > 0 ? 'bg-success' : 'bg-danger'}">${margin.toFixed(1)}%</span>
            `;
            marginInfo.style.display = 'block';
        } else {
            marginInfo.style.display = 'none';
        }
    }

    priceInput.addEventListener('input', calculateEditMargin);
    costInput.addEventListener('input', calculateEditMargin);

    // Calcular margen inicial
    calculateEditMargin();
});
</script>
