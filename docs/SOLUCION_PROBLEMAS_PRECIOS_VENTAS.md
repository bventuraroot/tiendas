# Solución de Problemas: Precios Múltiples en Ventas

## 🔍 **Problema: Los precios múltiples no aparecen en las ventas**

### **Síntomas:**
- ✅ Tienes precios múltiples configurados en productos
- ❌ No aparecen los selectores adicionales en la página de ventas
- ❌ No se muestran los tipos de precio (Regular, Mayorista, etc.)

## 🛠️ **Pasos de Diagnóstico**

### **Paso 1: Verificar que el Script se Cargue**

1. **Abre la página de ventas** en tu navegador
2. **Abre las herramientas de desarrollador** (F12)
3. **Ve a la pestaña "Console"**
4. **Busca estos mensajes:**

```
📦 Cargando SalesMultiplePrices...
✅ jQuery está disponible
✅ Estamos en la página de ventas
🚀 Inicializando SalesMultiplePrices...
✅ SalesMultiplePrices inicializado correctamente
```

**Si NO ves estos mensajes:**
- ❌ El script no se está cargando
- ❌ Hay un error en la carga del JavaScript

### **Paso 2: Verificar que las APIs Funcionen**

1. **En la consola del navegador, ejecuta:**

```javascript
// Probar la API de verificación de precios
fetch('/product-prices/product/23/has-prices')
  .then(response => response.json())
  .then(data => console.log('Respuesta API:', data))
  .catch(error => console.error('Error API:', error));
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "has_prices": true
  }
}
```

**Si hay error:**
- ❌ Las rutas no están registradas correctamente
- ❌ Hay un problema de autenticación

### **Paso 3: Verificar Selección de Producto**

1. **Selecciona un producto** que tenga precios múltiples configurados
2. **Busca en la consola estos mensajes:**

```
📦 Producto seleccionado: 23
🔍 Cargando precios para producto: 23
📊 ¿Tiene precios múltiples? {success: true, data: {has_prices: true}}
💰 Precios obtenidos: {success: true, data: [...]}
✅ Selector de unidades actualizado
```

**Si NO ves estos mensajes:**
- ❌ El evento de cambio no se está disparando
- ❌ Hay un problema con el ID del producto

## 🔧 **Soluciones por Problema**

### **Problema 1: Script no se carga**

**Síntomas:**
- No aparecen mensajes de inicialización en la consola

**Solución:**
1. **Verifica que el script esté incluido en la vista:**

```php
// En resources/views/sales/create.blade.php
@section('page-script')
    <script src="{{ asset('assets/js/form-wizard-icons.js') }}"></script>
    <script src="{{ asset('assets/js/sales-units.js') }}"></script>
    <script src="{{ asset('assets/js/sales-multiple-prices.js') }}"></script>  <!-- ← Debe estar aquí -->
@endsection
```

2. **Verifica que el archivo existe:**
```bash
ls -la public/assets/js/sales-multiple-prices.js
```

3. **Limpia la caché:**
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

### **Problema 2: APIs no responden**

**Síntomas:**
- Error 404 o 500 en las peticiones fetch
- Mensajes de error en la consola

**Solución:**
1. **Verifica que las rutas estén registradas:**
```bash
docker-compose exec app php artisan route:list --name=product-prices
```

2. **Verifica que estés autenticado:**
- Las APIs requieren autenticación
- Asegúrate de estar logueado en el sistema

3. **Verifica el token CSRF:**
```html
<!-- En la vista debe estar: -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### **Problema 3: Selectores no aparecen**

**Síntomas:**
- El script se carga pero no aparecen los selectores adicionales

**Solución:**
1. **Verifica que el producto tenga precios múltiples:**
```bash
docker-compose exec app php artisan tinker --execute="
\$service = new App\Services\ProductPriceService(); 
\$hasPrices = \$service->hasConfiguredPrices(23); 
echo 'Producto 23 tiene precios: ' . (\$hasPrices ? 'SÍ' : 'NO');
"
```

2. **Verifica la estructura HTML:**
- El selector `#unit-select` debe existir en la página
- Debe estar dentro de un contenedor `.col-sm-3`

### **Problema 4: Conflictos con otros scripts**

**Síntomas:**
- Errores de JavaScript en la consola
- Comportamiento inesperado

**Solución:**
1. **Verifica el orden de carga de scripts:**
```php
// El orden debe ser:
// 1. jQuery
// 2. Bootstrap
// 3. Otros scripts
// 4. sales-multiple-prices.js (al final)
```

2. **Verifica que no haya conflictos de nombres:**
- Asegúrate de que no haya otra clase llamada `SalesMultiplePrices`

## 🧪 **Pruebas de Verificación**

### **Prueba 1: Verificar Producto con Precios**

```bash
# En el contenedor Docker
docker-compose exec app php artisan tinker --execute="
\$prices = App\Models\ProductPrice::with('product', 'unit')->get();
foreach(\$prices as \$price) {
    echo 'Producto: ' . \$price->product->name . ' - Unidad: ' . \$price->unit->unit_name . ' - Precio: $' . \$price->price . PHP_EOL;
}
"
```

### **Prueba 2: Verificar Servicio**

```bash
docker-compose exec app php artisan tinker --execute="
\$service = new App\Services\ProductPriceService();
\$prices = \$service->getProductPrices(23);
echo 'Precios encontrados: ' . \$prices->count();
"
```

### **Prueba 3: Verificar Controlador**

```bash
docker-compose exec app php artisan tinker --execute="
\$controller = new App\Http\Controllers\ProductPriceSaleController();
\$request = new Illuminate\Http\Request();
\$request->merge(['product_id' => 23]);
\$response = \$controller->hasConfiguredPrices(\$request);
echo 'Respuesta: ' . \$response->getContent();
"
```

## 📋 **Checklist de Verificación**

### **Antes de Reportar un Problema:**

- [ ] **Script se carga** (mensajes en consola)
- [ ] **jQuery está disponible** (no hay errores de $)
- [ ] **Estoy en la página de ventas** (existe #psearch)
- [ ] **Producto tiene precios múltiples** (verificado en BD)
- [ ] **APIs responden** (pruebas fetch exitosas)
- [ ] **Estoy autenticado** (no redirige a login)
- [ ] **No hay errores JavaScript** (consola limpia)

### **Información para Reportar:**

1. **Mensajes de la consola** (copiar todos)
2. **ID del producto** que estás probando
3. **URL de la página** donde ocurre el problema
4. **Pasos exactos** para reproducir el problema
5. **Navegador y versión** que estás usando

## 🚀 **Solución Rápida**

Si todo lo anterior falla, puedes forzar la carga manual:

```javascript
// En la consola del navegador
if (typeof SalesMultiplePrices !== 'undefined') {
    const instance = SalesMultiplePrices.getInstance();
    instance.loadProductPrices(23); // Reemplaza 23 con el ID de tu producto
}
```

## 📞 **Soporte**

Si después de seguir todos estos pasos el problema persiste:

1. **Revisa los logs de Laravel:**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

2. **Verifica los logs del navegador** (Network tab)
3. **Documenta todos los pasos** que seguiste
4. **Proporciona capturas de pantalla** de la consola

---

**Nota:** La mayoría de problemas se resuelven verificando que el script se cargue correctamente y que las APIs respondan como se espera.
