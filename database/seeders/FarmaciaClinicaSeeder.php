<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FarmaciaClinicaSeeder extends Seeder
{
    /**
     * Seeder para configuración inicial de Farmacia y Clínica
     *
     * @return void
     */
    public function run()
    {
        // Configuraciones del sistema
        $this->seedConfiguraciones();
        
        // Categorías de productos para farmacia
        $this->seedCategoriasFarmacia();
        
        // Tipos de servicios para clínica
        $this->seedServiciosClinica();
        
        // Especialidades médicas
        $this->seedEspecialidades();
        
        // Unidades de medida comunes en farmacia
        $this->seedUnidadesFarmacia();
    }

    private function seedConfiguraciones()
    {
        DB::table('config')->insert([
            [
                'key' => 'sistema_tipo',
                'value' => 'farmacia_clinica_laboratorio',
                'description' => 'Tipo de sistema implementado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'modulos_activos',
                'value' => json_encode(['farmacia', 'clinica', 'laboratorio']),
                'description' => 'Módulos activos en el sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedCategoriasFarmacia()
    {
        $categorias = [
            ['nombre' => 'Medicamentos de Prescripción', 'tipo' => 'farmacia', 'requiere_receta' => true],
            ['nombre' => 'Medicamentos OTC (Venta Libre)', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Antibióticos', 'tipo' => 'farmacia', 'requiere_receta' => true],
            ['nombre' => 'Psicofármacos', 'tipo' => 'farmacia', 'requiere_receta' => true],
            ['nombre' => 'Antiinflamatorios', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Analgésicos', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Vitaminas y Suplementos', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Medicamentos Refrigerados', 'tipo' => 'farmacia', 'requiere_receta' => true],
            ['nombre' => 'Material Médico', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Productos de Higiene', 'tipo' => 'farmacia', 'requiere_receta' => false],
            ['nombre' => 'Equipo Médico', 'tipo' => 'farmacia', 'requiere_receta' => false],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categories')->insert([
                'name' => $categoria['nombre'],
                'type' => $categoria['tipo'],
                'requires_prescription' => $categoria['requiere_receta'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedServiciosClinica()
    {
        $servicios = [
            'Consulta Medicina General',
            'Consulta Pediátrica',
            'Control Prenatal',
            'Inyecciones',
            'Curaciones',
            'Toma de Presión Arterial',
            'Control de Glucosa',
            'Aplicación de Vacunas',
            'Sutura de Heridas',
            'Nebulizaciones',
        ];

        foreach ($servicios as $servicio) {
            DB::table('services')->insert([
                'name' => $servicio,
                'type' => 'clinica',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedEspecialidades()
    {
        $especialidades = [
            'Medicina General',
            'Pediatría',
            'Ginecología',
            'Enfermería',
            'Odontología',
            'Nutrición',
        ];

        foreach ($especialidades as $especialidad) {
            DB::table('medical_specialties')->insert([
                'name' => $especialidad,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedUnidadesFarmacia()
    {
        $unidades = [
            ['codigo' => 'TAB', 'nombre' => 'Tableta', 'tipo' => 'unidad'],
            ['codigo' => 'CAP', 'nombre' => 'Cápsula', 'tipo' => 'unidad'],
            ['codigo' => 'AMP', 'nombre' => 'Ampolla', 'tipo' => 'unidad'],
            ['codigo' => 'VIA', 'nombre' => 'Vial', 'tipo' => 'unidad'],
            ['codigo' => 'FRA', 'nombre' => 'Frasco', 'tipo' => 'unidad'],
            ['codigo' => 'TUB', 'nombre' => 'Tubo', 'tipo' => 'unidad'],
            ['codigo' => 'SOB', 'nombre' => 'Sobre', 'tipo' => 'unidad'],
            ['codigo' => 'ML', 'nombre' => 'Mililitro', 'tipo' => 'volumen'],
            ['codigo' => 'MG', 'nombre' => 'Miligramo', 'tipo' => 'peso'],
            ['codigo' => 'GR', 'nombre' => 'Gramo', 'tipo' => 'peso'],
        ];

        foreach ($unidades as $unidad) {
            DB::table('units')->insert([
                'code' => $unidad['codigo'],
                'name' => $unidad['nombre'],
                'type' => $unidad['tipo'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

