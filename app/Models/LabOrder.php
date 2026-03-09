<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_orden',
        'patient_id',
        'doctor_id',
        'consultation_id',
        'fecha_orden',
        'fecha_toma_muestra',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'indicaciones_especiales',
        'requiere_ayuno',
        'preparacion_requerida',
        'prioridad',
        'estado',
        'total',
        'recibido_por',
        'procesado_por',
        'company_id',
    ];

    protected $casts = [
        'fecha_orden' => 'datetime',
        'fecha_toma_muestra' => 'datetime',
        'fecha_entrega_estimada' => 'datetime',
        'fecha_entrega_real' => 'datetime',
        'requiere_ayuno' => 'boolean',
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
     * Relación con la consulta
     */
    public function consultation()
    {
        return $this->belongsTo(MedicalConsultation::class, 'consultation_id');
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con el usuario que recibió la orden
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    /**
     * Relación con el técnico que procesó
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    /**
     * Relación con los exámenes de la orden
     */
    public function exams()
    {
        return $this->hasMany(LabOrderExam::class, 'order_id');
    }

    /**
     * Relación con las muestras
     */
    public function samples()
    {
        return $this->hasMany(LabSample::class, 'order_id');
    }

    /**
     * Verificar si todos los exámenes de la orden tienen resultados completos
     */
    public function allExamsHaveResults()
    {
        $exams = $this->exams;
        
        if ($exams->isEmpty()) {
            return false;
        }

        // Verificar que todos los exámenes tengan estado 'completado' y al menos un resultado
        foreach ($exams as $exam) {
            if ($exam->estado !== 'completado') {
                return false;
            }
            
            // Verificar que tenga al menos un resultado registrado
            if ($exam->results->isEmpty()) {
                return false;
            }
        }

        return true;
    }
}

