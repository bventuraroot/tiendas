# 📚 Tutorial: Cómo Registrar Productos

## 🎯 Orden de Creación

Para registrar un producto, primero crea:
1. ✅ **Marcas** (obligatorio)
2. ✅ **Proveedores** (obligatorio)
3. ⚪ **Laboratorios** (opcional)
4. ✅ **Productos**

---

## Paso 1: Crear Marca

1. Menú → **Farmacia** → **Marcas**
2. Clic en **"Agregar Marca"**
3. Completa:
   - **Nombre Marca** ⭐ (obligatorio)
   - **Descripción** (opcional)
4. Clic en **"Crear"**

**Ejemplo:**
- Nombre: `Bayer`
- Descripción: `Laboratorio farmacéutico alemán`

---

## Paso 2: Crear Proveedor

1. Menú → **Farmacia** → **Proveedores**
2. Clic en **"Agregar Proveedor"**
3. Completa los campos:

**Información Básica:**
- **Razón Social** ⭐
- **NIT** (debe ser único)
- **Correo**

**Ubicación:**
- **Empresa** ⭐
- **País** ⭐
- **Departamento** ⭐
- **Municipio** ⭐
- **Dirección** ⭐

**Contacto:**
- **Teléfono Principal**
- **Teléfono Secundario**

4. Clic en **"Crear"**

**Ejemplo:**
- Razón Social: `Distribuidora Farmacéutica S.A.`
- NIT: `0614-123456-001-2`
- Dirección: `Colonia Centro, San Salvador`

---

## Paso 3: Crear Laboratorio (Opcional)

1. Menú → **Laboratorios Farmacéuticos**
2. Clic en **"Crear Nuevo Laboratorio"**
3. Completa:
   - **Nombre** ⭐ (obligatorio, único)
   - **Código** (opcional)
   - **País, Teléfono, Email** (opcional)
4. Clic en **"Guardar"**

---

## Paso 4: Registrar Producto

1. Menú → **Farmacia** → **Productos**
2. Clic en **"Crear nuevo producto"**
3. Completa las 5 secciones:

### Sección 1: Información Básica ⭐

- **Código** ⭐ (único, obligatorio)
  - Ejemplo: `ASP500`
- **Nombre** ⭐ (obligatorio)
  - Ejemplo: `Aspirina 500mg`
- **Descripción** ⭐ (obligatorio)
  - Ejemplo: `Tabletas de ácido acetilsalicílico 500mg`
- **Imagen** (opcional: JPG, PNG, GIF, WEBP, máx. 5MB)

### Sección 2: Clasificación

- **Marca** (seleccionar del menú)
- **Proveedor** (seleccionar del menú)
- **Categoría** (opcional: Analgésicos, Antibióticos, etc.)
- **Laboratorio Farmacéutico** (opcional)

### Sección 3: Información Farmacéutica

- **Presentación** (Caja, Blister, Pastilla, etc.)
- **Especialidad** (Cardiología, Pediatría, etc.)
- **Unidad de Medida** (mg, ml, unidades)
- **Número de Registro Sanitario**
- **Forma de Venta** (Venta libre, Con receta, Controlado)
- **Tipo de Producto** (Monofármico, Genérico, etc.)
- **Fórmula** (Ingredientes activos)

### Sección 4: Configuración de Presentaciones

- **Pastillas por Blister** (ejemplo: `10`)
- **Blisters por Caja** (ejemplo: `2`)

> 💡 El inventario se guarda en **pastillas** (unidad base)

### Sección 5: Información Fiscal y Comercial ⭐

- **Condición Fiscal** ⭐
  - `Gravado` (paga IVA) o `Exento` (no paga IVA)
- **Tipo** ⭐
  - `Directo` o `Tercero`
- **Precio** ⭐
  - Ejemplo: `2.50`

4. Revisa la información
5. Clic en **"Guardar"**

---

## ✅ Ejemplo Completo

```
═══════════════════════════════════════════════════════════
PRODUCTO: Aspirina 500mg
═══════════════════════════════════════════════════════════

Código: ASP500
Nombre: Aspirina 500mg
Descripción: Tabletas de ácido acetilsalicílico 500mg

Marca: Bayer
Proveedor: Distribuidora Farmacéutica S.A.
Categoría: Analgésicos y Antiinflamatorios

Presentación: Caja
Forma de Venta: Venta libre
Fórmula: Ácido acetilsalicílico 500mg

Pastillas por Blister: 10
Blisters por Caja: 2

Condición Fiscal: Gravado
Tipo: Directo
Precio: 2.50
```

---

## ❓ Preguntas Frecuentes

**¿Puedo crear un producto sin marca y proveedor?**
- Sí, el sistema usa valores por defecto, pero es mejor crearlos primero.

**¿Qué pasa si el código ya existe?**
- El sistema mostrará error. Usa un código diferente.

**¿Qué significa "Gravado" y "Exento"?**
- **Gravado:** Paga IVA. **Exento:** No paga IVA.

**¿Para qué sirven "Pastillas por Blister" y "Blisters por Caja"?**
- Para que el sistema calcule automáticamente el inventario total en pastillas.

---

## ❌ Solución de Problemas

**Error: "El código ya existe"**
- Usa un código diferente (ejemplo: `ASP500-1`)

**Error: "La marca no existe"**
- Crea la marca primero en el menú de Marcas

**Error: "El proveedor no existe"**
- Crea el proveedor primero en el menú de Proveedores

**Error al subir imagen**
- Verifica formato (JPG, PNG, GIF, WEBP) y tamaño (máx. 5MB)

**El formulario no se guarda**
- Verifica que todos los campos obligatorios (⭐) estén completos:
  - Código
  - Nombre
  - Descripción
  - Condición Fiscal
  - Tipo
  - Precio

---

## 💡 Consejos

1. Crea todas las marcas y proveedores antes de registrar productos
2. Usa códigos consistentes: `ASP500`, `IBU400`, `PAR500`
3. Escribe descripciones claras y detalladas
4. Verifica los precios antes de guardar
5. Sube imágenes de los productos cuando sea posible

---

**¿Necesitas ayuda?** Contacta al administrador del sistema.

---

*Última actualización: Enero 2025*
