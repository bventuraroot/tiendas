<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductPriceController extends Controller
{
    /**
     * Mostrar todos los precios de un producto
     */
    public function index($productId)
    {
        $product = Product::with(['prices.unit', 'activeUnitConversions.unit'])->findOrFail($productId);
        $units = Unit::where('is_active', true)->get();

        return view('products.prices.index', compact('product', 'units'));
    }

    /**
     * Mostrar formulario para crear nuevo precio
     */
    public function create($productId)
    {
        $product = Product::findOrFail($productId);
        $units = Unit::where('is_active', true)->get();
        $existingUnits = $product->prices()->pluck('unit_id')->toArray();

        return view('products.prices.create', compact('product', 'units', 'existingUnits'));
    }

    /**
     * Almacenar nuevo precio
     */
    public function store(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'cost_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'wholesale_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'retail_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'special_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'is_default' => 'boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Si se marca como por defecto, desactivar otros precios por defecto
            if ($request->is_default) {
                ProductPrice::where('product_id', $productId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $productPrice = ProductPrice::create([
                'product_id' => $productId,
                'unit_id' => $request->unit_id,
                'price' => $request->price,
                'cost_price' => $request->cost_price,
                'wholesale_price' => $request->wholesale_price,
                'retail_price' => $request->retail_price,
                'special_price' => $request->special_price,
                'is_default' => $request->is_default ?? false,
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Precio creado exitosamente',
                'data' => $productPrice->load('unit')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar precio
     */
    public function edit($productId, $priceId)
    {
        $product = Product::findOrFail($productId);
        $productPrice = ProductPrice::with('unit')->findOrFail($priceId);
        $units = Unit::where('is_active', true)->get();

        return view('products.prices.partials.edit-form', compact('product', 'productPrice', 'units'));
    }

    /**
     * Actualizar precio
     */
    public function update(Request $request, $productId, $priceId)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'cost_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'wholesale_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'retail_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'special_price' => 'nullable|numeric|min:0|regex:/^\d+(\.\d{1,4})?$/',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $productPrice = ProductPrice::findOrFail($priceId);

            // Si se marca como por defecto, desactivar otros precios por defecto
            if ($request->is_default && !$productPrice->is_default) {
                ProductPrice::where('product_id', $productId)
                    ->where('id', '!=', $priceId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $productPrice->update([
                'unit_id' => $request->unit_id,
                'price' => $request->price,
                'cost_price' => $request->cost_price,
                'wholesale_price' => $request->wholesale_price,
                'retail_price' => $request->retail_price,
                'special_price' => $request->special_price,
                'is_default' => $request->is_default ?? false,
                'is_active' => $request->is_active ?? true,
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Precio actualizado exitosamente',
                'data' => $productPrice->load('unit')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar precio
     */
    public function destroy($productId, $priceId)
    {
        try {
            $productPrice = ProductPrice::findOrFail($priceId);
            $productPrice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Precio eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener precios de un producto (API)
     */
    public function getProductPrices($productId)
    {
        $prices = ProductPrice::getActivePrices($productId);

        return response()->json([
            'success' => true,
            'data' => $prices->map(function($price) {
                return $price->getPriceInfo();
            })
        ]);
    }

    /**
     * Obtener precio específico por unidad (API)
     */
    public function getPriceByUnit($productId, $unitCode)
    {
        $price = ProductPrice::getPriceByUnitCode($productId, $unitCode);

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró precio para la unidad especificada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $price->getPriceInfo()
        ]);
    }

    /**
     * Crear precios masivos para un producto
     */
    public function createBulkPrices(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'prices' => 'required|array|min:1',
            'prices.*.unit_id' => 'required|exists:units,id',
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.cost_price' => 'nullable|numeric|min:0',
            'prices.*.wholesale_price' => 'nullable|numeric|min:0',
            'prices.*.retail_price' => 'nullable|numeric|min:0',
            'prices.*.special_price' => 'nullable|numeric|min:0',
            'prices.*.is_default' => 'boolean',
            'prices.*.notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $createdPrices = [];

            foreach ($request->prices as $priceData) {
                // Si se marca como por defecto, desactivar otros precios por defecto
                if (isset($priceData['is_default']) && $priceData['is_default']) {
                    ProductPrice::where('product_id', $productId)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }

                $productPrice = ProductPrice::create([
                    'product_id' => $productId,
                    'unit_id' => $priceData['unit_id'],
                    'price' => $priceData['price'],
                    'cost_price' => $priceData['cost_price'] ?? null,
                    'wholesale_price' => $priceData['wholesale_price'] ?? null,
                    'retail_price' => $priceData['retail_price'] ?? null,
                    'special_price' => $priceData['special_price'] ?? null,
                    'is_default' => $priceData['is_default'] ?? false,
                    'notes' => $priceData['notes'] ?? null
                ]);

                $createdPrices[] = $productPrice->load('unit');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Precios creados exitosamente',
                'data' => $createdPrices
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear los precios: ' . $e->getMessage()
            ], 500);
        }
    }
}
