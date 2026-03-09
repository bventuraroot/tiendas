<?php
/**
 * Script completo de importación para farmacia
 * Importa laboratorios y productos desde el CSV
 */

require __DIR__ . '/../vendor/autoload.php';

$csvFile = __DIR__ . '/../Recursos /Productos/existencia medicamento.csv';
$delimiter = ';';

echo "=================================================\n";
echo "IMPORTACIÓN COMPLETA PARA FARMACIA\n";
echo "=================================================\n\n";

if (!file_exists($csvFile)) {
    die("❌ Error: No se encontró el archivo CSV\n");
}

// Abrir CSV
$file = fopen($csvFile, 'r');

// Saltar primeras 4 líneas
for ($i = 0; $i < 4; $i++) {
    fgets($file);
}

// Leer encabezados
$headers = fgetcsv($file, 0, $delimiter);

// Arrays para laboratorios y productos
$laboratorios = [];
$productos = [];
$codigosUsados = [];
$contador = 1;

// Mapeo de tipos de presentación
$presentationTypeMap = [
    'FRASCO' => 'frasco',
    'SOLUCION INYECTABLE' => 'ampolla',
    'TABLETA' => 'tableta',
    'JARABE' => 'jarabe',
    'AMPOLLAS' => 'ampolla',
    'CREMA' => 'crema',
    'GEL' => 'gel',
    'CAPSULA' => 'capsula',
    'SUSPENSION' => 'suspension',
    'GOTAS' => 'frasco',
    'SPRAY' => 'spray',
    'UNGUENTO' => 'unguento',
    'SOBRE' => 'sobre',
    'BLISTER' => 'blister',
    'CAJA' => 'caja',
    'TUBO' => 'tubo',
];

echo "📊 Procesando archivo CSV...\n\n";

$totalProductos = 0;

while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
    if (empty($row[0]) || trim($row[0]) == '') {
        continue;
    }
    
    $codigo = trim($row[0]);
    $descripcion = trim($row[1]);
    $precio = trim($row[3]);
    $presentacion = trim($row[6]); // Va a description
    $especialidad = trim($row[7]);
    $laboratorio = trim($row[8]);
    $ubicacion = trim($row[9]);
    
    if (empty($descripcion)) {
        continue;
    }
    
    // Preservar código de barras exacto del CSV
    // Convertir notación científica a número normal si existe
    if (preg_match('/^([0-9.]+)[Ee]\+([0-9]+)$/', $codigo, $matches)) {
        // Convertir notación científica (ej: 77513812E+11 -> 77513812000000000000)
        $base = floatval($matches[1]);
        $exponent = intval($matches[2]);
        $codigo = number_format($base * pow(10, $exponent), 0, '', '');
    } else {
        // Limpiar solo espacios y caracteres invisibles, pero preservar el código original
        $codigo = trim($codigo);
    }
    
    // Si el código está vacío, generar uno único
    if (empty($codigo)) {
        $codigo = 'MED' . str_pad($contador, 6, '0', STR_PAD_LEFT);
        $contador++;
    }
    
    // Manejar códigos duplicados: agregar sufijo único en lugar de reemplazar
    $codigoOriginal = $codigo;
    if (isset($codigosUsados[$codigo])) {
        $sufijo = 1;
        do {
            $codigo = $codigoOriginal . '_' . $sufijo;
            $sufijo++;
        } while (isset($codigosUsados[$codigo]));
    }
    $codigosUsados[$codigo] = true;
    
    // Limpiar precio
    $precio = preg_replace('/[^0-9.]/', '', $precio);
    if (empty($precio) || !is_numeric($precio)) {
        $precio = '0.00';
    }
    
    // Tipo de presentación
    $tipoPresentacion = 'otro';
    foreach ($presentationTypeMap as $keyword => $type) {
        if (stripos($presentacion, $keyword) !== false) {
            $tipoPresentacion = $type;
            break;
        }
    }
    
    // Especialidad
    $especialidad = trim($especialidad);
    if (empty($especialidad)) {
        $especialidad = 'General';
    }
    
    // Laboratorio (extraer únicos)
    $laboratorio = trim($laboratorio);
    if (!empty($laboratorio) && $laboratorio != ' ') {
        $laboratorios[$laboratorio] = [
            'name' => $laboratorio,
            'active' => true
        ];
    }
    
    // Ubicación
    $ubicacion = trim($ubicacion);
    
    // Guardar producto
    $productos[] = [
        'code' => $codigo,
        'name' => $descripcion,
        'price' => $precio,
        'description' => $presentacion, // Presentación va a description
        'presentation_type' => $tipoPresentacion,
        'specialty' => $especialidad,
        'laboratory_name' => $laboratorio,
        'location' => $ubicacion
    ];
    
    $totalProductos++;
    
    if ($totalProductos % 50 == 0) {
        echo "  Procesados: $totalProductos productos...\n";
    }
}

