<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'nu_unico',
        'nu_doc',
        'acuenta',
        'state',
        'state_credit',
        'totalamount',
        'retencion_agente',
        'waytopay',
        'typesale',
        'date',
        'user_id',
        'typedocument_id',
        'client_id',
        'company_id',
        'json',
        'doc_related',
        'id_contingencia',
        'codigoGeneracion',
        'motivo',
        'card_authorization_number'
    ];

    protected $casts = [
        'date' => 'date',
        'state' => 'boolean',
        'state_credit' => 'boolean',
        'totalamount' => 'decimal:8',
        'retencion_agente' => 'decimal:8'
    ];

    public function details()
    {
        return $this->hasMany(Salesdetail::class, 'sale_id');
    }

    public function salesdetails()
    {
        return $this->hasMany(Salesdetail::class, 'sale_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typedocument()
    {
        return $this->belongsTo(Typedocument::class);
    }

    /**
     * Relación con documento tributario electrónico
     */
    public function dte()
    {
        return $this->hasOne(Dte::class, 'sale_id');
    }

    /**
     * Verificar si la venta tiene DTE
     */
    public function hasDte()
    {
        return $this->dte()->exists();
    }

    /**
     * Obtener información del DTE si existe
     */
    public function getDteInfo()
    {
        if ($this->hasDte()) {
            return $this->dte;
        }
        return null;
    }
}
