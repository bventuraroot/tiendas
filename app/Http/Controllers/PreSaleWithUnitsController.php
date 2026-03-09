<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use App\Models\Inventory;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PreSaleWithUnitsController extends Controller
{
    protected $unitConversionService;

    public function __construct()
    {
        $this->unitConversionService = new UnitConversionService();
    }

    /**
     * Agregar producto a la preventa con manejo de unidades
     */
    public function addProductToPreSale(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
            'base_price' => 'required|numeric|min:0',
        ]);

        try {
            $productId = $request->product_id;
            $unitCode = $request->unit_code;
            $quantity = $request->quantity;
            $basePrice = $request->base_price;
            $sessionId = Session::getId();

            // 1. Verificar disponibilidad de stock
            $stockCheck = $this->unitConversionService->checkStockAvailability(
                $productId,
                $quantity,
                $unitCode
            );

            if (!$stockCheck['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'details' => $stockCheck
                ], 400);
            }

            // 2. Obtener información de conversión
            $conversionInfo = $this->unitConversionService->getConversionInfo($productId, $unitCode);

            // 3. Calcular totales de venta
            $saleCalculation = $this->unitConversionService->calculateSaleTotals(
                $productId,
                $quantity,
                $unitCode,
                $basePrice
            );

            // 4. Obtener información del producto
            $product = Product::with(['marca', 'provider'])->find($productId);

            // 5. Guardar en sesión (simulando preventa)
            $preVentaItems = Session::get('preventa_items', []);

            $itemKey = $productId . '_' . $unitCode;

            $preVentaItems[$itemKey] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'product_code' => $product->code,
                'marca' => $product->marca->name ?? '',
                'unit_id' => $conversionInfo['unit_id'],
                'unit_code' => $unitCode,
                'unit_name' => $conversionInfo['unit_name'],
                'conversion_factor' => $conversionInfo['conversion_factor'],
                'quantity' => $quantity,
                'unit_price' => $saleCalculation['unit_price'],
                'subtotal' => $saleCalculation['subtotal'],
                'base_quantity_used' => $saleCalculation['base_quantity_used'],
                'base_price' => $basePrice,
                'session_id' => $sessionId,
                'added_at' => now()->toISOString()
            ];

            Session::put('preventa_items', $preVentaItems);

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado a la preventa correctamente',
                'data' => [
                    'item' => $preVentaItems[$itemKey],
                    'unit_info' => $conversionInfo,
                    'calculation' => $saleCalculation,
                    'stock_check' => $stockCheck,
                    'total_items' => count($preVentaItems),
                    'total_amount' => array_sum(array_column($preVentaItems, 'subtotal'))
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error agregando producto a preventa con unidades', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id,
                'unit_code' => $request->unit_code
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto a la preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener items de la preventa
     */
    public function getPreVentaItems(): JsonResponse
    {
        try {
            $preVentaItems = Session::get('preventa_items', []);

            $totalAmount = array_sum(array_column($preVentaItems, 'subtotal'));
            $totalItems = count($preVentaItems);

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => array_values($preVentaItems),
                    'total_items' => $totalItems,
                    'total_amount' => $totalAmount,
                    'session_id' => Session::getId()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items de preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cantidad de un item en preventa
     */
    public function updatePreVentaItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $productId = $request->product_id;
            $unitCode = $request->unit_code;
            $quantity = $request->quantity;
            $itemKey = $productId . '_' . $unitCode;

            $preVentaItems = Session::get('preventa_items', []);

            if (!isset($preVentaItems[$itemKey])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item no encontrado en la preventa'
                ], 404);
            }

            // Verificar disponibilidad de stock
            $stockCheck = $this->unitConversionService->checkStockAvailability(
                $productId,
                $quantity,
                $unitCode
            );

            if (!$stockCheck['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente para la nueva cantidad',
                    'details' => $stockCheck
                ], 400);
            }

            // Recalcular totales
            $saleCalculation = $this->unitConversionService->calculateSaleTotals(
                $productId,
                $quantity,
                $unitCode,
                $preVentaItems[$itemKey]['base_price']
            );

            // Actualizar item
            $preVentaItems[$itemKey]['quantity'] = $quantity;
            $preVentaItems[$itemKey]['subtotal'] = $saleCalculation['subtotal'];
            $preVentaItems[$itemKey]['base_quantity_used'] = $saleCalculation['base_quantity_used'];

            Session::put('preventa_items', $preVentaItems);

            return response()->json([
                'success' => true,
                'message' => 'Item actualizado correctamente',
                'data' => [
                    'item' => $preVentaItems[$itemKey],
                    'total_amount' => array_sum(array_column($preVentaItems, 'subtotal'))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover item de la preventa
     */
    public function removePreVentaItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
        ]);

        try {
            $itemKey = $request->product_id . '_' . $request->unit_code;
            $preVentaItems = Session::get('preventa_items', []);

            if (isset($preVentaItems[$itemKey])) {
                unset($preVentaItems[$itemKey]);
                Session::put('preventa_items', $preVentaItems);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item removido correctamente',
                'data' => [
                    'total_items' => count($preVentaItems),
                    'total_amount' => array_sum(array_column($preVentaItems, 'subtotal'))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar preventa (convertir a venta real)
     */
    public function finalizePreVenta(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'required|exists:companies,id',
            'typedocument_id' => 'required|exists:typedocuments,id',
        ]);

        DB::beginTransaction();

        try {
            $preVentaItems = Session::get('preventa_items', []);

            if (empty($preVentaItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay items en la preventa'
                ], 400);
            }

            // Crear la venta
            $sale = Sale::create([
                'client_id' => $request->client_id,
                'company_id' => $request->company_id,
                'typedocument_id' => $request->typedocument_id,
                'user_id' => auth()->user()->id,
                'totalamount' => array_sum(array_column($preVentaItems, 'subtotal')),
                'typesale' => 0, // Borrador
                'waytopay' => 1, // Contado por defecto
                'acuenta' => 0,
                'date' => now()
            ]);

            // Crear los detalles de venta
            foreach ($preVentaItems as $item) {
                $saleDetail = new Salesdetail();
                $saleDetail->sale_id = $sale->id;
                $saleDetail->product_id = $item['product_id'];
                $saleDetail->unit_id = $item['unit_id'];
                $saleDetail->unit_name = $item['unit_name'];
                $saleDetail->conversion_factor = $item['conversion_factor'];
                $saleDetail->base_quantity_used = $item['base_quantity_used'];
                $saleDetail->amountp = $item['quantity'];
                $saleDetail->priceunit = $item['unit_price'];
                $saleDetail->pricesale = $item['subtotal'];
                $saleDetail->nosujeta = 0;
                $saleDetail->exempt = 0;
                $saleDetail->detained13 = 0;
                $saleDetail->detained = 0;
                $saleDetail->renta = 0;
                $saleDetail->fee = 0;
                $saleDetail->feeiva = 0;
                $saleDetail->user_id = auth()->user()->id;
                $saleDetail->save();

                // Actualizar inventario
                $this->updateInventoryAfterSale(
                    $item['product_id'],
                    $item['base_quantity_used'],
                    $item['unit_id']
                );
            }

            // Limpiar la preventa
            Session::forget('preventa_items');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preventa finalizada correctamente',
                'data' => [
                    'sale_id' => $sale->id,
                    'total_amount' => $sale->totalamount,
                    'items_count' => count($preVentaItems)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error finalizando preventa', [
                'error' => $e->getMessage(),
                'user_id' => auth()->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar preventa
     */
    public function clearPreVenta(): JsonResponse
    {
        try {
            Session::forget('preventa_items');

            return response()->json([
                'success' => true,
                'message' => 'Preventa limpiada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar inventario después de una venta
     */
    private function updateInventoryAfterSale($productId, $baseQuantityUsed, $unitId)
    {
        $inventory = Inventory::where('product_id', $productId)->first();

        if ($inventory) {
            // Descontar de la cantidad base
            $inventory->base_quantity = max(0, $inventory->base_quantity - $baseQuantityUsed);

            // También actualizar la cantidad legacy para compatibilidad
            $inventory->quantity = max(0, $inventory->quantity - $baseQuantityUsed);

            $inventory->save();

            Log::info('Inventario actualizado después de finalizar preventa', [
                'product_id' => $productId,
                'base_quantity_used' => $baseQuantityUsed,
                'remaining_base_quantity' => $inventory->base_quantity
            ]);
        }
    }
}
