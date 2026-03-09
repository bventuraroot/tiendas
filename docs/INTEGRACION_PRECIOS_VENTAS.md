# Integración de Precios Múltiples con el Módulo de Ventas

## 📋 Descripción General

Este documento explica cómo se integra el sistema de precios múltiples con el módulo de ventas, permitiendo seleccionar diferentes tipos de precio según la unidad de medida y el tipo de cliente.

## 🏗️ Arquitectura de la Integración

### **Componentes Principales:**

1. **ProductPriceService** - Servicio para manejar la lógica de precios
2. **ProductPriceSaleController** - Controlador para APIs de precios en ventas
3. **SalesMultiplePrices** - Clase JavaScript para la interfaz de usuario
4. **Rutas API** - Endpoints para consultar precios

## 🚀 Funcionalidades Implementadas

### **1. Selección Automática de Precios**

Cuando se selecciona un producto en la venta:

```javascript
// Al seleccionar un producto
$('#psearch').on('change', function() {
    const productId = $(this).val();
    if (productId) {
        salesMultiplePrices.loadProductPrices(productId);
    }
});
```

**Proceso:**
1. Se verifica si el producto tiene precios múltiples configurados
2. Se cargan todas las unidades disponibles con sus precios
3. Se selecciona automáticamente la unidad por defecto
4. Se muestran los tipos de precio disponibles

### **2. Selector de Unidades con Precios**

```html
<select class="form-select" id="unit-select" name="unit-select">
    <option value="">Seleccionar unidad...</option>
    <option value="1">Libra (LB) - Por Defecto</option>
    <option value="2">Kilogramo (KG)</option>
    <option value="3">Saco (SAC)</option>
</select>
```

**Características:**
- Muestra solo unidades con precios configurados
- Indica cuál es la unidad por defecto
- Incluye código y nombre de la unidad

### **3. Selector de Tipos de Precio**

```html
<select class="form-select" id="price-type-select" name="price-type-select">
    <option value="">Seleccionar tipo...</option>
    <option value="regular">Precio Regular - $2.50</option>
    <option value="wholesale">Precio al Por Mayor - $2.00</option>
    <option value="retail">Precio al Detalle - $3.00</option>
    <option value="special">Precio Especial - $2.25</option>
</select>
```

**Tipos de Precio Disponibles:**
- **Regular** - Precio estándar del producto
- **Wholesale** - Precio al por mayor
- **Retail** - Precio al detalle
- **Special** - Precio promocional

### **4. Información Visual del Precio**

```html
<div class="alert alert-info" id="price-info-display">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Precio Seleccionado:</strong> 
    <span id="selected-price-info">Precio Regular - $2.50</span>
</div>
```

## 🔧 APIs Disponibles

### **1. Verificar si un producto tiene precios**
```http
GET /product-prices/product/{productId}/has-prices
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "has_prices": true
    }
}
```

### **2. Obtener precios de un producto**
```http
GET /product-prices/product/{productId}/prices
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "unit_id": 1,
            "unit_code": "LB",
            "unit_name": "Libra",
            "prices": {
                "regular": 2.50,
                "wholesale": 2.00,
                "retail": 3.00,
                "special": 2.25
            },
            "is_default": true
        }
    ]
}
```

### **3. Obtener tipos de precio por unidad**
```http
GET /product-prices/product/{productId}/unit/{unitId}/price-types
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "unit_id": 1,
        "unit_name": "Libra",
        "unit_code": "LB",
        "price_types": {
            "regular": {
                "name": "Precio Regular",
                "value": 2.50,
                "description": "Precio estándar del producto"
            },
            "wholesale": {
                "name": "Precio al Por Mayor",
                "value": 2.00,
                "description": "Precio para compras al por mayor"
            }
        },
        "is_default": true
    }
}
```

### **4. Calcular precio de venta**
```http
POST /product-prices/calculate-sale-price
```

**Body:**
```json
{
    "product_id": 1,
    "unit_id": 1,
    "quantity": 10,
    "client_type": "wholesale"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "unit_price": 2.00,
        "total_price": 20.00,
        "unit_name": "Libra",
        "unit_code": "LB",
        "price_type": "wholesale",
        "quantity": 10
    }
}
```

