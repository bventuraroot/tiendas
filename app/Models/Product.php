<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'state',
        'cfiscal',
        'type',
        'price',
        'description',
        'has_expiration',
        'expiration_days',
        'expiration_type',
        'expiration_notes',
        'image',
        'category',
        'provider_id',
        'marca_id',
        'pharmaceutical_laboratory_id',
        'presentation_type',
        'specialty',
        'registration_number',
        'formula',
        'unit_measure',
        'sale_form',
        'product_type',
        'pastillas_per_blister',
        'blisters_per_caja',
        'user_id',
        // Campos de unidades de medida
        'weight_per_unit',
        'volume_per_unit',
        'content_per_unit',
        'sale_type'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function pharmaceuticalLaboratory()
    {
        return $this->belongsTo(PharmaceuticalLaboratory::class, 'pharmaceutical_laboratory_id');
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Relación con las conversiones de unidades del producto
     */
    public function unitConversions()
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    /**
     * Obtener la conversión por defecto del producto
     */
    public function defaultUnitConversion()
    {
        return $this->hasOne(ProductUnitConversion::class)->where('is_default', true);
    }

    /**
     * Obtener conversiones activas del producto
     */
    public function activeUnitConversions()
    {
        return $this->hasMany(ProductUnitConversion::class)->where('is_active', true);
    }

    /**
     * Relación con unidades a través de conversiones
     */
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'product_unit_conversions')
                    ->withPivot(['conversion_factor', 'price_multiplier', 'is_default', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Relación con los detalles de compras
     */
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Relación con los precios múltiples del producto
     */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Obtener precios activos del producto
     */
    public function activePrices()
    {
        return $this->hasMany(ProductPrice::class)->where('is_active', true);
    }

    /**
     * Obtener el precio por defecto del producto
     */
    public function defaultPrice()
    {
        return $this->hasOne(ProductPrice::class)->where('is_default', true);
    }

        /**
     * Obtener el peso en una unidad específica
     */
    public function getWeightInUnit($unit)
    {
        if ($this->sale_type !== 'weight' || !$this->weight_per_unit) {
            return null;
        }

        return match($unit) {
            'gr', 'grams' => $this->weight_per_unit * 453.592, // 1 lb = 453.592 gr
            'lb', 'lbs', 'pounds' => $this->weight_per_unit,
            'kg', 'kilograms' => $this->weight_per_unit * 0.453592, // 1 lb = 0.453592 kg
            default => null
        };
    }

    /**
     * Obtener el volumen en una unidad específica
     */
    public function getVolumeInUnit($unit)
    {
        if ($this->sale_type !== 'volume' || !$this->volume_per_unit) {
            return null;
        }

        return match($unit) {
            'ml', 'milliliters' => $this->volume_per_unit * 1000, // 1 l = 1000 ml
            'l', 'liters' => $this->volume_per_unit,
            default => null
        };
    }

    /**
     * Calcular el precio por libra (para productos por peso)
     */
    public function getPricePerPound()
    {
        if ($this->sale_type === 'weight' && $this->weight_per_unit && $this->weight_per_unit > 0) {
            return $this->price / $this->weight_per_unit;
        }
        return null;
    }

    /**
     * Calcular el precio por litro (para productos por volumen)
     */
    public function getPricePerLiter()
    {
        if ($this->sale_type === 'volume' && $this->volume_per_unit && $this->volume_per_unit > 0) {
            return $this->price / $this->volume_per_unit;
        }
        return null;
    }

    /**
     * Obtener información de medidas del producto
     */
    public function getMeasureInfo()
    {
        $info = [
            'sale_type' => $this->sale_type,
            'content_per_unit' => $this->content_per_unit
        ];

        if ($this->sale_type === 'weight') {
            $info['weight_per_unit'] = $this->weight_per_unit;
            $info['weight_kg'] = $this->getWeightInUnit('kg');
            $info['weight_grams'] = $this->getWeightInUnit('gr');
            $info['price_per_pound'] = $this->getPricePerPound();
        }

        if ($this->sale_type === 'volume') {
            $info['volume_per_unit'] = $this->volume_per_unit;
            $info['volume_ml'] = $this->getVolumeInUnit('ml');
            $info['price_per_liter'] = $this->getPricePerLiter();
        }

        return $info;
    }

    /**
     * Calcular fecha de caducidad basada en la fecha de compra
     */
    public function calculateExpirationDate($purchaseDate = null)
    {
        if (!$this->has_expiration || !$this->expiration_days) {
            return null;
        }

        $date = $purchaseDate ? Carbon::parse($purchaseDate) : Carbon::now();

        return match($this->expiration_type) {
            'days' => $date->addDays($this->expiration_days),
            'months' => $date->addMonths($this->expiration_days),
            'years' => $date->addYears($this->expiration_days),
            default => $date->addDays($this->expiration_days)
        };
    }

    /**
     * Verificar si el producto tiene caducidad configurada
     */
    public function hasExpirationConfigured()
    {
        return $this->has_expiration && $this->expiration_days > 0;
    }
}
