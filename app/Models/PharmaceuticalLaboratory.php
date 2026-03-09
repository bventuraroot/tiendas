<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaceuticalLaboratory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'country',
        'description',
        'phone',
        'email',
        'address',
        'website',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relación con productos
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'pharmaceutical_laboratory_id');
    }

    /**
     * Scope para laboratorios activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}


