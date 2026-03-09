<?php
/**
 * Actualizar productos con sus marcas
 * Relaciona cada producto con su marca según el laboratorio del CSV
 */

$csvFile = __DIR__ . '/../Recursos /Productos/existencia medicamento.csv';
$delimiter = ';';

echo "🔄 Actualizando productos con marcas...\n\n";

if (!file_exists($csvFile)) {
    die("❌ Error: No se encontró el archivo CSV\n");
}

$file = fopen($csvFile, 'r');

// Saltar primeras 4 líneas
for ($i = 0; $i < 4; $i++) {
    fgets($file);
}

// Leer encabezados
$headers = fgetcsv($file, 0, $delimiter);

// Arrays para actualización
$updates = [];
$codigosUsados = [];
$contador = 1;

while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
    if (empty($row[0]) || trim($row[0]) == '') {
        continue;
    }
    
    $codigo = trim($row[0]);
    $laboratorio = trim($row[8]); // Columna de laboratorio que usamos como marca
    
    // Limpiar código
    $codigo = str_replace(['E+', 'e+'], '', $codigo);
    $codigo = preg_replace('/[^0-9A-Za-z\-]/', '', $codigo);
    
    // Código único (igual que en la importación)
    if (isset($codigosUsados[$codigo])) {
        $codigo = 'MED' . str_pad($contador, 6, '0', STR_PAD_LEFT);
        $contador++;
    }
    $codigosUsados[$codigo] = true;
    
    if (!empty($laboratorio) && $laboratorio != ' ') {
        $updates[] = [
            'code' => $codigo,
            'marca' => $laboratorio
        ];
    }
}

fclose($file);

echo "✅ Procesados: " . count($updates) . " productos con marca\n\n";

// Generar SQL de actualización
$sqlContent = "-- ACTUALIZAR PRODUCTOS CON MARCAS\n";
$sqlContent .= "-- Total: " . count($updates) . "\n\n";

$sqlContent .= "-- Actualizar productos relacionando con marcas\n";
foreach ($updates as $update) {
    $codeSafe = addslashes($update['code']);
    $marcaSafe = addslashes($update['marca']);
    
    $sqlContent .= "UPDATE products p\n";
    $sqlContent .= "JOIN marcas m ON m.name = '{$marcaSafe}'\n";
    $sqlContent .= "SET p.marca_id = m.id\n";
    $sqlContent .= "WHERE p.code = '{$codeSafe}';\n\n";
}

$sqlContent .= "-- Verificación\n";
$sqlContent .= "SELECT COUNT(*) as 'Productos con Marca' FROM products WHERE marca_id > 0;\n";
$sqlContent .= "SELECT COUNT(*) as 'Productos sin Marca' FROM products WHERE marca_id = 0 OR marca_id IS NULL;\n";

$outputFile = __DIR__ . '/../database/update_products_marcas.sql';
file_put_contents($outputFile, $sqlContent);

echo "📝 Archivo generado: database/update_products_marcas.sql\n";
echo "✅ Listo para ejecutar\n";


