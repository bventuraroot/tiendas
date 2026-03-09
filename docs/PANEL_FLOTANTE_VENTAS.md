# Panel Flotante de Información de Productos - Módulo de Ventas

## Descripción

Se ha implementado un panel flotante mejorado para el módulo de ventas que muestra información detallada sobre conversión de unidades, stock disponible y validaciones de productos. Este panel reemplaza la información estática anterior y proporciona una experiencia de usuario más dinámica e interactiva.

## Características Principales

### 🎯 Panel Flotante Interactivo
- **Posición fija**: Se mantiene visible al lado derecho de la pantalla
- **Animaciones suaves**: Transiciones elegantes al mostrar/ocultar
- **Colapsable**: Se puede minimizar para ahorrar espacio
- **Responsive**: Se adapta a diferentes tamaños de pantalla

### 📊 Información Organizada
El panel se divide en tres secciones principales:

#### 1. Conversión de Unidades
- Factor de conversión seleccionado
- Cantidad base necesaria
- Precio por unidad
- Subtotal calculado
- Equivalencias en otras unidades

#### 2. Stock Disponible
- Stock actual en la unidad seleccionada
- Unidad base del producto
- Stock mínimo y máximo
- Estado de disponibilidad
- Stock por diferentes unidades

#### 3. Validaciones
- Cantidad solicitada vs stock disponible
- Stock después de la venta
- Alertas de stock bajo
- Validaciones de disponibilidad
- Estado de la venta

## Funcionalidades

### Controles del Panel
- **Botón flotante**: Muestra/oculta el panel
- **Botón de colapso**: Minimiza el panel a una barra lateral
- **Cierre automático**: Se cierra al hacer clic fuera o presionar Escape
- **Scroll interno**: Para contenido extenso

### Integración con el Sistema
- **Actualización automática**: Se actualiza al seleccionar productos y unidades
- **Cálculos en tiempo real**: Recalcula al cambiar cantidades o precios
- **Limpieza automática**: Se limpia al cambiar de producto

## Archivos Modificados

### Nuevos Archivos Creados
1. `public/assets/js/sales-floating-panel.js` - Lógica del panel flotante
2. `public/css/sales-floating-panel.css` - Estilos específicos del panel
3. `docs/PANEL_FLOTANTE_VENTAS.md` - Esta documentación

### Archivos Modificados
1. `resources/views/sales/create.blade.php` - Estructura HTML del panel
2. `public/assets/js/sales-units.js` - Integración con el panel flotante

## Uso del Panel

### Para el Usuario Final

1. **Acceder al panel**:
   - Hacer clic en el botón flotante azul en el lado derecho
   - El panel aparecerá con animación suave

2. **Navegar por la información**:
   - Seleccionar un producto y unidad
   - La información se actualizará automáticamente
   - Hacer scroll dentro del panel si es necesario

3. **Minimizar el panel**:
   - Hacer clic en el botón de colapso (flecha)
   - El panel se reducirá a una barra lateral

4. **Cerrar el panel**:
   - Hacer clic fuera del panel
   - Presionar la tecla Escape
   - Hacer clic nuevamente en el botón flotante

### Para Desarrolladores

#### API del Panel Flotante
```javascript
// Actualizar información del panel
window.SalesFloatingPanel.updatePanelInfo(productData);

// Limpiar información del panel
window.SalesFloatingPanel.clearPanelInfo();

// Mostrar panel
window.SalesFloatingPanel.showPanel();

// Ocultar panel
window.SalesFloatingPanel.hidePanel();

// Alternar visibilidad
window.SalesFloatingPanel.togglePanel();
```

#### Estructura de Datos
```javascript
const productData = {
    conversion: {
        unit_name: "Libra",
        conversion_factor: 1.0,
        equivalent_units: [...]
    },
    stock: {
        available_quantity: 100,
        base_unit: "libras",
        min_stock: 10,
        max_stock: 1000,
        inventory_by_unit: [...]
    },
    product: {
        // Información del producto
    }
};
```

## Responsive Design

### Desktop (>1200px)
- Panel de 380px de ancho
- Posición fija en el lado derecho
- Altura máxima del 90% de la ventana

### Tablet (768px - 1200px)
- Panel de 320px de ancho
- Mantiene la funcionalidad completa

### Mobile (<768px)
- Panel ocupa toda la pantalla
- Botón de toggle en la esquina superior derecha
- Optimizado para touch

## Personalización

### Colores y Temas
Los colores se pueden personalizar modificando las variables CSS en `sales-floating-panel.css`:

```css
/* Colores principales */
--panel-primary: #667eea;
--panel-secondary: #764ba2;
--success-color: #28a745;
--warning-color: #ffc107;
--danger-color: #dc3545;
```

### Animaciones
Las animaciones se pueden ajustar modificando las propiedades de transición:

```css
.floating-info-panel {
    transition: all 0.3s ease; /* Duración y timing */
}
```

## Mejoras Futuras

### Funcionalidades Planificadas
1. **Persistencia de estado**: Recordar si el panel estaba abierto/cerrado
2. **Filtros avanzados**: Filtrar información por categorías
3. **Exportación**: Exportar información a PDF o Excel
4. **Notificaciones**: Alertas push para cambios de stock
5. **Historial**: Ver cambios recientes en stock

### Optimizaciones Técnicas
1. **Lazy loading**: Cargar información bajo demanda
2. **Caché**: Almacenar datos frecuentemente consultados
3. **WebSockets**: Actualizaciones en tiempo real
4. **Service Workers**: Funcionalidad offline

## Troubleshooting

### Problemas Comunes

1. **Panel no aparece**:
   - Verificar que los archivos JS y CSS estén cargados
   - Revisar la consola del navegador para errores
   - Confirmar que jQuery esté disponible

2. **Información no se actualiza**:
   - Verificar que la función `updatePanelInfo` se esté llamando
   - Revisar la estructura de datos enviada
   - Confirmar que los elementos HTML existan

3. **Problemas de responsive**:
   - Verificar las media queries en el CSS
   - Probar en diferentes dispositivos
   - Revisar el viewport meta tag

### Debug
```javascript
// Habilitar logs de debug
console.log('Panel state:', window.SalesFloatingPanel);
console.log('Current data:', currentProductData);
```

## Contribución

Para contribuir al desarrollo del panel flotante:

1. Seguir las convenciones de código existentes
2. Probar en diferentes navegadores y dispositivos
3. Documentar nuevas funcionalidades
4. Mantener la compatibilidad con el sistema existente

## Licencia

Este módulo es parte del sistema Agroservicio Milagro de Dios y sigue las mismas políticas de licenciamiento del proyecto principal.
