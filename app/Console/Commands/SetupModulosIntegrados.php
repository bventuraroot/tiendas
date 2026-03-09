<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupModulosIntegrados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:modulos-integrados {--assign-admin : Asignar permisos al rol administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar permisos para los módulos de Clínica, Laboratorio y Facturación Integral';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Iniciando configuración de módulos integrados...');
        $this->newLine();

        // Crear permisos de Clínica
        $this->info('📋 Creando permisos de Clínica...');
        $this->createClinicPermissions();
        $this->info('✅ Permisos de Clínica creados');
        $this->newLine();

        // Crear permisos de Laboratorio
        $this->info('🧪 Creando permisos de Laboratorio...');
        $this->createLaboratoryPermissions();
        $this->info('✅ Permisos de Laboratorio creados');
        $this->newLine();

        // Crear permisos de Facturación Integral
        $this->info('💰 Creando permisos de Facturación Integral...');
        $this->createFacturacionPermissions();
        $this->info('✅ Permisos de Facturación Integral creados');
        $this->newLine();

        // Asignar al rol administrador si se solicita
        if ($this->option('assign-admin')) {
            $this->info('🔐 Asignando permisos al rol Administrador...');
            $this->assignPermissionsToAdmin();
            $this->info('✅ Permisos asignados al administrador');
            $this->newLine();
        }

        $this->info('🎉 ¡Configuración completada exitosamente!');
        $this->newLine();
        
        $this->table(
            ['Módulo', 'Permisos', 'Estado'],
            [
                ['Clínica', '24 permisos', '✅ Creados'],
                ['Laboratorio', '22 permisos', '✅ Creados'],
                ['Facturación', '5 permisos', '✅ Creados'],
                ['TOTAL', '51 permisos', '✅ Listos']
            ]
        );

        $this->newLine();
        $this->info('📝 Accede al sistema en: http://localhost:8003/dashboard');
        
        return Command::SUCCESS;
    }

    private function createClinicPermissions()
    {
        $permissions = [
            'patients.index' => 'Ver lista de pacientes',
            'patients.create' => 'Crear pacientes',
            'patients.edit' => 'Editar pacientes',
            'patients.destroy' => 'Eliminar pacientes',
            'patients.show' => 'Ver detalles de pacientes',
            'doctors.index' => 'Ver lista de médicos',
            'doctors.create' => 'Crear médicos',
            'doctors.edit' => 'Editar médicos',
            'doctors.destroy' => 'Eliminar médicos',
            'doctors.show' => 'Ver detalles de médicos',
            'appointments.index' => 'Ver agenda de citas',
            'appointments.create' => 'Crear citas médicas',
            'appointments.edit' => 'Editar citas médicas',
            'appointments.destroy' => 'Eliminar citas médicas',
            'appointments.show' => 'Ver detalles de citas',
            'consultations.index' => 'Ver lista de consultas',
            'consultations.create' => 'Crear consultas médicas',
            'consultations.edit' => 'Editar consultas médicas',
            'consultations.show' => 'Ver detalles de consultas',
            'prescriptions.index' => 'Ver lista de recetas',
            'prescriptions.create' => 'Crear recetas médicas',
            'prescriptions.edit' => 'Editar recetas médicas',
            'prescriptions.show' => 'Ver detalles de recetas',
            'prescriptions.dispense' => 'Dispensar medicamentos',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }
    }

    private function createLaboratoryPermissions()
    {
        $permissions = [
            'lab-orders.index' => 'Ver lista de órdenes de laboratorio',
            'lab-orders.create' => 'Crear órdenes de laboratorio',
            'lab-orders.edit' => 'Editar órdenes de laboratorio',
            'lab-orders.show' => 'Ver detalles de órdenes',
            'lab-orders.process' => 'Procesar órdenes de laboratorio',
            'lab-orders.print' => 'Imprimir órdenes de laboratorio',
            'lab-exams.index' => 'Ver catálogo de exámenes',
            'lab-exams.create' => 'Crear exámenes de laboratorio',
            'lab-exams.edit' => 'Editar exámenes de laboratorio',
            'lab-exams.destroy' => 'Eliminar exámenes de laboratorio',
            'lab-results.index' => 'Ver resultados de laboratorio',
            'lab-results.create' => 'Crear resultados de laboratorio',
            'lab-results.edit' => 'Editar resultados de laboratorio',
            'lab-results.validate' => 'Validar resultados de laboratorio',
            'lab-results.print' => 'Imprimir resultados de laboratorio',
            'lab-samples.index' => 'Ver lista de muestras',
            'lab-samples.create' => 'Registrar toma de muestras',
            'lab-samples.edit' => 'Editar información de muestras',
            'lab-quality.index' => 'Ver control de calidad',
            'lab-quality.create' => 'Registrar control de calidad',
            'lab-equipment.index' => 'Ver lista de equipos',
            'lab-equipment.create' => 'Registrar equipos de laboratorio',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }
    }

    private function createFacturacionPermissions()
    {
        $permissions = [
            'facturacion.integral' => 'Acceder a facturación integral',
            'facturacion.consultas-pendientes' => 'Ver consultas pendientes de facturar',
            'facturacion.ordenes-lab-pendientes' => 'Ver órdenes de laboratorio pendientes',
            'facturacion.facturar-consulta' => 'Facturar consultas médicas',
            'facturacion.facturar-orden-lab' => 'Facturar órdenes de laboratorio',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }
    }

    private function assignPermissionsToAdmin()
    {
        $adminRole = Role::where('id', 1)->first();

        if (!$adminRole) {
            $this->error('❌ No se encontró el rol Administrador (ID: 1)');
            return;
        }

        // Asignar todos los permisos de clínica
        $clinicPermissions = Permission::where('name', 'like', 'patients.%')
            ->orWhere('name', 'like', 'doctors.%')
            ->orWhere('name', 'like', 'appointments.%')
            ->orWhere('name', 'like', 'consultations.%')
            ->orWhere('name', 'like', 'prescriptions.%')
            ->pluck('name');

        $adminRole->givePermissionTo($clinicPermissions);

        // Asignar todos los permisos de laboratorio
        $labPermissions = Permission::where('name', 'like', 'lab-%')->pluck('name');
        $adminRole->givePermissionTo($labPermissions);

        // Asignar permisos de facturación integral
        $facturacionPermissions = Permission::where('name', 'like', 'facturacion.%')->pluck('name');
        $adminRole->givePermissionTo($facturacionPermissions);

        $this->info('✅ Permisos asignados: Clínica, Laboratorio y Facturación');
    }
}

