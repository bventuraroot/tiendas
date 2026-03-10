<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    /**
     * Listado de categorías de productos.
     */
    public function index()
    {
        $categories = ProductCategory::orderBy('name')->get();
        return view('product-categories.index', compact('categories'));
    }

    /**
     * Guardar nueva categoría.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        ProductCategory::create([
            'name' => trim($request->input('name')),
            'description' => $request->filled('description') ? trim($request->input('description')) : null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente.',
            ]);
        }

        return redirect()->route('product-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Actualizar categoría.
     */
    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe otra categoría con ese nombre.',
        ]);

        $category->update([
            'name' => trim($request->input('name')),
            'description' => $request->filled('description') ? trim($request->input('description')) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente.',
        ]);
    }

    /**
     * Eliminar categoría.
     * No se modifica products.category; los productos conservan el texto.
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}
