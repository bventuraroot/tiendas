<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_code',
        'unit_name',
        'unit_type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relación con las conversiones de productos
     */
    public function productConversions()
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    /**
     * Obtener unidades activas
     */
    public static function getActiveUnits()
    {
        return static::where('is_active', true)
                    ->orderBy('unit_name')
                    ->get();
    }

    /**
     * Obtener unidades por tipo
     */
    public static function getUnitsByType($type)
    {
        return static::where('is_active', true)
                    ->where('unit_type', $type)
                    ->orderBy('unit_name')
                    ->get();
    }

    /**
     * Obtener unidad por código
     */
    public static function getByCode($unitCode)
    {
        return static::where('unit_code', $unitCode)
                    ->where('is_active', true)
                    ->first();
    }

    /**
     * Scope para unidades activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para unidades por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('unit_type', $type);
    }

    /**
     * Obtener información completa de la unidad
     */
    public function getUnitInfo()
    {
        return [
            'id' => $this->id,
            'unit_code' => $this->unit_code,
            'unit_name' => $this->unit_name,
            'unit_type' => $this->unit_type,
            'description' => $this->description,
            'is_active' => $this->is_active
        ];
    }
}
