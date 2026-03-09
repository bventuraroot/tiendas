<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_exam_id',
        'sample_id',
        'parametro',
        'resultado',
        'unidad_medida',
        'valor_referencia',
        'estado_resultado',
        'observaciones',
        'fecha_procesamiento',
        'procesado_por',
        'validado_por',
        'fecha_validacion',
        'resultado_critico',
    ];

    protected $casts = [
        'fecha_procesamiento' => 'datetime',
        'fecha_validacion' => 'datetime',
        'resultado_critico' => 'boolean',
    ];

    /**
     * Relación con el examen de la orden
     */
    public function orderExam()
    {
        return $this->belongsTo(LabOrderExam::class, 'order_exam_id');
    }

    /**
     * Relación con la muestra
     */
    public function sample()
    {
        return $this->belongsTo(LabSample::class, 'sample_id');
    }

    /**
     * Relación con el usuario que procesó
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    /**
     * Relación con el usuario que validó
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}

