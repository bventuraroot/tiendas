# Ejemplo Práctico: Uso de Precios Múltiples en Ventas

## 🎯 Escenario de Ejemplo

**Empresa:** Agroservicio Milagro de Dios  
**Producto:** Fertilizante NPK 15-15-15  
**Cliente:** Distribuidor Mayorista "AgroSupply S.A."

## 📋 Configuración del Producto

### **1. Configuración de Precios Múltiples**

El producto "Fertilizante NPK 15-15-15" tiene los siguientes precios configurados:

| Unidad | Precio Regular | Precio Mayorista | Precio Detalle | Precio Especial |
|--------|----------------|------------------|----------------|-----------------|
| **Libra (LB)** | $2.50 | $2.00 | $3.00 | $2.25 |
| **Kilogramo (KG)** | $5.50 | $4.40 | $6.60 | $4.95 |
| **Saco 50 LB** | $120.00 | $95.00 | $140.00 | $110.00 |

### **2. Configuración en el Sistema**

```sql
-- Precios configurados en la tabla product_prices
INSERT INTO product_prices (product_id, unit_id, price, wholesale_price, retail_price, special_price, is_default, is_active) VALUES
(1, 1, 2.50, 2.00, 3.00, 2.25, true, true),   -- Libra
(1, 2, 5.50, 4.40, 6.60, 4.95, false, true),  -- Kilogramo  
(1, 3, 120.00, 95.00, 140.00, 110.00, false, true); -- Saco
```

## 🛒 Proceso de Venta

### **Paso 1: Selección del Producto**

1. **Usuario:** Selecciona "Fertilizante NPK 15-15-15" del catálogo
2. **Sistema:** Verifica si tiene precios múltiples configurados
3. **Resultado:** ✅ Producto tiene precios múltiples

```javascript
// El sistema automáticamente ejecuta:
salesMultiplePrices.loadProductPrices(1); // product_id = 1
```

### **Paso 2: Carga de Unidades Disponibles**

**Sistema muestra en el selector de unidades:**

```html
<select id="unit-select">
    <option value="">Seleccionar unidad...</option>
    <option value="1">Libra (LB) - Por Defecto</option>
    <option value="2">Kilogramo (KG)</option>
    <option value="3">Saco 50 LB (SAC)</option>
</select>
```

**Selección automática:** Libra (unidad por defecto)

### **Paso 3: Carga de Tipos de Precio**

**Sistema muestra en el selector de tipos de precio:**

```html
<select id="price-type-select">
    <option value="">Seleccionar tipo...</option>
    <option value="regular">Precio Regular - $2.50</option>
    <option value="wholesale">Precio al Por Mayor - $2.00</option>
    <option value="retail">Precio al Detalle - $3.00</option>
    <option value="special">Precio Especial - $2.25</option>
</select>
```

**Selección automática:** Precio Regular ($2.50)

### **Paso 4: Información Visual**

**Sistema muestra:**

```html
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Precio Seleccionado:</strong> 
    <span>Precio Regular - $2.50</span>
</div>
```

### **Paso 5: Selección del Cliente**

**Usuario:** Selecciona "AgroSupply S.A." (cliente mayorista)

**Usuario cambia el tipo de precio:** Selecciona "Precio al Por Mayor"

**Sistema actualiza automáticamente:**
- Precio unitario: $2.00 (en lugar de $2.50)
- Información visual: "Precio al Por Mayor - $2.00"

### **Paso 6: Ingreso de Cantidad**

**Usuario:** Ingresa 100 libras

**Sistema calcula automáticamente:**
- Precio unitario: $2.00
- Cantidad: 100
- Subtotal: $200.00
- IVA (13%): $26.00
- **Total: $226.00**

## 💰 Comparación de Precios

### **Mismo Producto, Diferentes Tipos de Cliente:**

| Tipo de Cliente | Precio/Libra | 100 Libras | Ahorro vs Regular |
|-----------------|--------------|------------|-------------------|
| **Cliente Regular** | $2.50 | $250.00 | - |
| **Cliente Mayorista** | $2.00 | $200.00 | $50.00 (20%) |
| **Cliente Detalle** | $3.00 | $300.00 | -$50.00 (+20%) |
| **Cliente Especial** | $2.25 | $225.00 | $25.00 (10%) |

