<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductPrice;
use App\Models\Product;
use App\Models\Unit;

class ProductPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear precios múltiples para el producto 25 (PRODUCTO POR VOLUMEN)
        $product25 = Product::find(25);
        if ($product25) {
            // Buscar unidad por código 23 (Litro)
            $unit23 = Unit::where('unit_code', '23')->first();
            if ($unit23) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => 25,
                        'unit_id' => $unit23->id
                    ],
                    [
                        'price' => 45.00, // Precio regular
                        'wholesale_price' => 40.00, // Precio al por mayor
                        'retail_price' => 50.00, // Precio al detalle
                        'special_price' => 42.00, // Precio especial
                        'is_default' => true,
                        'is_active' => true
                    ]
                );
                echo "✅ Precios múltiples creados para producto 25, unidad Litro (ID: {$unit23->id})\n";
            } else {
                echo "⚠️ Unidad con código 23 (Litro) no encontrada\n";
            }

            // Buscar unidad por código 59 (Contenedor)
            $unit59 = Unit::where('unit_code', '59')->first();
            if ($unit59) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => 25,
                        'unit_id' => $unit59->id
                    ],
                    [
                        'price' => 45.00, // Precio regular
                        'wholesale_price' => 40.00, // Precio al por mayor
                        'retail_price' => 50.00, // Precio al detalle
                        'special_price' => 42.00, // Precio especial
                        'is_default' => false,
                        'is_active' => true
                    ]
                );
                echo "✅ Precios múltiples creados para producto 25, unidad Contenedor (ID: {$unit59->id})\n";
            } else {
                echo "⚠️ Unidad con código 59 (Contenedor) no encontrada\n";
            }
        }

        // Crear precios múltiples para el producto 23 (PRODUCTO POR PESO)
        $product23 = Product::find(23);
        if ($product23) {
            // Buscar unidad por código 36 (Libra)
            $unit36 = Unit::where('unit_code', '36')->first();
            if ($unit36) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => 23,
                        'unit_id' => $unit36->id
                    ],
                    [
                        'price' => 0.80, // Precio regular
                        'wholesale_price' => 0.70, // Precio al por mayor
                        'retail_price' => 0.90, // Precio al detalle
                        'special_price' => 0.75, // Precio especial
                        'is_default' => true,
                        'is_active' => true
                    ]
                );
                echo "✅ Precios múltiples creados para producto 23, unidad Libra (ID: {$unit36->id})\n";
            } else {
                echo "⚠️ Unidad con código 36 (Libra) no encontrada\n";
            }

            // Buscar unidad por código 59 (Saco)
            $unit59 = Unit::where('unit_code', '59')->first();
            if ($unit59) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => 23,
                        'unit_id' => $unit59->id
                    ],
                    [
                        'price' => 80.00, // Precio regular
                        'wholesale_price' => 70.00, // Precio al por mayor
                        'retail_price' => 90.00, // Precio al detalle
                        'special_price' => 75.00, // Precio especial
                        'is_default' => false,
                        'is_active' => true
                    ]
                );
                echo "✅ Precios múltiples creados para producto 23, unidad Saco (ID: {$unit59->id})\n";
            } else {
                echo "⚠️ Unidad con código 59 (Saco) no encontrada\n";
            }
        }

        echo "🎯 Seeder de precios múltiples completado\n";
    }
}
