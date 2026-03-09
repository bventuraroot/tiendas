<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Extensión para el SaleController con manejo de unidades
 * Métodos que se pueden incluir en el SaleController principal
 */
trait SaleUnitsExtension
{
    protected $unitConversionService;

    /**
     * Inicializar el servicio de conversión de unidades
     */
    public function initUnitConversionService()
    {
        if (!$this->unitConversionService) {
            $this->unitConversionService = new UnitConversionService();
        }
    }

    /**
     * Obtener producto con sus unidades de medida disponibles
     */
    public function getproductbyid($id): JsonResponse
    {
        $this->initUnitConversionService();

        try {
            $product = Product::with(['marca', 'provider', 'inventory'])->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Obtener unidades disponibles para este producto
            $units = $this->unitConversionService->getAvailableUnitsForProduct($id);

            // Obtener inventario actual
            $inventory = $product->inventory;
            $stockInfo = null;

            if ($inventory) {
                $stockInfo = [
                    'base_quantity' => $inventory->base_quantity ?? 0,
                    'base_unit' => $inventory->baseUnit->unit_name ?? 'Unidad',
                    'legacy_quantity' => $inventory->quantity ?? 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'marca' => $product->marca->name ?? '',
                        'provider' => $product->provider->razonsocial ?? ''
                    ],
                    'units' => $units,
                    'stock' => $stockInfo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular precio y conversión para una unidad específica
     */
    public function calculateUnitConversion(Request $request): JsonResponse
    {
        $this->initUnitConversionService();

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'nullable|numeric|min:0'
        ]);

        try {
            $productId = $request->product_id;
            $unitCode = $request->unit_code;
            $quantity = $request->quantity ?? 1;

            // Obtener información del producto
            $product = Product::find($productId);
            $basePrice = $product->price;

            // Obtener información de conversión
            $conversionInfo = $this->unitConversionService->getConversionInfo($productId, $unitCode);

            // Calcular precio por unidad
            $unitPrice = $this->unitConversionService->calculateUnitPrice($productId, $basePrice, $unitCode);

            // Verificar stock disponible
            $stockCheck = $this->unitConversionService->checkStockAvailability($productId, $quantity, $unitCode);

            // Calcular totales
            $subtotal = $quantity * $unitPrice;

            return response()->json([
                'success' => true,
                'data' => [
                    'unit_info' => $conversionInfo,
                    'unit_price' => round($unitPrice, 2),
                    'subtotal' => round($subtotal, 2),
                    'stock_info' => $stockCheck,
                    'calculations' => [
                        'base_price' => $basePrice,
                        'conversion_factor' => $conversionInfo['conversion_factor'],
                        'price_multiplier' => $conversionInfo['price_multiplier'],
                        'quantity' => $quantity,
                        'base_quantity_needed' => $quantity * $conversionInfo['conversion_factor']
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular conversión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener inventario disponible en diferentes unidades
     */
    public function getInventoryByUnits($productId): JsonResponse
    {
        $this->initUnitConversionService();

        try {
            $product = Product::with('inventory')->find($productId);

            if (!$product || !$product->inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto o inventario no encontrado'
                ], 404);
            }

            $inventory = $product->inventory;
            $units = $this->unitConversionService->getAvailableUnitsForProduct($productId);

            $inventoryByUnits = [];

            foreach ($units as $unit) {
                try {
                    $availableInUnit = $this->unitConversionService->convertFromBaseUnit(
                        $productId,
                        $inventory->base_quantity,
                        $unit['unit_code']
                    );

                    $inventoryByUnits[] = [
                        'unit_code' => $unit['unit_code'],
                        'unit_name' => $unit['unit_name'],
                        'available_quantity' => round($availableInUnit['quantity'], 4),
                        'is_default' => $unit['is_default']
                    ];
                } catch (\Exception $e) {
                    // Si hay error en conversión, continuar con la siguiente unidad
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_name' => $product->name,
                    'base_quantity' => $inventory->base_quantity,
                    'base_unit' => $inventory->baseUnit->unit_name ?? 'Unidad',
                    'inventory_by_units' => $inventoryByUnits
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener inventario por unidades: ' . $e->getMessage()
            ], 500);
        }
    }
}
