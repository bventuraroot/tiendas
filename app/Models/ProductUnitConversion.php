<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnitConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'price_multiplier',
        'is_default',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'price_multiplier' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
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
     * Obtener conversiones activas de un producto
     */
    public static function getActiveConversions($productId)
    {
        return static::where('product_id', $productId)
                    ->where('is_active', true)
                    ->with('unit')
                    ->orderBy('is_default', 'desc')
                    ->orderBy('id')
                    ->get();
    }

    /**
     * Obtener la conversión por defecto de un producto
     */
    public static function getDefaultConversion($productId)
    {
        return static::where('product_id', $productId)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->with('unit')
                    ->first();
    }

    /**
     * Obtener conversión específica por código de unidad
     */
    public static function getConversionByUnitCode($productId, $unitCode)
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
     * Calcular precio para esta conversión
     */
    public function calculatePrice($basePrice)
    {
        return $basePrice * $this->price_multiplier;
    }

    /**
     * Convertir cantidad a unidad base
     */
    public function convertToBase($quantity)
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convertir cantidad desde unidad base
     */
    public function convertFromBase($baseQuantity)
    {
        return $baseQuantity / $this->conversion_factor;
    }

    /**
     * Obtener información completa de la conversión
     */
    public function getConversionInfo()
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'unit_code' => $this->unit->unit_code ?? null,
            'unit_name' => $this->unit->unit_name ?? null,
            'conversion_factor' => $this->conversion_factor,
            'price_multiplier' => $this->price_multiplier,
            'is_default' => $this->is_default,
            'notes' => $this->notes
        ];
    }

    /**
     * Scope para conversiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para conversiones por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
