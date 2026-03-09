<?php

namespace App\Http\Controllers;

use App\Models\LabExam;
use App\Models\LabExamCategory;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lab-exams.index')->only(['index']);
        $this->middleware('permission:lab-exams.create')->only(['create', 'store']);
        $this->middleware('permission:lab-exams.edit')->only(['edit', 'update']);
        $this->middleware('permission:lab-exams.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of lab exams
     */
    public function index()
    {
        $user = Auth::user();
        $company_id = $user->company_id ?? Company::first()->id;

        $categories = LabExamCategory::where('company_id', $company_id)
            ->orderBy('orden')
            ->get();

        return view('laboratory.exams.index', compact('company_id', 'categories'));
    }

    /**
     * Get exams data for datatables
     */
    public function getExams(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

        $exams = LabExam::with('category')
            // Contar cuántas órdenes \"autorizadas\" usan este examen
            ->withCount(['orderExams as authorized_orders_count' => function ($q) {
                $q->whereHas('order', function ($qo) {
                    $qo->whereIn('estado', ['en_proceso', 'completada', 'entregada']);
                });
            }])
            ->where('company_id', $company_id)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo_examen', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo_muestra', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('nombre')
            ->paginate(20);

        return response()->json($exams);
    }

    /**
     * Show the form for creating a new exam
     */
    public function create()
    {
        $categories = LabExamCategory::where('activo', true)->orderBy('nombre')->get();

        return view('laboratory.exams.create', compact('categories'));
    }

    /**
     * Store a newly created exam
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:lab_exam_categories,id',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'preparacion_requerida' => 'nullable|string',
            'tipo_muestra' => 'required|string|max:100',
            'tiempo_procesamiento_horas' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'valores_referencia' => 'nullable|string',
            'requiere_ayuno' => 'boolean',
            'prioridad' => 'required|in:normal,urgente,stat',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $request->company_id ?? $user->company_id ?? Company::first()->id;

        // Generar código de examen único
        $validated['codigo_examen'] = 'EX-' . strtoupper(uniqid());
        $validated['activo'] = true;

        // Procesar template y valores de referencia específicos
        if ($request->has('template_id') && $request->template_id) {
            $validated['template_id'] = $request->template_id;
            $validated['valores_referencia_especificos'] = $this->procesarValoresReferenciaEspecificos($request);
        }

        $exam = LabExam::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Examen creado exitosamente',
                'exam' => $exam->load('category')
            ]);
        }

        return redirect()->route('lab-exams.index')->with('success', 'Examen creado exitosamente');
    }

    /**
     * Display the specified exam
     */
    public function show($id)
    {
        try {
            $exam = LabExam::with('category')->findOrFail($id);

            return response()->json($exam);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo examen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el examen: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified exam
     */
    public function update(Request $request, $id)
    {
        $exam = LabExam::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:lab_exam_categories,id',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'preparacion_requerida' => 'nullable|string',
            'tipo_muestra' => 'required|string|max:100',
            'tiempo_procesamiento_horas' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'valores_referencia' => 'nullable|string',
            'requiere_ayuno' => 'boolean',
            'prioridad' => 'required|in:normal,urgente,stat',
            'activo' => 'boolean',
        ]);

        // Procesar template y valores de referencia específicos
        // Solo incluir estos campos si están presentes en el request y no están vacíos
        if ($request->has('template_id') && !empty($request->template_id)) {
            // Verificar si las columnas existen antes de intentar actualizarlas
            try {
                $columns = \Schema::getColumnListing('lab_exams');
                if (in_array('template_id', $columns)) {
                    $validated['template_id'] = $request->template_id;
                    $validated['valores_referencia_especificos'] = $this->procesarValoresReferenciaEspecificos($request);
                }
            } catch (\Exception $e) {
                // Si hay error al verificar columnas, simplemente no incluimos estos campos
                \Log::warning('No se pudieron actualizar campos de template: ' . $e->getMessage());
            }
        }

        $exam->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Examen actualizado exitosamente',
                'exam' => $exam->load('category')
            ]);
        }

        return redirect()->route('lab-exams.index')->with('success', 'Examen actualizado exitosamente');
    }

    /**
     * Remove the specified exam
     */
    public function destroy($id)
    {
        try {
            $exam = LabExam::findOrFail($id);

            // Verificar si tiene órdenes asociadas
            if ($exam->orderExams()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el examen porque tiene órdenes asociadas'
                ], 400);
            }

            $exam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Examen eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando examen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el examen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar todos los exámenes (eliminar permanentemente)
     */
    public function clearAll(Request $request)
    {
        try {
            $user = Auth::user();
            $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

            // Contar exámenes antes de eliminar
            $count = LabExam::where('company_id', $company_id)->count();

            // Eliminar todos los exámenes de la empresa (soft delete)
            LabExam::where('company_id', $company_id)->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} exámenes exitosamente"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error limpiando exámenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar los exámenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar permanentemente todos los exámenes (force delete)
     */
    public function forceClearAll(Request $request)
    {
        try {
            $user = Auth::user();
            $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);

            // Contar exámenes antes de eliminar
            $count = LabExam::withTrashed()->where('company_id', $company_id)->count();

            // Eliminar permanentemente todos los exámenes
            LabExam::withTrashed()->where('company_id', $company_id)->forceDelete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron permanentemente {$count} exámenes"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando permanentemente exámenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar los exámenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active exams for select dropdown
     */
    public function getActiveExams(Request $request)
    {
        $user = Auth::user();
        $company_id = $request->get('company_id', $user->company_id ?? Company::first()->id);
        $category_id = $request->get('category_id');

        $query = LabExam::with('category')
            ->where('company_id', $company_id)
            ->where('activo', true);

        if ($category_id) {
            $query->where('category_id', $category_id);
        }

        $exams = $query->orderBy('nombre')->get();

        return response()->json($exams);
    }

    /**
     * Toggle exam status
     */
    public function toggleStatus($id)
    {
        $exam = LabExam::findOrFail($id);
        $exam->activo = !$exam->activo;
        $exam->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'activo' => $exam->activo
        ]);
    }

    /**
     * Procesar valores de referencia específicos según el template
     */
    private function procesarValoresReferenciaEspecificos(Request $request)
    {
        $templateId = $request->template_id;
        $valores = [];

        if ($templateId === 'acido_valproico') {
            $valores = [
                'unidad_medida' => $request->unidad_medida ?? 'ug/mL',
                'categoria' => 'DROGAS TERAPEUTICAS',
                'terapeutico' => [
                    'label' => 'Terapéutico',
                    'min' => (float)($request->ref_terapeutico_min ?? 50),
                    'max' => (float)($request->ref_terapeutico_max ?? 100),
                    'rango' => ($request->ref_terapeutico_min ?? 50) . ' a ' . ($request->ref_terapeutico_max ?? 100) . ' ug/mL'
                ],
                'toxico' => [
                    'label' => 'Tóxico',
                    'min' => (float)($request->ref_toxico_min ?? 100),
                    'max' => null,
                    'rango' => 'Mayor de ' . ($request->ref_toxico_min ?? 100) . ' ug/mL'
                ]
            ];
        }

        return $valores;
    }
}

