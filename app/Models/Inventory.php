<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'description',
        'price',
        'category',
        'user_id',
        'provider_id',
        'active',
        'quantity',
        'base_unit_id',
        'base_quantity',
        'base_unit_price',
        'minimum_stock',
        'location'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'minimum_stock' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'base_unit_price' => 'decimal:4',
        'price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
