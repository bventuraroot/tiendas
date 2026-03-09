<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabExamCategory;
use App\Models\LabExam;
use App\Models\Company;

class LabExamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        
        if (!$company) {
            $this->command->error('No hay empresas en el sistema. Crea una empresa primero.');
            return;
        }

        $company_id = $company->id;

        // Crear categorías
        $categories = [
            [
                'nombre' => 'Hematología',
                'codigo' => 'HEMAT',
                'descripcion' => 'Exámenes del estudio de la sangre',
                'orden' => 1,
            ],
            [
                'nombre' => 'Química Clínica',
                'codigo' => 'QUIM',
                'descripcion' => 'Exámenes bioquímicos',
                'orden' => 2,
            ],
            [
                'nombre' => 'Urianálisis',
                'codigo' => 'URINA',
                'descripcion' => 'Exámenes de orina',
                'orden' => 3,
            ],
            [
                'nombre' => 'Coprología',
                'codigo' => 'COPRO',
                'descripcion' => 'Exámenes de heces',
                'orden' => 4,
            ],
            [
                'nombre' => 'Inmunología',
                'codigo' => 'INMUNO',
                'descripcion' => 'Pruebas inmunológicas y serológicas',
                'orden' => 5,
            ],
            [
                'nombre' => 'Microbiología',
                'codigo' => 'MICRO',
                'descripcion' => 'Cultivos y antibiogramas',
                'orden' => 6,
            ],
        ];

        $categoriesCreated = [];
        foreach ($categories as $categoryData) {
            $categoryData['company_id'] = $company_id;
            $categoryData['activo'] = true;
            $categoriesCreated[] = LabExamCategory::create($categoryData);
        }

        $this->command->info('Categorías creadas: ' . count($categoriesCreated));

        // Crear exámenes
        $exams = [
            // HEMATOLOGÍA
            [
                'category' => 'HEMAT',
                'codigo_examen' => 'EX-HEM001',
                'nombre' => 'Hemograma Completo',
                'descripcion' => 'Conteo completo de células sanguíneas',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 8.00,
                'valores_referencia' => 'Glóbulos rojos: 4.5-5.5 millones/μL, Hemoglobina: 13-17 g/dL',
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'HEMAT',
                'codigo_examen' => 'EX-HEM002',
                'nombre' => 'Grupo Sanguíneo y Factor RH',
                'descripcion' => 'Determinación de grupo y factor RH',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 1,
                'precio' => 5.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'HEMAT',
                'codigo_examen' => 'EX-HEM003',
                'nombre' => 'Tiempo de Protrombina (TP)',
                'descripcion' => 'Evaluación de coagulación',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 6.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            
            // QUÍMICA CLÍNICA
            [
                'category' => 'QUIM',
                'codigo_examen' => 'EX-QUI001',
                'nombre' => 'Glucosa en Ayunas',
                'descripcion' => 'Medición de niveles de azúcar en sangre',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 4.00,
                'valores_referencia' => 'Normal: 70-100 mg/dL, Prediabetes: 100-125 mg/dL',
                'preparacion_requerida' => 'Ayuno de 8 horas',
                'requiere_ayuno' => true,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'QUIM',
                'codigo_examen' => 'EX-QUI002',
                'nombre' => 'Perfil Lipídico',
                'descripcion' => 'Colesterol total, HDL, LDL, Triglicéridos',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 4,
                'precio' => 12.00,
                'valores_referencia' => 'Colesterol total < 200 mg/dL, LDL < 100 mg/dL, HDL > 40 mg/dL',
                'preparacion_requerida' => 'Ayuno de 12 horas',
                'requiere_ayuno' => true,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'QUIM',
                'codigo_examen' => 'EX-QUI003',
                'nombre' => 'Creatinina',
                'descripcion' => 'Función renal',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 5.00,
                'valores_referencia' => 'Hombres: 0.7-1.3 mg/dL, Mujeres: 0.6-1.1 mg/dL',
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'QUIM',
                'codigo_examen' => 'EX-QUI004',
                'nombre' => 'Transaminasas (TGO-TGP)',
                'descripcion' => 'Función hepática',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 3,
                'precio' => 8.00,
                'valores_referencia' => 'TGO: 5-40 U/L, TGP: 7-56 U/L',
                'requiere_ayuno' => true,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'QUIM',
                'codigo_examen' => 'EX-QUI005',
                'nombre' => 'Ácido Úrico',
                'descripcion' => 'Niveles de ácido úrico en sangre',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 5.00,
                'valores_referencia' => 'Hombres: 3.4-7.0 mg/dL, Mujeres: 2.4-6.0 mg/dL',
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],

            // URIANÁLISIS
            [
                'category' => 'URINA',
                'codigo_examen' => 'EX-URI001',
                'nombre' => 'Examen General de Orina (EGO)',
                'descripcion' => 'Análisis físico, químico y microscópico de orina',
                'tipo_muestra' => 'Orina',
                'tiempo_procesamiento_horas' => 1,
                'precio' => 4.00,
                'preparacion_requerida' => 'Primera orina de la mañana',
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'URINA',
                'codigo_examen' => 'EX-URI002',
                'nombre' => 'Urocultivo',
                'descripcion' => 'Cultivo de orina para detectar infecciones',
                'tipo_muestra' => 'Orina',
                'tiempo_procesamiento_horas' => 48,
                'precio' => 15.00,
                'preparacion_requerida' => 'Muestra estéril, primera orina de la mañana',
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],

            // COPROLOGÍA
            [
                'category' => 'COPRO',
                'codigo_examen' => 'EX-COP001',
                'nombre' => 'Examen General de Heces',
                'descripcion' => 'Análisis macroscópico y microscópico',
                'tipo_muestra' => 'Heces',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 4.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'COPRO',
                'codigo_examen' => 'EX-COP002',
                'nombre' => 'Coprocultivo',
                'descripcion' => 'Cultivo de heces',
                'tipo_muestra' => 'Heces',
                'tiempo_procesamiento_horas' => 72,
                'precio' => 18.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],

            // INMUNOLOGÍA
            [
                'category' => 'INMUNO',
                'codigo_examen' => 'EX-INM001',
                'nombre' => 'VDRL (Sífilis)',
                'descripcion' => 'Detección de sífilis',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 4,
                'precio' => 7.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'INMUNO',
                'codigo_examen' => 'EX-INM002',
                'nombre' => 'Prueba de Embarazo (Beta HCG)',
                'descripcion' => 'Detección de hormona del embarazo',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 2,
                'precio' => 8.00,
                'requiere_ayuno' => false,
                'prioridad' => 'urgente',
            ],
            [
                'category' => 'INMUNO',
                'codigo_examen' => 'EX-INM003',
                'nombre' => 'VIH (ELISA)',
                'descripcion' => 'Detección de anticuerpos VIH',
                'tipo_muestra' => 'Sangre',
                'tiempo_procesamiento_horas' => 24,
                'precio' => 15.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],

            // MICROBIOLOGÍA
            [
                'category' => 'MICRO',
                'codigo_examen' => 'EX-MIC001',
                'nombre' => 'Cultivo de Garganta',
                'descripcion' => 'Identificación de microorganismos en garganta',
                'tipo_muestra' => 'Exudado faríngeo',
                'tiempo_procesamiento_horas' => 48,
                'precio' => 12.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
            [
                'category' => 'MICRO',
                'codigo_examen' => 'EX-MIC002',
                'nombre' => 'Cultivo de Herida',
                'descripcion' => 'Identificación de microorganismos en heridas',
                'tipo_muestra' => 'Exudado de herida',
                'tiempo_procesamiento_horas' => 48,
                'precio' => 12.00,
                'requiere_ayuno' => false,
                'prioridad' => 'normal',
            ],
        ];

        $examsCreated = 0;
        foreach ($exams as $examData) {
            $category = LabExamCategory::where('codigo', $examData['category'])
                ->where('company_id', $company_id)
                ->first();

            if ($category) {
                unset($examData['category']);
                $examData['category_id'] = $category->id;
                $examData['company_id'] = $company_id;
                $examData['activo'] = true;

                LabExam::create($examData);
                $examsCreated++;
            }
        }

        $this->command->info("Exámenes creados: {$examsCreated}");
    }
}

