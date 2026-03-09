<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'inventory_id',
        'product_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'base_quantity_before',
        'base_quantity_change',
        'base_quantity_after',
        'reference_type',
        'reference_id',
        'reference_doc',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity_before'      => 'decimal:4',
        'quantity_change'      => 'decimal:4',
        'quantity_after'       => 'decimal:4',
        'base_quantity_before' => 'decimal:4',
        'base_quantity_change' => 'decimal:4',
        'base_quantity_after'  => 'decimal:4',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'entrada_inicial'  => 'Entrada Inicial',
            'compra'           => 'Compra',
            'ajuste_manual'    => 'Ajuste Manual',
            'venta'            => 'Venta',
            'anulacion_compra' => 'Anulación Compra',
            'anulacion_venta'  => 'Anulación Venta',
            default            => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'entrada_inicial'  => 'bg-label-primary',
            'compra'           => 'bg-label-success',
            'ajuste_manual'    => 'bg-label-warning',
            'venta'            => 'bg-label-info',
            'anulacion_compra' => 'bg-label-danger',
            'anulacion_venta'  => 'bg-label-danger',
            default            => 'bg-label-secondary',
        };
    }

    /**
     * Registrar un movimiento de inventario de forma estática
     */
    public static function record(
        Inventory $inventory,
        string $type,
        float $qtyBefore,
        float $qtyChange,
        float $baseBefore,
        float $baseChange,
        ?string $refType = null,
        ?int $refId = null,
        ?string $refDoc = null,
        ?int $userId = null,
        ?string $notes = null
    ): self {
        return self::create([
            'inventory_id'          => $inventory->id,
            'product_id'            => $inventory->product_id,
            'type'                  => $type,
            'quantity_before'       => $qtyBefore,
            'quantity_change'       => $qtyChange,
            'quantity_after'        => $qtyBefore + $qtyChange,
            'base_quantity_before'  => $baseBefore,
            'base_quantity_change'  => $baseChange,
            'base_quantity_after'   => $baseBefore + $baseChange,
            'reference_type'        => $refType,
            'reference_id'          => $refId,
            'reference_doc'         => $refDoc,
            'user_id'               => $userId ?? auth()->id(),
            'notes'                 => $notes,
        ]);
    }
}
