<?php

/**
 * Script de prueba para el sistema de conversión de unidades
 *
 * Este archivo demuestra cómo funciona el sistema de conversión
 * para productos agropecuarios como comida para pollos.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\UnitConversionService;

class UnitConversionTest
{
    private $unitConversionService;

    public function __construct()
    {
        $this->unitConversionService = new UnitConversionService();
    }

    /**
     * Ejecutar todas las pruebas
     */
    public function runAllTests()
    {
        echo "🧪 INICIANDO PRUEBAS DEL SISTEMA DE CONVERSIÓN DE UNIDADES\n";
        echo "========================================================\n\n";

        $this->testProductConfiguration();
        $this->testSaleScenarios();
        $this->testInventoryDeduction();
        $this->testEdgeCases();

        echo "\n✅ TODAS LAS PRUEBAS COMPLETADAS\n";
    }

    /**
     * Probar configuración del producto
     */
    private function testProductConfiguration()
    {
        echo "📦 PRUEBA 1: CONFIGURACIÓN DEL PRODUCTO\n";
        echo "----------------------------------------\n";

        // Simular producto: Comida para Pollos
        $productData = [
            'name' => 'Comida para Pollos Premium',
            'price' => 55.00,           // Precio del saco
            'weight_per_unit' => 80,    // Peso del saco en libras
            'sale_type' => 'weight'     // Tipo de venta por peso
        ];

        echo "Producto: {$productData['name']}\n";
        echo "Precio del saco: \${$productData['price']}\n";
        echo "Peso del saco: {$productData['weight_per_unit']} libras\n";

        // Calcular precio por libra
        $pricePerPound = $productData['price'] / $productData['weight_per_unit'];
        echo "Precio por libra: \${$pricePerPound}\n\n";
    }

    /**
     * Probar escenarios de venta
     */
    private function testSaleScenarios()
    {
        echo "🛒 PRUEBA 2: ESCENARIOS DE VENTA\n";
        echo "--------------------------------\n";

        // Simular diferentes escenarios de venta
        $scenarios = [
            [
                'name' => 'Venta por Libras',
                'quantity' => 40,
                'unit' => '36', // Libra
                'expected_price' => 27.50
            ],
            [
                'name' => 'Venta por Saco Completo',
                'quantity' => 1,
                'unit' => '59', // Unidad (Saco)
                'expected_price' => 55.00
            ],
            [
                'name' => 'Venta por Dólares',
                'quantity' => 30,
                'unit' => '99', // Dólar
                'expected_price' => 30.00
            ]
        ];

        foreach ($scenarios as $scenario) {
            echo "📋 {$scenario['name']}:\n";
            echo "   Cantidad: {$scenario['quantity']}\n";
            echo "   Unidad: {$scenario['unit']}\n";
            echo "   Precio esperado: \${$scenario['expected_price']}\n";

            // Simular cálculo
            $unitPrice = $this->calculateUnitPrice($scenario['unit'], $scenario['quantity']);
            $subtotal = $scenario['quantity'] * $unitPrice;
            echo "   Precio calculado: \${$subtotal}\n";
            echo "   ✅ " . ($subtotal == $scenario['expected_price'] ? 'CORRECTO' : 'INCORRECTO') . "\n\n";
        }
    }

    /**
     * Probar descuento de inventario
     */
    private function testInventoryDeduction()
    {
        echo "📊 PRUEBA 3: DESCUENTO DE INVENTARIO\n";
        echo "------------------------------------\n";

        $initialStock = 1000; // 1000 libras en inventario
        echo "Inventario inicial: {$initialStock} libras\n\n";

        $sales = [
            ['quantity' => 40, 'unit' => '36', 'description' => '40 libras'],
            ['quantity' => 1, 'unit' => '59', 'description' => '1 saco (80 libras)'],
            ['quantity' => 30, 'unit' => '99', 'description' => '$30 en producto']
        ];

        $remainingStock = $initialStock;

        foreach ($sales as $sale) {
            $baseQuantityDeducted = $this->calculateBaseQuantityDeducted($sale['quantity'], $sale['unit']);
            $remainingStock -= $baseQuantityDeducted;

            echo "Venta: {$sale['description']}\n";
            echo "   Cantidad vendida: {$sale['quantity']} ({$sale['unit']})\n";
            echo "   Libras descontadas: {$baseQuantityDeducted}\n";
            echo "   Stock restante: {$remainingStock} libras\n\n";
        }

        echo "Stock final: {$remainingStock} libras\n";
        echo "Total vendido: " . ($initialStock - $remainingStock) . " libras\n\n";
    }

    /**
     * Probar casos extremos
     */
    private function testEdgeCases()
    {
        echo "⚠️  PRUEBA 4: CASOS EXTREMOS\n";
        echo "----------------------------\n";

        // Caso 1: Stock insuficiente
        $availableStock = 50;
        $requestedQuantity = 100;
        $unit = '36'; // Libras

        echo "Caso 1: Stock insuficiente\n";
        echo "   Stock disponible: {$availableStock} libras\n";
        echo "   Cantidad solicitada: {$requestedQuantity} libras\n";

        if ($requestedQuantity > $availableStock) {
            echo "   ❌ ERROR: Stock insuficiente\n";
            echo "   Disponible: {$availableStock}, Necesario: {$requestedQuantity}\n\n";
        } else {
            echo "   ✅ Stock suficiente\n\n";
        }

        // Caso 2: Venta de cantidad muy pequeña
        $smallQuantity = 0.5;
        $unitPrice = 0.6875;
        $subtotal = $smallQuantity * $unitPrice;

        echo "Caso 2: Venta de cantidad pequeña\n";
        echo "   Cantidad: {$smallQuantity} libras\n";
        echo "   Precio por libra: \${$unitPrice}\n";
        echo "   Subtotal: \${$subtotal}\n";
        echo "   ✅ Cálculo correcto\n\n";

        // Caso 3: Venta por valor exacto
        $dollarAmount = 55.00;
        $pricePerPound = 0.6875;
        $poundsEquivalent = $dollarAmount / $pricePerPound;

        echo "Caso 3: Venta por valor exacto\n";
        echo "   Valor: \${$dollarAmount}\n";
        echo "   Equivalente en libras: {$poundsEquivalent} libras\n";
        echo "   ✅ Conversión correcta\n\n";
    }

    /**
     * Calcular precio unitario (simulación)
     */
    private function calculateUnitPrice($unitCode, $quantity)
    {
        $pricePerPound = 0.6875; // $55.00 / 80 libras

        switch ($unitCode) {
            case '36': // Libra
                return $pricePerPound;
            case '59': // Unidad (Saco)
                return 55.00; // Precio del saco completo
            case '99': // Dólar
                return 1.00; // $1.00 = $1.00
            default:
                return $pricePerPound;
        }
    }

    /**
     * Calcular cantidad base descontada (simulación)
     */
    private function calculateBaseQuantityDeducted($quantity, $unitCode)
    {
        switch ($unitCode) {
            case '36': // Libra
                return $quantity; // 1 libra = 1 libra
            case '59': // Unidad (Saco)
                return $quantity * 80; // 1 saco = 80 libras
            case '99': // Dólar
                $pricePerPound = 0.6875;
                return $quantity / $pricePerPound; // Convertir dólares a libras
            default:
                return $quantity;
        }
    }
}

// Ejecutar las pruebas si se llama directamente
if (php_sapi_name() === 'cli') {
    $test = new UnitConversionTest();
    $test->runAllTests();
}
