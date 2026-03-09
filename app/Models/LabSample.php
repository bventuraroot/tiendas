<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabSample extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_muestra',
        'order_id',
        'tipo_muestra',
        'fecha_toma',
        'condiciones_muestra',
        'observaciones',
        'tomada_por',
        'estado',
        'company_id',
    ];

    protected $casts = [
        'fecha_toma' => 'datetime',
    ];

    /**
     * Relación con la orden
     */
    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'order_id');
    }

    /**
     * Relación con el usuario que tomó la muestra
     */
    public function takenBy()
    {
        return $this->belongsTo(User::class, 'tomada_por');
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con los resultados
     */
    public function results()
    {
        return $this->hasMany(LabResult::class, 'sample_id');
    }
}

