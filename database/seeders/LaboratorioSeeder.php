<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaboratorioSeeder extends Seeder
{
    /**
     * Seeder para configuración inicial del Laboratorio Clínico
     *
     * @return void
     */
    public function run()
    {
        // Categorías de exámenes
        $this->seedCategoriasExamenes();
        
        // Exámenes comunes
        $this->seedExamenesComunes();
        
        // Perfiles de exámenes
        $this->seedPerfilesExamenes();
    }

    private function seedCategoriasExamenes()
    {
        $categorias = [
            'Hematología',
            'Química Clínica',
            'Inmunología',
            'Microbiología',
            'Parasitología',
            'Urianálisis',
            'Coprología',
            'Serología',
            'Hormonas',
            'Marcadores Cardíacos',
        ];

        foreach ($categorias as $categoria) {
            DB::table('lab_categories')->insert([
                'name' => $categoria,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedExamenesComunes()
    {
        $examenes = [
            // Hematología
            ['nombre' => 'Hemograma Completo', 'categoria' => 'Hematología', 'tiempo_entrega' => 1],
            ['nombre' => 'Hemoglobina', 'categoria' => 'Hematología', 'tiempo_entrega' => 1],
            ['nombre' => 'Hematocrito', 'categoria' => 'Hematología', 'tiempo_entrega' => 1],
            ['nombre' => 'Recuento de Plaquetas', 'categoria' => 'Hematología', 'tiempo_entrega' => 1],
            ['nombre' => 'Grupo Sanguíneo y Factor Rh', 'categoria' => 'Hematología', 'tiempo_entrega' => 1],
            
            // Química Clínica
            ['nombre' => 'Glucosa en Ayunas', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Glucosa Postprandial', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Hemoglobina Glicosilada (HbA1c)', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 2],
            ['nombre' => 'Colesterol Total', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Triglicéridos', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Creatinina', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Ácido Úrico', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            ['nombre' => 'Urea', 'categoria' => 'Química Clínica', 'tiempo_entrega' => 1],
            
            // Urianálisis
            ['nombre' => 'Examen General de Orina', 'categoria' => 'Urianálisis', 'tiempo_entrega' => 1],
            ['nombre' => 'Urocultivo', 'categoria' => 'Urianálisis', 'tiempo_entrega' => 3],
            
            // Coprología
            ['nombre' => 'Examen General de Heces', 'categoria' => 'Coprología', 'tiempo_entrega' => 1],
            ['nombre' => 'Coprocultivo', 'categoria' => 'Coprología', 'tiempo_entrega' => 3],
            
            // Serología
            ['nombre' => 'VDRL (Sífilis)', 'categoria' => 'Serología', 'tiempo_entrega' => 2],
            ['nombre' => 'VIH', 'categoria' => 'Serología', 'tiempo_entrega' => 2],
            ['nombre' => 'Hepatitis B', 'categoria' => 'Serología', 'tiempo_entrega' => 3],
            ['nombre' => 'Hepatitis C', 'categoria' => 'Serología', 'tiempo_entrega' => 3],
        ];

        foreach ($examenes as $examen) {
            DB::table('lab_tests')->insert([
                'name' => $examen['nombre'],
                'category' => $examen['categoria'],
                'delivery_time_days' => $examen['tiempo_entrega'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPerfilesExamenes()
    {
        $perfiles = [
            [
                'nombre' => 'Perfil Básico',
                'examenes' => ['Hemograma Completo', 'Glucosa en Ayunas', 'Examen General de Orina']
            ],
            [
                'nombre' => 'Perfil Lipídico',
                'examenes' => ['Colesterol Total', 'Triglicéridos', 'HDL', 'LDL']
            ],
            [
                'nombre' => 'Perfil Renal',
                'examenes' => ['Creatinina', 'Urea', 'Ácido Úrico']
            ],
            [
                'nombre' => 'Perfil Prenatal',
                'examenes' => ['Hemograma Completo', 'Grupo Sanguíneo y Factor Rh', 'VDRL', 'VIH', 'Examen General de Orina']
            ],
        ];

        foreach ($perfiles as $perfil) {
            DB::table('lab_profiles')->insert([
                'name' => $perfil['nombre'],
                'tests' => json_encode($perfil['examenes']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

