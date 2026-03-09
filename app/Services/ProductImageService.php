<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    /**
     * Buscar imagen de medicamento en internet
     */
    public function searchMedicineImage($productName)
    {
        // Limpiar nombre del producto
        $searchTerm = $this->cleanProductName($productName);
        
        // Intentar buscar en diferentes fuentes
        $imageUrl = $this->searchUnsplash($searchTerm);
        
        if (!$imageUrl) {
            $imageUrl = $this->getPlaceholderImage($productName);
        }
        
        return $imageUrl;
    }

    /**
     * Descargar imagen y guardarla localmente
     */
    public function downloadAndSaveImage($imageUrl, $productCode)
    {
        try {
            $response = Http::timeout(10)->get($imageUrl);
            
            if ($response->successful()) {
                $extension = $this->getImageExtension($imageUrl);
                $filename = time() . '_' . Str::slug($productCode) . '.' . $extension;
                $path = 'products/' . $filename;
                
                Storage::disk('public')->put($path, $response->body());
                
                return $filename;
            }
        } catch (\Exception $e) {
            \Log::error("Error descargando imagen: " . $e->getMessage());
        }
        
        return 'none.jpg';
    }

    /**
     * Buscar en Unsplash (API gratuita)
     */
    private function searchUnsplash($searchTerm)
    {
        // Necesitarías una API key de Unsplash
        // Por ahora retornamos null
        return null;
    }

    /**
     * Obtener imagen placeholder según tipo de medicamento
     */
    public function getPlaceholderImage($productName)
    {
        $name = strtolower($productName);
        
        // Imágenes genéricas según tipo
        if (str_contains($name, 'jarabe') || str_contains($name, 'suspension')) {
            return 'https://via.placeholder.com/200x200/4CAF50/FFFFFF?text=Jarabe';
        }
        
        if (str_contains($name, 'ampolla') || str_contains($name, 'inyectable')) {
            return 'https://via.placeholder.com/200x200/2196F3/FFFFFF?text=Ampolla';
        }
        
        if (str_contains($name, 'crema') || str_contains($name, 'gel') || str_contains($name, 'unguento')) {
            return 'https://via.placeholder.com/200x200/FF9800/FFFFFF?text=Crema';
        }
        
        if (str_contains($name, 'capsula')) {
            return 'https://via.placeholder.com/200x200/9C27B0/FFFFFF?text=Capsula';
        }
        
        // Por defecto: tableta/pastilla
        return 'https://via.placeholder.com/200x200/607D8B/FFFFFF?text=Medicamento';
    }

    /**
     * Limpiar nombre del producto para búsqueda
     */
    private function cleanProductName($name)
    {
        // Extraer solo el nombre principal sin dosis ni presentación
        $name = preg_replace('/\d+\s*(mg|ml|g|mcg|%)/i', '', $name);
        $name = preg_replace('/\d+\s*(tabletas|capsulas|ml)/i', '', $name);
        $name = trim($name);
        
        return $name;
    }

    /**
     * Obtener extensión de imagen desde URL
     */
    private function getImageExtension($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $extension : 'jpg';
    }

    /**
     * Obtener imagen por tipo de presentación
     */
    public function getImageByPresentationType($presentationType)
    {
        $colors = [
            'tableta' => '607D8B',
            'capsula' => '9C27B0',
            'jarabe' => '4CAF50',
            'suspension' => '4CAF50',
            'ampolla' => '2196F3',
            'frasco' => '00BCD4',
            'crema' => 'FF9800',
            'gel' => 'FF9800',
            'sobre' => 'FFC107',
            'tubo' => 'FF5722',
            'otro' => '9E9E9E'
        ];
        
        $color = $colors[$presentationType] ?? '9E9E9E';
        $text = ucfirst($presentationType);
        
        return "https://via.placeholder.com/200x200/{$color}/FFFFFF?text={$text}";
    }
}


