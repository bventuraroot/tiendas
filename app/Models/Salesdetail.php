<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesdetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'unit_name',
        'conversion_factor',
        'base_quantity_used',
        'amountp',
        'pricesale',
        'priceunit',
        'nosujeta',
        'exempt',
        'detained',
        'detained13',
        'detainedP',
        'renta',
        'fee',
        'feeiva',
        'reserva',
        'ruta',
        'destino',
        'linea',
        'canal',
        'user_id'
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'base_quantity_used' => 'decimal:4',
        'amountp' => 'decimal:2',
        'pricesale' => 'decimal:8',
        'priceunit' => 'decimal:8',
        'nosujeta' => 'decimal:8',
        'exempt' => 'decimal:8',
        'detained' => 'decimal:8',
        'detained13' => 'decimal:8',
        'detainedP' => 'decimal:8',
        'renta' => 'decimal:8',
        'fee' => 'decimal:8',
        'feeiva' => 'decimal:8'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

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
}
