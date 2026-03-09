<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Unit;
use Illuminate\Http\Request;

class ProductUnitConversionController extends Controller
{
    public function list($productId)
    {
        $conversions = ProductUnitConversion::where('product_id', $productId)
            ->with('unit')
            ->orderBy('is_default', 'desc')
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                return $c->getConversionInfo();
            });

        return response()->json([
            'success' => true,
            'data' => $conversions
        ]);
    }

    public function listUnits()
    {
        $units = Unit::orderBy('unit_name')->get(['id', 'unit_code', 'unit_name', 'unit_type']);
        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'price_multiplier' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($productId);

        // Si marcan como default, desmarcar otras
        if (!empty($validated['is_default'])) {
            ProductUnitConversion::where('product_id', $product->id)->update(['is_default' => false]);
        }

        $conversion = ProductUnitConversion::updateOrCreate(
            [
                'product_id' => $product->id,
                'unit_id' => $validated['unit_id'],
            ],
            [
                'conversion_factor' => $validated['conversion_factor'],
                'price_multiplier' => $validated['price_multiplier'],
                'is_default' => (bool)($validated['is_default'] ?? false),
                'is_active' => (bool)($validated['is_active'] ?? true),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $conversion->getConversionInfo()
        ]);
    }

    public function update(Request $request, $productId, $id)
    {
        $validated = $request->validate([
            'conversion_factor' => 'required|numeric|min:0.0001',
            'price_multiplier' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $conversion = ProductUnitConversion::where('product_id', $productId)->findOrFail($id);

        if (!empty($validated['is_default'])) {
            ProductUnitConversion::where('product_id', $productId)->update(['is_default' => false]);
        }

        $conversion->update([
            'conversion_factor' => $validated['conversion_factor'],
            'price_multiplier' => $validated['price_multiplier'],
            'is_default' => (bool)($validated['is_default'] ?? false),
            'is_active' => (bool)($validated['is_active'] ?? true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $conversion->getConversionInfo(),
        ]);
    }

    public function destroy($productId, $id)
    {
        $conversion = ProductUnitConversion::where('product_id', $productId)->findOrFail($id);
        $conversion->delete();
        return response()->json(['success' => true]);
    }
}


