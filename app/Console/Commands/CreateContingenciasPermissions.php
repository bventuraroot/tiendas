<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateContingenciasPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:create-contingencias {--assign-to-admin : Asignar permisos al rol de administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear permisos para el módulo de contingencias DTE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creando permisos para el módulo de contingencias DTE...');

        $permissions = [
            'dte.contingencias' => 'Ver gestión de contingencias DTE',
            'dte.contingencias.create' => 'Crear contingencias',
            'dte.contingencias.store' => 'Guardar contingencias',
            'dte.contingencias.edit' => 'Editar contingencias',
            'dte.contingencias.update' => 'Actualizar contingencias',
            'dte.contingencias.destroy' => 'Eliminar contingencias',
            'dte.contingencias.autorizar' => 'Autorizar contingencias',
            'dte.contingencias.aprobar' => 'Aprobar contingencias',
            'dte.contingencias.activar' => 'Activar contingencias',
            'dte.contingencias.rechazar' => 'Rechazar contingencias',
            'dte.contingencias.estadisticas' => 'Ver estadísticas de contingencias',
            'dte.contingencias.exportar' => 'Exportar reportes de contingencias',
            'dte.contingencias.automaticas' => 'Gestionar contingencias automáticas',
            'dte.contingencias.alertas' => 'Configurar alertas de contingencias',
            'factmh.contingencias' => 'Ver contingencias legacy',
            'factmh.store' => 'Crear contingencias legacy',
            'factmh.autoriza_contingencia' => 'Autorizar contingencias legacy',
            'factmh.muestra_lote' => 'Ver lote de contingencias'
        ];

        $createdCount = 0;
        $existingCount = 0;
        $createdPermissions = [];
        $existingPermissions = [];

        foreach ($permissions as $name => $description) {
            // Verificar si el permiso ya existe
            $existingPermission = Permission::where('name', $name)->first();

            if (!$existingPermission) {
                Permission::create([
                    'name' => $name,
                    'guard_name' => 'web'
                ]);
                $createdPermissions[] = $name;
                $createdCount++;
                $this->line("✅ Creado: {$name}");
            } else {
                $existingPermissions[] = $name;
                $existingCount++;
                $this->line("⚠️  Ya existe: {$name}");
            }
        }

        $this->newLine();
        $this->info("Resumen de permisos:");
        $this->line("• Creados: {$createdCount}");
        $this->line("• Ya existían: {$existingCount}");
        $this->line("• Total procesados: " . ($createdCount + $existingCount));

        // Asignar permisos al rol de administrador si se solicita
        if ($this->option('assign-to-admin')) {
            $this->newLine();
            $this->info('Asignando permisos al rol de administrador...');

            try {
                $adminRole = Role::where('name', 'admin')->first();

                if (!$adminRole) {
                    $adminRole = Role::where('name', 'administrador')->first();
                }

                if (!$adminRole) {
                    $adminRole = Role::where('name', 'Administrador')->first();
                }

                if ($adminRole) {
                    $allPermissions = array_merge($createdPermissions, $existingPermissions);
                    $adminRole->syncPermissions(array_merge($adminRole->permissions->pluck('name')->toArray(), $allPermissions));

                    $this->info("✅ Permisos asignados al rol: {$adminRole->name}");
                } else {
                    $this->error("❌ No se encontró el rol de administrador. Roles disponibles:");
                    $roles = Role::all();
                    foreach ($roles as $role) {
                        $this->line("• {$role->name}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("❌ Error al asignar permisos al administrador: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('🎉 Comando completado exitosamente!');

        if ($createdCount > 0) {
            $this->newLine();
            $this->comment('Permisos creados:');
            foreach ($createdPermissions as $permission) {
                $this->line("• {$permission}");
            }
        }

        return Command::SUCCESS;
    }
}
