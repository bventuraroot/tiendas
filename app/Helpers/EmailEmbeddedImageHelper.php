<?php

namespace App\Helpers;

class EmailEmbeddedImageHelper
{
    /**
     * Agregar imagen embebida al correo para usar como foto de contacto
     *
     * @param \Illuminate\Mail\Mailable $message
     * @param string|null $logoPath Ruta personalizada del logo (opcional)
     * @return string|null CID de la imagen embebida
     */
    public static function embedContactPhoto($message, $logoPath = null)
    {
        try {
            $defaultLogoPath = public_path('assets/img/logo/logo.png');
            $logoPath = $logoPath ?: $defaultLogoPath;

            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                if ($logoData && strlen($logoData) < 2000000) {
                    // Generar un CID único para la imagen
                    $cid = 'company-logo-' . uniqid();

                    // Agregar la imagen como adjunto embebido
                    $message->attachData($logoData, 'company-logo.png', [
                        'mime' => 'image/png',
                        'as' => $cid,
                        'cid' => $cid
                    ]);

                    return $cid;
                }
            }
        } catch (\Exception $e) {
            \Log::info('Error al embebir foto de contacto: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Crear un HTML con imagen embebida para usar en plantillas de correo
     *
     * @param string $cid CID de la imagen embebida
     * @param string $alt Texto alternativo
     * @param array $attributes Atributos adicionales para la imagen
     * @return string
     */
    public static function createEmbeddedImageHtml($cid, $alt = 'Logo Empresa', $attributes = [])
    {
        $defaultAttributes = [
            'style' => 'max-width: 120px; max-height: 60px; height: auto; border-radius: 5px;',
            'alt' => $alt
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= " {$key}=\"{$value}\"";
        }

        return "<img src=\"cid:{$cid}\"{$attributeString}>";
    }
}
