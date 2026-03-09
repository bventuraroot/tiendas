<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
        'notas',
        'company_id',
    ];

    protected $casts = [
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'activo' => 'boolean',
    ];

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
     * Verificar si un horario está disponible para una fecha y hora específica
     */
    public function isAvailableAt($fechaHora)
    {
        if (!$this->activo) {
            return false;
        }

        $carbon = \Carbon\Carbon::parse($fechaHora);
        
        // Mapear días en inglés a español
        $diasMap = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];

        $diaEnIngles = strtolower($carbon->format('l'));
        $diaEnEsp = $diasMap[$diaEnIngles] ?? null;
        
        if ($diaEnEsp !== $this->dia_semana) {
            return false;
        }

        $horaCita = $carbon->format('H:i:s');
        $horaInicio = $this->hora_inicio;
        $horaFin = $this->hora_fin;

        return $horaCita >= $horaInicio && $horaCita <= $horaFin;
    }
}
