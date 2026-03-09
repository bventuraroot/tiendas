<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Unidades de medida del catálogo CAT-014 del MH
        $units = [
            // Unidades de peso
            ['unit_code' => '34', 'unit_name' => 'Kilogramo', 'unit_type' => 'peso', 'description' => 'Unidad de masa del Sistema Internacional'],
            ['unit_code' => '36', 'unit_name' => 'Libra', 'unit_type' => 'peso', 'description' => 'Unidad de masa del sistema imperial'],
            ['unit_code' => '38', 'unit_name' => 'Onza', 'unit_type' => 'peso', 'description' => 'Unidad de masa del sistema imperial'],
            ['unit_code' => '39', 'unit_name' => 'Gramo', 'unit_type' => 'peso', 'description' => 'Unidad de masa del Sistema Internacional'],
            ['unit_code' => '40', 'unit_name' => 'Miligramo', 'unit_type' => 'peso', 'description' => 'Unidad de masa del Sistema Internacional'],
            ['unit_code' => '29', 'unit_name' => 'Tonelada métrica', 'unit_type' => 'peso', 'description' => 'Unidad de masa del Sistema Internacional'],
            ['unit_code' => '30', 'unit_name' => 'Tonelada', 'unit_type' => 'peso', 'description' => 'Unidad de masa del sistema imperial'],
            ['unit_code' => '31', 'unit_name' => 'Quintal métrico', 'unit_type' => 'peso', 'description' => 'Unidad de masa del Sistema Internacional'],
            ['unit_code' => '32', 'unit_name' => 'Quintal', 'unit_type' => 'peso', 'description' => 'Unidad de masa tradicional'],
            ['unit_code' => '33', 'unit_name' => 'Arroba', 'unit_type' => 'peso', 'description' => 'Unidad de masa tradicional'],

            // Unidades de volumen
            ['unit_code' => '23', 'unit_name' => 'Litro', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del Sistema Internacional'],
            ['unit_code' => '26', 'unit_name' => 'Mililitro', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del Sistema Internacional'],
            ['unit_code' => '22', 'unit_name' => 'Galón', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del sistema imperial'],
            ['unit_code' => '27', 'unit_name' => 'Onza fluida', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del sistema imperial'],
            ['unit_code' => '18', 'unit_name' => 'Metro cúbico', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del Sistema Internacional'],
            ['unit_code' => '21', 'unit_name' => 'Pie cúbico', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen del sistema imperial'],
            ['unit_code' => '20', 'unit_name' => 'Barril', 'unit_type' => 'volumen', 'description' => 'Unidad de volumen para líquidos'],

            // Unidades de longitud
            ['unit_code' => '01', 'unit_name' => 'Metro', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud del Sistema Internacional'],
            ['unit_code' => '06', 'unit_name' => 'Milímetro', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud del Sistema Internacional'],
            ['unit_code' => '02', 'unit_name' => 'Yarda', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud del sistema imperial'],
            ['unit_code' => '03', 'unit_name' => 'Vara', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud tradicional'],
            ['unit_code' => '04', 'unit_name' => 'Pie', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud del sistema imperial'],
            ['unit_code' => '05', 'unit_name' => 'Pulgada', 'unit_type' => 'longitud', 'description' => 'Unidad de longitud del sistema imperial'],

            // Unidades de área
            ['unit_code' => '13', 'unit_name' => 'Metro cuadrado', 'unit_type' => 'area', 'description' => 'Unidad de área del Sistema Internacional'],
            ['unit_code' => '10', 'unit_name' => 'Hectárea', 'unit_type' => 'area', 'description' => 'Unidad de área del Sistema Internacional'],
            ['unit_code' => '11', 'unit_name' => 'Manzana', 'unit_type' => 'area', 'description' => 'Unidad de área tradicional'],
            ['unit_code' => '12', 'unit_name' => 'Acre', 'unit_type' => 'area', 'description' => 'Unidad de área del sistema imperial'],

            // Unidades de conteo
            ['unit_code' => '59', 'unit_name' => 'Unidad', 'unit_type' => 'conteo', 'description' => 'Unidad de conteo estándar'],
            ['unit_code' => '55', 'unit_name' => 'Millar', 'unit_type' => 'conteo', 'description' => 'Mil unidades'],
            ['unit_code' => '56', 'unit_name' => 'Medio millar', 'unit_type' => 'conteo', 'description' => 'Quinientas unidades'],
            ['unit_code' => '57', 'unit_name' => 'Ciento', 'unit_type' => 'conteo', 'description' => 'Cien unidades'],
            ['unit_code' => '58', 'unit_name' => 'Docena', 'unit_type' => 'conteo', 'description' => 'Doce unidades'],

            // Unidades especiales
            ['unit_code' => '24', 'unit_name' => 'Botella', 'unit_type' => 'especial', 'description' => 'Unidad de empaque'],
            ['unit_code' => '99', 'unit_name' => 'Otra', 'unit_type' => 'especial', 'description' => 'Otra unidad de medida'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        $this->command->info('Catálogo de unidades de medida sembrado correctamente.');
    }
}
