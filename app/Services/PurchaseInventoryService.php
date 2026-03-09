<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PurchaseInventoryService
{
    protected $unitConversionService;

    public function __construct()
    {
        $this->unitConversionService = new UnitConversionService();
    }
    /**
     * Agregar productos de una compra al inventario
     */
    public function addPurchaseToInventory(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $details = $purchase->details()->where('added_to_inventory', false)->get();

            foreach ($details as $detail) {
                $this->addDetailToInventory($detail);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Productos agregados al inventario correctamente',
                'details_processed' => $details->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al agregar productos al inventario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Agregar un detalle específico al inventario
     */
    public function addDetailToInventory(PurchaseDetail $detail)
    {
        $product = $detail->product;

        // Calcular cantidad en unidad base según la configuración del producto
        $baseQuantity = $this->calculateBaseQuantity($detail, $product);
        $baseUnitId = $this->getBaseUnitId($product);

        // Buscar inventario existente para este producto
        $inventory = Inventory::where('product_id', $product->id)->first();

        if (!$inventory) {
            // Crear nuevo registro de inventario
            $inventory = Inventory::create([
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $detail->quantity,
                'base_unit_id' => $baseUnitId,
                'base_quantity' => $baseQuantity,
                'minimum_stock' => 0,
                'location' => 'Almacén Principal',
                'expiration_date' => $detail->expiration_date,
                'batch_number' => $detail->batch_number,
                'expiring_quantity' => $detail->expiration_date ? $baseQuantity : 0,
                'expiration_warning_sent' => false,
                'last_expiration_check' => now()
            ]);

            // Registrar movimiento de compra (inventario nuevo)
            $purchase = $detail->purchase;
            InventoryMovement::record(
                $inventory, 'compra',
                0, (float)$detail->quantity,
                0, (float)$baseQuantity,
                'Purchase', $purchase?->id,
                $purchase?->ndocumento ?? $purchase?->id,
                null,
                'Compra - nuevo inventario'
            );
        } else {
            $qtyBefore  = (float)$inventory->quantity;
            $baseBefore = (float)$inventory->base_quantity;

            // Actualizar inventario existente
            $inventory->quantity += $detail->quantity;
            $inventory->base_quantity += $baseQuantity;

            // Si el producto tiene fecha de caducidad, actualizar la cantidad que vence
            if ($detail->expiration_date) {
                $inventory->expiring_quantity += $baseQuantity;

                if (!$inventory->expiration_date || $detail->expiration_date < $inventory->expiration_date) {
                    $inventory->expiration_date = $detail->expiration_date;
                }
            }

            $inventory->save();

            // Registrar movimiento de compra
            $purchase = $detail->purchase;
            InventoryMovement::record(
                $inventory, 'compra',
                $qtyBefore, (float)$detail->quantity,
                $baseBefore, (float)$baseQuantity,
                'Purchase', $purchase?->id,
                $purchase?->ndocumento ?? $purchase?->id,
                null,
                'Compra agregada al inventario'
            );
        }

        // Marcar el detalle como agregado al inventario
        $detail->update(['added_to_inventory' => true]);
    }

    /**
     * Calcular cantidad en unidad base
     */
    private function calculateBaseQuantity(PurchaseDetail $detail, Product $product)
    {
        $quantity = $detail->quantity;
        $unitCode = $detail->unit_code;
        $conversionFactor = $detail->conversion_factor ?? 1;

        // Si no hay información de unidad, intentar determinar la unidad base
        if (!$unitCode) {
            // Si es un producto farmacéutico, asumir que la cantidad está en pastillas
            if ($product->pastillas_per_blister || $product->blisters_per_caja) {
                return (float)$quantity;
            }
            
            // Para otros productos, usar el factor de conversión si está disponible
            if ($conversionFactor && $conversionFactor != 1) {
                return (float)$quantity * (float)$conversionFactor;
            }
            
            // Si no hay conversión, asumir que ya está en unidad base
            return (float)$quantity;
        }

        // Usar la lógica del UnitConversionService
        try {
            $baseQuantity = $this->unitConversionService->calculateBaseQuantityNeeded(
                $product->id,
                $quantity,
                $unitCode
            );
            return (float)$baseQuantity;
        } catch (\Exception $e) {
            // Si hay error, usar el factor de conversión si está disponible
            \Log::warning("Error calculando cantidad base para producto {$product->id}: " . $e->getMessage());
            if ($conversionFactor && $conversionFactor != 1) {
                return (float)$quantity * (float)$conversionFactor;
            }
            return (float)$quantity;
        }
    }

    /**
     * Obtener ID de unidad base según el tipo de producto
     */
    private function getBaseUnitId(Product $product)
    {
        // Verificar si es un producto farmacéutico (tiene configuración de pastillas/blisters/cajas)
        if ($product->pastillas_per_blister || $product->blisters_per_caja) {
            // Para productos farmacéuticos, la unidad base es PASTILLA
            $pastillaUnit = \App\Models\Unit::where('unit_code', 'PASTILLA')->first();
            if ($pastillaUnit) {
                return $pastillaUnit->id;
            }
            // Si no existe la unidad PASTILLA, usar 'Unidad' como fallback
            return \App\Models\Unit::where('unit_code', '59')->first()->id ?? null;
        }

        // Para productos no farmacéuticos, usar sale_type
        switch ($product->sale_type) {
            case 'weight':
                return \App\Models\Unit::where('unit_code', '36')->first()->id ?? null; // Libra
            case 'volume':
                return \App\Models\Unit::where('unit_code', '23')->first()->id ?? null; // Litro
            case 'unit':
            default:
                return \App\Models\Unit::where('unit_code', '59')->first()->id ?? null; // Unidad
        }
    }

    /**
     * Remover productos del inventario (para cancelaciones o devoluciones)
     */
    public function removePurchaseFromInventory(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $details = $purchase->details()->where('added_to_inventory', true)->get();

            foreach ($details as $detail) {
                $this->removeDetailFromInventory($detail);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Productos removidos del inventario correctamente',
                'details_processed' => $details->count()
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al remover productos del inventario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remover un detalle específico del inventario
     */
    public function removeDetailFromInventory(PurchaseDetail $detail)
    {
        $product = $detail->product;

        // Calcular cantidad en unidad base según la configuración del producto
        $baseQuantity = $this->calculateBaseQuantity($detail, $product);

        $inventory = Inventory::where('product_id', $detail->product_id)->first();

        if ($inventory) {
            $qtyBefore  = (float)$inventory->quantity;
            $baseBefore = (float)$inventory->base_quantity;

            // Restar tanto la cantidad en unidad de compra como la cantidad base
            $inventory->quantity = max(0, $inventory->quantity - $detail->quantity);
            $inventory->base_quantity = max(0, $inventory->base_quantity - $baseQuantity);

            // Si el producto tiene fecha de caducidad, actualizar la cantidad que vence
            if ($detail->expiration_date) {
                $inventory->expiring_quantity = max(0, $inventory->expiring_quantity - $baseQuantity);
            }

            // Registrar movimiento de anulación antes de posible eliminación
            $purchase = $detail->purchase;
            InventoryMovement::record(
                $inventory, 'anulacion_compra',
                $qtyBefore, -(float)$detail->quantity,
                $baseBefore, -(float)$baseQuantity,
                'Purchase', $purchase?->id,
                $purchase?->ndocumento ?? $purchase?->id,
                null,
                'Anulación/reversa de compra'
            );

            // Si no quedan productos, eliminar el registro de inventario
            if ($inventory->quantity <= 0 && $inventory->base_quantity <= 0) {
                $inventory->delete();
            } else {
                $inventory->save();
            }
        }

        // Marcar el detalle como removido del inventario
        $detail->update(['added_to_inventory' => false]);
    }

    /**
     * Verificar productos próximos a vencer
     */
    public function checkExpiringProducts($days = 30)
    {
        // Buscar TODOS los productos con fecha de expiración
        $allProductsWithExpiration = \App\Models\PurchaseDetail::whereNotNull('expiration_date')
            ->where('quantity', '>', 0)
            ->with(['product', 'product.provider', 'purchase'])
            ->get();

        // Buscar en PurchaseDetail (detalles de compra) que tienen fechas de expiración
        $expiringProducts = $allProductsWithExpiration->filter(function($detail) use ($days) {
            if (!$detail->expiration_date) return false;

            $diffInDays = now()->diffInDays($detail->expiration_date, false);

            // Incluir productos vencidos hasta 1 año atrás y próximos a vencer
            return $diffInDays >= -365 && $diffInDays <= $days;
        });

        // También buscar productos que deberían tener fechas de expiración pero no las tienen
        $productsWithoutExpiration = \App\Models\PurchaseDetail::whereNull('expiration_date')
            ->where('quantity', '>', 0)
            ->with(['product', 'product.provider', 'purchase'])
            ->get();

        $results = [
            'expired' => collect(),   // Ya vencidos (días negativos)
            'critical' => collect(), // 7 días o menos
            'warning' => collect(),  // 8-30 días
            'no_expiration' => $productsWithoutExpiration, // Sin fecha de expiración
            'total' => $expiringProducts->count() + $productsWithoutExpiration->count()
        ];

        foreach ($expiringProducts as $detail) {
            // Usar fecha local para evitar problemas de zona horaria
            $today = Carbon::today();
            $expirationDate = Carbon::parse($detail->expiration_date)->startOfDay();
            $daysUntilExpiration = $today->diffInDays($expirationDate, false);

            if ($daysUntilExpiration < 0) {
                $results['expired']->push($detail);
            } elseif ($daysUntilExpiration <= 7) {
                $results['critical']->push($detail);
            } else {
                $results['warning']->push($detail);
            }
        }

        return $results;
    }

    /**
     * Obtener productos vencidos
     */
    public function getExpiredProducts()
    {
        return \App\Models\PurchaseDetail::whereNotNull('expiration_date')
            ->where('expiration_date', '<', now())
            ->where('quantity', '>', 0)
            ->with(['product', 'product.provider'])
            ->get();
    }

    /**
     * Actualizar fechas de caducidad basadas en la configuración del producto
     */
    public function updateExpirationDates(PurchaseDetail $detail)
    {
        $product = $detail->product;

        if ($product->hasExpirationConfigured() && !$detail->expiration_date) {
            $expirationDate = $product->calculateExpirationDate($detail->purchase->date);
            $detail->update(['expiration_date' => $expirationDate]);
        }
    }

    /**
     * Generar número de lote automático
     */
    public function generateBatchNumber(PurchaseDetail $detail)
    {
        if (!$detail->batch_number) {
            $purchase = $detail->purchase;
            $product = $detail->product;

            $batchNumber = sprintf(
                'LOT-%s-%s-%s',
                $purchase->date->format('Ymd'),
                $product->code ?? $product->id,
                str_pad($detail->id, 4, '0', STR_PAD_LEFT)
            );

            $detail->update(['batch_number' => $batchNumber]);
        }
    }

    /**
     * Obtener reporte de inventario con caducidad
     */
    public function getInventoryExpirationReport()
    {
        return Inventory::with(['product', 'product.provider'])
            ->whereNotNull('expiration_date')
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date')
            ->get()
            ->groupBy(function ($inventory) {
                $status = $inventory->getExpirationStatus();
                return match($status) {
                    'expired' => 'Vencidos',
                    'critical' => 'Críticos (≤7 días)',
                    'warning' => 'Advertencia (8-30 días)',
                    'ok' => 'OK (>30 días)',
                    default => 'Sin clasificar'
                };
            });
    }
}
