#!/usr/bin/env python3
import os
import re

# Directorio de los PDFs
pdf_dir = "resources/views/laboratory/results"

# Archivos ya actualizados
updated = [
    'generic-pdf.blade.php', 'testosterona-pdf.blade.php', 'cortisol-am-pdf.blade.php',
    'cortisol-pm-pdf.blade.php', 'prolactina-pdf.blade.php', 'hormona-luteinizante-pdf.blade.php',
    'hormona-folículo-estimulante-pdf.blade.php', 'hormona-crecimiento-pdf.blade.php',
    'insulina-pdf.blade.php', 'cea-pdf.blade.php', 'ca125-pdf.blade.php',
    'ca19-9-pdf.blade.php', 'ca15-3-pdf.blade.php'
]

# Obtener todos los archivos PDF
pdf_files = [f for f in os.listdir(pdf_dir) if f.endswith('-pdf.blade.php') and f not in updated]

css_addition = """        .observaciones-section {
            margin-top: 15px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }"""

observaciones_section = """    <!-- Observaciones -->
    @php
        $observaciones = '';
        if ($results->count() > 0) {
            $firstResult = $results->first();
            if ($firstResult && $firstResult->observaciones) {
                $observaciones = $firstResult->observaciones;
                $observaciones = str_replace('**', '', $observaciones);
            }
        }
        if (empty($observaciones)) {
            $observaciones = 'DATOS CONTROLADOS';
        }
    @endphp
    @if(!empty($observaciones))
    <div class="observaciones-section">
        {{ $observaciones }}
    </div>
    @endif

"""

updated_count = 0

for filename in pdf_files:
    filepath = os.path.join(pdf_dir, filename)

    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Verificar si ya tiene observaciones-section
        if 'observaciones-section' in content:
            continue

        # Agregar CSS si no existe
        if '.observaciones-section' not in content:
            # Buscar el patrón .footer-text seguido de } y </style>
            pattern = r'(\.footer-text\s*\{[^}]*\})\s*(\</style\>)'
            replacement = r'\1\n\n' + css_addition + r'\n    \2'
            content = re.sub(pattern, replacement, content, flags=re.DOTALL)

        # Agregar sección de observaciones antes de <!-- Validación -->
        if '<!-- Observaciones -->' not in content:
            # Buscar el patrón de spacers seguido de <!-- Validación -->
            pattern = r'(<div class="spacer"></div>\s*)+(\s*<!-- Validación -->)'
            replacement = r'\1' + observaciones_section + r'\2'
            content = re.sub(pattern, replacement, content, flags=re.DOTALL)

        # Escribir el archivo actualizado
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)

        updated_count += 1
        print(f"✓ Actualizado: {filename}")

    except Exception as e:
        print(f"✗ Error en {filename}: {e}")

print(f"\nTotal de archivos actualizados: {updated_count}")
