<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnitConversion;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleWithUnitsController extends Controller
{
    protected $unitConversionService;

    public function __construct()
    {
        $this->unitConversionService = new UnitConversionService();
    }

    /**
     * Agregar producto a la venta con manejo de unidades
     */
    public function addProductToSaleWithUnits(Request $request): JsonResponse
    {
        // Log de debug

        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
            'base_price' => 'required|numeric|min:0',
            'client_id' => 'required|exists:clients,id',
            'acuenta' => 'nullable|string|max:255',
            'waytopay' => 'required|in:1,2,3',
            // Campos fiscales opcionales
            'price_nosujeta' => 'nullable|numeric|min:0',
            'price_exenta' => 'nullable|numeric|min:0',
            'price_gravada' => 'nullable|numeric|min:0',
            'iva_rete13' => 'nullable|numeric|min:0',
            'iva_percibido' => 'nullable|numeric|min:0', // IVA Percibido (cuando empresa es gran contribuyente)
            'renta' => 'nullable|numeric|min:0',
            'iva_rete' => 'nullable|numeric|min:0', // IVA Retenido (cuando cliente es agente de retención)
            'typedoc' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $saleId = $request->sale_id;
            $productId = $request->product_id;
            $unitCode = $request->unit_code;
            $quantity = $request->quantity;
            $basePrice = $request->base_price;
            $priceunit = $request->priceunit;
            $userId = auth()->user()->id;

            // 1. Verificar que la venta existe
            $sale = Sale::findOrFail($saleId);

            // 2. Verificar disponibilidad de stock
            $stockCheck = $this->unitConversionService->checkStockAvailability(
                $productId,
                $quantity,
                $unitCode
            );
            /*if (!$stockCheck['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'details' => $stockCheck
                ], 400);
            }*/

            // 3. Obtener información de conversión
            $conversionInfo = $this->unitConversionService->getConversionInfo($productId, $unitCode);
            //dd($conversionInfo);

            // 4. Calcular totales de venta
            $saleCalculation = $this->unitConversionService->calculateSaleTotals(
                $productId,
                $quantity,
                $unitCode,
                $basePrice
            );

            // 5. Actualizar información de la venta
            $sale->client_id = $request->client_id;
            $sale->acuenta = $request->acuenta ?? 'Venta al menudeo';
            $sale->waytopay = $request->waytopay;
            $sale->save();

            // 6. Crear el detalle de venta
            $saleDetail = new Salesdetail();
            $saleDetail->sale_id = $saleId;
            $saleDetail->product_id = $productId;
            $saleDetail->unit_id = $conversionInfo['unit_id'];
            $saleDetail->unit_name = $conversionInfo['unit_name'];
            $saleDetail->conversion_factor = $conversionInfo['conversion_factor'];
            $saleDetail->base_quantity_used = $saleCalculation['base_quantity_used'];

            // Cantidades y precios
            $saleDetail->amountp = $quantity;
            //$saleDetail->priceunit = $saleCalculation['unit_price']; Error en el precio unitario hace una conversion extraña
            $saleDetail->priceunit = $priceunit; //Aqui se divide el precio base por la cantidad para obtener el precio unitario
            //$saleDetail->pricesale = $request->price_gravada ?? $saleCalculation['subtotal']; //Aqui se genera un error cuando la venta total es mayor a $99.99999999.
            if($request->typedoc == '3'){
                $saleDetail->pricesale = $basePrice;
            }else{
                $saleDetail->pricesale = $basePrice*$quantity;
            }
            //$saleDetail->pricesale = $basePrice*$quantity;

            // Campos fiscales
            $saleDetail->nosujeta = $request->price_nosujeta ?? 0;
            $saleDetail->exempt = $request->price_exenta ?? 0;
            $saleDetail->detained13 = $request->iva_rete13 ?? 0;
            $saleDetail->detainedP = $request->iva_percibido ?? 0; // IVA Percibido (cuando empresa es gran contribuyente)
            $saleDetail->detained = $request->iva_rete ?? 0; // IVA Retenido (cuando cliente es agente de retención)
            $saleDetail->renta = $request->renta ?? 0;

            // Campos adicionales
            $saleDetail->fee = 0;
            $saleDetail->feeiva = 0;
            $saleDetail->reserva = 0;
            $saleDetail->ruta = 0;
            $saleDetail->destino = 0;
            $saleDetail->linea = 0;
            $saleDetail->canal = 0;
            $saleDetail->user_id = $userId;

            $saleDetail->save();

            // 7. Actualizar inventario (descontar stock) - COMENTADO: Se descuenta al finalizar el documento
            // $this->updateInventoryAfterSale($productId, $saleCalculation['base_quantity_used'], $conversionInfo['unit_id']);

            // 8. Recalcular totales de la venta
            $this->updateSaleTotalAmount($saleId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado a la venta correctamente',
                'data' => [
                    'sale_detail_id' => $saleDetail->id,
                    'unit_info' => $conversionInfo,
                    'calculation' => $saleCalculation,
                    'stock_check' => $stockCheck
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto a la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener unidades disponibles para un producto
     */
    public function getProductUnits($productId): JsonResponse
    {
        try {
            $units = $this->unitConversionService->getAvailableUnitsForProduct($productId);

            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener unidades del producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular precio para una unidad específica
     */
    public function calculateUnitPrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'base_price' => 'required|numeric|min:0'
        ]);

        try {
            $unitPrice = $this->unitConversionService->calculateUnitPrice(
                $request->product_id,
                $request->base_price,
                $request->unit_code
            );

            $conversionInfo = $this->unitConversionService->getConversionInfo(
                $request->product_id,
                $request->unit_code
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'unit_price' => $unitPrice,
                    'conversion_info' => $conversionInfo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function checkStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_code' => 'required|string',
            'quantity' => 'required|numeric|min:0.01'
        ]);

        try {
            $stockCheck = $this->unitConversionService->checkStockAvailability(
                $request->product_id,
                $request->quantity,
                $request->unit_code
            );

            return response()->json([
                'success' => true,
                'data' => $stockCheck
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar stock: ' . $e->getMessage()
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
            $qtyBefore  = (float)$inventory->quantity;
            $baseBefore = (float)$inventory->base_quantity;

            // Descontar de la cantidad base
            $inventory->base_quantity = max(0, $inventory->base_quantity - $baseQuantityUsed);

            // También actualizar la cantidad legacy para compatibilidad
            $inventory->quantity = max(0, $inventory->quantity - $baseQuantityUsed);

            $inventory->save();

            // Registrar movimiento de venta
            try {
                InventoryMovement::record(
                    $inventory, 'venta',
                    $qtyBefore, -$baseQuantityUsed,
                    $baseBefore, -$baseQuantityUsed,
                    'Sale', null, null,
                    auth()->id(),
                    'Venta con unidades'
                );
            } catch (\Exception $e) {
                Log::warning('No se pudo registrar movimiento de venta: ' . $e->getMessage());
            }

            Log::info('Inventario actualizado después de venta', [
                'product_id' => $productId,
                'base_quantity_used' => $baseQuantityUsed,
                'remaining_base_quantity' => $inventory->base_quantity
            ]);
        }
    }

    /**
     * Recalcular el total de la venta
     */
    private function updateSaleTotalAmount($saleId)
    {
        try {
            $sale = Sale::find($saleId);
            if (!$sale) {
                return false;
            }

            // Calcular el total basado en los detalles de venta (misma lógica que SaleController)
            $totals = Salesdetail::where('sale_id', $saleId)
                ->selectRaw('
                    SUM(nosujeta) as nosujeta,
                    SUM(exempt) as exempt,
                    SUM(pricesale) as pricesale,
                    SUM(detained13) as iva,
                    SUM(detained) as ivarete,
                    SUM(renta) as renta
                ')
                ->first();

            // Calcular el total a pagar según el tipo de documento (lógica de Roma Copies)
            if ($sale->typedocument_id == '8') {
                // Para Factura de Sujeto Excluido, no se incluye IVA en el total pero SÍ se incluye retención de renta
                $totalAmount = ($totals->nosujeta + $totals->exempt + $totals->pricesale) - ($totals->renta + $totals->ivarete);
            } else {
                // Para Factura normal, se incluye IVA pero NO se incluye retención de renta
                $totalAmount = ($totals->nosujeta + $totals->exempt + $totals->pricesale + $totals->iva) - ($totals->renta + $totals->ivarete);
            }

            // Actualizar el totalamount en la venta
            $sale->totalamount = round($totalAmount, 2);
            $sale->save();


            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
