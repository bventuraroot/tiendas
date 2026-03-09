<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'date_pay',
        'current',
        'initial',
        'amountpay',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'date_pay' => 'datetime',
        'current' => 'decimal:2',
        'initial' => 'decimal:2',
        'amountpay' => 'decimal:2'
    ];

    /**
     * Relación con la compra
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
