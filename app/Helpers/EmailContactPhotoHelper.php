<?php

namespace App\Helpers;

class EmailContactPhotoHelper
{
    /**
     * Agregar headers personalizados para foto de contacto a un mensaje de correo
     *
     * @param \Illuminate\Mail\Mailable $message
     * @param string|null $logoPath Ruta personalizada del logo (opcional)
     * @return void
     */
    public static function addContactPhotoHeaders($message, $logoPath = null)
    {
        try {
            // Usar la ruta proporcionada o la ruta por defecto
            $defaultLogoPath = public_path('assets/img/logo/logo.png');
            $logoPath = $logoPath ?: $defaultLogoPath;

            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                if ($logoData && strlen($logoData) < 2000000) {
                    $logoBase64 = base64_encode($logoData);

                    // Headers para foto de contacto (compatibles con algunos clientes)
                    $message->getSwiftMessage()->getHeaders()->addTextHeader(
                        'X-Contact-Photo',
                        'data:image/png;base64,' . $logoBase64
                    );

                    // Header alternativo para algunos clientes
                    $message->getSwiftMessage()->getHeaders()->addTextHeader(
                        'X-Avatar',
                        'data:image/png;base64,' . $logoBase64
                    );

                    // Header adicional para compatibilidad con más clientes
                    $message->getSwiftMessage()->getHeaders()->addTextHeader(
                        'X-Profile-Image',
                        'data:image/png;base64,' . $logoBase64
                    );
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores para no afectar el envío del correo
            \Log::info('Error al agregar foto de contacto al correo: ' . $e->getMessage());
        }
    }

    /**
     * Obtener el logo de la empresa en formato base64
     *
     * @param string|null $logoPath Ruta personalizada del logo (opcional)
     * @return string|null
     */
    public static function getCompanyLogoBase64($logoPath = null)
    {
        try {
            $defaultLogoPath = public_path('assets/img/logo/logo.png');
            $logoPath = $logoPath ?: $defaultLogoPath;

            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                if ($logoData && strlen($logoData) < 2000000) {
                    return 'data:image/png;base64,' . base64_encode($logoData);
                }
            }
        } catch (\Exception $e) {
            \Log::info('Error al obtener logo de la empresa: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Verificar si existe un logo válido
     *
     * @param string|null $logoPath Ruta personalizada del logo (opcional)
     * @return bool
     */
    public static function hasValidLogo($logoPath = null)
    {
        try {
            $defaultLogoPath = public_path('assets/img/logo/logo.png');
            $logoPath = $logoPath ?: $defaultLogoPath;

            return file_exists($logoPath) && filesize($logoPath) > 0 && filesize($logoPath) < 2000000;
        } catch (\Exception $e) {
            return false;
        }
    }
}
