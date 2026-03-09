// CRUD de conversiones de unidades integrado con la calculadora del modal de edición
(function() {

  function getProductId() {
    const id = document.getElementById('idedit')?.value;
    return id;
  }

  function api(path, options = {}) {
    return fetch(path, Object.assign({
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    }, options)).then(r => r.json());
  }

  let unitCatalog = [];
  function loadUnits() {
    return api('/product-units/catalog').then(res => {
      if (res.success) unitCatalog = res.data || [];
    });
  }

  function createConversionFromCalculator() {
    const productId = getProductId();
    if (!productId) return alert('Producto no identificado');

    const saleType = document.getElementById('sale_type_edit')?.value;
    if (!saleType) return alert('Selecciona el tipo de venta');

    let factor = 1;
    if (saleType === 'weight') {
      const weight = parseFloat(document.getElementById('weight_per_unit_edit')?.value || '0');
      if (weight <= 0) return alert('Ingresa el peso total en libras');
      factor = weight;
    } else if (saleType === 'volume') {
      const vol = parseFloat(document.getElementById('volume_per_unit_edit')?.value || '0');
      if (vol <= 0) return alert('Ingresa el volumen total en litros');
      factor = vol;
    } else {
      factor = 1;
    }

    if (!unitCatalog.length) return alert('Catálogo de unidades no disponible');

    // Pedir al usuario la unidad a guardar (simple prompt). Alternativamente, escoger por tipo
    let candidates = unitCatalog;
    if (saleType === 'weight') candidates = unitCatalog.filter(u => (u.unit_name || '').toLowerCase().includes('libra'));
    if (saleType === 'volume') candidates = unitCatalog.filter(u => (u.unit_name || '').toLowerCase().includes('litro'));

    const first = candidates[0] || unitCatalog[0];
    const chosen = first; // estrategia simple: primera sugerida

    if (!chosen) return alert('No hay unidad disponible para guardar');

    const payload = {
      product_id: productId,
      unit_id: chosen.id,
      conversion_factor: factor,
      price_multiplier: 1,
      is_default: false
    };

    api('/product-units/store', { method: 'POST', body: JSON.stringify(payload) }).then(res => {
      if (res.success) {
        alert(`Conversión guardada: ${chosen.unit_name} (factor ${factor})`);
      } else {
        alert(res.message || 'No se pudo guardar la conversión');
      }
    }).catch(err => {
      alert('Error al guardar la conversión');
    });
  }

  // Inicialización al abrir el modal de edición
  $('#updateProductModal').on('shown.bs.modal', function () {
    loadUnits();
    // Abrir acordeón automáticamente si no estuviera abierto (ya está marcado como show en HTML)
  });

  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'btn-conv-from-calculator') {
      createConversionFromCalculator();
    }
  });
})();
