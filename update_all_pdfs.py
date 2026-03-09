#!/usr/bin/env python3
import os
import re
import glob

# Directorio de los PDFs
pdf_dir = "resources/views/laboratory/results"
pdf_files = glob.glob(os.path.join(pdf_dir, "*-pdf.blade.php"))

signature_css = """
        .signature-container {
            margin-top: 10px;
            text-align: center;
        }

        .signature-image {
            max-width: 150px;
            max-height: 60px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .signature-name {
            margin-top: 3px;
            font-weight: bold;
            font-size: 10px;
        }"""

signature_html = """
        @php
            // Usar siempre el médico que autorizó/ordenó el examen
            $authorizedDoctorName = $doctorName;
            $orderAuthorized = $order && $order->estado === 'completada';
            $doctorHasSignature = $doctor && $doctor->firma && \Storage::disk('public')->exists($doctor->firma);
        @endphp

        <div>Validado por: {{ $authorizedDoctorName }}</div>

        @if($orderAuthorized && $doctorHasSignature)
        <div class="signature-container">
            <img src="{{ storage_path('app/public/' . $doctor->firma) }}" alt="Firma del médico" class="signature-image">
            <div class="signature-name">{{ $authorizedDoctorName }}</div>
        </div>
        @endif"""

for pdf_file in pdf_files:
    with open(pdf_file, 'r', encoding='utf-8') as f:
        content = f.read()

    original_content = content
    modified = False

    # 1. Actualizar header
    if '- CLINICO -' in content and '&nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -' not in content:
        content = content.replace(
            '<div class="lab-name-medium">- CLINICO -</div>',
            '<div class="lab-name-medium">&nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>'
        )
        content = content.replace(
            '<div class="lab-name-large">PRO-MEDIC</div>',
            '<div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>'
        )
        modified = True

    # 2. Agregar CSS de firma si no existe
    if 'signature-container' not in content:
        # Buscar .validation-section y agregar CSS después
        pattern = r'(\.validation-section\s*\{[^}]*\})'
        match = re.search(pattern, content)
        if match:
            replacement = match.group(1) + signature_css
            content = content.replace(match.group(1), replacement, 1)
            modified = True

    # 3. Agregar lógica de firma en la sección de validación
    if 'signature-container' in content and '$orderAuthorized' not in content:
        # Buscar el patrón de validación simple y reemplazarlo
        old_pattern = '@php\n            // Usar siempre el médico que autorizó/ordenó el examen\n            $authorizedDoctorName = $doctorName;\n        @endphp\n        Validado por: {{ $authorizedDoctorName }}'
        if old_pattern in content:
            content = content.replace(
                old_pattern,
                signature_html.strip()
            )
            modified = True

    if modified and content != original_content:
        with open(pdf_file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Actualizado: {pdf_file}")
    else:
        print(f"Sin cambios: {pdf_file}")
