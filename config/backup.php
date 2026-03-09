<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Respaldos de Base de Datos
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar las opciones para el módulo de respaldos
    | de base de datos de tu aplicación.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Directorio de Respaldos
    |--------------------------------------------------------------------------
    |
    | Directorio donde se almacenarán los archivos de respaldo.
    | Los respaldos se guardarán en storage/app/{backup_path}
    |
    */
    'backup_path' => env('BACKUP_PATH', 'backups'),

    /*
    |--------------------------------------------------------------------------
    | Configuración por Defecto
    |--------------------------------------------------------------------------
    |
    | Configuración predeterminada para los respaldos automáticos.
    |
    */
    'defaults' => [
        'compress' => env('BACKUP_COMPRESS', true),
        'keep_backups' => env('BACKUP_KEEP_COUNT', 7),
        'include_routines' => env('BACKUP_INCLUDE_ROUTINES', true),
        'include_triggers' => env('BACKUP_INCLUDE_TRIGGERS', true),
        'include_events' => env('BACKUP_INCLUDE_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para notificaciones de respaldos.
    |
    */
    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS', false),
        'email' => [
            'enabled' => env('BACKUP_EMAIL_NOTIFICATIONS', false),
            'recipients' => explode(',', env('BACKUP_EMAIL_RECIPIENTS', '')),
        ],
        'slack' => [
            'enabled' => env('BACKUP_SLACK_NOTIFICATIONS', false),
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Programación
    |--------------------------------------------------------------------------
    |
    | Configuración para respaldos automáticos programados.
    |
    */
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
        'frequency' => env('BACKUP_SCHEDULE_FREQUENCY', 'daily'), // daily, weekly, monthly
        'time' => env('BACKUP_SCHEDULE_TIME', '02:00'), // Hora en formato 24h
        'day_of_week' => env('BACKUP_SCHEDULE_DAY_OF_WEEK', 1), // 1 = Lunes, 7 = Domingo
        'day_of_month' => env('BACKUP_SCHEDULE_DAY_OF_MONTH', 1), // 1-31
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad para los respaldos.
    |
    */
    'security' => [
        'encrypt_backups' => env('BACKUP_ENCRYPT', false),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY', ''),
        'require_confirmation' => env('BACKUP_REQUIRE_CONFIRMATION', true),
        'max_file_size' => env('BACKUP_MAX_FILE_SIZE', 1024 * 1024 * 100), // 100MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Limpieza
    |--------------------------------------------------------------------------
    |
    | Configuración para la limpieza automática de respaldos antiguos.
    |
    */
    'cleanup' => [
        'enabled' => env('BACKUP_CLEANUP_ENABLED', true),
        'keep_days' => env('BACKUP_CLEANUP_KEEP_DAYS', 30),
        'keep_count' => env('BACKUP_CLEANUP_KEEP_COUNT', 7),
        'strategy' => env('BACKUP_CLEANUP_STRATEGY', 'count'), // count, days, both
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Almacenamiento Externo
    |--------------------------------------------------------------------------
    |
    | Configuración para copiar respaldos a almacenamiento externo.
    |
    */
    'external_storage' => [
        'enabled' => env('BACKUP_EXTERNAL_STORAGE', false),
        'disk' => env('BACKUP_EXTERNAL_DISK', 's3'),
        'path' => env('BACKUP_EXTERNAL_PATH', 'backups'),
        'delete_local_after_upload' => env('BACKUP_DELETE_LOCAL_AFTER_UPLOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    |
    | Configuración para el registro de actividades de respaldo.
    |
    */
    'logging' => [
        'enabled' => env('BACKUP_LOGGING', true),
        'channel' => env('BACKUP_LOG_CHANNEL', 'daily'),
        'level' => env('BACKUP_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Rendimiento
    |--------------------------------------------------------------------------
    |
    | Configuración para optimizar el rendimiento de los respaldos.
    |
    */
    'performance' => [
        'timeout' => env('BACKUP_TIMEOUT', 300), // 5 minutos
        'memory_limit' => env('BACKUP_MEMORY_LIMIT', '512M'),
        'use_queue' => env('BACKUP_USE_QUEUE', false),
        'queue_name' => env('BACKUP_QUEUE_NAME', 'backups'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Validación
    |--------------------------------------------------------------------------
    |
    | Configuración para validar la integridad de los respaldos.
    |
    */
    'validation' => [
        'enabled' => env('BACKUP_VALIDATION', true),
        'check_file_size' => env('BACKUP_CHECK_FILE_SIZE', true),
        'check_file_integrity' => env('BACKUP_CHECK_FILE_INTEGRITY', true),
        'test_restore' => env('BACKUP_TEST_RESTORE', false),
    ],

];
