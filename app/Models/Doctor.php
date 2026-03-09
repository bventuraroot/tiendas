<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo_medico',
        'numero_jvpm',
        'user_id',
        'nombres',
        'apellidos',
        'especialidad',
        'especialidades_secundarias',
        'telefono',
        'email',
        'direccion_consultorio',
        'horario_atencion',
        'firma',
        'estado',
        'company_id',
    ];

    protected $appends = [
        'nombre_completo',
    ];

    /**
     * Accessor para obtener el nombre completo del médico
     */
    public function getNombreCompletoAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    /**
     * Relación con el usuario (si el médico tiene cuenta de usuario)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
     * Relación con horarios de atención
     */
    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * Obtener horarios activos
     */
    public function activeSchedules()
    {
        return $this->hasMany(DoctorSchedule::class)->where('activo', true);
    }

    /**
     * Verificar si el médico está disponible en una fecha y hora específica
     */
    public function isAvailableAt($fechaHora)
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        $carbon = \Carbon\Carbon::parse($fechaHora);
        $diaSemana = strtolower($carbon->locale('es')->dayName);

        // Mapear días en español
        $diasMap = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];

        $diaSemanaEsp = $diasMap[strtolower($carbon->format('l'))] ?? null;

        if (!$diaSemanaEsp) {
            return false;
        }

        $schedule = $this->activeSchedules()
            ->where('dia_semana', $diaSemanaEsp)
            ->first();

        if (!$schedule) {
            return false;
        }

        return $schedule->isAvailableAt($fechaHora);
    }

    /**
     * Obtener horarios disponibles para un día específico
     */
    public function getAvailableHoursForDate($fecha)
    {
        $carbon = \Carbon\Carbon::parse($fecha);
        $diaSemana = strtolower($carbon->locale('es')->dayName);

        $diasMap = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];

        $diaSemanaEsp = $diasMap[strtolower($carbon->format('l'))] ?? null;

        if (!$diaSemanaEsp) {
            return [];
        }

        $schedule = $this->activeSchedules()
            ->where('dia_semana', $diaSemanaEsp)
            ->first();

        if (!$schedule) {
            return [];
        }

        // Generar slots de tiempo cada 30 minutos
        $horas = [];
        $inicio = \Carbon\Carbon::parse($fecha . ' ' . $schedule->hora_inicio);
        $fin = \Carbon\Carbon::parse($fecha . ' ' . $schedule->hora_fin);

        while ($inicio->lt($fin)) {
            $horas[] = $inicio->format('H:i');
            $inicio->addMinutes(30);
        }

        return $horas;
    }
}

