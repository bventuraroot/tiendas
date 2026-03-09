<?php

namespace App\Http\Controllers;

use App\Models\MedicalConsultation;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:consultations.index')->only(['index']);
        $this->middleware('permission:consultations.create')->only(['create', 'store']);
        $this->middleware('permission:consultations.edit')->only(['edit', 'update']);
        $this->middleware('permission:consultations.show')->only(['show']);
    }

    /**
     * Display a listing of consultations
     */
    public function index()
    {
        $user = Auth::user();
        $company_id = $user->company_id ?? Company::first()->id;

        return view('clinic.consultations.index', compact('company_id'));
    }

    /**
     * Get consultations data
     */
    public function getConsultations(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

        $consultations = MedicalConsultation::with(['patient', 'doctor', 'appointment'])
            ->where('company_id', $company_id)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero_consulta', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q) use ($search) {
                            $q->where('primer_nombre', 'like', "%{$search}%")
                                ->orWhere('primer_apellido', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('fecha_hora', 'desc')
            ->paginate(15);

        return response()->json($consultations);
    }

    /**
     * Show the form for creating a new consultation
     */
    public function create(Request $request)
    {
        $appointment_id = $request->get('appointment_id');
        $appointment = $appointment_id ? Appointment::with(['patient', 'doctor'])->findOrFail($appointment_id) : null;

        $patients = Patient::where('estado', 'activo')->get();
        $doctors = Doctor::where('estado', 'activo')->get();

        return view('clinic.consultations.create', compact('appointment', 'patients', 'doctors'));
    }

    /**
     * Store a newly created consultation
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'appointment_id' => 'nullable|exists:appointments,id',
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:doctors,id',
                'motivo_consulta' => 'required|string',
                'sintomas' => 'nullable|string',

                // Signos vitales
                'temperatura' => 'nullable|numeric|min:30|max:45',
                'presion_arterial' => 'nullable|string|max:20',
                'frecuencia_cardiaca' => 'nullable|integer|min:30|max:200',
                'frecuencia_respiratoria' => 'nullable|integer|min:5|max:60',
                'peso' => 'nullable|numeric|min:0|max:500',
                'altura' => 'nullable|numeric|min:0|max:300',
                'saturacion_oxigeno' => 'nullable|integer|min:50|max:100',

                // Diagnóstico
                'exploracion_fisica' => 'nullable|string',
                'diagnostico_cie10' => 'nullable|string|max:20',
                'diagnostico_descripcion' => 'required|string',
                'diagnosticos_secundarios' => 'nullable|string',
                'plan_tratamiento' => 'nullable|string',
                'indicaciones' => 'nullable|string',
                'observaciones' => 'nullable|string',

                // Receta y seguimiento
                'genera_receta' => 'nullable|boolean',
                'receta_digital' => 'nullable|string',
                'requiere_seguimiento' => 'nullable|boolean',
                'fecha_proximo_control' => 'nullable|date',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Obtener company_id del paciente si está disponible, sino usar el primero disponible
            $patient = Patient::find($validated['patient_id']);
            $company_id = $request->company_id
                ?? ($patient ? $patient->company_id : null)
                ?? ($user && isset($user->company_id) ? $user->company_id : null)
                ?? Company::first()?->id;

            if (!$company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar la empresa. Por favor, asegúrese de que existe al menos una empresa en el sistema.'
                ], 422);
            }

            $validated['company_id'] = $company_id;
            $validated['fecha_hora'] = now();
            $validated['estado'] = 'en_curso'; // Estado inicial

            // Generar número de consulta único
            $datePrefix = now()->format('Ymd');
            $lastNumber = MedicalConsultation::withTrashed()
                ->where('numero_consulta', 'like', 'CONS-' . $datePrefix . '-%')
                ->count();

            $numeroConsulta = 'CONS-' . $datePrefix . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

            // Verificar que el número no exista (por si acaso)
            $attempts = 0;
            while (MedicalConsultation::withTrashed()->where('numero_consulta', $numeroConsulta)->exists() && $attempts < 10) {
                $lastNumber++;
                $numeroConsulta = 'CONS-' . $datePrefix . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            $validated['numero_consulta'] = $numeroConsulta;

            // Calcular IMC si hay peso y altura
            if (isset($validated['peso']) && isset($validated['altura']) && $validated['altura'] > 0) {
                $altura_metros = $validated['altura'] / 100;
                $validated['imc'] = round($validated['peso'] / ($altura_metros * $altura_metros), 2);
            }

            // Asegurar que los campos booleanos tengan valores
            $validated['genera_receta'] = $request->has('genera_receta') && ($request->genera_receta === '1' || $request->genera_receta === true || $request->genera_receta === 'on') ? true : false;
            $validated['requiere_seguimiento'] = $request->has('requiere_seguimiento') && ($request->requiere_seguimiento === '1' || $request->requiere_seguimiento === true || $request->requiere_seguimiento === 'on') ? true : false;

            // Limpiar campos vacíos para evitar problemas con null
            $nullableFields = ['sintomas', 'receta_digital', 'diagnosticos_secundarios', 'plan_tratamiento', 'indicaciones', 'observaciones', 'exploracion_fisica', 'diagnostico_cie10'];
            foreach ($nullableFields as $field) {
                if (isset($validated[$field]) && trim($validated[$field]) === '') {
                    $validated[$field] = null;
                }
            }

            $consultation = MedicalConsultation::create($validated);

            // Si viene de una cita, actualizar el estado de la cita
            if (isset($validated['appointment_id']) && $validated['appointment_id']) {
                $appointment = Appointment::find($validated['appointment_id']);
                if ($appointment) {
                    $appointment->update(['estado' => 'completada']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Consulta creada exitosamente',
                'consultation' => $consultation->load(['patient', 'doctor'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear consulta médica: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la consulta: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 422);
        }
    }

    /**
     * Display the specified consultation
     */
    public function show($id)
    {
        $consultation = MedicalConsultation::with(['patient', 'doctor', 'appointment', 'prescriptions', 'labOrders'])->findOrFail($id);

        return view('clinic.consultations.show', compact('consultation'));
    }

    /**
     * Update the specified consultation
     */
    public function update(Request $request, $id)
    {
        $consultation = MedicalConsultation::findOrFail($id);

        $validated = $request->validate([
            'motivo_consulta' => 'required|string',
            'sintomas' => 'nullable|string',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'presion_arterial' => 'nullable|string|max:20',
            'frecuencia_cardiaca' => 'nullable|integer|min:30|max:200',
            'frecuencia_respiratoria' => 'nullable|integer|min:5|max:60',
            'peso' => 'nullable|numeric|min:0|max:500',
            'altura' => 'nullable|numeric|min:0|max:300',
            'saturacion_oxigeno' => 'nullable|integer|min:50|max:100',
            'exploracion_fisica' => 'nullable|string',
            'diagnostico_cie10' => 'nullable|string|max:20',
            'diagnostico_descripcion' => 'required|string',
            'diagnosticos_secundarios' => 'nullable|string',
            'plan_tratamiento' => 'nullable|string',
            'indicaciones' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'genera_receta' => 'boolean',
            'receta_digital' => 'nullable|string',
            'requiere_seguimiento' => 'boolean',
            'fecha_proximo_control' => 'nullable|date',
            'estado' => 'required|in:en_curso,finalizada,cancelada',
        ]);

        // Recalcular IMC si hay peso y altura
        if (isset($validated['peso']) && isset($validated['altura']) && $validated['altura'] > 0) {
            $altura_metros = $validated['altura'] / 100;
            $validated['imc'] = round($validated['peso'] / ($altura_metros * $altura_metros), 2);
        }

        $consultation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Consulta actualizada exitosamente',
            'consultation' => $consultation
        ]);
    }

    /**
     * Finalizar consulta
     */
    public function finalize($id)
    {
        $consultation = MedicalConsultation::findOrFail($id);

        $consultation->update(['estado' => 'finalizada']);

        return response()->json([
            'success' => true,
            'message' => 'Consulta finalizada exitosamente'
        ]);
    }

    /**
     * Get patient medical history
     */
    public function patientHistory($patient_id)
    {
        $consultations = MedicalConsultation::with(['doctor', 'prescriptions', 'labOrders'])
            ->where('patient_id', $patient_id)
            ->where('estado', 'finalizada')
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return response()->json($consultations);
    }
}

