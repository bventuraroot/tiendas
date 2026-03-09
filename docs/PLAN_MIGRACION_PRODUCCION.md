# Plan de Migración a Producción

## Resumen de Cambios
- **3 nuevas migraciones** de base de datos
- **2 nuevos archivos** de validación de requests
- **Múltiples archivos modificados** en controllers, models y frontend

## 🚨 ANTES DE EMPEZAR - RESPALDO OBLIGATORIO

### 1. Crear Respaldo Completo de la Base de Datos
```bash
# En el servidor de producción
mysqldump -u [usuario] -p[password] [nombre_bd] > backup_pre_migracion_$(date +%Y%m%d_%H%M%S).sql

# O si usas Docker:
docker exec [container_mysql] mysqldump -u [usuario] -p[password] [nombre_bd] > backup_pre_migracion_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Verificar Respaldo
```bash
# Comprobar que el archivo se creó y tiene contenido
ls -lh backup_pre_migracion_*.sql
head -20 backup_pre_migracion_*.sql
```

## 📋 PASOS DE MIGRACIÓN

### Paso 1: Preparar Archivos en Producción

1. **Subir código actualizado:**
```bash
# En tu repositorio local
git add .
git commit -m "feat: database migrations and provider validation"
git push origin main

# En producción
git pull origin main
```

2. **Verificar archivos nuevos:**
```bash
# Verificar que las migraciones estén presentes
ls -la database/migrations/2025_08_15_*

# Verificar nuevos requests
ls -la app/Http/Requests/Provider*Request.php
```

### Paso 2: Revisar Estado Actual de la BD

```bash
# Verificar migraciones pendientes
php artisan migrate:status

# O si usas Docker:
docker exec [container_php] php artisan migrate:status
```

### Paso 3: Ejecutar Migraciones (CON CUIDADO)

**⚠️ IMPORTANTE: Ejecutar UNA POR UNA y verificar cada paso**

```bash
# Migración 1: Agregar campos a inventory
docker exec [container_php] php artisan migrate --path=/database/migrations/2025_08_15_221307_add_sku_to_inventory_if_not_exists.php

# Verificar estructura de tabla inventory
docker exec [container_mysql] mysql -u [usuario] -p[password] [bd] -e "DESCRIBE inventory;"

# Migración 2: Restricciones únicas en providers
docker exec [container_php] php artisan migrate --path=/database/migrations/2025_08_15_230215_add_unique_constraints_to_providers_table.php

# Verificar índices de providers
docker exec [container_mysql] mysql -u [usuario] -p[password] [bd] -e "SHOW INDEX FROM providers;"

# Migración 3: Restricción única en products
docker exec [container_php] php artisan migrate --path=/database/migrations/2025_08_15_230510_add_unique_constraint_to_products_code.php

# Verificar índices de products
docker exec [container_mysql] mysql -u [usuario] -p[password] [bd] -e "SHOW INDEX FROM products;"
```

### Paso 4: Verificaciones Post-Migración

```bash
# 1. Verificar estado de migraciones
docker exec [container_php] php artisan migrate:status

# 2. Verificar que la aplicación funciona
curl -I http://[tu-dominio]/

# 3. Probar funcionalidades clave:
# - Crear/editar proveedor
# - Crear/editar producto
# - Gestión de inventario
```

### Paso 5: Limpiar Cache y Optimizar

```bash
# Limpiar caches
docker exec [container_php] php artisan config:clear
docker exec [container_php] php artisan cache:clear
docker exec [container_php] php artisan route:clear
docker exec [container_php] php artisan view:clear

# Regenerar caches optimizados
docker exec [container_php] php artisan config:cache
docker exec [container_php] php artisan route:cache
docker exec [container_php] php artisan view:cache
```

## 🔄 PLAN DE ROLLBACK (Si algo sale mal)

### Rollback de Migraciones (en orden inverso):
```bash
# Rollback migración 3
docker exec [container_php] php artisan migrate:rollback --path=/database/migrations/2025_08_15_230510_add_unique_constraint_to_products_code.php

# Rollback migración 2
docker exec [container_php] php artisan migrate:rollback --path=/database/migrations/2025_08_15_230215_add_unique_constraints_to_providers_table.php

# Rollback migración 1
docker exec [container_php] php artisan migrate:rollback --path=/database/migrations/2025_08_15_221307_add_sku_to_inventory_if_not_exists.php
```

### Restaurar desde Respaldo (último recurso):
```bash
# Detener aplicación
docker-compose down

# Restaurar base de datos
mysql -u [usuario] -p[password] [nombre_bd] < backup_pre_migracion_[fecha].sql

# O con Docker:
docker exec -i [container_mysql] mysql -u [usuario] -p[password] [nombre_bd] < backup_pre_migracion_[fecha].sql

# Reiniciar aplicación
docker-compose up -d
```

## ⚠️ POSIBLES PROBLEMAS Y SOLUCIONES

### Problema 1: Datos Duplicados en Providers
**Error:** `Duplicate entry for key 'providers_ncr_unique'`

**Solución:**
```sql
-- Buscar duplicados en NCR
SELECT ncr, COUNT(*) FROM providers WHERE ncr IS NOT NULL GROUP BY ncr HAVING COUNT(*) > 1;

-- Buscar duplicados en NIT
SELECT nit, COUNT(*) FROM providers WHERE nit IS NOT NULL GROUP BY nit HAVING COUNT(*) > 1;

-- Limpiar duplicados manualmente antes de aplicar la migración
```

### Problema 2: Datos Duplicados en Products
**Error:** `Duplicate entry for key 'products_code_unique'`

**Solución:**
```sql
-- Buscar duplicados en code
SELECT code, COUNT(*) FROM products GROUP BY code HAVING COUNT(*) > 1;

-- Limpiar duplicados manualmente
```

### Problema 3: Columnas Faltantes en Inventory
**Error:** Las columnas ya existen o conflictos de tipo

**La migración maneja esto automáticamente con `Schema::hasColumn()`**

## 📝 CHECKLIST PRE-MIGRACIÓN

- [ ] ✅ Respaldo de base de datos creado
- [ ] ✅ Respaldo verificado (archivo existe y tiene contenido)
- [ ] ✅ Código actualizado en producción
- [ ] ✅ Verificar que no hay usuarios activos críticos
- [ ] ✅ Notificar a usuarios sobre mantenimiento (si es necesario)
- [ ] ✅ Tener acceso SSH/Docker al servidor
- [ ] ✅ Tener credenciales de base de datos a mano

## 📝 CHECKLIST POST-MIGRACIÓN

- [ ] ✅ Todas las migraciones aplicadas exitosamente
- [ ] ✅ Estructura de tablas verificada
- [ ] ✅ Aplicación responde correctamente
- [ ] ✅ Funcionalidades clave probadas
- [ ] ✅ Cache limpiado y regenerado
- [ ] ✅ Logs revisados para errores
- [ ] ✅ Notificar a usuarios que el mantenimiento terminó

## 🕐 TIEMPO ESTIMADO
- **Respaldo:** 5-10 minutos
- **Migraciones:** 5-15 minutos
- **Verificaciones:** 10-15 minutos
- **Total:** 20-40 minutos

## 📞 CONTACTOS DE EMERGENCIA
- Desarrollador: [Tu información]
- Administrador del servidor: [Información]
- Usuario clave para pruebas: [Información]
