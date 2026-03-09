<?php

namespace App\Http\Controllers;

use App\Models\PharmaceuticalLaboratory;
use Illuminate\Http\Request;

class PharmaceuticalLaboratoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $laboratories = PharmaceuticalLaboratory::withCount('products')
            ->orderBy('name')
            ->get();

        return view('pharmaceutical-laboratories.index', compact('laboratories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pharmaceutical-laboratories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200|unique:pharmaceutical_laboratories,name',
            'code' => 'nullable|string|max:50|unique:pharmaceutical_laboratories,code',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        PharmaceuticalLaboratory::create($request->all());

        return redirect()->route('pharmaceutical-laboratories.index')
            ->with('success', 'Laboratorio creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PharmaceuticalLaboratory $pharmaceutical_laboratory)
    {
        $pharmaceutical_laboratory->loadCount('products');
        $products = $pharmaceutical_laboratory->products()->paginate(20);

        return view('pharmaceutical-laboratories.show', compact('pharmaceutical_laboratory', 'products'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PharmaceuticalLaboratory $pharmaceutical_laboratory)
    {
        return response()->json($pharmaceutical_laboratory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PharmaceuticalLaboratory $pharmaceutical_laboratory)
    {
        $request->validate([
            'name' => 'required|string|max:200|unique:pharmaceutical_laboratories,name,' . $pharmaceutical_laboratory->id,
            'code' => 'nullable|string|max:50|unique:pharmaceutical_laboratories,code,' . $pharmaceutical_laboratory->id,
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        $pharmaceutical_laboratory->update($request->all());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laboratorio actualizado exitosamente',
                'laboratory' => $pharmaceutical_laboratory
            ]);
        }

        return redirect()->route('pharmaceutical-laboratories.index')
            ->with('success', 'Laboratorio actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PharmaceuticalLaboratory $pharmaceutical_laboratory)
    {
        $productsCount = $pharmaceutical_laboratory->products()->count();
        
        if ($productsCount > 0) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar el laboratorio porque tiene {$productsCount} productos asociados."
                ], 422);
            }
            return redirect()->route('pharmaceutical-laboratories.index')
                ->with('error', "No se puede eliminar el laboratorio porque tiene {$productsCount} productos asociados.");
        }

        $pharmaceutical_laboratory->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laboratorio eliminado exitosamente'
            ]);
        }

        return redirect()->route('pharmaceutical-laboratories.index')
            ->with('success', 'Laboratorio eliminado exitosamente.');
    }

    /**
     * Get all active laboratories for select options
     */
    public function getLaboratories()
    {
        $laboratories = PharmaceuticalLaboratory::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($laboratories);
    }
}
