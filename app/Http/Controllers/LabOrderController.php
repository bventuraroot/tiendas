<?php

namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\LabOrderExam;
use App\Models\LabExam;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\MedicalConsultation;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LabOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lab-orders.index')->only(['index']);
        $this->middleware('permission:lab-orders.create')->only(['create', 'store']);
        $this->middleware('permission:lab-orders.edit')->only(['edit', 'update']);
        $this->middleware('permission:lab-orders.show')->only(['show']);
        $this->middleware('permission:lab-orders.process')->only(['updateStatus', 'addResults']);
    }

    /**
     * Display a listing of lab orders
     */
    public function index()
    {
        $user = Auth::user();
        $company_id = $user->company_id ?? Company::first()->id;

        return view('laboratory.orders.index', compact('company_id'));
    }

    /**
     * Get lab orders data
     */
    public function getOrders(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

        $orders = LabOrder::with(['patient', 'doctor', 'exams.results'])
            ->where('company_id', $company_id)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero_orden', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q) use ($search) {
                            $q->where('primer_nombre', 'like', "%{$search}%")
                                ->orWhere('primer_apellido', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->orderBy('fecha_orden', 'desc')
            ->paginate(15);

        // Agregar información sobre si todos los exámenes tienen resultados
        $orders->getCollection()->transform(function ($order) {
            $order->all_exams_completed = $order->allExamsHaveResults();
            return $order;
        });

        return response()->json($orders);
    }

    /**
     * Show the form for creating a new lab order
     */
    public function create(Request $request)
    {
        $consultation_id = $request->get('consultation_id');
        $patient_id = $request->get('patient_id');
        $doctor_id = $request->get('doctor_id');

        // Si hay consultation_id, cargar desde la consulta
        $consultation = $consultation_id ? MedicalConsultation::with(['patient', 'doctor'])->findOrFail($consultation_id) : null;

        // Si no hay consulta pero hay patient_id, crear objeto simulado para la vista
        if (!$consultation && $patient_id) {
            $consultation = (object)[
                'patient_id' => $patient_id,
                'doctor_id' => $doctor_id,
                'patient' => Patient::find($patient_id),
                'doctor' => $doctor_id ? Doctor::find($doctor_id) : null,
            ];
        }

        $patients = Patient::where('estado', 'activo')->get();
        $doctors = Doctor::where('estado', 'activo')->get();
        $exams = LabExam::with('category')->where('activo', true)->orderBy('nombre')->get();

        return view('laboratory.orders.create', compact('consultation', 'patients', 'doctors', 'exams'));
    }

    /**
     * Store a newly created lab order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'consultation_id' => 'nullable|exists:medical_consultations,id',
            'indicaciones_especiales' => 'nullable|string',
            'requiere_ayuno' => 'boolean',
            'preparacion_requerida' => 'nullable|string',
            'prioridad' => 'required|in:normal,urgente,stat',
            'exams' => 'required|array|min:1',
            'exams.*' => 'exists:lab_exams,id',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $validated['company_id'] = $request->company_id ?? $user->company_id ?? Company::first()->id;
            $validated['fecha_orden'] = now();
            $validated['recibido_por'] = $user->id;

            // Generar número de orden único
            $validated['numero_orden'] = 'LAB-' . now()->format('Ymd') . '-' . str_pad(LabOrder::count() + 1, 5, '0', STR_PAD_LEFT);

            // Calcular fecha estimada de entrega según prioridad
            $horasEstimadas = match($validated['prioridad']) {
                'stat' => 2,
                'urgente' => 12,
                default => 72
            };
            $validated['fecha_entrega_estimada'] = now()->addHours($horasEstimadas);

            // Calcular total
            $exams = LabExam::whereIn('id', $validated['exams'])->get();
            $validated['total'] = $exams->sum('precio');
            $validated['estado'] = 'pendiente';

            $order = LabOrder::create($validated);

            // Guardar exámenes de la orden
            foreach ($exams as $exam) {
                LabOrderExam::create([
                    'order_id' => $order->id,
                    'exam_id' => $exam->id,
                    'precio' => $exam->precio,
                    'estado' => 'pendiente'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden de laboratorio creada exitosamente',
                'order' => $order->load(['patient', 'doctor', 'exams'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified lab order
     */
    public function show($id)
    {
        $order = LabOrder::with([
            'patient',
            'doctor',
            'exams.exam',
            'exams.results',
            'samples'
        ])->findOrFail($id);

        return view('laboratory.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = LabOrder::with(['exams.results', 'doctor'])->findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:pendiente,muestra_tomada,en_proceso,completada,entregada,cancelada',
            'fecha_toma_muestra' => 'nullable|date',
            'fecha_entrega_real' => 'nullable|date',
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $user = Auth::user();

        // Si se está autorizando como completada (firma final), validar que todos los exámenes tengan resultados
        if ($validated['estado'] === 'completada') {
            if (!$order->allExamsHaveResults()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede autorizar la orden. Todos los exámenes deben tener resultados registrados.'
                ], 400);
            }

            // Marcar todos los resultados como validados/firmados
            $doctor = null;
            if (isset($validated['doctor_id']) && $validated['doctor_id']) {
                $doctor = \App\Models\Doctor::find($validated['doctor_id']);
            }

            // Usar el user_id del doctor si existe, sino usar el usuario actual
            $validatorUserId = $doctor && $doctor->user_id
                ? $doctor->user_id
                : $user->id;

            // Actualizar todos los resultados de todos los exámenes de la orden
            foreach ($order->exams as $exam) {
                foreach ($exam->results as $result) {
                    if (!$result->validado_por) {
                        $result->validado_por = $validatorUserId;
                        $result->fecha_validacion = now();
                        $result->save();
                    }
                }
            }
        }

        if ($validated['estado'] === 'en_proceso') {
            $validated['procesado_por'] = $user->id;
        }

        if ($validated['estado'] === 'entregada' && !isset($validated['fecha_entrega_real'])) {
            $validated['fecha_entrega_real'] = now();
        }

        if ($validated['estado'] === 'completada' && !isset($validated['fecha_entrega_real'])) {
            $validated['fecha_entrega_real'] = now();
        }

        // Asignar campos explícitamente para evitar problemas de mass-assignment
        $order->estado = $validated['estado'];

        if (array_key_exists('fecha_toma_muestra', $validated)) {
            $order->fecha_toma_muestra = $validated['fecha_toma_muestra'];
        }
        if (array_key_exists('fecha_entrega_real', $validated)) {
            $order->fecha_entrega_real = $validated['fecha_entrega_real'];
        }
        if (array_key_exists('doctor_id', $validated)) {
            // Permitir asignar o limpiar el médico
            $order->doctor_id = $validated['doctor_id'] ?? null;
        }

        // procesado_por ya se agregó arriba si aplica
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado de orden actualizado',
            'order' => $order
        ]);
    }

    /**
     * Get pending orders count and statistics
     */
    public function getPendingCount(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

        $pendientes = LabOrder::where('company_id', $company_id)
            ->where('estado', 'pendiente')
            ->count();

        $en_proceso = LabOrder::where('company_id', $company_id)
            ->where('estado', 'en_proceso')
            ->count();

        $completadas_hoy = LabOrder::where('company_id', $company_id)
            ->where('estado', 'completada')
            ->whereDate('fecha_entrega_real', Carbon::today())
            ->count();

        $total_mes = LabOrder::where('company_id', $company_id)
            ->whereMonth('fecha_orden', Carbon::now()->month)
            ->whereYear('fecha_orden', Carbon::now()->year)
            ->count();

        return response()->json([
            'pendientes' => $pendientes,
            'en_proceso' => $en_proceso,
            'completadas_hoy' => $completadas_hoy,
            'total_mes' => $total_mes
        ]);
    }

    /**
     * Print lab order
     */
    public function print($id)
    {
        $order = LabOrder::with([
            'patient',
            'doctor',
            'exams.exam',
            'company'
        ])->findOrFail($id);

        return view('laboratory.orders.print', compact('order'));
    }
}

