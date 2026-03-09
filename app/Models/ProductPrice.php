<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'price',
        'cost_price',
        'wholesale_price',
        'retail_price',
        'special_price',
        'is_active',
        'is_default',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'wholesale_price' => 'decimal:4',
        'retail_price' => 'decimal:4',
        'special_price' => 'decimal:4',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con la unidad
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Obtener precios activos de un producto
     */
    public static function getActivePrices($productId)
    {
        return static::where('product_id', $productId)
                    ->where('is_active', true)
                    ->with('unit')
                    ->orderBy('is_default', 'desc')
                    ->orderBy('id')
                    ->get();
    }

    /**
     * Obtener el precio por defecto de un producto
     */
    public static function getDefaultPrice($productId)
    {
        return static::where('product_id', $productId)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->with('unit')
                    ->first();
    }

    /**
     * Obtener precio específico por código de unidad
     */
    public static function getPriceByUnitCode($productId, $unitCode)
    {
        return static::where('product_id', $productId)
                    ->whereHas('unit', function($query) use ($unitCode) {
                        $query->where('unit_code', $unitCode);
                    })
                    ->where('is_active', true)
                    ->with('unit')
                    ->first();
    }

    /**
     * Obtener todos los tipos de precio disponibles
     */
    public function getAvailablePriceTypes()
    {
        $types = [];

        if ($this->price) {
            $types['price'] = [
                'name' => 'Precio Regular',
                'value' => $this->price,
                'description' => 'Precio estándar del producto'
            ];
        }

        if ($this->cost_price) {
            $types['cost_price'] = [
                'name' => 'Precio de Costo',
                'value' => $this->cost_price,
                'description' => 'Precio de compra del producto'
            ];
        }

        if ($this->wholesale_price) {
            $types['wholesale_price'] = [
                'name' => 'Precio al Por Mayor',
                'value' => $this->wholesale_price,
                'description' => 'Precio para compras al por mayor'
            ];
        }

        if ($this->retail_price) {
            $types['retail_price'] = [
                'name' => 'Precio al Detalle',
                'value' => $this->retail_price,
                'description' => 'Precio para ventas al detalle'
            ];
        }

        if ($this->special_price) {
            $types['special_price'] = [
                'name' => 'Precio Especial',
                'value' => $this->special_price,
                'description' => 'Precio promocional o especial'
            ];
        }

        return $types;
    }

    /**
     * Calcular margen de ganancia
     */
    public function calculateProfitMargin($priceType = 'price')
    {
        if (!$this->cost_price || !$this->$priceType) {
            return null;
        }

        $profit = $this->$priceType - $this->cost_price;
        $margin = ($profit / $this->cost_price) * 100;

        return [
            'profit' => $profit,
            'margin_percentage' => round($margin, 2),
            'cost_price' => $this->cost_price,
            'selling_price' => $this->$priceType
        ];
    }

    /**
     * Obtener información completa del precio
     */
    public function getPriceInfo()
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'unit_code' => $this->unit->unit_code ?? null,
            'unit_name' => $this->unit->unit_name ?? null,
            'prices' => $this->getAvailablePriceTypes(),
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'notes' => $this->notes
        ];
    }

    /**
     * Scope para precios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para precios por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para precios por unidad específica
     */
    public function scopeByUnit($query, $unitCode)
    {
        return $query->whereHas('unit', function($q) use ($unitCode) {
            $q->where('unit_code', $unitCode);
        });
    }
}
