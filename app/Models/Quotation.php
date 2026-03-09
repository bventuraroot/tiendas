<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_number',
        'company_id',
        'client_id',
        'user_id',
        'quote_date',
        'valid_until',
        'status',
        'subtotal',
        'total_amount',
        'notes',
        'terms_conditions',
        'payment_terms',
        'delivery_time',
        'currency',
        'discount_percentage',
        'discount_amount'
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2'
    ];

    /**
     * Estados posibles de una cotización
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CONVERTED = 'converted';
    const STATUS_EXPIRED = 'expired';

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con el cliente
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el usuario que creó la cotización
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los detalles de la cotización
     */
    public function details()
    {
        return $this->hasMany(QuotationDetail::class);
    }

    /**
     * Generar automáticamente el número de cotización
     */
    public static function generateQuoteNumber($companyId)
    {
        $year = date('Y');
        $month = date('m');

        // Buscar el último número de cotización para esta empresa en este año
        $lastQuote = self::where('company_id', $companyId)
            ->where('quote_number', 'like', "COT-{$year}{$month}-%")
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            // Extraer el número secuencial del último número de cotización
            $lastNumber = (int) substr($lastQuote->quote_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("COT-%s%s-%04d", $year, $month, $newNumber);
    }

    /**
     * Verificar si la cotización está expirada
     */
    public function isExpired()
    {
        return Carbon::now()->isAfter($this->valid_until);
    }

    /**
     * Obtener el estado en español
     */
    public function getStatusInSpanish()
    {
        $statuses = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_CONVERTED => 'Convertida a Venta',
            self::STATUS_EXPIRED => 'Expirada'
        ];

        return $statuses[$this->status] ?? 'Desconocido';
    }

    /**
     * Calcular totales automáticamente
     */
        public function calculateTotals()
    {
        $details = $this->details;

        // Calcular subtotal sumando los totales de los detalles
        $subtotal = $details->sum(function ($detail) {
            // Calcular el total de cada detalle
            $detailSubtotal = $detail->quantity * $detail->unit_price;

            // Aplicar descuento si existe
            if ($detail->discount_percentage > 0) {
                $discountAmount = $detailSubtotal * ($detail->discount_percentage / 100);
                $detailSubtotal = $detailSubtotal - $discountAmount;
            }

            return $detailSubtotal;
        });

        // El total es igual al subtotal ya que los productos ya incluyen IVA
        $total = $subtotal;

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total
        ]);
    }
}
