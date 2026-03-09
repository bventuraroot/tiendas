<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalConsultation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_consulta',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'fecha_hora',
        'motivo_consulta',
        'sintomas',
        'temperatura',
        'presion_arterial',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'peso',
        'altura',
        'imc',
        'saturacion_oxigeno',
        'exploracion_fisica',
        'diagnostico_cie10',
        'diagnostico_descripcion',
        'diagnosticos_secundarios',
        'plan_tratamiento',
        'indicaciones',
        'observaciones',
        'genera_receta',
        'receta_digital',
        'requiere_seguimiento',
        'fecha_proximo_control',
        'estado',
        'company_id',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_proximo_control' => 'date',
        'genera_receta' => 'boolean',
        'requiere_seguimiento' => 'boolean',
    ];

    /**
     * Relación con la cita
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

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
     * Relación con las recetas
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'consultation_id');
    }

    /**
     * Relación con órdenes de laboratorio
     */
    public function labOrders()
    {
        return $this->hasMany(LabOrder::class, 'consultation_id');
    }
}

