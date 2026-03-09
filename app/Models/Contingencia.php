<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Contingencia extends Model
{
    use HasFactory;

    protected $table = "contingencias";

    protected $fillable = [
        'nombre',
        'empresa_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
        'created_by',
        'updated_by',
        // Campos legacy para compatibilidad
        'idEmpresa',
        'ambiente',
        'versionJson',
        'codEstado',
        'tipoContingencia',
        'motivoContingencia',
        'nombreResponsable',
        'tipoDocResponsable',
        'nuDocResponsable',
        'fechaCreacion',
        'fInicio',
        'fFin',
        'horaCreacion',
        'hInicio',
        'hFin',
        'codigoGeneracion',
        'selloRecibido',
        'fhRecibido',
        'descripcionMsg',
        'observacionesMsg',
        'estadoHacienda',
        'codEstadoHacienda'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fechaCreacion' => 'datetime',
        'fInicio' => 'date',
        'fFin' => 'date',
        'fhRecibido' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Estados de contingencia
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_ACTIVA = 'activa';
    const ESTADO_FINALIZADA = 'finalizada';
    const ESTADO_CANCELADA = 'cancelada';

    // Tipos de contingencia
    const TIPO_TECNICA = 'tecnica';
    const TIPO_ADMINISTRATIVA = 'administrativa';
    const TIPO_EMERGENCIA = 'emergencia';

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'empresa_id');
    }

    /**
     * Relación con el usuario que la creó
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que la aprobó
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relación con los DTEs afectados
     */
    public function dtes(): HasMany
    {
        return $this->hasMany(Dte::class, 'idContingencia');
    }

    /**
     * Scope para contingencias activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    /**
     * Scope para contingencias pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para contingencias por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('idEmpresa', $empresaId);
    }

    /**
     * Verificar si la contingencia está activa
     */
    public function isActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA;
    }

    /**
     * Verificar si la contingencia está pendiente
     */
    public function isPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verificar si la contingencia está vigente (dentro del rango de fechas)
     */
    public function isVigente(): bool
    {
        $now = now();
        return $this->isActiva() &&
               $this->fInicio <= $now &&
               $this->fFin >= $now;
    }

    /**
     * Obtener el estado como texto
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_APROBADA => 'Aprobada',
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_FINALIZADA => 'Finalizada',
            self::ESTADO_CANCELADA => 'Cancelada',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el tipo como texto
     */
    public function getTipoTextoAttribute(): string
    {
        return match($this->tipoContingencia) {
            self::TIPO_TECNICA => 'Técnica',
            self::TIPO_ADMINISTRATIVA => 'Administrativa',
            self::TIPO_EMERGENCIA => 'Emergencia',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el estado con clase CSS para badges
     */
    public function getEstadoBadgeAttribute(): string
    {
        $color = match($this->estado) {
            self::ESTADO_PENDIENTE => 'warning',
            self::ESTADO_APROBADA => 'info',
            self::ESTADO_ACTIVA => 'success',
            self::ESTADO_FINALIZADA => 'secondary',
            self::ESTADO_CANCELADA => 'danger',
            default => 'dark'
        };

        return '<span class="badge bg-' . $color . '">' . $this->estado_texto . '</span>';
    }

    /**
     * Obtener días restantes de vigencia
     */
    public function getDiasRestantesAttribute(): int
    {
        if (!$this->isVigente()) {
            return 0;
        }

        return $this->fFin->diffInDays(now());
    }

    /**
     * Obtener porcentaje de tiempo transcurrido
     */
    public function getPorcentajeTranscurridoAttribute(): float
    {
        if (!$this->fInicio || !$this->fFin) {
            return 0;
        }

        $total = $this->fFin->diffInDays($this->fInicio);
        $transcurrido = now()->diffInDays($this->fInicio);

        if ($total == 0) {
            return 0;
        }

        return min(100, max(0, ($transcurrido / $total) * 100));
    }

    /**
     * Aprobar contingencia
     */
    public function aprobar(int $userId): bool
    {
        return $this->update([
            'estado' => self::ESTADO_APROBADA,
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    /**
     * Activar contingencia
     */
    public function activar(): bool
    {
        return $this->update([
            'estado' => self::ESTADO_ACTIVA
        ]);
    }

    /**
     * Finalizar contingencia
     */
    public function finalizar(): bool
    {
        return $this->update([
            'estado' => self::ESTADO_FINALIZADA
        ]);
    }

    /**
     * Cancelar contingencia
     */
    public function cancelar(): bool
    {
        return $this->update([
            'estado' => self::ESTADO_CANCELADA
        ]);
    }

    /**
     * Obtener estadísticas de la contingencia
     */
    public function getEstadisticas(): array
    {
        $dtes = $this->dtes;

        return [
            'total_documentos' => $dtes->count(),
            'enviados' => $dtes->where('codEstado', Dte::ESTADO_ENVIADO)->count(),
            'rechazados' => $dtes->where('codEstado', Dte::ESTADO_RECHAZADO)->count(),
            'en_revision' => $dtes->where('codEstado', Dte::ESTADO_REVISION)->count(),
            'porcentaje_exito' => $dtes->count() > 0 ?
                round(($dtes->where('codEstado', Dte::ESTADO_ENVIADO)->count() / $dtes->count()) * 100, 2) : 0
        ];
    }
}