fclose($file);

echo "\n=================================================\n";
echo "✅ PROCESAMIENTO COMPLETADO\n";
echo "=================================================\n";
echo "Total productos: $totalProductos\n";
echo "Total laboratorios únicos: " . count($laboratorios) . "\n\n";

// Generar SQL de laboratorios
echo "📝 Generando SQL de laboratorios...\n";

$sqlLabs = "-- LABORATORIOS ÚNICOS DEL CSV\n";
$sqlLabs .= "-- Total: " . count($laboratorios) . "\n\n";

$labId = 1;
$labMap = [];

foreach ($laboratorios as $labName => $labData) {
    $nameSafe = addslashes($labName);
    $codeSafe = addslashes(substr($labName, 0, 50)); // Usar nombre como código
    $sqlLabs .= "INSERT INTO pharmaceutical_laboratories (id, name, code, active, created_at, updated_at) VALUES\n";
    $sqlLabs .= "({$labId}, '{$nameSafe}', '{$codeSafe}', 1, NOW(), NOW());\n\n";
    
    $labMap[$labName] = $labId;
    $labId++;
}

file_put_contents(__DIR__ . '/../database/insert_laboratories.sql', $sqlLabs);
echo "✓ Archivo generado: database/insert_laboratories.sql\n\n";

// Generar SQL de productos
echo "📝 Generando SQL de productos...\n";

$sqlProds = "-- PRODUCTOS FARMACÉUTICOS\n";
$sqlProds .= "-- Total: $totalProductos\n\n";
$sqlProds .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($productos as $prod) {
    $code = addslashes($prod['code']);
    $name = addslashes($prod['name']);
    $price = $prod['price'];
    $description = addslashes($prod['description']);
    $presentationType = $prod['presentation_type'];
    $specialty = addslashes($prod['specialty']);
    $labName = $prod['laboratory_name'];
    $location = addslashes($prod['location']);
    
    // Obtener ID del laboratorio
    $labId = isset($labMap[$labName]) && !empty($labName) ? $labMap[$labName] : 'NULL';
    
    // Valores por defecto para campos requeridos
    $providerId = 1; // Proveedor General Farmacia
    $marcaId = 99; // Genérico
    
    $sqlProds .= "INSERT INTO products (code, name, state, cfiscal, type, price, description, presentation_type, specialty, pharmaceutical_laboratory_id, category, provider_id, marca_id, registration_number, formula, unit_measure, sale_form, product_type, pastillas_per_blister, blisters_per_caja, created_at, updated_at) VALUES\n";
    $sqlProds .= "('{$code}', '{$name}', 1, 'gravado', 'directo', '{$price}', '{$description}', '{$presentationType}', '{$specialty}', {$labId}, '{$specialty}', {$providerId}, {$marcaId}, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW());\n\n";
}

$sqlProds .= "SET FOREIGN_KEY_CHECKS=1;\n\n";
$sqlProds .= "SELECT COUNT(*) as 'Total Productos' FROM products;\n";
$sqlProds .= "SELECT COUNT(*) as 'Total Laboratorios' FROM pharmaceutical_laboratories;\n";

file_put_contents(__DIR__ . '/../database/insert_products_complete.sql', $sqlProds);
echo "✓ Archivo generado: database/insert_products_complete.sql\n\n";

// Estadísticas
echo "=================================================\n";
echo "📊 ESTADÍSTICAS\n";
echo "=================================================\n\n";

echo "Laboratorios únicos encontrados:\n";
$labsArray = array_keys($laboratorios);
sort($labsArray);
foreach (array_slice($labsArray, 0, 20) as $lab) {
    if (!empty($lab) && $lab != ' ') {
        echo "  • {$lab}\n";
    }
}
if (count($labsArray) > 20) {
    echo "  ... y " . (count($labsArray) - 20) . " más\n";
}

echo "\n=================================================\n";
echo "✅ ARCHIVOS LISTOS PARA IMPORTAR\n";
echo "=================================================\n";
echo "1. database/insert_laboratories.sql\n";
echo "2. database/insert_products_complete.sql\n\n";

echo "📝 PRÓXIMOS PASOS:\n";
echo "1. Ejecutar migración de laboratorios\n";
echo "2. Importar laboratorios (insert_laboratories.sql)\n";
echo "3. Importar productos (insert_products_complete.sql)\n\n";


