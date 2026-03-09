# Módulo de Respaldos de Base de Datos

Este módulo proporciona una solución completa para gestionar respaldos de base de datos en la aplicación Laravel, incluyendo creación, restauración, programación automática y gestión desde la interfaz web.

## Características

- ✅ **Creación de respaldos** con soporte para MySQL, PostgreSQL y SQLite
- ✅ **Compresión automática** de archivos para ahorrar espacio
- ✅ **Restauración de respaldos** con confirmación de seguridad
- ✅ **Interfaz web moderna** para gestión completa
- ✅ **Programación automática** de respaldos
- ✅ **Notificaciones** por email y Slack
- ✅ **Limpieza automática** de respaldos antiguos
- ✅ **Logs detallados** de todas las operaciones
- ✅ **Configuración flexible** mediante archivo de configuración

## Instalación

### 1. Comandos Artisan Disponibles

El módulo incluye los siguientes comandos:

```bash
# Crear un respaldo
php artisan backup:database [--compress] [--keep=7] [--path=backups]

# Restaurar un respaldo
php artisan backup:restore {filename} [--force] [--path=backups]

# Listar respaldos disponibles
php artisan backup:list [--format=table] [--path=backups]

# Ejecutar respaldo programado
php artisan backup:scheduled
```

### 2. Configuración

El módulo utiliza el archivo `config/backup.php` para su configuración. Puedes personalizar las siguientes opciones:

```php
// Configuración básica
'backup_path' => env('BACKUP_PATH', 'backups'),

// Configuración por defecto
'defaults' => [
    'compress' => env('BACKUP_COMPRESS', true),
    'keep_backups' => env('BACKUP_KEEP_COUNT', 7),
    'include_routines' => env('BACKUP_INCLUDE_ROUTINES', true),
    'include_triggers' => env('BACKUP_INCLUDE_TRIGGERS', true),
    'include_events' => env('BACKUP_INCLUDE_EVENTS', true),
],

// Programación automática
'schedule' => [
    'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
    'frequency' => env('BACKUP_SCHEDULE_FREQUENCY', 'daily'),
    'time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
],
```

### 3. Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Configuración básica
BACKUP_PATH=backups
BACKUP_COMPRESS=true
BACKUP_KEEP_COUNT=7

# Programación automática
BACKUP_SCHEDULE_ENABLED=true
BACKUP_SCHEDULE_FREQUENCY=daily
BACKUP_SCHEDULE_TIME=02:00

# Notificaciones
BACKUP_NOTIFICATIONS=false
BACKUP_EMAIL_NOTIFICATIONS=false
BACKUP_EMAIL_RECIPIENTS=admin@example.com
BACKUP_SLACK_NOTIFICATIONS=false
BACKUP_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

## Uso

### Interfaz Web

Accede al módulo de respaldos en: `/backups`

La interfaz web incluye:

- **Panel de estadísticas** con información de respaldos
- **Panel de control** para crear nuevos respaldos
- **Lista de respaldos** con acciones de descarga, restauración y eliminación
- **Confirmaciones de seguridad** para operaciones críticas

### Comandos de Línea

#### Crear un Respaldo

```bash
# Respaldo básico
php artisan backup:database

# Respaldo comprimido
php artisan backup:database --compress

# Respaldo manteniendo solo 5 archivos
php artisan backup:database --keep=5

# Respaldo en directorio personalizado
php artisan backup:database --path=custom-backups
```

#### Restaurar un Respaldo

```bash
# Restaurar con confirmación
php artisan backup:restore backup_mysql_2024-01-15_14-30-00.sql

# Restaurar sin confirmación
php artisan backup:restore backup_mysql_2024-01-15_14-30-00.sql --force
```

#### Listar Respaldos

```bash
# Lista en formato tabla
php artisan backup:list

# Lista en formato JSON
php artisan backup:list --format=json

# Lista en formato CSV
php artisan backup:list --format=csv
```

### Programación Automática

Para habilitar respaldos automáticos:

1. **Configurar el cron job** en tu servidor:

```bash
# Editar crontab
crontab -e

# Agregar la siguiente línea para ejecutar cada minuto
* * * * * cd /path/to/your/project && php artisan backup:scheduled >> /dev/null 2>&1
```

2. **Habilitar en la configuración**:

