<!-- Modal para Crear Precio -->
<div class="modal fade" id="createPriceModal" tabindex="-1" aria-labelledby="createPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPriceModalLabel">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Nuevo Precio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="createPriceForm" onsubmit="event.preventDefault(); createPrice();">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Unidad de Medida -->
                        <div class="col-md-6 mb-3">
                            <label for="unit_id" class="form-label">
                                <i class="fas fa-ruler me-1"></i>
                                Unidad de Medida <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="unit_id" name="unit_id" required>
                                <option value="">Seleccione una unidad</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}"
                                            data-code="{{ $unit->unit_code }}"
                                            data-name="{{ $unit->unit_name }}">
                                        {{ $unit->unit_name }} ({{ $unit->unit_code }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Seleccione la unidad de medida para este precio</div>
                        </div>

                        <!-- Precio Regular -->
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Precio Regular <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price"
                                       step="0.0001" min="0" required placeholder="0.0000">
                            </div>
                            <div class="form-text">Precio estándar del producto</div>
                        </div>

                        <!-- Precio de Costo -->
                        <div class="col-md-6 mb-3">
                            <label for="cost_price" class="form-label">
                                <i class="fas fa-shopping-cart me-1"></i>
                                Precio de Costo
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="cost_price" name="cost_price"
                                       step="0.0001" min="0" placeholder="0.0000">
                            </div>
                            <div class="form-text">Precio de compra del producto</div>
                        </div>

                        <!-- Precio al Por Mayor -->
                        <div class="col-md-6 mb-3">
                            <label for="wholesale_price" class="form-label">
                                <i class="fas fa-boxes me-1"></i>
                                Precio al Por Mayor
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="wholesale_price" name="wholesale_price"
                                       step="0.0001" min="0" placeholder="0.0000">
                            </div>
                            <div class="form-text">Precio para compras al por mayor</div>
                        </div>

                        <!-- Precio al Detalle -->
                        <div class="col-md-6 mb-3">
                            <label for="retail_price" class="form-label">
                                <i class="fas fa-store me-1"></i>
                                Precio al Detalle
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="retail_price" name="retail_price"
                                       step="0.0001" min="0" placeholder="0.0000">
                            </div>
                            <div class="form-text">Precio para ventas al detalle</div>
                        </div>

                        <!-- Precio Especial -->
                        <div class="col-md-6 mb-3">
                            <label for="special_price" class="form-label">
                                <i class="fas fa-star me-1"></i>
                                Precio Especial
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="special_price" name="special_price"
                                       step="0.0001" min="0" placeholder="0.0000">
                            </div>
                            <div class="form-text">Precio promocional o especial</div>
                        </div>

                        <!-- Opciones -->
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                <label class="form-check-label" for="is_default">
                                    <i class="fas fa-star me-1"></i>
                                    Marcar como precio por defecto
                                </label>
                                <div class="form-text">Este será el precio principal del producto</div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="col-12 mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note me-1"></i>
                                Notas Adicionales
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Información adicional sobre este precio..."></textarea>
                        </div>
                    </div>

                    <!-- Información de Margen -->
                    <div class="alert alert-info" id="marginInfo" style="display: none;">
                        <h6 class="alert-heading">
                            <i class="fas fa-chart-line me-1"></i>
                            Información de Margen
                        </h6>
                        <div id="marginDetails"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Guardar Precio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Calcular margen automáticamente
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('price');
    const costInput = document.getElementById('cost_price');
    const marginInfo = document.getElementById('marginInfo');
    const marginDetails = document.getElementById('marginDetails');

    function calculateMargin() {
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

    priceInput.addEventListener('input', calculateMargin);
    costInput.addEventListener('input', calculateMargin);
});
</script>
