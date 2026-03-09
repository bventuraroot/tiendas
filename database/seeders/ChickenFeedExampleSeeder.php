<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use App\Models\Inventory;

class ChickenFeedExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener IDs válidos
        $firstProvider = \App\Models\Provider::first();
        $firstMarca = \App\Models\Marca::first();
        $firstUser = \App\Models\User::first();
        
        if (!$firstProvider || !$firstMarca || !$firstUser) {
            $this->command->error('No se encontraron provider, marca o usuario válidos');
            return;
        }

        // 1. Crear o usar producto: Comida para Pollos
        $product = Product::where('code', 'FEED-CHICKEN-001')->first();
        
        if (!$product) {
            $product = Product::create([
                'code' => 'FEED-CHICKEN-001',
                'name' => 'Comida para Pollos Premium',
                'description' => 'Alimento balanceado para pollos de engorde',
                'price' => 0.85, // Precio base por libra
                'state' => 1,
                'cfiscal' => '01', // Bien
                'type' => 'Alimento',
                'category' => 'Alimentos para Animales',
                'provider_id' => $firstProvider->id,
                'marca_id' => $firstMarca->id,
                'user_id' => $firstUser->id
            ]);
        }

        // 2. Obtener unidades necesarias
        $libra = Unit::getByCode('36'); // Libra - unidad base
        $unidad = Unit::getByCode('59'); // Unidad (Saco)
        $kilogramo = Unit::getByCode('34'); // Kilogramo
        $otra = Unit::getByCode('99'); // Otra (para dólares)

        if (!$libra || !$unidad || !$kilogramo || !$otra) {
            $this->command->error('No se encontraron todas las unidades necesarias');
            return;
        }

        // 3. Crear conversiones específicas del producto
        $conversions = [
            // Libra (unidad base)
            [
                'product_id' => $product->id,
                'unit_id' => $libra->id,
                'conversion_factor' => 1.0000, // 1 libra = 1 libra
                'price_multiplier' => 1.0000, // $0.85 por libra
                'is_default' => true,
                'is_active' => true,
                'notes' => 'Unidad base - venta por libra'
            ],
            // Saco (55 libras)
            [
                'product_id' => $product->id,
                'unit_id' => $unidad->id,
                'conversion_factor' => 55.0000, // 1 saco = 55 libras
                'price_multiplier' => 1.0000, // Mismo precio por libra
                'is_default' => false,
                'is_active' => true,
                'notes' => 'Saco de 55 libras - venta por saco completo'
            ],
            // Kilogramo
            [
                'product_id' => $product->id,
                'unit_id' => $kilogramo->id,
                'conversion_factor' => 2.2046, // 1 kg = 2.2046 libras
                'price_multiplier' => 1.0000, // Mismo precio por libra
                'is_default' => false,
                'is_active' => true,
                'notes' => 'Venta por kilogramo'
            ],
            // Dólar (venta por valor)
            [
                'product_id' => $product->id,
                'unit_id' => $otra->id,
                'conversion_factor' => 1.1765, // $1 = 1.1765 libras (a $0.85/lb)
                'price_multiplier' => 1.0000,
                'is_default' => false,
                'is_active' => true,
                'notes' => 'Venta por valor en dólares'
            ]
        ];

        foreach ($conversions as $conversion) {
            ProductUnitConversion::create($conversion);
        }

        // 4. Crear inventario inicial
        Inventory::create([
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1100, // Cantidad legacy (para compatibilidad)
            'base_unit_id' => $libra->id,
            'base_quantity' => 1100.0000, // 1100 libras (20 sacos)
            'base_unit_price' => 0.85, // $0.85 por libra
            'minimum_stock' => 275, // 5 sacos mínimo
            'location' => 'Almacén Principal - Zona A',
            'user_id' => $firstUser->id,
            'provider_id' => $firstProvider->id,
            'active' => 1
        ]);

        $this->command->info('Ejemplo de Comida para Pollos creado exitosamente:');
        $this->command->info("- Producto: {$product->name} (Código: {$product->code})");
        $this->command->info("- Conversiones: 4 unidades configuradas");
        $this->command->info("- Inventario: 1100 libras (20 sacos de 55 lb c/u)");
        $this->command->info("- Precio base: $0.85 por libra");
        $this->command->info("- Precio por saco: $46.75 (55 x $0.85)");
    }
}
