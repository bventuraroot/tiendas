<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'consultation_id',
        'tipo_documento',
        'titulo',
        'descripcion',
        'ruta_archivo',
        'fecha_documento',
        'uploaded_by',
        'company_id',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
    ];

    /**
     * Relación con el paciente
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con la consulta
     */
    public function consultation()
    {
        return $this->belongsTo(MedicalConsultation::class);
    }

    /**
     * Relación con el usuario que subió el archivo
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

