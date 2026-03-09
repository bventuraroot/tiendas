<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:appointments.index')->only(['index']);
        $this->middleware('permission:appointments.create')->only(['create', 'store']);
        $this->middleware('permission:appointments.edit')->only(['edit', 'update']);
        $this->middleware('permission:appointments.destroy')->only(['destroy']);
        $this->middleware('permission:appointments.show')->only(['show']);
    }

    /**
     * Display a listing of appointments
     */
    public function index()
    {
        $user = Auth::user();
        $company_id = $user->company_id ?? Company::first()->id;

        return view('clinic.appointments.index', compact('company_id'));
    }

    /**
     * Get appointments for calendar view
     */
    public function getAppointments(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

        $start = $request->get('start');
        $end = $request->get('end');
        $doctor_id = $request->get('doctor_id');

        $query = Appointment::with(['patient', 'doctor'])
            ->where('company_id', $company_id);

        if ($start && $end) {
            $query->whereBetween('fecha_hora', [$start, $end]);
        }

        if ($doctor_id) {
            $query->where('doctor_id', $doctor_id);
        }

        $appointments = $query->orderBy('fecha_hora')->get();

        // Formatear para FullCalendar
        $events = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->patient->nombre_completo . ' - ' . $appointment->doctor->nombre_completo,
                'start' => $appointment->fecha_hora->toIso8601String(),
                'end' => $appointment->fecha_hora->addMinutes($appointment->duracion_minutos)->toIso8601String(),
                'backgroundColor' => $this->getColorByStatus($appointment->estado),
                'borderColor' => $this->getColorByStatus($appointment->estado),
                'extendedProps' => [
                    'patient' => $appointment->patient->nombre_completo,
                    'doctor' => $appointment->doctor->nombre_completo,
                    'status' => $appointment->estado,
                    'tipo' => $appointment->tipo_cita,
                    'motivo' => $appointment->motivo_consulta,
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Show the form for creating a new appointment
     */
    public function create()
    {
        $patients = Patient::where('estado', 'activo')->get();
        $doctors = Doctor::where('estado', 'activo')->get();
        
        return view('clinic.appointments.create', compact('patients', 'doctors'));
    }

    /**
     * Store a newly created appointment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'fecha_hora' => 'required|date',
            'duracion_minutos' => 'required|integer|min:15|max:180',
            'tipo_cita' => 'required|in:primera_vez,seguimiento,emergencia,control',
            'motivo_consulta' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $request->company_id ?? $user->company_id ?? Company::first()->id;
        
        // Generar código de cita único
        $validated['codigo_cita'] = 'CITA-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $validated['estado'] = 'programada';

        // Verificar disponibilidad del doctor
        $fecha_hora = Carbon::parse($validated['fecha_hora']);
        $doctor = Doctor::with('activeSchedules')->findOrFail($validated['doctor_id']);

        // Verificar si el médico está disponible según su horario de atención
        if (!$doctor->isAvailableAt($fecha_hora)) {
            return response()->json([
                'success' => false,
                'message' => 'El médico no tiene horario de atención disponible en ese día y hora'
            ], 422);
        }

        // Verificar conflictos con otras citas
        $conflicto = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('estado', '!=', 'cancelada')
            ->where(function ($query) use ($fecha_hora, $validated) {
                $query->whereBetween('fecha_hora', [
                    $fecha_hora,
                    $fecha_hora->copy()->addMinutes($validated['duracion_minutos'])
                ]);
            })
            ->exists();

        if ($conflicto) {
            return response()->json([
                'success' => false,
                'message' => 'El médico ya tiene una cita programada en ese horario'
            ], 422);
        }

        $appointment = Appointment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cita creada exitosamente',
            'appointment' => $appointment->load(['patient', 'doctor'])
        ]);
    }

    /**
     * Display the specified appointment
     */
    public function show($id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'consultation'])->findOrFail($id);
        
        return view('clinic.appointments.show', compact('appointment'));
    }

    /**
     * Update appointment status
     */
    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:programada,confirmada,en_curso,completada,cancelada,no_asistio',
            'motivo_cancelacion' => 'required_if:estado,cancelada|nullable|string',
        ]);

        if ($validated['estado'] === 'confirmada') {
            $validated['fecha_confirmacion'] = now();
        }

        if ($validated['estado'] === 'cancelada') {
            $validated['fecha_cancelacion'] = now();
        }

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Estado de cita actualizado',
            'appointment' => $appointment
        ]);
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'motivo_cancelacion' => 'required|string',
        ]);

        $appointment->update([
            'estado' => 'cancelada',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $validated['motivo_cancelacion'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cita cancelada exitosamente'
        ]);
    }

    /**
     * Get color by appointment status
     */
    private function getColorByStatus($estado)
    {
        return match($estado) {
            'programada' => '#3788d8',
            'confirmada' => '#22c55e',
            'en_curso' => '#f59e0b',
            'completada' => '#8b5cf6',
            'cancelada' => '#ef4444',
            'no_asistio' => '#6b7280',
            default => '#3788d8'
        };
    }

    /**
     * Obtener horarios disponibles de un médico para una fecha
     */
    public function getAvailableHours(Request $request)
    {
        $doctorId = $request->get('doctor_id');
        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        if (!$doctorId) {
            return response()->json([
                'success' => false,
                'message' => 'Debe seleccionar un médico'
            ], 422);
        }

        $doctor = Doctor::with('activeSchedules')->findOrFail($doctorId);
        $horas = $doctor->getAvailableHoursForDate($fecha);

        // Filtrar horas que ya tienen citas
        $citasExistentes = Appointment::where('doctor_id', $doctorId)
            ->whereDate('fecha_hora', $fecha)
            ->where('estado', '!=', 'cancelada')
            ->get()
            ->map(function ($cita) {
                return Carbon::parse($cita->fecha_hora)->format('H:i');
            })
            ->toArray();

        $horasDisponibles = array_filter($horas, function ($hora) use ($citasExistentes) {
            return !in_array($hora, $citasExistentes);
        });

        return response()->json([
            'success' => true,
            'horas' => array_values($horasDisponibles),
            'schedules' => $doctor->activeSchedules
        ]);
    }
}

