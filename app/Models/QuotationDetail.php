<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'subtotal',
        'total',
        'description'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relación con la cotización
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcular automáticamente los valores del detalle
     */
    public function calculateAmounts()
    {
        // Calcular subtotal sin descuento
        $this->subtotal = $this->quantity * $this->unit_price;

        // Aplicar descuento
        if ($this->discount_percentage > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
            $this->subtotal = $this->subtotal - $this->discount_amount;
        }

        // El total es igual al subtotal ya que los productos ya incluyen IVA
        $this->total = $this->subtotal;

        return $this;
    }

    /**
     * Boot method para calcular automáticamente al crear/actualizar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            $detail->calculateAmounts();
        });

        static::saved(function ($detail) {
            // Recalcular totales de la cotización después de guardar el detalle
            $detail->quotation->calculateTotals();
        });

        static::deleted(function ($detail) {
            // Recalcular totales de la cotización después de eliminar el detalle
            if ($detail->quotation) {
                $detail->quotation->calculateTotals();
            }
        });
    }
}
