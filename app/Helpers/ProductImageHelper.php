<?php

namespace App\Helpers;

class ProductImageHelper
{
    /**
     * Obtener imagen SVG según tipo de presentación
     */
    public static function getImageSVG($presentationType)
    {
        $colors = [
            'tableta' => ['bg' => '#607D8B', 'text' => 'TABLETA'],
            'capsula' => ['bg' => '#9C27B0', 'text' => 'CÁPSULA'],
            'jarabe' => ['bg' => '#4CAF50', 'text' => 'JARABE'],
            'suspension' => ['bg' => '#4CAF50', 'text' => 'SUSPENSIÓN'],
            'ampolla' => ['bg' => '#2196F3', 'text' => 'AMPOLLA'],
            'frasco' => ['bg' => '#00BCD4', 'text' => 'FRASCO'],
            'crema' => ['bg' => '#FF9800', 'text' => 'CREMA'],
            'gel' => ['bg' => '#FF9800', 'text' => 'GEL'],
            'sobre' => ['bg' => '#FFC107', 'text' => 'SOBRE'],
            'tubo' => ['bg' => '#FF5722', 'text' => 'TUBO'],
            'blister' => ['bg' => '#3F51B5', 'text' => 'BLISTER'],
            'caja' => ['bg' => '#795548', 'text' => 'CAJA'],
            'otro' => ['bg' => '#9E9E9E', 'text' => 'MEDICAMENTO'],
        ];

        $config = $colors[$presentationType] ?? $colors['otro'];
        
        $svg = '<svg width="80" height="80" xmlns="http://www.w3.org/2000/svg">
            <rect width="80" height="80" fill="' . $config['bg'] . '" rx="8"/>
            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="10" font-weight="bold" 
                  fill="white" text-anchor="middle" dominant-baseline="middle">
                ' . $config['text'] . '
            </text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}


