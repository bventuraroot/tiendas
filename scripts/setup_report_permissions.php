<?php
/**
 * Script para configurar permisos de reportes
 *
 * Este script crea todos los permisos necesarios para el módulo de reportes
 * y los asigna a roles específicos.
 *
 * Uso:
 * php scripts/setup_report_permissions.php
 *
 * O desde la interfaz web:
 * POST /permission/create-reports-permissions
 * POST /permission/assign-reports-permissions
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Configurar Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "=== CONFIGURACIÓN DE PERMISOS DE REPORTES ===\n\n";

try {
    // Definir permisos de reportes
    $permissions = [
        // Permisos existentes de reportes
        'report.sales' => 'Ver reporte de ventas',
        'report.purchases' => 'Ver reporte de compras',
        'report.contribuyentes' => 'Ver reporte de contribuyentes',
        'report.consumidor' => 'Ver reporte de consumidor final',
        'report.bookpurchases' => 'Ver libro de compras',
        'report.reportyear' => 'Ver reporte anual',
        'report.inventory' => 'Ver reporte de inventario',
        'report.sales-by-client' => 'Ver reporte de ventas por cliente',

        // Nuevos permisos de reportes
        'report.sales-by-provider' => 'Ver reporte de ventas por proveedor',
        'report.sales-analysis' => 'Ver análisis general de ventas',
        'report.sales-by-product' => 'Ver reporte de ventas por producto',
        'report.sales-by-category' => 'Ver reporte de ventas por categoría',
        'report.inventory-by-category' => 'Ver reporte de inventario por categoría',
        'report.inventory-by-provider' => 'Ver reporte de inventario por proveedor',

        // Permisos de búsqueda
        'report.sales-by-provider-search' => 'Buscar en reporte de ventas por proveedor',
        'report.sales-analysis-search' => 'Buscar en análisis general de ventas',
        'report.sales-by-product-search' => 'Buscar en reporte de ventas por producto',
        'report.sales-by-category-search' => 'Buscar en reporte de ventas por categoría',
        'report.inventory-search' => 'Buscar en reporte de inventario',

        // Permisos de exportación
        'report.export-excel' => 'Exportar reportes a Excel',
        'report.export-pdf' => 'Exportar reportes a PDF'
    ];

    $createdPermissions = [];
    $existingPermissions = [];

    echo "Creando permisos de reportes...\n";

    foreach ($permissions as $name => $description) {
        // Verificar si el permiso ya existe
        $existingPermission = Permission::where('name', $name)->first();

        if (!$existingPermission) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web'
            ]);
            $createdPermissions[] = $name;
            echo "✓ Creado: {$name}\n";
        } else {
            $existingPermissions[] = $name;
            echo "- Ya existe: {$name}\n";
        }
    }

    echo "\n=== RESUMEN DE PERMISOS ===\n";
    echo "Permisos creados: " . count($createdPermissions) . "\n";
    echo "Permisos existentes: " . count($existingPermissions) . "\n";
    echo "Total procesados: " . count($permissions) . "\n";

    if (count($createdPermissions) > 0) {
        echo "\nPermisos creados:\n";
        foreach ($createdPermissions as $permission) {
            echo "  - {$permission}\n";
        }
    }

    // Asignar permisos a roles
    echo "\n=== ASIGNACIÓN A ROLES ===\n";

    $roles = Role::all();
    foreach ($roles as $role) {
        echo "\nProcesando rol: {$role->name}\n";

        // Obtener permisos de reportes
        $reportsPermissions = Permission::where('name', 'like', 'report.%')->pluck('name')->toArray();

        // Asignar permisos al rol (mantener permisos existentes)
        $currentPermissions = $role->permissions->pluck('name')->toArray();
        $allPermissions = array_unique(array_merge($currentPermissions, $reportsPermissions));

        $role->syncPermissions($allPermissions);

        echo "✓ Asignados " . count($reportsPermissions) . " permisos de reportes al rol '{$role->name}'\n";
    }

    echo "\n=== CONFIGURACIÓN COMPLETADA ===\n";
    echo "✓ Todos los permisos de reportes han sido configurados correctamente\n";
    echo "✓ Los permisos han sido asignados a todos los roles existentes\n";
    echo "\nLos usuarios ahora pueden acceder a los reportes según su rol asignado.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    exit(1);
}
