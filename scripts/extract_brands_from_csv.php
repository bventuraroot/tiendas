<?php
/**
 * Extraer marcas únicas del CSV de productos
 */

$csvFile = __DIR__ . '/../Recursos /Productos/existencia medicamento.csv';
$delimiter = ';';

echo "📊 Analizando CSV para extraer marcas...\n\n";

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

echo "📋 Columnas disponibles:\n";
foreach ($headers as $index => $header) {
    echo "  [$index] " . trim($header) . "\n";
}

echo "\n¿Hay columna de MARCA en el CSV? Revisando...\n\n";

// Buscar columnas que puedan ser marcas
$marcaColumnIndex = null;
foreach ($headers as $index => $header) {
    $headerLower = strtolower(trim($header));
    if (in_array($headerLower, ['marca', 'brand', 'fabricante', 'manufacturer'])) {
        $marcaColumnIndex = $index;
        echo "✓ Columna de marca encontrada en índice [$index]: " . trim($header) . "\n";
        break;
    }
}

if ($marcaColumnIndex === null) {
    echo "⚠️  No se encontró columna específica de marca.\n";
    echo "💡 Opciones:\n";
    echo "   1. Usar el nombre del LABORATORIO como marca\n";
    echo "   2. Crear marcas manualmente después\n";
    echo "   3. Extraer marca del nombre del producto\n\n";
    
    echo "🔄 Usando LABORATORIO como marca...\n\n";
    
    // Usar laboratorio (columna 8)
    $marcaColumnIndex = 8;
}

// Extraer marcas únicas
$marcas = [];
$contador = 0;

while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
    if (empty($row[0]) || trim($row[0]) == '') {
        continue;
    }
    
    $marca = isset($row[$marcaColumnIndex]) ? trim($row[$marcaColumnIndex]) : '';
    
    if (!empty($marca) && $marca != ' ') {
        $marcas[$marca] = [
            'name' => $marca,
            'description' => 'Importada desde CSV',
            'status' => 'active'
        ];
    }
    
    $contador++;
}

fclose($file);

echo "✅ Procesados: $contador productos\n";
echo "✅ Marcas únicas encontradas: " . count($marcas) . "\n\n";

// Generar SQL
$sqlContent = "-- MARCAS ÚNICAS DEL CSV\n";
$sqlContent .= "-- Total: " . count($marcas) . "\n";
$sqlContent .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$marcaId = 1;
$marcaMap = [];

foreach ($marcas as $marcaName => $marcaData) {
    $nameSafe = addslashes($marcaName);
    $descSafe = addslashes($marcaData['description']);
    
    $sqlContent .= "INSERT INTO marcas (id, name, description, status, user_id, created_at, updated_at) VALUES\n";
    $sqlContent .= "({$marcaId}, '{$nameSafe}', '{$descSafe}', 'active', 1, NOW(), NOW());\n\n";
    
    $marcaMap[$marcaName] = $marcaId;
    $marcaId++;
}

// Guardar SQL
$outputFile = __DIR__ . '/../database/insert_marcas.sql';
file_put_contents($outputFile, $sqlContent);

echo "📝 Archivo generado: database/insert_marcas.sql\n\n";

// Mostrar primeras 20 marcas
echo "📋 Marcas encontradas (primeras 20):\n";
$marcasArray = array_keys($marcas);
sort($marcasArray);
foreach (array_slice($marcasArray, 0, 20) as $marca) {
    echo "  • {$marca}\n";
}
if (count($marcasArray) > 20) {
    echo "  ... y " . (count($marcasArray) - 20) . " más\n";
}

echo "\n✅ Listo para importar marcas\n";


