<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductPriceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductPriceSaleController extends Controller
{
    protected $productPriceService;

    public function __construct()
    {
        $this->productPriceService = new ProductPriceService();
    }

    /**
     * Obtener precios de un producto para el selector de ventas
     */
    public function getProductPrices(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $prices = $this->productPriceService->getPricesForSaleSelector($productId);

        return response()->json([
            'success' => true,
            'data' => $prices
        ]);
    }

    /**
     * Obtener precios de un producto para el selector de precios múltiples
     */
    public function getProductPricesForSelector(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $prices = $this->productPriceService->getPricesForSaleSelector($productId);

        return response()->json([
            'success' => true,
            'data' => $prices
        ]);
    }

    /**
     * Obtener precio específico por unidad y tipo
     */
    public function getPriceByUnit(Request $request, $productId, $unitId): JsonResponse
    {
        // Validar que el producto y unidad existen
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $priceType = $request->get('price_type', 'regular');

        $priceInfo = $this->productPriceService->getPriceByType($productId, $unitId, $priceType);

        if (!$priceInfo) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró precio para la unidad especificada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $priceInfo
        ]);
    }

    /**
     * Calcular precio de venta
     */
    public function calculateSalePrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'required|exists:units,id',
            'quantity' => 'required|numeric|min:0.01',
            'client_type' => 'nullable|string|in:regular,wholesale,retail,special'
        ]);

        $productId = $request->product_id;
        $unitId = $request->unit_id;
        $quantity = $request->quantity;
        $clientType = $request->client_type ?? 'regular';

        $salePrice = $this->productPriceService->calculateSalePrice($productId, $unitId, $clientType, $quantity);

        if (!$salePrice) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo calcular el precio de venta'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $salePrice
        ]);
    }

    /**
     * Obtener información completa de precios de un producto
     */
    public function getProductPriceInfo(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $priceInfo = $this->productPriceService->getProductPriceInfo($productId);

        return response()->json([
            'success' => true,
            'data' => $priceInfo
        ]);
    }

    /**
     * Obtener el precio por defecto de un producto
     */
    public function getDefaultPrice(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $defaultPrice = $this->productPriceService->getDefaultPrice($productId);

        if (!$defaultPrice) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró precio por defecto para el producto'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unit_id' => $defaultPrice->unit_id,
                'unit_code' => $defaultPrice->unit->unit_code,
                'unit_name' => $defaultPrice->unit->unit_name,
                'price' => $defaultPrice->price,
                'is_default' => $defaultPrice->is_default
            ]
        ]);
    }

    /**
     * Verificar si un producto tiene precios configurados
     */
    public function hasConfiguredPrices(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $hasPrices = $this->productPriceService->hasConfiguredPrices($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'has_prices' => $hasPrices
            ]
        ]);
    }

    /**
     * Obtener el mejor precio disponible para un producto
     */
    public function getBestPrice(Request $request, $productId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $unitId = $request->get('unit_id');

        $bestPrice = $this->productPriceService->getBestPrice($productId, $unitId);

        if (!$bestPrice) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró precio para el producto'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bestPrice
        ]);
    }

    /**
     * Obtener todos los tipos de precio disponibles para un producto
     */
    public function getAvailablePriceTypes(Request $request, $productId, $unitId): JsonResponse
    {
        // Validar que el producto existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Buscar el precio por unit_code en lugar de unit_id
        $productPrice = ProductPrice::where('product_id', $productId)
            ->whereHas('unit', function($query) use ($unitId) {
                $query->where('unit_code', $unitId);
            })
            ->first();

        if (!$productPrice) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró precio para la unidad especificada'
            ], 404);
        }

        $priceTypes = [];

        if ($productPrice->price) {
            $priceTypes['regular'] = [
                'name' => 'Precio Regular',
                'value' => $productPrice->price,
                'description' => 'Precio estándar del producto'
            ];
        }

        if ($productPrice->wholesale_price) {
            $priceTypes['wholesale'] = [
                'name' => 'Precio al Por Mayor',
                'value' => $productPrice->wholesale_price,
                'description' => 'Precio para compras al por mayor'
            ];
        }

        if ($productPrice->retail_price) {
            $priceTypes['retail'] = [
                'name' => 'Precio al Detalle',
                'value' => $productPrice->retail_price,
                'description' => 'Precio para ventas al detalle'
            ];
        }

        if ($productPrice->special_price) {
            $priceTypes['special'] = [
                'name' => 'Precio Especial',
                'value' => $productPrice->special_price,
                'description' => 'Precio promocional o especial'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unit_id' => $productPrice->unit_id,
                'unit_name' => $productPrice->unit->unit_name,
                'unit_code' => $productPrice->unit->unit_code,
                'price_types' => $priceTypes,
                'is_default' => $productPrice->is_default
            ]
        ]);
    }

    /**
     * Método de prueba para verificar precios múltiples
     */
    public function testPrices(): JsonResponse
    {
        // Obtener todos los productos con precios múltiples
        $productsWithPrices = ProductPrice::where('is_active', true)
            ->with(['product:id,name,code', 'unit:id,unit_name,unit_code'])
            ->get()
            ->groupBy('product_id')
            ->map(function($prices, $productId) {
                $product = $prices->first()->product;
                return [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'prices_count' => $prices->count(),
                    'units' => $prices->map(function($price) {
                        return [
                            'unit_id' => $price->unit_id,
                            'unit_name' => $price->unit->unit_name,
                            'unit_code' => $price->unit->unit_code,
                            'has_regular' => !is_null($price->price),
                            'has_wholesale' => !is_null($price->wholesale_price),
                            'has_retail' => !is_null($price->retail_price),
                            'has_special' => !is_null($price->special_price)
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_products_with_prices' => $productsWithPrices->count(),
                'products' => $productsWithPrices->values()
            ]
        ]);
    }
}
