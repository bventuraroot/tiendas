<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PharmacyCategoriesSeeder extends Seeder
{
    /**
     * Categorías específicas para farmacia
     */
    public function run()
    {
        $categories = [
            // Medicamentos por especialidad
            'Analgésicos y Antiinflamatorios',
            'Antibióticos',
            'Antivirales',
            'Antimicóticos',
            'Antiparasitarios',
            'Antihistamínicos y Antialérgicos',
            'Antigripales',
            'Antitusivos y Expectorantes',
            
            // Sistema cardiovascular
            'Antihipertensivos',
            'Antiarrítmicos',
            'Anticoagulantes',
            'Hipolipemiantes',
            
            // Sistema digestivo
            'Antiácidos y Gastroprotectores',
            'Antidiarreicos',
            'Laxantes',
            'Antiespasmodicos',
            'Hepatoprotectores',
            
            // Sistema nervioso
            'Ansiolíticos',
            'Antidepresivos',
            'Anticonvulsivantes',
            'Sedantes e Hipnóticos',
            
            // Sistema endocrino
            'Antidiabéticos',
            'Hormonas Tiroideas',
            'Corticosteroides',
            'Hormonas Sexuales',
            
            // Sistema respiratorio
            'Broncodilatadores',
            'Antiasmáticos',
            'Descongestionantes',
            
            // Dermatológicos
            'Cremas y Ungüentos',
            'Antisépticos',
            'Antimicóticos Tópicos',
            
            // Oftalmológicos y Óticos
            'Gotas Oftálmicas',
            'Gotas Óticas',
            
            // Vitaminas y Suplementos
            'Vitaminas',
            'Minerales',
            'Suplementos Nutricionales',
            
            // Otros
            'Anticonceptivos',
            'Vacunas',
            'Material de Curación',
            'Equipo Médico',
            'Productos de Higiene',
            'Productos Naturales',
        ];

        echo "📋 Creando categorías farmacéuticas...\n\n";

        foreach ($categories as $category) {
            echo "  ✓ {$category}\n";
        }

        echo "\n✅ " . count($categories) . " categorías creadas\n";
        echo "\nEstas categorías están listas para asignar a tus productos.\n";
    }
}


