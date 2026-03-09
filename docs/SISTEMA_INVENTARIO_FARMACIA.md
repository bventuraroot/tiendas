# 📦 Sistema de Inventario para Farmacia - Guía Completa

## 🎯 Objetivo

Manejar el inventario de medicamentos que se venden en múltiples presentaciones:
- **Pastilla** (unidad base)
- **Blister** (grupo de pastillas)
- **Caja** (conjunto de blisters)

El sistema guarda el inventario en **pastillas** (unidad base) y permite vender en cualquier presentación.

---

## 🏗️ Arquitectura del Sistema

### Unidades Base Disponibles:
- **Pastilla** (ID: 36, código: PASTILLA) - Unidad base
- **Blister** (ID: 39, código: BLISTER)
- **Caja** (ID: 40, código: CAJA)

### Estructura de Conversión:
```
1 Caja = X Blisters
1 Blister = Y Pastillas
1 Caja = X × Y Pastillas (total)
```

---

## 📋 Configuración Paso a Paso

### Paso 1: Configurar el Producto

1. **Crear/Editar Producto:**
   - Seleccionar **Presentación**: caja, blister, o pastilla
   - Este campo es informativo y ayuda a identificar el producto

2. **Tipo de Venta:**
   - Para productos farmacéuticos, usar: **"Por Unidad"** (`sale_type = 'unit'`)

### Paso 2: Configurar Conversiones de Unidades

Para cada producto que se venda en múltiples presentaciones, necesitas configurar las conversiones:

#### Ejemplo: Paracetamol 500mg
- **1 Caja = 10 Blisters**
- **1 Blister = 10 Pastillas**
- **Total: 1 Caja = 100 Pastillas**

#### Cómo Configurar:

1. **Ir al producto** → Sección de "Unidades de Venta" o "Conversiones"

2. **Agregar Conversión para Blister:**
   - Unidad: **Blister**
   - Factor de conversión: **10** (1 blister = 10 pastillas)
   - Precio: Se calcula automáticamente o se define manualmente
   - ✅ Marcar como activa

3. **Agregar Conversión para Caja:**
   - Unidad: **Caja**
   - Factor de conversión: **100** (1 caja = 100 pastillas)
   - Precio: Precio de la caja completa
   - ✅ Marcar como activa

4. **Marcar Pastilla como Unidad Base:**
   - Unidad: **Pastilla**
   - Factor de conversión: **1** (1 pastilla = 1 pastilla)
   - ✅ Marcar como unidad por defecto

---

## 📦 Gestión de Inventario

### Agregar Inventario Inicial

1. **Ir a Inventario** → Agregar nuevo producto

2. **Seleccionar el Producto**

3. **Elegir Unidad de Entrada:**
   - Puedes ingresar en **Cajas**, **Blisters**, o **Pastillas**
   - El sistema automáticamente convertirá a pastillas

4. **Ejemplo de Entrada:**
   ```
   Producto: Paracetamol 500mg
   Unidad: Caja
   Cantidad: 5 cajas
   → El sistema guarda: 5 × 100 = 500 pastillas
   ```

### Ver Inventario

El inventario se muestra en **pastillas** (unidad base), pero puedes ver equivalencias:

**Ejemplo de Visualización:**
```
Stock: 500 pastillas
Equivalencias:
  - 5.0 cajas
  - 50.0 blisters
  - 500 pastillas
```

### Ajustar Inventario

Puedes agregar o quitar stock en cualquier unidad:
- Agregar 2 cajas → +200 pastillas
- Quitar 15 blisters → -150 pastillas
- Agregar 25 pastillas → +25 pastillas

---

## 💰 Sistema de Ventas

### Vender en Diferentes Unidades

Al hacer una venta, puedes seleccionar en qué unidad vender:

1. **Seleccionar Producto**
2. **Elegir Unidad de Venta:**
   - Pastilla (precio unitario)
   - Blister (precio por blister)
   - Caja (precio por caja)
