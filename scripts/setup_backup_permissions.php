<?php

/**
 * Script para configurar permisos del módulo de respaldos
 *
 * Este script crea automáticamente todos los permisos necesarios
 * para el módulo de respaldos y los asigna al rol de administrador.
 *
 * Uso: php scripts/setup_backup_permissions.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Inicializar Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Configurando permisos del módulo de respaldos...\n\n";

try {
    // Definir permisos del módulo de respaldos
    $permissions = [
        'backups.index' => 'Ver lista de respaldos',
        'backups.create' => 'Crear respaldos de base de datos',
        'backups.download' => 'Descargar respaldos',
        'backups.destroy' => 'Eliminar respaldos',
        'backups.restore' => 'Restaurar respaldos',
        'backups.list' => 'Listar respaldos disponibles',
        'backups.stats' => 'Ver estadísticas de respaldos',
        'backups.scheduled' => 'Gestionar respaldos programados',
        'backups.automated' => 'Configurar respaldos automáticos',
        'backups.compression' => 'Configurar compresión de respaldos',
        'backups.retention' => 'Gestionar política de retención',
        'backups.notifications' => 'Configurar notificaciones de respaldos'
    ];

    $createdPermissions = [];
    $existingPermissions = [];

    echo "📋 Creando permisos...\n";

    foreach ($permissions as $name => $description) {
        // Verificar si el permiso ya existe
        $existingPermission = Permission::where('name', $name)->first();

        if (!$existingPermission) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web'
            ]);
            $createdPermissions[] = $name;
            echo "  ✅ Creado: {$name}\n";
        } else {
            $existingPermissions[] = $name;
            echo "  ⚠️  Ya existe: {$name}\n";
        }
    }

    echo "\n📊 Resumen de permisos:\n";
    echo "  - Creados: " . count($createdPermissions) . "\n";
    echo "  - Existentes: " . count($existingPermissions) . "\n";

    // Asignar permisos al rol de administrador
    echo "\n👤 Asignando permisos al rol de administrador...\n";

    $adminRole = Role::where('name', 'admin')->first();

    if (!$adminRole) {
        // Buscar rol con ID 1 (generalmente es el administrador)
        $adminRole = Role::find(1);
    }

    if ($adminRole) {
        $allBackupsPermissions = Permission::where('name', 'like', 'backups.%')->pluck('name')->toArray();

        // Obtener permisos actuales del rol
        $currentPermissions = $adminRole->permissions->pluck('name')->toArray();

        // Combinar permisos existentes con los nuevos
        $allPermissions = array_unique(array_merge($currentPermissions, $allBackupsPermissions));

        // Asignar todos los permisos al rol
        $adminRole->syncPermissions($allPermissions);

        echo "  ✅ Permisos asignados al rol: {$adminRole->name}\n";
        echo "  📈 Total de permisos del rol: " . count($allPermissions) . "\n";
    } else {
        echo "  ⚠️  No se encontró el rol de administrador\n";
        echo "  💡 Puedes asignar los permisos manualmente desde la interfaz web\n";
    }

    echo "\n🎉 Configuración completada exitosamente!\n";
    echo "\n📝 Próximos pasos:\n";
    echo "  1. Accede al módulo de respaldos en: /backups\n";
    echo "  2. Crea tu primer respaldo de prueba\n";
    echo "  3. Configura respaldos automáticos si es necesario\n";
    echo "  4. Asigna permisos específicos a otros roles según sea necesario\n";

} catch (Exception $e) {
    echo "\n❌ Error durante la configuración: " . $e->getMessage() . "\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
