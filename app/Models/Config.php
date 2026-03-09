<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Config extends Model
{
    use HasFactory;
    protected $table = "config";// <-- El nombre personalizado

    protected $fillable = [
        'company_id',
        'version',
        'ambiente',
        'typeModel',
        'typeTransmission',
        'typeContingencia',
        'versionJson',
        'passPrivateKey',
        'passkeyPublic',
        'passMH',
        'codeCountry',
        'nameCountry',
        'dte_emission_enabled',
        'dte_emission_notes'
    ];

    protected $casts = [
        'dte_emission_enabled' => 'boolean',
    ];

    /**
     * Verificar si la emisión DTE está habilitada para una empresa específica
     *
     * @param int $companyId ID de la empresa
     * @return bool
     */
    public static function isDteEmissionEnabled($companyId)
    {
        $config = self::where('company_id', $companyId)->first();

        if (!$config) {
            Log::warning("No se encontró configuración DTE para empresa ID: {$companyId}");
            return false;
        }

        $isEnabled = (bool) $config->dte_emission_enabled;

        Log::info("Estado emisión DTE para empresa ID {$companyId}: " . ($isEnabled ? 'HABILITADO' : 'DESHABILITADO'));

        return $isEnabled;
    }

    /**
     * Obtener la configuración DTE de una empresa
     *
     * @param int $companyId ID de la empresa
     * @return Config|null
     */
    public static function getDteConfig($companyId)
    {
        return self::where('company_id', $companyId)->first();
    }
}
