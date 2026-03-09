# Sistema de Precios Múltiples por Producto

## 📋 Descripción General

El sistema de precios múltiples permite configurar hasta **5 tipos de precio diferentes** para cada producto según la unidad de medida. Esto es especialmente útil para productos agropecuarios que se venden en diferentes presentaciones y cantidades.

## 🏗️ Arquitectura del Sistema

### **Tabla: `product_prices`**
```sql
CREATE TABLE `product_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL COMMENT 'Precio regular',
  `cost_price` decimal(10,2) NULL COMMENT 'Precio de costo',
  `wholesale_price` decimal(10,2) NULL COMMENT 'Precio al por mayor',
  `retail_price` decimal(10,2) NULL COMMENT 'Precio al detalle',
  `special_price` decimal(10,2) NULL COMMENT 'Precio especial/promocional',
  `is_active` boolean DEFAULT true,
  `is_default` boolean DEFAULT false,
  `notes` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
```

### **Tipos de Precio Disponibles**

1. **Precio Regular** (`price`) - Precio estándar del producto
2. **Precio de Costo** (`cost_price`) - Precio de compra del producto
3. **Precio al Por Mayor** (`wholesale_price`) - Precio para compras al por mayor
4. **Precio al Detalle** (`retail_price`) - Precio para ventas al detalle
5. **Precio Especial** (`special_price`) - Precio promocional o especial

## 🚀 Características Principales

### **1. Múltiples Unidades por Producto**
- Cada producto puede tener precios configurados para diferentes unidades de medida
- Soporte para unidades del catálogo CAT-014 del MH (libras, kilogramos, litros, galones, etc.)
- Unidad por defecto configurable

### **2. Gestión de Precios**
- **CRUD completo** de precios por producto
- **Precios masivos** con plantillas predefinidas
- **Cálculo automático** de márgenes de ganancia
- **Validación** de precios y unidades

### **3. Plantillas de Precios**
- **Productos por Peso**: Configuración automática para libras, sacos, kilogramos
- **Productos por Volumen**: Configuración para litros, galones, mililitros
- **Productos por Unidad**: Configuración para unidades, docenas, etc.

## 📱 Interfaz de Usuario

### **Acceso al Sistema**
1. Ir a **Productos** → **Lista de Productos**
2. En la columna **Acciones** de cualquier producto, hacer clic en el menú desplegable
3. Seleccionar **"Precios Múltiples"**

### **Vista Principal de Precios**
- **Información del producto** (código, categoría, tipo de venta)
- **Tabla de precios** con todos los tipos configurados
- **Cálculo de márgenes** automático
- **Botones de acción** para agregar, editar y eliminar precios

### **Modal de Creación de Precios**
- **Selección de unidad** de medida
- **5 campos de precio** (regular, costo, mayor, detalle, especial)
- **Opciones** (por defecto, activo)
- **Cálculo de margen** en tiempo real
- **Notas adicionales**

### **Modal de Precios Masivos**
- **Tabla completa** con todas las unidades disponibles
- **Plantillas predefinidas** para diferentes tipos de productos
- **Resumen automático** de precios configurados
- **Selección de precio por defecto**

## 🔧 API Endpoints

### **Obtener Precios de un Producto**
```http
GET /product/{productId}/prices/api/prices
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "unit_id": 36,
      "unit_code": "36",
      "unit_name": "Libra",
      "prices": {
        "price": {
          "name": "Precio Regular",
          "value": "0.85",
          "description": "Precio estándar del producto"
        },
        "cost_price": {
          "name": "Precio de Costo",
          "value": "0.65",
          "description": "Precio de compra del producto"
        }
      },
      "is_default": true,
      "is_active": true
    }
  ]
}
```

### **Obtener Precio por Unidad Específica**
```http
GET /product/{productId}/prices/api/unit/{unitCode}
```

### **Crear Nuevo Precio**
```http
POST /product/{productId}/prices/store
```

### **Actualizar Precio**
```http
PUT /product/{productId}/prices/{priceId}
```

### **Eliminar Precio**
```http
DELETE /product/{productId}/prices/{priceId}
```

## 💡 Casos de Uso

### **Ejemplo 1: Comida para Pollos**
- **Producto**: Comida para Pollos Premium
- **Unidades configuradas**:
  - **Saco (59)**: $55.00 (precio regular), $45.00 (costo)
  - **Libra (36)**: $0.85 (precio regular), $0.65 (costo)
  - **Kilogramo (34)**: $1.87 (precio regular), $1.43 (costo)
  - **Dólar (99)**: $1.00 (valor monetario)

### **Ejemplo 2: Fertilizante Líquido**
- **Producto**: Fertilizante Orgánico
- **Unidades configuradas**:
  - **Galón (22)**: $9.45 (precio regular), $6.80 (costo)
  - **Litro (23)**: $2.50 (precio regular), $1.80 (costo)
  - **Mililitro (26)**: $0.0025 (precio regular), $0.0018 (costo)

## 🛠️ Configuración Técnica

### **Modelos Relacionados**
- `Product` → `ProductPrice` (1:N)
- `Unit` → `ProductPrice` (1:N)
- `ProductPrice` → `ProductUnitConversion` (relación indirecta)

### **Validaciones**
- **Precios**: Números positivos con máximo 2 decimales
- **Unidades**: Deben existir en el catálogo de unidades
- **Por defecto**: Solo un precio por defecto por producto
- **Activo**: Control de estado de precios

### **Cálculos Automáticos**
- **Margen de ganancia**: `(precio_venta - precio_costo) / precio_costo * 100`
- **Conversiones**: Integración con sistema de unidades existente
- **Totales**: Cálculo automático en ventas y cotizaciones

## 📊 Reportes y Análisis

### **Información Disponible**
- **Margen por producto** y unidad
- **Comparación de precios** entre unidades
- **Historial de cambios** de precios
- **Análisis de rentabilidad** por tipo de precio

### **Exportación**
- **Lista de precios** por producto
- **Comparativo** de precios por unidad
- **Reporte de márgenes** de ganancia

## 🔒 Seguridad y Permisos

### **Validaciones de Seguridad**
- **CSRF Protection** en todos los formularios
- **Validación de datos** en servidor y cliente
- **Control de acceso** por roles de usuario
- **Auditoría** de cambios de precios

### **Permisos Requeridos**
- **Ver precios**: Acceso básico a productos
- **Crear precios**: Permiso de edición de productos
- **Editar precios**: Permiso de edición de productos
- **Eliminar precios**: Permiso de administración

## 🚀 Próximas Mejoras

### **Funcionalidades Planificadas**
- **Historial de precios** con fechas de vigencia
- **Precios por cliente** específico
- **Precios por temporada** o promociones
- **Sincronización** con sistemas externos
- **Notificaciones** de cambios de precios

### **Integraciones**
- **Sistema de ventas** con selección de tipo de precio
- **Cotizaciones** con precios múltiples
- **Reportes avanzados** de rentabilidad
- **API pública** para consulta de precios

## 📞 Soporte

Para dudas o problemas con el sistema de precios múltiples:

1. **Documentación**: Revisar este archivo y ejemplos
2. **Logs**: Verificar logs de Laravel para errores
3. **Base de datos**: Validar integridad de datos
4. **Desarrollador**: Contactar al equipo de desarrollo

---

**Versión**: 1.0  
**Fecha**: Enero 2025  
**Autor**: Sistema Agroservicio Milagro de Dios
