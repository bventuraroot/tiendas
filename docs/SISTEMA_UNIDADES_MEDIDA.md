# Sistema de Unidades de Medida para Productos Agropecuarios

## **Descripción General**

Este sistema permite manejar productos agropecuarios que se compran en una unidad (ej: sacos) pero se venden en diferentes unidades (libras, sacos completos, o por valor monetario).

## **Ejemplo Práctico: Comida para Pollos**

### **Configuración del Producto**
- **Nombre**: Comida para Pollos Premium
- **Precio del saco**: $55.00
- **Peso del saco**: 80 libras
- **Precio por libra**: $55.00 ÷ 80 = $0.6875 por libra

### **Escenarios de Venta**

#### **1. Venta por Libras**
- **Cliente compra**: 40 libras
- **Precio**: 40 × $0.6875 = $27.50
- **Descuento del inventario**: 40 libras (directo)

#### **2. Venta por Saco Completo**
- **Cliente compra**: 1 saco
- **Precio**: $55.00
- **Descuento del inventario**: 80 libras (1 saco × 80 libras)

#### **3. Venta por Dólares**
- **Cliente compra**: $30.00 en producto
- **Cantidad**: $30.00 ÷ $0.6875 = 43.64 libras
- **Descuento del inventario**: 43.64 libras

## **Configuración en el Sistema**

### **1. Crear Producto**
```php
// En el formulario de productos
'sale_type' => 'weight',           // Tipo de venta por peso
'price' => 55.00,                  // Precio del saco completo
'weight_per_unit' => 80,           // Peso del saco en libras
```

### **2. Configurar Conversiones**
El sistema automáticamente crea las conversiones:
- **Unidad (59)**: 1 saco = 80 libras
- **Libra (36)**: 1 libra = 1 libra
- **Dólar (99)**: $1.00 = 1.45 libras ($1.00 ÷ $0.6875)

### **3. Agregar Inventario**
```php
// Al agregar inventario
'quantity' => 1000,                // 1000 libras en inventario
'base_unit_id' => 36,              // Unidad base: libras
```

## **Flujo de Venta**

### **1. Selección de Producto**
- El sistema carga las unidades disponibles
- Muestra stock en libras (unidad base)

### **2. Selección de Unidad**
- **Unidad**: Muestra precio por saco
- **Libra**: Muestra precio por libra
- **Dólar**: Muestra valor monetario

### **3. Cálculo Automático**
```php
// Ejemplo: Venta de 40 libras
$conversionData = $unitConversionService->calculateSaleConversion(
    $productId = 1,
    $quantity = 40,
    $unitCode = '36' // Libra
);

// Resultado:
[
    'unit_price' => 0.6875,        // Precio por libra
    'subtotal' => 27.50,           // 40 × $0.6875
    'base_quantity_needed' => 40,   // Libras a descontar
    'stock_available' => true
]
```

### **4. Descuento de Inventario**
```php
// Al guardar la venta
$baseQuantityToDeduct = $unitConversionService->calculateBaseQuantityNeeded(
    $productId = 1,
    $quantity = 40,
    $unitCode = '36'
);
// Resultado: 40 libras

// Actualizar inventario
$inventory->quantity = $inventory->quantity - 40;
```

## **Casos de Uso Comunes**

### **Caso 1: Venta Mixta**
- Cliente compra: 2 sacos + 15 libras
- Total: (2 × 80) + 15 = 175 libras
- Precio: (2 × $55.00) + (15 × $0.6875) = $110.00 + $10.31 = $120.31

### **Caso 2: Venta por Valor**
- Cliente compra: $50.00 en producto
- Cantidad: $50.00 ÷ $0.6875 = 72.73 libras
- Descuento: 72.73 libras del inventario

### **Caso 3: Control de Stock**
- Inventario disponible: 500 libras
- Venta solicitada: 600 libras
- Sistema: "Stock insuficiente. Disponible: 500, Necesario: 600"

## **Ventajas del Sistema**

1. **Flexibilidad**: Múltiples formas de vender el mismo producto
2. **Precisión**: Conversiones automáticas y exactas
3. **Control**: Descuento automático del inventario
4. **Trazabilidad**: Registro de todas las conversiones
5. **Simplicidad**: Interfaz intuitiva para el usuario

## **Configuración Técnica**

### **Base de Datos**
```sql
-- Producto
products.weight_per_unit = 80 (libras por saco)
products.price = 55.00 (precio del saco)

-- Inventario
inventory.quantity = 1000 (libras disponibles)
inventory.base_unit_id = 36 (libras como unidad base)

-- Conversiones automáticas
product_unit_conversions:
- unit_code: '59', conversion_factor: 80 (1 saco = 80 libras)
- unit_code: '36', conversion_factor: 1 (1 libra = 1 libra)
- unit_code: '99', conversion_factor: 1.45 (1 dólar = 1.45 libras)
```

### **API Endpoints**
```php
// Calcular conversión
POST /sale/calculate-unit-conversion
{
    "product_id": 1,
    "unit_code": "36",
    "quantity": 40
}

// Guardar venta con descuento automático
GET /sale/savefactemp/{sale_id}/{client_id}/{product_id}/{quantity}/{price}/.../{unitCode}/{unitId}/{conversionFactor}
```

## **Próximos Pasos**

1. **Implementar en inventario**: Agregar selección de unidad al agregar inventario
2. **Reportes**: Crear reportes de ventas por unidad
3. **Historial**: Mostrar historial de conversiones
4. **Optimización**: Mejorar rendimiento de cálculos
