<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ProductPriceService
{
    /**
     * Obtener todos los precios disponibles para un producto
     */
    public function getProductPrices($productId)
    {
        return ProductPrice::where('product_id', $productId)
                          ->where('is_active', true)
                          ->with('unit')
                          ->orderBy('is_default', 'desc')
                          ->orderBy('id')
                          ->get();
    }

    /**
     * Obtener el precio por defecto de un producto
     */
    public function getDefaultPrice($productId)
    {
        return ProductPrice::where('product_id', $productId)
                          ->where('is_default', true)
                          ->where('is_active', true)
                          ->with('unit')
                          ->first();
    }

    /**
     * Obtener precio específico por unidad
     */
    public function getPriceByUnit($productId, $unitId)
    {
        return ProductPrice::where('product_id', $productId)
                          ->where('unit_id', $unitId)
                          ->where('is_active', true)
                          ->with('unit')
                          ->first();
    }

    /**
     * Obtener precio específico por código de unidad
     */
    public function getPriceByUnitCode($productId, $unitCode)
    {
        return ProductPrice::where('product_id', $productId)
                          ->whereHas('unit', function($query) use ($unitCode) {
                              $query->where('unit_code', $unitCode);
                          })
                          ->where('is_active', true)
                          ->with('unit')
                          ->first();
    }

    /**
     * Obtener precio según el tipo de cliente (mayorista, detalle, etc.)
     */
    public function getPriceByType($productId, $unitId, $priceType = 'price')
    {
        $productPrice = $this->getPriceByUnit($productId, $unitId);

        if (!$productPrice) {
            return null;
        }

        // Mapear tipos de precio
        $priceTypes = [
            'regular' => 'price',
            'cost' => 'cost_price',
            'wholesale' => 'wholesale_price',
            'retail' => 'retail_price',
            'special' => 'special_price'
        ];

        $field = $priceTypes[$priceType] ?? 'price';

        return [
            'price' => $productPrice->$field,
            'unit_name' => $productPrice->unit->unit_name,
            'unit_code' => $productPrice->unit->unit_code,
            'price_type' => $priceType,
            'is_default' => $productPrice->is_default
        ];
    }

    /**
     * Obtener información completa de precios para un producto
     */
    public function getProductPriceInfo($productId)
    {
        $prices = $this->getProductPrices($productId);
        $defaultPrice = $this->getDefaultPrice($productId);

        return [
            'prices' => $prices->map(function($price) {
                return [
                    'id' => $price->id,
                    'unit_id' => $price->unit_id,
                    'unit_code' => $price->unit->unit_code,
                    'unit_name' => $price->unit->unit_name,
                    'price_types' => [
                        'regular' => $price->price,
                        'cost' => $price->cost_price,
                        'wholesale' => $price->wholesale_price,
                        'retail' => $price->retail_price,
                        'special' => $price->special_price
                    ],
                    'is_default' => $price->is_default,
                    'is_active' => $price->is_active
                ];
            }),
            'default_price' => $defaultPrice ? [
                'unit_id' => $defaultPrice->unit_id,
                'unit_code' => $defaultPrice->unit->unit_code,
                'unit_name' => $defaultPrice->unit->unit_name,
                'price' => $defaultPrice->price
            ] : null
        ];
    }

    /**
     * Calcular precio de venta según tipo de cliente y unidad
     */
    public function calculateSalePrice($productId, $unitId, $clientType = 'regular', $quantity = 1)
    {
        $productPrice = $this->getPriceByUnit($productId, $unitId);

        if (!$productPrice) {
            return null;
        }

        // Determinar qué precio usar según el tipo de cliente
        $priceField = $this->getPriceFieldByClientType($clientType);
        $unitPrice = $productPrice->$priceField ?? $productPrice->price;

        return [
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'unit_name' => $productPrice->unit->unit_name,
            'unit_code' => $productPrice->unit->unit_code,
            'price_type' => $clientType,
            'quantity' => $quantity
        ];
    }

    /**
     * Obtener el campo de precio según el tipo de cliente
     */
    private function getPriceFieldByClientType($clientType)
    {
        $priceMap = [
            'wholesale' => 'wholesale_price',
            'retail' => 'retail_price',
            'special' => 'special_price',
            'regular' => 'price'
        ];

        return $priceMap[$clientType] ?? 'price';
    }

    /**
     * Obtener precios para el selector de ventas
     */
    public function getPricesForSaleSelector($productId)
    {
        $prices = $this->getProductPrices($productId);

        return $prices->map(function($price) {
            return [
                'unit_id' => $price->unit_id,
                'unit_code' => $price->unit->unit_code,
                'unit_name' => $price->unit->unit_name,
                'prices' => [
                    'regular' => $price->price,
                    'wholesale' => $price->wholesale_price,
                    'retail' => $price->retail_price,
                    'special' => $price->special_price
                ],
                'is_default' => $price->is_default
            ];
        });
    }

    /**
     * Validar si un producto tiene precios configurados
     */
    public function hasConfiguredPrices($productId)
    {
        return ProductPrice::where('product_id', $productId)
                          ->where('is_active', true)
                          ->exists();
    }

    /**
     * Obtener el mejor precio disponible para un producto
     */
    public function getBestPrice($productId, $unitId = null)
    {
        $query = ProductPrice::where('product_id', $productId)
                            ->where('is_active', true);

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        $price = $query->orderBy('is_default', 'desc')
                      ->orderBy('price', 'asc')
                      ->with('unit')
                      ->first();

        return $price ? [
            'price' => $price->price,
            'unit_id' => $price->unit_id,
            'unit_name' => $price->unit->unit_name,
            'unit_code' => $price->unit->unit_code,
            'is_default' => $price->is_default
        ] : null;
    }
}
