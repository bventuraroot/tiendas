<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'date_pay',
        'current',
        'initial',
        'amountpay',
        'user_id'
    ];

    protected $casts = [
        'date_pay' => 'datetime',
        'current' => 'decimal:2',
        'initial' => 'decimal:2',
        'amountpay' => 'decimal:2'
    ];

    // Relaciones
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
