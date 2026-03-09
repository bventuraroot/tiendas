<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupReportPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:setup-permissions
                            {--assign-to-role= : Asignar permisos a un rol específico}
                            {--list-roles : Listar roles disponibles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear y configurar permisos para el módulo de reportes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== CONFIGURACIÓN DE PERMISOS DE REPORTES ===');
        $this->newLine();

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

            $this->info('Creando permisos de reportes...');

            foreach ($permissions as $name => $description) {
                // Verificar si el permiso ya existe
                $existingPermission = Permission::where('name', $name)->first();

                if (!$existingPermission) {
                    Permission::create([
                        'name' => $name,
                        'guard_name' => 'web'
                    ]);
                    $createdPermissions[] = $name;
                    $this->line("✓ Creado: <fg=green>{$name}</>");
                } else {
                    $existingPermissions[] = $name;
                    $this->line("- Ya existe: <fg=yellow>{$name}</>");
                }
            }

            $this->newLine();
            $this->info('=== RESUMEN DE PERMISOS ===');
            $this->line("Permisos creados: <fg=green>" . count($createdPermissions) . "</>");
            $this->line("Permisos existentes: <fg=yellow>" . count($existingPermissions) . "</>");
            $this->line("Total procesados: <fg=blue>" . count($permissions) . "</>");

            // Listar roles si se solicita
            if ($this->option('list-roles')) {
                $this->newLine();
                $this->info('=== ROLES DISPONIBLES ===');
                $roles = Role::all();
                foreach ($roles as $role) {
                    $this->line("- {$role->name} (ID: {$role->id})");
                }
                $this->newLine();
            }

            // Asignar permisos a rol específico o todos los roles
            if ($roleName = $this->option('assign-to-role')) {
                $this->assignToSpecificRole($roleName, $permissions);
            } else {
                $this->assignToAllRoles($permissions);
            }

            $this->newLine();
            $this->info('=== CONFIGURACIÓN COMPLETADA ===');
            $this->line('✓ Todos los permisos de reportes han sido configurados correctamente');
            $this->line('✓ Los permisos han sido asignados según la configuración');
            $this->newLine();
            $this->comment('Los usuarios ahora pueden acceder a los reportes según su rol asignado.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->line('Línea: ' . $e->getLine());
            $this->line('Archivo: ' . $e->getFile());
            return Command::FAILURE;
        }
    }

    /**
     * Asignar permisos a un rol específico
     */
    private function assignToSpecificRole(string $roleName, array $permissions)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("❌ El rol '{$roleName}' no existe.");
            $this->line('Usa --list-roles para ver los roles disponibles.');
            return;
        }

        $this->newLine();
        $this->info("Asignando permisos al rol: <fg=cyan>{$role->name}</>");

        // Obtener permisos de reportes
        $reportsPermissions = Permission::where('name', 'like', 'report.%')->pluck('name')->toArray();

        // Asignar permisos al rol (mantener permisos existentes)
        $currentPermissions = $role->permissions->pluck('name')->toArray();
        $allPermissions = array_unique(array_merge($currentPermissions, $reportsPermissions));

        $role->syncPermissions($allPermissions);

        $this->line("✓ Asignados " . count($reportsPermissions) . " permisos de reportes al rol '{$role->name}'");
    }

    /**
     * Asignar permisos a todos los roles
     */
    private function assignToAllRoles(array $permissions)
    {
        $this->newLine();
        $this->info('=== ASIGNACIÓN A ROLES ===');

        $roles = Role::all();
        foreach ($roles as $role) {
            $this->line("Procesando rol: <fg=cyan>{$role->name}</>");

            // Obtener permisos de reportes
            $reportsPermissions = Permission::where('name', 'like', 'report.%')->pluck('name')->toArray();

            // Asignar permisos al rol (mantener permisos existentes)
            $currentPermissions = $role->permissions->pluck('name')->toArray();
            $allPermissions = array_unique(array_merge($currentPermissions, $reportsPermissions));

            $role->syncPermissions($allPermissions);

            $this->line("✓ Asignados " . count($reportsPermissions) . " permisos de reportes al rol '{$role->name}'");
        }
    }
}
