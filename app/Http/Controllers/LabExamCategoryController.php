<?php

namespace App\Http\Controllers;

use App\Models\LabExamCategory;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabExamCategoryController extends Controller
{
    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $request->company_id ?? $user->company_id ?? Company::first()->id;

        // Establecer orden por defecto si no se proporciona
        if (empty($validated['orden'])) {
            // Obtener el máximo orden actual y agregar 1
            $maxOrden = LabExamCategory::where('company_id', $validated['company_id'])->max('orden') ?? 0;
            $validated['orden'] = $maxOrden + 1;
        }

        // Generar código único
        $validated['codigo'] = 'CAT-' . strtoupper(uniqid());
        $validated['activo'] = true;

        try {
            $category = LabExamCategory::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories
     */
    public function index()
    {
        $user = Auth::user();
        $company_id = request()->get('company_id', $user->company_id ?? Company::first()->id);

        $categories = LabExamCategory::where('company_id', $company_id)
            ->orderBy('orden')
            ->get();

        return response()->json($categories);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = LabExamCategory::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'orden' => 'integer|min:0',
            'activo' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = LabExamCategory::findOrFail($id);

        // Verificar si tiene exámenes asociados
        if ($category->exams()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene exámenes asociados'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
}

