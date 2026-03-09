# Configuración de Cron Job para Respaldos Automáticos

Este documento explica cómo configurar respaldos automáticos de base de datos usando cron jobs.

## Configuración Básica

### 1. Editar Crontab

```bash
# Abrir el editor de crontab
crontab -e
```

### 2. Agregar Entrada de Cron

Agrega la siguiente línea al final del archivo:

```bash
# Ejecutar respaldos programados cada minuto
* * * * * cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /dev/null 2>&1
```

### 3. Verificar Configuración

```bash
# Ver las entradas de cron actuales
crontab -l

# Verificar que el cron está ejecutándose
sudo systemctl status cron
```

## Configuraciones Avanzadas

### Respaldo Diario a las 2:00 AM

```bash
# Solo ejecutar a las 2:00 AM todos los días
0 2 * * * cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /dev/null 2>&1
```

### Respaldo Semanal (Domingos a las 3:00 AM)

```bash
# Ejecutar solo los domingos a las 3:00 AM
0 3 * * 0 cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /dev/null 2>&1
```

### Respaldo Mensual (Primer día del mes a las 1:00 AM)

```bash
# Ejecutar el primer día de cada mes a la 1:00 AM
0 1 1 * * cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /dev/null 2>&1
```

## Configuración con Logs

### Con Logs Detallados

```bash
# Guardar logs en archivo específico
* * * * * cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /var/log/backup.log 2>&1
```

### Con Rotación de Logs

```bash
# Usar logrotate para gestionar logs
* * * * * cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled >> /var/log/backup.log 2>&1
```

Crear archivo `/etc/logrotate.d/backup`:

```
/var/log/backup.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## Configuración en Diferentes Sistemas

### Ubuntu/Debian

```bash
# Instalar cron si no está instalado
sudo apt-get update
sudo apt-get install cron

# Habilitar y iniciar el servicio
sudo systemctl enable cron
sudo systemctl start cron

# Editar crontab del usuario web
sudo crontab -u www-data -e
```

### CentOS/RHEL

```bash
# Instalar cronie
sudo yum install cronie

# Habilitar y iniciar el servicio
sudo systemctl enable crond
sudo systemctl start crond

# Editar crontab
sudo crontab -e
```

### macOS

```bash
# macOS tiene cron preinstalado
# Editar crontab
crontab -e

# Verificar que cron está ejecutándose
sudo launchctl list | grep cron
```

## Variables de Entorno

### Configurar PATH

Si tienes problemas con el PATH, especifica la ruta completa:

```bash
# Con PATH completo
* * * * * cd /ruta/completa/a/tu/proyecto && /usr/bin/php artisan backup:scheduled >> /dev/null 2>&1
```

### Configurar Variables de Entorno

```bash
# Con variables de entorno específicas
* * * * * cd /ruta/completa/a/tu/proyecto && DB_CONNECTION=mysql php artisan backup:scheduled >> /dev/null 2>&1
```

## Monitoreo y Debugging

### Verificar que el Cron se Ejecuta

```bash
# Ver logs del sistema
sudo tail -f /var/log/syslog | grep CRON

# Ver logs específicos de backup
tail -f /var/log/backup.log
```

### Probar Comando Manualmente

```bash
# Ir al directorio del proyecto
cd /ruta/completa/a/tu/proyecto

# Ejecutar comando manualmente
php artisan backup:scheduled

# Verificar salida
echo $?
```

### Verificar Permisos

```bash
# Verificar permisos del directorio
ls -la /ruta/completa/a/tu/proyecto

# Verificar permisos de storage
ls -la /ruta/completa/a/tu/proyecto/storage/app/backups

# Ajustar permisos si es necesario
chmod 755 /ruta/completa/a/tu/proyecto/storage/app/backups
chown www-data:www-data /ruta/completa/a/tu/proyecto/storage/app/backups
```

## Configuración de Notificaciones

### Habilitar Notificaciones en .env

```env
# Notificaciones básicas
BACKUP_NOTIFICATIONS=true

# Notificaciones por email
BACKUP_EMAIL_NOTIFICATIONS=true
BACKUP_EMAIL_RECIPIENTS=admin@example.com,backup@example.com

# Notificaciones por Slack
BACKUP_SLACK_NOTIFICATIONS=true
BACKUP_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

## Solución de Problemas

### Error: "Permission denied"

```bash
# Verificar usuario del cron
whoami

# Cambiar a usuario correcto
sudo crontab -u www-data -e

# O agregar usuario al comando
* * * * * su - www-data -c "cd /ruta/completa/a/tu/proyecto && php artisan backup:scheduled"
```

### Error: "Command not found"

```bash
# Usar ruta completa a PHP
which php

# Agregar al cron
* * * * * cd /ruta/completa/a/tu/proyecto && /usr/bin/php artisan backup:scheduled
```

### Error: "Database connection failed"

```bash
# Verificar configuración de base de datos
php artisan config:show database

# Probar conexión manualmente
php artisan tinker
DB::connection()->getPdo();
```

## Ejemplos Completos

### Configuración de Producción

```bash
# Respaldo diario a las 2:00 AM con logs
0 2 * * * cd /var/www/html/mi-proyecto && /usr/bin/php artisan backup:scheduled >> /var/log/backup.log 2>&1

# Limpiar logs antiguos semanalmente
0 3 * * 0 find /var/log/backup.log -mtime +30 -delete
```

### Configuración de Desarrollo

```bash
# Respaldo cada hora en desarrollo
0 * * * * cd /home/usuario/proyectos/mi-proyecto && php artisan backup:scheduled
```

### Configuración con Múltiples Entornos

```bash
# Respaldo de producción
0 2 * * * cd /var/www/html/produccion && APP_ENV=production php artisan backup:scheduled

# Respaldo de staging
0 3 * * * cd /var/www/html/staging && APP_ENV=staging php artisan backup:scheduled
```

## Seguridad

### Consideraciones de Seguridad

1. **Permisos mínimos**: Usar solo los permisos necesarios
2. **Logs seguros**: No incluir información sensible en logs
3. **Usuario dedicado**: Usar un usuario específico para cron jobs
4. **Monitoreo**: Revisar logs regularmente
5. **Backup de configuración**: Mantener copia de la configuración de cron

### Usuario Dedicado

```bash
# Crear usuario para respaldos
sudo useradd -r -s /bin/false backup-user

# Asignar permisos
sudo chown -R backup-user:backup-user /ruta/completa/a/tu/proyecto/storage/app/backups

# Configurar cron para el usuario
sudo crontab -u backup-user -e
```
