<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_cita',
        'patient_id',
        'doctor_id',
        'fecha_hora',
        'duracion_minutos',
        'tipo_cita',
        'motivo_consulta',
        'notas',
        'estado',
        'fecha_confirmacion',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'company_id',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    /**
     * Relación con el paciente
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con el médico
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con la consulta médica (si fue realizada)
     */
    public function consultation()
    {
        return $this->hasOne(MedicalConsultation::class);
    }
}

