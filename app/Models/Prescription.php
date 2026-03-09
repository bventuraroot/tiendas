<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_receta',
        'consultation_id',
        'patient_id',
        'doctor_id',
        'fecha_emision',
        'fecha_vencimiento',
        'indicaciones_generales',
        'estado',
        'company_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Relación con la consulta
     */
    public function consultation()
    {
        return $this->belongsTo(MedicalConsultation::class);
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
     * Relación con los detalles de la receta
     */
    public function details()
    {
        return $this->hasMany(PrescriptionDetail::class);
    }
}

