<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_code',
        'unit_id',
        'conversion_factor',
        'subtotal',
        'tax_amount',
        'total_amount',
        'expiration_date',
        'batch_number',
        'notes',
        'added_to_inventory',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:5',
        'subtotal' => 'decimal:5',
        'tax_amount' => 'decimal:5',
        'total_amount' => 'decimal:5',
        'expiration_date' => 'date:Y-m-d',
        'added_to_inventory' => 'boolean'
    ];

    /**
     * Relación con la compra
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la unidad de medida
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Mutator para expiration_date - asegurar formato correcto
     */
    public function setExpirationDateAttribute($value)
    {
        if ($value) {
            // Si es una fecha válida, formatearla como Y-m-d
            if (is_string($value)) {
                $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } else {
                $date = Carbon::parse($value)->startOfDay();
            }
            $this->attributes['expiration_date'] = $date->format('Y-m-d');
        } else {
            $this->attributes['expiration_date'] = null;
        }
    }

    /**
     * Accessor para expiration_date - asegurar formato correcto
     */
    public function getExpirationDateAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return null;
    }

    /**
     * Verificar si el producto está próximo a vencer
     */
    public function isExpiringSoon($days = 30)
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::now()->diffInDays($this->expiration_date, false) <= $days;
    }

    /**
     * Verificar si el producto ya venció
     */
    public function isExpired()
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::now()->isAfter($this->expiration_date);
    }

    /**
     * Obtener días restantes hasta la caducidad
     */
    public function getDaysUntilExpiration()
    {
        if (!$this->expiration_date) {
            return null;
        }

        // Usar fecha local para evitar problemas de zona horaria
        $today = Carbon::today();
        $expirationDate = Carbon::parse($this->expiration_date)->startOfDay();
        return $today->diffInDays($expirationDate, false);
    }

    /**
     * Calcular utilidad unitaria (precio venta - costo compra)
     */
    public function getUnitProfitAttribute()
    {
        if (!$this->product || !$this->product->price) {
            return 0;
        }

        return $this->product->price - $this->unit_price;
    }

    /**
     * Calcular utilidad total (utilidad unitaria * cantidad)
     */
    public function getTotalProfitAttribute()
    {
        return $this->unit_profit * $this->quantity;
    }

    /**
     * Calcular margen de utilidad en porcentaje
     */
    public function getProfitMarginAttribute()
    {
        if (!$this->product || !$this->product->price || $this->product->price == 0) {
            return 0;
        }

        return (($this->unit_profit / $this->product->price) * 100);
    }

    /**
     * Obtener información completa de utilidad
     */
    public function getProfitInfoAttribute()
    {
        return [
            'sale_price' => $this->product ? $this->product->price : 0,
            'unit_cost' => $this->unit_price,
            'unit_profit' => $this->unit_profit,
            'total_profit' => $this->total_profit,
            'profit_margin' => $this->profit_margin,
            'quantity' => $this->quantity
        ];
    }

    /**
     * Obtener el estado de caducidad
     */
    public function getExpirationStatus()
    {
        if (!$this->expiration_date) {
            return 'no_expiration';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon(7)) {
            return 'critical';
        }

        if ($this->isExpiringSoon(30)) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Obtener el color del estado de caducidad
     */
    public function getExpirationStatusColor()
    {
        return match($this->getExpirationStatus()) {
            'expired' => 'danger',
            'critical' => 'danger',
            'warning' => 'warning',
            'ok' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Obtener el texto del estado de caducidad
     */
    public function getExpirationStatusText()
    {
        return match($this->getExpirationStatus()) {
            'expired' => 'Vencido',
            'critical' => 'Crítico',
            'warning' => 'Advertencia',
            'ok' => 'OK',
            default => 'Sin caducidad'
        };
    }

}
