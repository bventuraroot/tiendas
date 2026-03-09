<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductUnitController extends Controller
{
    /**
     * Obtener catálogo de unidades de medida
     */
    public function getCatalogUnits(): JsonResponse
    {
        try {
            $units = Unit::getActiveUnits();

            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener catálogo de unidades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener unidades por tipo
     */
    public function getUnitsByType($type): JsonResponse
    {
        try {
            $units = Unit::getUnitsByType($type);

            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener unidades por tipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener conversiones de unidades de un producto
     */
    public function getProductConversions($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $conversions = ProductUnitConversion::getActiveConversions($productId);

            return response()->json([
                'success' => true,
                'data' => $conversions,
                'default_conversion' => ProductUnitConversion::getDefaultConversion($productId)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conversiones de unidades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener conversión específica por código de unidad
     */
    public function getConversionByUnitCode($productId, $unitCode): JsonResponse
    {
        try {
            $conversion = ProductUnitConversion::getConversionByUnitCode($productId, $unitCode);

            if (!$conversion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversión de unidad no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $conversion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conversión de unidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva conversión de unidad para un producto
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'unit_id' => 'required|exists:units,id',
                'conversion_factor' => 'required|numeric|min:0',
                'price_multiplier' => 'required|numeric|min:0',
                'is_default' => 'boolean',
                'notes' => 'nullable|string'
            ]);

            // Verificar que no exista ya esta conversión para el producto
            $existingConversion = ProductUnitConversion::where('product_id', $request->product_id)
                                                     ->where('unit_id', $request->unit_id)
                                                     ->first();

            if ($existingConversion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta conversión de unidad ya existe para este producto'
                ], 400);
            }

            // Si es la conversión por defecto, desactivar las demás
            if ($request->is_default) {
                ProductUnitConversion::where('product_id', $request->product_id)
                                   ->where('is_default', true)
                                   ->update(['is_default' => false]);
            }

            $conversion = ProductUnitConversion::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Conversión de unidad creada correctamente',
                'data' => $conversion->load('unit')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear conversión de unidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar conversión de unidad
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $conversion = ProductUnitConversion::findOrFail($id);

            $request->validate([
                'conversion_factor' => 'required|numeric|min:0',
                'price_multiplier' => 'required|numeric|min:0',
                'is_default' => 'boolean',
                'notes' => 'nullable|string'
            ]);

            // Si se está marcando como conversión por defecto, desactivar las demás
            if ($request->is_default && !$conversion->is_default) {
                ProductUnitConversion::where('product_id', $conversion->product_id)
                                   ->where('is_default', true)
                                   ->update(['is_default' => false]);
            }

            $conversion->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Conversión de unidad actualizada correctamente',
                'data' => $conversion->load('unit')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar conversión de unidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar conversión de unidad
     */
    public function destroy($id): JsonResponse
    {
        try {
            $conversion = ProductUnitConversion::findOrFail($id);

            // No permitir eliminar la conversión por defecto si es la única
            if ($conversion->is_default) {
                $activeConversionsCount = ProductUnitConversion::where('product_id', $conversion->product_id)
                                                             ->where('is_active', true)
                                                             ->count();

                if ($activeConversionsCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar la única conversión activa'
                    ], 400);
                }
            }

            $conversion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversión de unidad eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar conversión de unidad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/desactivar conversión de unidad
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $conversion = ProductUnitConversion::findOrFail($id);
            $conversion->is_active = !$conversion->is_active;
            $conversion->save();

            $status = $conversion->is_active ? 'activada' : 'desactivada';

            return response()->json([
                'success' => true,
                'message' => "Conversión de unidad {$status} correctamente",
                'data' => $conversion->load('unit')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado de conversión de unidad: ' . $e->getMessage()
            ], 500);
        }
    }
}
