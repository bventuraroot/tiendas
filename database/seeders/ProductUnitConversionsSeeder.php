<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;

class ProductUnitConversionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener TODOS los productos que no tengan conversiones
        $productsWithoutConversions = Product::whereDoesntHave('unitConversions')->get();

        if ($productsWithoutConversions->isEmpty()) {
            $this->command->info('Todos los productos ya tienen conversiones configuradas.');
            return;
        }

        $this->command->info('Creando conversiones para ' . $productsWithoutConversions->count() . ' productos...');

        // Obtener unidades comunes
        $libra = Unit::getByCode('36'); // Libra
        $unidad = Unit::getByCode('59'); // Unidad
        $kilogramo = Unit::getByCode('34'); // Kilogramo
        $galon = Unit::getByCode('22'); // Galón
        $litro = Unit::getByCode('23'); // Litro

        if (!$libra || !$unidad || !$kilogramo) {
            $this->command->info('No se encontraron las unidades necesarias.');
            return;
        }

        // Crear conversiones para cada producto
        foreach ($productsWithoutConversions as $product) {
            $this->command->info('Configurando conversiones para producto: ' . $product->name);

            // Determinar conversiones según el tipo de producto
            $conversions = $this->getConversionsForProduct($product, $libra, $unidad, $kilogramo, $galon, $litro);

            foreach ($conversions as $conversion) {
                ProductUnitConversion::create($conversion);
            }
        }

        $this->command->info('Conversiones de unidades de producto sembradas correctamente.');
    }

    /**
     * Obtener conversiones específicas para un producto según su tipo
     */
    private function getConversionsForProduct($product, $libra, $unidad, $kilogramo, $galon, $litro)
    {
        $conversions = [];

        // Unidad por defecto (siempre disponible)
        $conversions[] = [
            'product_id' => $product->id,
            'unit_id' => $unidad->id,
            'conversion_factor' => 1.0000,
            'price_multiplier' => 1.0000,
            'is_default' => true,
            'is_active' => true,
            'notes' => 'Unidad por defecto del producto'
        ];

        // Conversiones según el tipo de producto
        switch ($product->sale_type) {
            case 'volume':
                // Producto por volumen (ej: depósitos, contenedores)
                if ($litro) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $litro->id,
                        'conversion_factor' => $product->volume_per_unit ?? 25.0,
                        'price_multiplier' => 1.0,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por litro'
                    ];
                }
                if ($galon) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $galon->id,
                        'conversion_factor' => ($product->volume_per_unit ?? 25.0) / 3.78541, // Convertir litros a galones
                        'price_multiplier' => 1.0,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por galón'
                    ];
                }
                break;

            case 'weight':
                // Producto por peso (ej: sacos, libras)
                if ($libra) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $libra->id,
                        'conversion_factor' => $product->weight_per_unit ?? 100.0,
                        'price_multiplier' => 1.0,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por libra'
                    ];
                }
                if ($kilogramo) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $kilogramo->id,
                        'conversion_factor' => ($product->weight_per_unit ?? 100.0) / 2.2046, // Convertir libras a kg
                        'price_multiplier' => 1.0,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por kilogramo'
                    ];
                }
                break;

            case 'unit':
            default:
                // Producto por unidad (precio normal)
                if ($libra) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $libra->id,
                        'conversion_factor' => 1.0000,
                        'price_multiplier' => 1.0000,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por libra'
                    ];
                }
                if ($kilogramo) {
                    $conversions[] = [
                        'product_id' => $product->id,
                        'unit_id' => $kilogramo->id,
                        'conversion_factor' => 2.2046,
                        'price_multiplier' => 1.0000,
                        'is_default' => false,
                        'is_active' => true,
                        'notes' => 'Venta por kilogramo'
                    ];
                }
                break;
        }

        return $conversions;
    }
}