## 🔄 Ejemplos de Uso por Unidad

### **Ejemplo 1: Venta por Libras (Mayorista)**
```
Producto: Fertilizante NPK 15-15-15
Unidad: Libra
Tipo de Precio: Mayorista
Cantidad: 500 libras
Precio Unitario: $2.00
Subtotal: $1,000.00
IVA: $130.00
Total: $1,130.00
```

### **Ejemplo 2: Venta por Kilogramos (Detalle)**
```
Producto: Fertilizante NPK 15-15-15
Unidad: Kilogramo
Tipo de Precio: Detalle
Cantidad: 50 kg
Precio Unitario: $6.60
Subtotal: $330.00
IVA: $42.90
Total: $372.90
```

### **Ejemplo 3: Venta por Sacos (Especial)**
```
Producto: Fertilizante NPK 15-15-15
Unidad: Saco 50 LB
Tipo de Precio: Especial
Cantidad: 10 sacos
Precio Unitario: $110.00
Subtotal: $1,100.00
IVA: $143.00
Total: $1,243.00
```

## 📊 Beneficios Demostrados

### **Para el Cliente Mayorista:**
- ✅ **Ahorro del 20%** en compras por libra
- ✅ **Ahorro del 20%** en compras por kilogramo
- ✅ **Ahorro del 20.8%** en compras por saco
- ✅ **Precios consistentes** en todas las unidades

### **Para la Empresa:**
- ✅ **Mantiene márgenes** en todos los tipos de cliente
- ✅ **Flexibilidad** para ofrecer descuentos estratégicos
- ✅ **Control** sobre precios por tipo de cliente
- ✅ **Trazabilidad** de ventas por tipo de precio

## 🎯 Casos de Uso Comunes

### **1. Cliente Frecuente (Mayorista)**
- **Tipo de Precio:** Mayorista
- **Unidad:** Saco (para mayor volumen)
- **Beneficio:** Máximo descuento por volumen

### **2. Cliente Ocasional (Detalle)**
- **Tipo de Precio:** Detalle
- **Unidad:** Libra o Kilogramo
- **Beneficio:** Precio premium por conveniencia

### **3. Cliente Promocional (Especial)**
- **Tipo de Precio:** Especial
- **Unidad:** Cualquiera
- **Beneficio:** Descuento temporal para fidelización

### **4. Cliente Regular (Estándar)**
- **Tipo de Precio:** Regular
- **Unidad:** Según preferencia
- **Beneficio:** Precio justo y competitivo

## 🔧 Configuración Técnica

### **API Calls Realizadas:**

```javascript
// 1. Verificar si tiene precios
GET /product-prices/product/1/has-prices
// Response: {"has_prices": true}

// 2. Obtener precios del producto
GET /product-prices/product/1/prices
// Response: [{"unit_id": 1, "prices": {...}}]

// 3. Obtener tipos de precio por unidad
GET /product-prices/product/1/unit/1/price-types
// Response: {"price_types": {"regular": {...}}}

// 4. Calcular precio de venta
POST /product-prices/calculate-sale-price
// Body: {"product_id": 1, "unit_id": 1, "quantity": 100, "client_type": "wholesale"}
// Response: {"unit_price": 2.00, "total_price": 200.00}
```

## 📈 Análisis de Rentabilidad

### **Margen por Tipo de Cliente:**

| Tipo de Cliente | Precio Venta | Precio Costo | Margen | % Margen |
|-----------------|--------------|--------------|--------|----------|
| **Regular** | $2.50 | $1.80 | $0.70 | 28% |
| **Mayorista** | $2.00 | $1.80 | $0.20 | 10% |
| **Detalle** | $3.00 | $1.80 | $1.20 | 40% |
| **Especial** | $2.25 | $1.80 | $0.45 | 20% |

### **Estrategia de Precios:**
- **Mayorista:** Margen bajo, volumen alto
- **Detalle:** Margen alto, volumen bajo
- **Regular:** Margen medio, volumen medio
- **Especial:** Margen medio-bajo, fidelización

---

**Conclusión:** El sistema de precios múltiples permite una gestión flexible y estratégica de precios, adaptándose a diferentes tipos de cliente y maximizando la rentabilidad del negocio.
