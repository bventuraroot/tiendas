<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabQualityControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'fecha_control',
        'lote_reactivo',
        'fecha_vencimiento_reactivo',
        'equipo_utilizado',
        'resultado_control',
        'resultado',
        'observaciones',
        'realizado_por',
        'company_id',
    ];

    protected $casts = [
        'fecha_control' => 'date',
        'fecha_vencimiento_reactivo' => 'date',
    ];

    /**
     * Relación con el examen
     */
    public function exam()
    {
        return $this->belongsTo(LabExam::class, 'exam_id');
    }

    /**
     * Relación con el usuario que realizó el control
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