3. **Cantidad:**
   - El sistema automáticamente convierte y descuenta del inventario en pastillas

### Ejemplo de Venta:
```
Venta: 3 Cajas de Paracetamol
→ Se descuentan: 3 × 100 = 300 pastillas del inventario
→ Precio: Precio de caja × 3
```

---

## 🔄 Conversiones Automáticas

El sistema realiza conversiones automáticas:

### Al Agregar Inventario:
- **Entrada:** 5 Cajas
- **Conversión:** 5 × 100 = 500 pastillas
- **Guardado:** 500 pastillas en base_quantity

### Al Vender:
- **Venta:** 10 Blisters
- **Conversión:** 10 × 10 = 100 pastillas
- **Descuento:** 100 pastillas del inventario

### Al Ver Stock:
- **Inventario Base:** 500 pastillas
- **Visualización:** 
  - 5.0 cajas
  - 50.0 blisters
  - 500 pastillas

---

## 📊 Ejemplo Completo: Paracetamol 500mg

### Configuración Inicial:
```
Producto: Paracetamol 500mg
Presentación: Caja
Tipo de Venta: Por Unidad

Conversiones Configuradas:
1. Pastilla (Base):
   - Factor: 1
   - Precio: $0.10
   - Por defecto: ✅

2. Blister:
   - Factor: 10 (1 blister = 10 pastillas)
   - Precio: $0.90 (descuento por volumen)
   - Activa: ✅

3. Caja:
   - Factor: 100 (1 caja = 100 pastillas)
   - Precio: $8.00 (descuento por volumen)
   - Activa: ✅
```

### Inventario:
```
Entrada inicial: 10 Cajas
Stock guardado: 1,000 pastillas

Visualización:
- 10.0 cajas
- 100.0 blisters  
- 1,000 pastillas
```

### Ventas:
```
Venta 1: 2 Cajas → Descuento: 200 pastillas
Venta 2: 15 Blisters → Descuento: 150 pastillas
Venta 3: 50 Pastillas → Descuento: 50 pastillas

Stock Final:
- 600 pastillas
- 6.0 cajas
- 60.0 blisters
```

---

## ✅ Ventajas del Sistema

1. **Precisión:** El inventario siempre se guarda en la unidad más pequeña (pastillas)
2. **Flexibilidad:** Puedes comprar y vender en cualquier presentación
3. **Conversiones Automáticas:** No necesitas calcular manualmente
4. **Trazabilidad:** Sabes exactamente cuántas pastillas tienes
5. **Múltiples Precios:** Diferentes precios para cada presentación

---

## 🔧 Configuración Técnica

### Campos en Base de Datos:

**Tabla: `inventory`**
- `base_quantity`: Cantidad en pastillas (unidad base)
- `base_unit_id`: ID de la unidad "Pastilla" (36)

**Tabla: `product_unit_conversions`**
- `product_id`: ID del producto
- `unit_id`: ID de la unidad (Pastilla=36, Blister=39, Caja=40)
- `conversion_factor`: Factor de conversión a pastillas
- `is_default`: Marcar Pastilla como unidad por defecto
- `is_active`: Activar las conversiones que quieras usar

---

## 📝 Notas Importantes

1. **Unidad Base:** Siempre debe ser **Pastilla** para productos farmacéuticos
2. **Factores de Conversión:** Deben reflejar cuántas pastillas hay en cada blister/caja
3. **Precios:** Se pueden configurar precios diferentes para cada presentación
4. **Inventario:** Siempre se guarda y maneja en pastillas
5. **Ventas:** Puedes vender en cualquier unidad configurada

---

## 🆘 Solución de Problemas

### El inventario no muestra las conversiones:
- Verificar que las conversiones estén marcadas como activas
- Verificar que el producto tenga `sale_type = 'unit'`

### No puedo vender en blister/caja:
- Verificar que las conversiones estén configuradas
- Verificar que estén marcadas como activas

### El inventario se descuenta incorrectamente:
- Verificar los factores de conversión
- Asegurarse que la unidad base sea Pastilla

