<?php
/**
 * Actualizar imágenes de productos según tipo de presentación
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Cargar Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🖼️  Actualizando imágenes de productos...\n\n";

$imageMap = [
    'tableta' => 'https://via.placeholder.com/200x200/607D8B/FFFFFF?text=Tableta',
    'capsula' => 'https://via.placeholder.com/200x200/9C27B0/FFFFFF?text=Capsula',
    'jarabe' => 'https://via.placeholder.com/200x200/4CAF50/FFFFFF?text=Jarabe',
    'suspension' => 'https://via.placeholder.com/200x200/4CAF50/FFFFFF?text=Suspension',
    'ampolla' => 'https://via.placeholder.com/200x200/2196F3/FFFFFF?text=Ampolla',
    'frasco' => 'https://via.placeholder.com/200x200/00BCD4/FFFFFF?text=Frasco',
    'crema' => 'https://via.placeholder.com/200x200/FF9800/FFFFFF?text=Crema',
    'gel' => 'https://via.placeholder.com/200x200/FF9800/FFFFFF?text=Gel',
    'sobre' => 'https://via.placeholder.com/200x200/FFC107/FFFFFF?text=Sobre',
    'tubo' => 'https://via.placeholder.com/200x200/FF5722/FFFFFF?text=Tubo',
    'blister' => 'https://via.placeholder.com/200x200/3F51B5/FFFFFF?text=Blister',
    'caja' => 'https://via.placeholder.com/200x200/795548/FFFFFF?text=Caja',
];

$defaultImage = 'https://via.placeholder.com/200x200/9E9E9E/FFFFFF?text=Medicamento';

// Actualizar productos con imagen según presentation_type
foreach ($imageMap as $type => $url) {
    DB::table('products')
        ->where('presentation_type', $type)
        ->where(function($query) {
            $query->whereNull('image')
                  ->orWhere('image', '')
                  ->orWhere('image', 'none.jpg');
        })
        ->update(['image' => $url]);
    
    $count = DB::table('products')
        ->where('presentation_type', $type)
        ->where('image', $url)
        ->count();
    
    if ($count > 0) {
        echo "✓ {$type}: {$count} productos actualizados\n";
    }
}

// Actualizar productos sin presentation_type
$updatedOthers = DB::table('products')
    ->whereNull('presentation_type')
    ->where(function($query) {
        $query->whereNull('image')
              ->orWhere('image', '')
              ->orWhere('image', 'none.jpg');
    })
    ->update(['image' => $defaultImage]);

if ($updatedOthers > 0) {
    echo "✓ otros: {$updatedOthers} productos actualizados\n";
}

echo "\n✅ Imágenes actualizadas exitosamente\n";

// Estadísticas
$withImages = DB::table('products')
    ->where('image', '!=', 'none.jpg')
    ->whereNotNull('image')
    ->count();

$withoutImages = DB::table('products')
    ->where(function($query) {
        $query->where('image', 'none.jpg')
              ->orWhereNull('image');
    })
    ->count();

echo "\n📊 Resumen:\n";
echo "  Con imagen: {$withImages}\n";
echo "  Sin imagen: {$withoutImages}\n";


