<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_paciente',
        'numero_expediente',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'documento_identidad',
        'tipo_documento',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'telefono_emergencia',
        'email',
        'direccion',
        'tipo_sangre',
        'alergias',
        'enfermedades_cronicas',
        'estado',
        'company_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    protected $appends = [
        'nombre_completo',
        'edad',
    ];

    /**
     * Accessor para obtener el nombre completo del paciente
     */
    public function getNombreCompletoAttribute()
    {
        $nombre = trim($this->primer_nombre . ' ' . $this->segundo_nombre);
        $apellidos = trim($this->primer_apellido . ' ' . $this->segundo_apellido);
        return trim($nombre . ' ' . $apellidos);
    }

    /**
     * Accessor para calcular la edad
     */
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return $this->fecha_nacimiento->diffInYears(now());
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con citas médicas
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Relación con consultas médicas
     */
    public function consultations()
    {
        return $this->hasMany(MedicalConsultation::class);
    }

    /**
     * Relación con órdenes de laboratorio
     */
    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }

    /**
     * Relación con historial clínico
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
}

