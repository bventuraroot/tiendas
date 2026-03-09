<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabEquipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_equipo',
        'nombre',
        'marca',
        'modelo',
        'numero_serie',
        'descripcion',
        'fecha_adquisicion',
        'ultima_calibracion',
        'proxima_calibracion',
        'ultimo_mantenimiento',
        'proximo_mantenimiento',
        'estado',
        'observaciones',
        'company_id',
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'ultima_calibracion' => 'date',
        'proxima_calibracion' => 'date',
        'ultimo_mantenimiento' => 'date',
        'proximo_mantenimiento' => 'date',
    ];

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