## 💻 Uso en el Código

### **Inicialización:**
```javascript
// Se inicializa automáticamente cuando se carga la página
$(document).ready(function() {
    SalesMultiplePrices.getInstance();
});
```

### **Cargar precios de un producto:**
```javascript
const salesPrices = SalesMultiplePrices.getInstance();
await salesPrices.loadProductPrices(productId);
```

### **Obtener precio por defecto:**
```javascript
const defaultPrice = await salesPrices.getDefaultPrice(productId);
console.log(defaultPrice.price); // $2.50
```

### **Calcular precio con descuentos:**
```javascript
const salePrice = await salesPrices.calculateSalePriceWithDiscounts(10, 5); // 10 unidades, 5% descuento
console.log(salePrice.final_price); // $19.00
```

## 🎯 Flujo de Trabajo en Ventas

### **1. Selección de Producto**
1. Usuario selecciona un producto del catálogo
2. Sistema verifica si tiene precios múltiples configurados
3. Si los tiene, carga las unidades disponibles

### **2. Selección de Unidad**
1. Usuario selecciona la unidad de medida
2. Sistema carga los tipos de precio disponibles para esa unidad
3. Se selecciona automáticamente el precio regular

### **3. Selección de Tipo de Precio**
1. Usuario puede cambiar el tipo de precio según el cliente
2. Sistema actualiza automáticamente el precio unitario
3. Se recalcula el total de la línea

### **4. Cálculo de Totales**
1. Sistema calcula el subtotal por línea
2. Se aplican impuestos según el tipo de venta
3. Se actualiza el total general de la venta

## 🔄 Integración con el Sistema Existente

### **Compatibilidad:**
- ✅ Funciona con productos que NO tienen precios múltiples
- ✅ Mantiene la funcionalidad existente de ventas
- ✅ Se integra con el sistema de unidades de medida
- ✅ Compatible con el cálculo de impuestos

### **Fallback:**
Si un producto no tiene precios múltiples configurados:
1. Se usa el precio original del producto
2. Se mantiene la funcionalidad estándar
3. No se muestran selectores adicionales

## 📊 Beneficios de la Integración

### **Para el Usuario:**
- ✅ Selección rápida de precios según el cliente
- ✅ Visualización clara de precios disponibles
- ✅ Cálculo automático de totales
- ✅ Interfaz intuitiva y fácil de usar

### **Para el Negocio:**
- ✅ Flexibilidad en precios por tipo de cliente
- ✅ Mejor control de márgenes de ganancia
- ✅ Precios diferenciados por unidad de medida
- ✅ Seguimiento de ventas por tipo de precio

## 🛠️ Configuración y Mantenimiento

### **Archivos Principales:**
- `app/Services/ProductPriceService.php` - Lógica de negocio
- `app/Http/Controllers/ProductPriceSaleController.php` - APIs
- `public/assets/js/sales-multiple-prices.js` - Interfaz de usuario
- `routes/web.php` - Rutas de la API

### **Dependencias:**
- jQuery (ya incluido en el proyecto)
- Bootstrap (ya incluido en el proyecto)
- FontAwesome (ya incluido en el proyecto)

## 🚀 Próximas Mejoras

### **Funcionalidades Planificadas:**
1. **Descuentos por volumen** - Descuentos automáticos según cantidad
2. **Precios por cliente específico** - Precios personalizados por cliente
3. **Historial de precios** - Seguimiento de cambios de precios
4. **Reportes de ventas por tipo de precio** - Análisis de rentabilidad
5. **Sincronización con inventario** - Actualización automática de precios

### **Optimizaciones:**
1. **Cache de precios** - Mejorar rendimiento de consultas
2. **Validación en tiempo real** - Verificar stock al cambiar precios
3. **Interfaz responsive** - Mejorar experiencia en móviles
4. **Accesos directos** - Teclas de acceso rápido para tipos de precio

---

**Nota:** Esta integración está diseñada para ser compatible con el sistema existente y no afecta la funcionalidad actual de ventas. Los productos sin precios múltiples configurados funcionan normalmente.
