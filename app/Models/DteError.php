<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DteError extends Model
{
    use HasFactory;

    protected $table = 'dte_errors';

    protected $fillable = [
        'dte_id',
        'sale_id',
        'company_id',
        'tipo_error',
        'codigo_error',
        'descripcion',
        'datos_adicionales'
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relaciones
    public function dte(): BelongsTo
    {
        return $this->belongsTo(Dte::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_error', $tipo);
    }

    public function scopePorVenta($query, int $saleId)
    {
        return $query->where('sale_id', $saleId);
    }

    public function scopePorEmpresa($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRecientes($query, int $dias = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    // Métodos de utilidad
    public function getTipoErrorTextoAttribute(): string
    {
        return match($this->tipo_error) {
            'hacienda' => 'Error de Hacienda',
            'firma' => 'Error de Firma',
            'conexion' => 'Error de Conexión',
            'validacion' => 'Error de Validación',
            'sistema' => 'Error del Sistema',
            'datos' => 'Error de Datos',
            'hacienda_rejected' => 'Rechazado por Hacienda',
            default => 'Error Desconocido'
        };
    }

    public function getSeveridadAttribute(): string
    {
        return match($this->tipo_error) {
            'hacienda', 'hacienda_rejected' => 'high',
            'firma' => 'high',
            'conexion' => 'medium',
            'validacion' => 'medium',
            'sistema' => 'high',
            'datos' => 'low',
            default => 'medium'
        };
    }

    public function getColorAttribute(): string
    {
        return match($this->severidad) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'secondary'
        };
    }
}