```env
BACKUP_SCHEDULE_ENABLED=true
BACKUP_SCHEDULE_FREQUENCY=daily
BACKUP_SCHEDULE_TIME=02:00
```

### Notificaciones

#### Email

```env
BACKUP_NOTIFICATIONS=true
BACKUP_EMAIL_NOTIFICATIONS=true
BACKUP_EMAIL_RECIPIENTS=admin@example.com,backup@example.com
```

#### Slack

```env
BACKUP_NOTIFICATIONS=true
BACKUP_SLACK_NOTIFICATIONS=true
BACKUP_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

## Estructura de Archivos

```
app/
├── Console/Commands/
│   ├── BackupDatabase.php      # Comando principal de respaldo
│   ├── RestoreDatabase.php     # Comando de restauración
│   ├── ListBackups.php         # Comando para listar respaldos
│   └── ScheduledBackup.php     # Comando de respaldos programados
├── Http/Controllers/
│   └── BackupController.php    # Controlador web
└── ...

resources/views/
└── backups/
    └── index.blade.php         # Vista principal

config/
└── backup.php                  # Configuración del módulo

routes/
└── web.php                     # Rutas del módulo
```

## API Endpoints

### Web Routes

- `GET /backups` - Página principal
- `POST /backups/create` - Crear respaldo
- `GET /backups/download/{filename}` - Descargar respaldo
- `DELETE /backups/destroy/{filename}` - Eliminar respaldo
- `POST /backups/restore/{filename}` - Restaurar respaldo
- `GET /backups/list` - Lista de respaldos (JSON)
- `GET /backups/stats` - Estadísticas (JSON)

## Seguridad

### Consideraciones Importantes

1. **Permisos de archivos**: Asegúrate de que el directorio de respaldos tenga permisos adecuados
2. **Acceso web**: El módulo requiere autenticación
3. **Confirmaciones**: Las operaciones críticas requieren confirmación
4. **Logs**: Todas las operaciones se registran en los logs

### Recomendaciones

- Configura respaldos automáticos en horarios de bajo tráfico
- Monitorea el espacio en disco regularmente
- Prueba la restauración de respaldos periódicamente
- Mantén copias de respaldo en ubicaciones externas
- Configura notificaciones para estar al tanto del estado de los respaldos

## Solución de Problemas

### Errores Comunes

#### Error: "mysqldump command not found"
```bash
# Instalar mysqldump en Ubuntu/Debian
sudo apt-get install mysql-client

# En macOS con Homebrew
brew install mysql-client
```

#### Error: "Permission denied"
```bash
# Verificar permisos del directorio
chmod 755 storage/app/backups
chown www-data:www-data storage/app/backups
```

#### Error: "Database connection failed"
- Verificar configuración de base de datos en `.env`
- Asegurar que las credenciales sean correctas
- Verificar que el usuario tenga permisos de respaldo

### Logs

Los logs del módulo se encuentran en:
- `storage/logs/laravel.log` - Logs generales
- `storage/logs/backup.log` - Logs específicos de respaldos (si está configurado)

### Verificación de Respaldos

```bash
# Verificar que el archivo existe
ls -la storage/app/backups/

# Verificar tamaño del archivo
du -h storage/app/backups/*.sql*

# Verificar integridad (para archivos comprimidos)
gunzip -t storage/app/backups/*.gz
```

## Personalización

### Agregar Nuevos Drivers de Base de Datos

Para agregar soporte para una nueva base de datos, modifica el método `executeBackup()` en `BackupDatabase.php`:

```php
case 'tu_nueva_db':
    return $this->backupTuNuevaDB($config, $filepath);
```

### Personalizar la Interfaz Web

La vista principal se encuentra en `resources/views/backups/index.blade.php`. Puedes personalizar:

- Estilos CSS
- Funcionalidad JavaScript
- Layout y diseño
- Campos adicionales

### Agregar Nuevas Notificaciones

Para agregar nuevos canales de notificación, modifica el método `sendNotifications()` en `ScheduledBackup.php`.

## Contribución

Para contribuir al módulo:

1. Fork el repositorio
2. Crea una rama para tu feature
3. Implementa los cambios
4. Agrega tests si es necesario
5. Envía un pull request

## Licencia

Este módulo está bajo la misma licencia que el proyecto principal.

## Soporte

Para soporte técnico o preguntas:

- Revisa los logs del sistema
- Consulta la documentación de Laravel
- Abre un issue en el repositorio del proyecto
