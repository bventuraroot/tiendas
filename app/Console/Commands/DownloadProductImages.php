<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images 
                            {--limit=10 : Número de productos a procesar}
                            {--force : Sobrescribir imágenes existentes}';

    protected $description = 'Busca y descarga imágenes de medicamentos desde internet';

    public function handle()
    {
        $this->info('🖼️  Iniciando descarga de imágenes de productos...');
        $this->newLine();

        $limit = $this->option('limit');
        $force = $this->option('force');

        // Obtener productos sin imagen o con none.jpg
        $query = Product::where(function($q) use ($force) {
            if ($force) {
                $q->whereNotNull('id');
            } else {
                $q->where('image', 'none.jpg')
                  ->orWhereNull('image')
                  ->orWhere('image', '');
            }
        });

        $total = $query->count();
        $products = $query->limit($limit)->get();

        $this->info("📊 Productos a procesar: {$products->count()} de {$total}");
        $this->newLine();

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $imageFilename = $this->downloadProductImage($product);
                
                if ($imageFilename && $imageFilename != 'none.jpg') {
                    $product->image = $imageFilename;
                    $product->save();
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nError con producto {$product->code}: " . $e->getMessage());
                $failed++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Proceso completado:");
        $this->info("   • Exitosos: {$success}");
        $this->info("   • Fallidos: {$failed}");
        
        if ($total > $limit) {
            $remaining = $total - $limit;
            $this->warn("   • Pendientes: {$remaining}");
            $this->info("\nEjecuta de nuevo con --limit={$remaining} para procesar los restantes");
        }

        return 0;
    }

    private function downloadProductImage($product)
    {
        // Intentar buscar imagen
        $imageUrl = $this->searchImageUrl($product);
        
        if (!$imageUrl) {
            // Si no encuentra, crear una imagen local según tipo
            $this->warn("\n⚠ No se encontró URL para: {$product->name}");
            return $this->createLocalImage($product);
        }

        // Descargar imagen
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($imageUrl);
            
            if ($response->successful() && $response->header('Content-Type') && strpos($response->header('Content-Type'), 'image') !== false) {
                $extension = $this->getImageExtension($imageUrl, $response->header('Content-Type'));
                $filename = time() . '_' . Str::slug(substr($product->code, 0, 20)) . '.' . $extension;
                
                // Guardar en public/assets/img/products/
                $path = public_path('assets/img/products/' . $filename);
                file_put_contents($path, $response->body());
                
                $this->line("\n✓ Descargada: {$product->name}");
                
                return $filename;
            } else {
                $this->warn("\n⚠ Respuesta no válida para: {$product->name} (URL: {$imageUrl})");
            }
        } catch (\Exception $e) {
            $this->warn("\n⚠ Error descargando {$product->name}: " . $e->getMessage());
        }
        
        return $this->createLocalImage($product);
    }

    private function searchImageUrl($product)
    {
        // Opción 1: Pexels API (100% GRATIS, ilimitado, REQUIERE API key) - PRIORIDAD ALTA
        $pexelsKey = env('PEXELS_API_KEY');
        if ($pexelsKey) {
            $url = $this->searchPexels($product->name, $pexelsKey);
            if ($url) {
                return $url;
            }
        }

        // Opción 2: DuckDuckGo (100% GRATIS, sin límites, sin API key)
        $url = $this->searchDuckDuckGo($product->name);
        if ($url) {
            return $url;
        }

        // Opción 3: CIMA API (100% GRATIS, específica para medicamentos españoles)
        $url = $this->searchCIMA($product->name);
        if ($url) {
            return $url;
        }

        // Opción 4: Google Custom Search (requiere API key)
        $googleKey = env('GOOGLE_API_KEY');
        $googleCX = env('GOOGLE_SEARCH_CX');
        if ($googleKey && $googleCX) {
            $url = $this->searchGoogleImages($product->name, $googleKey, $googleCX);
            if ($url) return $url;
        }

        // Opción 5: Serpapi (requiere API key)
        $serpapiKey = env('SERPAPI_KEY');
        if ($serpapiKey) {
            $url = $this->searchSerpapi($product->name, $serpapiKey);
            if ($url) return $url;
        }

        // Opción 6: Bing Image Search (requiere API key)
        $bingKey = env('BING_SEARCH_KEY');
        if ($bingKey) {
            $url = $this->searchBingImages($product->name, $bingKey);
            if ($url) return $url;
        }

        // Si ninguna funciona, retornar null
        return null;
    }

    /**
     * DuckDuckGo Image Search - 100% GRATIS, sin límites, sin API key
     * Usa scraping de DuckDuckGo HTML
     */
    private function searchDuckDuckGo($productName)
    {
        try {
            $query = urlencode($productName . ' medicamento farmacia');
            
            // DuckDuckGo HTML search (funciona sin API key)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])
                ->get("https://html.duckduckgo.com/html/", [
                    'q' => $productName . ' medicamento empaque'
                ]);

            if ($response->successful()) {
                $html = $response->body();
                
                // Buscar imágenes en el HTML
                // DuckDuckGo estructura puede variar, buscar varios patrones
                $patterns = [
                    '/data-src=["\']([^"\']+\.(?:jpg|jpeg|png|webp))[^"\']*["\']/i',
                    '/src=["\']([^"\']+\.(?:jpg|jpeg|png|webp))[^"\']*["\']/i',
                    '/<img[^>]+src=["\']([^"\']+\.(?:jpg|jpeg|png|webp))[^"\']*["\']/i'
                ];
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        $imageUrl = $matches[1];
                        if (strpos($imageUrl, 'http') === 0) {
                            return $imageUrl;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores
        }

        return null;
    }

    /**
     * CIMA API - Base de datos de medicamentos españoles (100% GRATIS)
     * https://cima.aemps.es
     */
    private function searchCIMA($productName)
    {
        try {
            // Buscar medicamento en CIMA
            $cleanName = $this->cleanProductName($productName);
            $response = Http::timeout(10)->get("https://cima.aemps.es/cima/rest/medicamentos", [
                'nombre' => $cleanName,
                'nregistro' => '',
                'laboratorio' => '',
                'pautas' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Si encuentra medicamento, intentar obtener su imagen
                if (isset($data['resultados'][0]['nregistro'])) {
                    $nregistro = $data['resultados'][0]['nregistro'];
                    
                    // Obtener detalles del medicamento
                    $detailResponse = Http::timeout(10)->get("https://cima.aemps.es/cima/rest/medicamento/{$nregistro}");
                    
                    if ($detailResponse->successful()) {
                        $detail = $detailResponse->json();
                        
                        // CIMA puede tener imágenes en diferentes campos
                        if (isset($detail['fotos'][0]['url'])) {
                            return $detail['fotos'][0]['url'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Continuar con siguiente método
        }

        return null;
    }

    /**
     * Unsplash Source - URLs directas públicas (100% GRATIS, sin API key)
     * NOTA: Unsplash Source fue descontinuado, usar método alternativo
     */
    private function searchUnsplash($productName)
    {
        // Unsplash Source fue descontinuado
        // Dejar vacío por ahora
        return null;
    }

    private function searchGoogleImages($productName, $apiKey, $cx)
    {
        try {
            // Búsqueda específica del medicamento
            $query = $productName . ' medicamento';
            
            $response = Http::get('https://www.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => $query,
                'searchType' => 'image',
                'num' => 1,
                'safe' => 'active'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['items'][0]['link'])) {
                    return $data['items'][0]['link'];
                }
            }
        } catch (\Exception $e) {
            // Error
        }

        return null;
    }

    private function searchSerpapi($productName, $apiKey)
    {
        try {
            $response = Http::get('https://serpapi.com/search', [
                'engine' => 'google',
                'tbm' => 'isch',
                'q' => $productName . ' medicamento farmacia',
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['images_results'][0]['original'])) {
                    return $data['images_results'][0]['original'];
                }
            }
        } catch (\Exception $e) {
            // Error
        }

        return null;
    }

    private function searchBingImages($productName, $apiKey)
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $apiKey
            ])->get('https://api.bing.microsoft.com/v7.0/images/search', [
                'q' => $productName . ' medicamento',
                'count' => 1,
                'safeSearch' => 'Strict'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['value'][0]['contentUrl'])) {
                    return $data['value'][0]['contentUrl'];
                }
            }
        } catch (\Exception $e) {
            // Error
        }

        return null;
    }

    private function searchPixabay($query, $apiKey)
    {
        try {
            $cleanQuery = $this->cleanProductName($query);
            $response = Http::get('https://pixabay.com/api/', [
                'key' => $apiKey,
                'q' => $cleanQuery,
                'image_type' => 'photo',
                'per_page' => 1,
                'safesearch' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['hits'][0]['webformatURL'])) {
                    return $data['hits'][0]['webformatURL'];
                }
            }
        } catch (\Exception $e) {
            // Falló
        }

        return null;
    }

    private function searchPexels($query, $apiKey = null)
    {
        // Pexels requiere API key obligatoriamente
        if (!$apiKey) {
            return null;
        }

        try {
            $cleanQuery = $this->cleanProductName($query) . ' medicine pharmaceutical';
            
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $apiKey
                ])
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $cleanQuery,
                    'per_page' => 1,
                    'orientation' => 'square'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Pexels devuelve imágenes en diferentes tamaños
                if (isset($data['photos'][0]['src']['medium'])) {
                    return $data['photos'][0]['src']['medium'];
                }
                if (isset($data['photos'][0]['src']['large'])) {
                    return $data['photos'][0]['src']['large'];
                }
                if (isset($data['photos'][0]['src']['original'])) {
                    return $data['photos'][0]['src']['original'];
                }
            } else {
                // Log error si la API key es inválida
                if ($response->status() === 401) {
                    $this->warn("\n⚠ API Key de Pexels inválida. Verifica tu clave.");
                }
            }
        } catch (\Exception $e) {
            // Falló, continuar
        }

        return null;
    }

    private function getGenericMedicineImage($product)
    {
        // URLs de imágenes genéricas de medicamentos (dominios públicos)
        $genericImages = [
            'tableta' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=200&h=200&fit=crop',
            'capsula' => 'https://images.unsplash.com/photo-1550572017-4c0da2f515a5?w=200&h=200&fit=crop',
            'jarabe' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=200&h=200&fit=crop',
            'frasco' => 'https://images.unsplash.com/photo-1584362917165-526a968579e8?w=200&h=200&fit=crop',
            'ampolla' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=200&h=200&fit=crop',
        ];

        $presentationType = $product->presentation_type ?? 'tableta';
        return $genericImages[$presentationType] ?? $genericImages['tableta'];
    }

    private function createLocalImage($product)
    {
        // Si no se puede descargar, retornar none.jpg
        // Las imágenes SVG se generarán dinámicamente en la vista
        return 'none.jpg';
    }

    private function cleanProductName($name)
    {
        // Limpiar nombre del producto para búsqueda
        $name = preg_replace('/\d+\s*(mg|ml|g|mcg|%)/i', '', $name);
        $name = preg_replace('/\d+\s*(tabletas|capsulas|ml)/i', '', $name);
        
        // Obtener solo las primeras 2-3 palabras
        $words = explode(' ', trim($name));
        $name = implode(' ', array_slice($words, 0, 2));
        
        return trim($name);
    }

    private function getImageExtension($url, $contentType = null)
    {
        // Primero intentar desde Content-Type header
        if ($contentType) {
            if (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) {
                return 'jpg';
            }
            if (strpos($contentType, 'png') !== false) {
                return 'png';
            }
            if (strpos($contentType, 'gif') !== false) {
                return 'gif';
            }
            if (strpos($contentType, 'webp') !== false) {
                return 'webp';
            }
        }
        
        // Si no, extraer de la URL
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $extension : 'jpg';
    }
}
