<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

class SyncPermissionsFromRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-routes {--force : Forzar creación de todos los permisos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar permisos desde las rutas del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Sincronizando permisos desde las rutas...');
        $this->newLine();

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
            
            // Saltar si no tiene nombre o está en la lista de ignorados
            if (!$routeName || $this->shouldSkip($routeName, $ignoredRoutes)) {
                $skipped++;
                continue;
            }

            // Extraer el módulo y acción
            $parts = explode('.', $routeName);
            $module = $parts[0];
            $action = isset($parts[1]) ? $parts[1] : 'index';

            // Construir el nombre del permiso
            $permissionName = $routeName;

            // Verificar si el permiso ya existe
            $existingPermission = Permission::where('name', $permissionName)->first();

            if (!$existingPermission) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                $created++;
                $permissions[$module][] = $permissionName;
                $this->line("✅ Creado: {$permissionName}");
            } else {
                $existing++;
                if (!$this->option('force')) {
                    $this->line("⚠️  Ya existe: {$permissionName}");
                }
            }
        }

        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("• Permisos creados: {$created}");
        $this->line("• Permisos existentes: {$existing}");
        $this->line("• Rutas ignoradas: {$skipped}");
        $this->line("• Total procesado: " . ($created + $existing + $skipped));

        if ($created > 0) {
            $this->newLine();
            $this->info('📋 Permisos creados por módulo:');
            foreach ($permissions as $module => $modulePermissions) {
                $this->line("  • {$module}: " . count($modulePermissions) . " permisos");
            }
        }

        $this->newLine();
        $this->info('✅ ¡Sincronización completada!');
        
        return Command::SUCCESS;
    }

    /**
     * Determinar si una ruta debe ser ignorada
     */
    private function shouldSkip($routeName, $ignoredRoutes)
    {
        foreach ($ignoredRoutes as $ignored) {
            if (str_starts_with($routeName, $ignored)) {
                return true;
            }
        }
        return false;
    }
}




