<?php
/**
 * Script para sincronizar permisos desde las rutas del sistema
 * 
 * Uso:
 * php scripts/sync-permissions-from-routes.php
 * 
 * O ejecutar desde el navegador:
 * http://tu-dominio/scripts/sync-permissions-from-routes.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Route;

echo "🔄 Sincronizando permisos desde las rutas...\n\n";

$routes = Route::getRoutes();
$permissions = [];
$created = 0;
$existing = 0;
$skipped = 0;

// Rutas a ignorar
$ignoredRoutes = [
    'login', 'logout', 'register', 'password', 'verification',
    'sanctum', 'ignition', 'profile', 'dashboard',
    'api.', 'generated::', 'debugbar', 'horizon'
];

foreach ($routes as $route) {
    $routeName = $route->getName();
    
    // Saltar si no tiene nombre
    if (!$routeName) {
        $skipped++;
        continue;
    }
    
    // Saltar si está en la lista de ignorados
    $shouldSkip = false;
    foreach ($ignoredRoutes as $ignored) {
        if (strpos($routeName, $ignored) === 0) {
            $shouldSkip = true;
            break;
        }
    }
    
    if ($shouldSkip) {
        $skipped++;
        continue;
    }

    // Verificar si el permiso ya existe
    $existingPermission = Permission::where('name', $routeName)->first();

    if (!$existingPermission) {
        Permission::create([
            'name' => $routeName,
            'guard_name' => 'web'
        ]);
        $created++;
        $parts = explode('.', $routeName);
        $module = $parts[0];
        if (!isset($permissions[$module])) {
            $permissions[$module] = [];
        }
        $permissions[$module][] = $routeName;
        echo "✅ Creado: {$routeName}\n";
    } else {
        $existing++;
    }
}

echo "\n";
echo "📊 Resumen:\n";
echo "• Permisos creados: {$created}\n";
echo "• Permisos existentes: {$existing}\n";
echo "• Rutas ignoradas: {$skipped}\n";
echo "• Total procesado: " . ($created + $existing + $skipped) . "\n";

if ($created > 0) {
    echo "\n";
    echo "📋 Permisos creados por módulo:\n";
    foreach ($permissions as $module => $modulePermissions) {
        echo "  • {$module}: " . count($modulePermissions) . " permisos\n";
    }
}

echo "\n";
echo "✅ ¡Sincronización completada!\n";
echo "\n";
echo "Ahora puedes asignar estos permisos a los roles desde:\n";
echo "- /rol/index (interfaz web)\n";
echo "- O usando el método syncPermissions() en código\n";




