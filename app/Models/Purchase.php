<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'provider_id',
        'company_id',
        'number',
        'date',
        'exenta',
        'gravada',
        'iva',
        'contrns',
        'fovial',
        'iretenido',
        'otros',
        'total',
        'paid_amount',
        'payment_status',
        'payment_type',
        'credit_days',
        'payment_due_date',
        'fingreso',
        'periodo',
        'user_id'
    ];

    protected $casts = [
        'date' => 'date',
        'fingreso' => 'date',
        'exenta' => 'decimal:5',
        'gravada' => 'decimal:5',
        'iva' => 'decimal:5',
        'contrns' => 'decimal:5',
        'fovial' => 'decimal:5',
        'iretenido' => 'decimal:5',
        'otros' => 'decimal:5',
        'total' => 'decimal:5',
        'paid_amount' => 'decimal:5',
        'payment_status' => 'integer',
        'payment_due_date' => 'date',
        'credit_days' => 'integer'
    ];

    /**
     * Relación con el proveedor
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los detalles de la compra
     */
    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Relación con los pagos de la compra
     */
    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * Calcular totales de la compra
     */
    public function calculateTotals()
    {
        $details = $this->details;

        $exenta = $details->sum('subtotal');
        $gravada = $details->sum('subtotal');
        $iva = $details->sum('tax_amount');
        $total = $details->sum('total_amount');

        $this->update([
            'exenta' => $exenta,
            'gravada' => $gravada,
            'iva' => $iva,
            'total' => $total
        ]);
    }

    /**
     * Verificar si todos los detalles han sido agregados al inventario
     */
    public function allDetailsAddedToInventory()
    {
        return $this->details()->where('added_to_inventory', false)->count() === 0;
    }

    /**
     * Obtener productos próximos a vencer en esta compra
     */
    public function getExpiringProducts($days = 30)
    {
        return $this->details()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now())
            ->with('product')
            ->get();
    }

    /**
     * Obtener productos vencidos en esta compra
     */
    public function getExpiredProducts()
    {
        return $this->details()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now())
            ->with('product')
            ->get();
    }

    /**
     * Obtener el saldo pendiente de la compra
     */
    public function getBalanceAttribute()
    {
        return max(0, $this->total - $this->paid_amount);
    }

    /**
     * Verificar si la compra está pagada completamente
     */
    public function isPaid()
    {
        return $this->payment_status == 2 || $this->balance <= 0;
    }

    /**
     * Verificar si la compra tiene pagos parciales
     */
    public function isPartiallyPaid()
    {
        return $this->payment_status == 1 || ($this->paid_amount > 0 && $this->balance > 0);
    }

    /**
     * Verificar si la compra está pendiente de pago
     */
    public function isPending()
    {
        return $this->payment_status == 0 || ($this->paid_amount == 0 && $this->balance == $this->total);
    }

    /**
     * Obtener el último pago registrado
     */
    public function getLastPayment()
    {
        return $this->payments()->orderBy('id', 'desc')->first();
    }

    /**
     * Obtener el total pagado hasta la fecha
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amountpay');
    }
}
