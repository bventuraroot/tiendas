<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen - {{ $exam->nombre ?? 'Prueba de Azul de Metileno (P.A.M.)' }}</title>
    <style>
        @page {
            margin: 1.5cm;
            size: Letter;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 15px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .separator-line {
            text-align: center;
            margin: 10px 0;
            font-size: 15px;
            letter-spacing: 0.5px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .header-container {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: left;
        }

        .header-center {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            text-align: center;
            padding: 0 10px;
        }

        .header-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: right;
        }

        .lab-name-large {
            font-size: 20px;
            font-weight: bold;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .lab-name-medium {
            font-size: 18px;
            font-weight: normal;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .lab-slogan {
            font-size: 13px;
            margin: 8px 0 5px 0;
            font-weight: normal;
            font-style: italic;
            font-family: Arial, sans-serif;
        }

        .lab-address {
            font-size: 12px;
            font-weight: bold;
            margin-top: 8%;
            margin-bottom: 2%;
            text-align: center;
            padding-left: 0;
            padding-right: 0;
            font-family: courier new;
            font-style: normal;
        }

        .horario-label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .horario-text {
            font-size: 16px;
            font-weight: normal;
            margin-top: 2px;
        }

        .patient-box {
            border: 1px solid #000;
            padding: 0;
            margin: 15px 0;
            font-size: 15px;
        }

        .patient-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .patient-row:first-child {
            border-bottom: 1px solid #000;
        }

        .patient-cell {
            display: table-cell;
            width: 50%;
            padding: 8px 10px;
            border-right: 1px solid #000;
            vertical-align: middle;
        }

        .patient-cell:last-child {
            border-right: none;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1px 0;
            font-size: 15px;
            border: 0px solid #000;
        }

        .results-table thead {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .results-table th {
            border: 1px solid #000;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 15px;
            background-color: transparent;
        }

        .results-table th:first-child {
            text-align: left;
            border-right: 1px solid #000;
            border-left: 1px solid #000;
        }

        .results-table th:nth-child(2) {
            text-align: center;
            border-right: 1px solid #000;
            border-left: 1px solid #000;
        }

        .results-table th:nth-child(3) {
            text-align: center;
            border-right: 1px solid #000;
        }

        .results-table tbody tr {
            border: 0px solid #000;
        }

        .results-table td {
            border: 0px solid #000;
            padding: 10px;
            vertical-align: top;
            font-size: 14px;
        }

        .results-table td:first-child {
            text-align: left;
        }

        .results-table td:nth-child(2) {
            text-align: left;
            white-space: pre-line;
        }

        .results-table td:nth-child(3) {
            text-align: left;
        }

        .exam-category {
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
        }

        .exam-name {
            font-weight: normal;
            font-size: 15px;
            padding-left: 0;
        }

        .reference-range {
            line-height: 1.2;
            font-size: 12px;
            white-space: pre-line;
            text-align: left;
        }

        .validation-section {
            margin-top: 30px;
            text-align: center;
            font-size: 15px;
        }

        .signature-container {
            margin-top: 2px;
            text-align: center;
        }

        .signature-image {
            max-width: 100px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-bottom: 1px solid #000;
            padding-bottom: 0px;
            margin: 0 auto;
        }

        .signature-name {
            margin-top: 0px;
            font-weight: bold;
            font-size: 13px;
        }

        .footer-line {
            text-align: center;
            margin: 10px 0;
            font-size: 15px;
            letter-spacing: 0.5px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .footer-text {
            display: table;
            width: 100%;
            font-size: 15px;
            margin-top: 5px;
        }

        .footer-left {
            display: table-cell;
            text-align: left;
            font-weight: bold;
            padding-right: 10px;
        }

        .footer-right {
            display: table-cell;
            text-align: right;
            font-weight: normal;
            padding-left: 10px;
        }
    </style>
</head>
<body>
    <div class="separator-line"></div>

    <div class="header-container">
        <div class="header-left">
            <div class="lab-name-large">LABORATORIO</div>
            <div class="lab-name-medium"> &nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>
            <div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>
        </div>
        <div class="header-center">
            <div class="lab-slogan">Calidad, Rapidez y Veracidad en sus resultados es lo que nos caracteriza.</div>
            <div class="lab-address">Dirección: {{ $labInfo['direccion'] ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)' }}</div>
        </div>
        <div class="header-right">
            <div class="horario-label">HORARIO</div>
            <div class="horario-text">Lunes a Sábado de:</div>
            <div class="horario-text" style="font-weight: bold;">7:00 a.m. - 3:00 p.m.</div>
        </div>
    </div>

    <div class="separator-line"></div>

    <div class="patient-box">
        @php
            $patientName = $patient ? ($patient->primer_nombre . ' ' . ($patient->segundo_nombre ? $patient->segundo_nombre . ' ' : '') . $patient->primer_apellido . ($patient->segundo_apellido ? ' ' . $patient->segundo_apellido : '')) : '';
            $patientAge = $patient && $patient->fecha_nacimiento
                ? \Carbon\Carbon::parse($patient->fecha_nacimiento)->age
                : '';
            $doctorName = $doctor
                ? ($doctor->nombres . ' ' . $doctor->apellidos)
                : '';
            $orderDate = $order->fecha_orden
                ? \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y')
                : date('d/m/Y');
        @endphp

        <div class="patient-row">
            <div class="patient-cell">Paciente: <strong>{{ $patientName }}</strong></div>
            <div class="patient-cell">Edad: <strong>{{ $patientAge }}</strong></div>
        </div>
        <div class="patient-row">
            <div class="patient-cell">Medico: <strong>{{ $doctorName }}</strong></div>
            <div class="patient-cell">Fecha: <strong>{{ $orderDate }}</strong></div>
        </div>
    </div>

    @php
        $valRefConfig = config('lab_exam_templates.pam_azul_metileno.valores_referencia', []);
        $resultsByParam = $results->keyBy('parametro');

        $params = [
            ['key' => 'leucocitos_polimorfonucleares', 'label' => 'Leucocitos Polimorfonucleares', 'unidad' => '%'],
            ['key' => 'leucocitos_mononucleares', 'label' => 'Leucocitos Mononucleares', 'unidad' => '%'],
        ];
    @endphp

    <table class="results-table">
        <thead>
            <tr>
                <th>EXAMEN</th>
                <th>RESULTADO</th>
                <th>RANGO DE REFERENCIA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="exam-category" colspan="3" style="padding-top: 1%; padding-bottom: 1%;">COPROLOGÍA</td>
            </tr>
            <tr>
                <td class="exam-name" colspan="3" style="padding-top: 1%; padding-bottom: 1%;">Prueba de Azul de Metileno (P.A.M.)</td>
            </tr>
            @foreach($params as $p)
                @php
                    $refCfg = $valRefConfig[$p['key']] ?? null;
                    $label = $refCfg['label'] ?? $p['label'];
                    $unidadCfg = $refCfg['unidad'] ?? $p['unidad'];
                    $r = $resultsByParam->get($label);
                    // Si no se encuentra por label exacto, buscar por coincidencia parcial
                    if (!$r && $resultsByParam->isNotEmpty()) {
                        foreach ($resultsByParam as $key => $result) {
                            $keyLower = strtolower($key);
                            $labelLower = strtolower($label);
                            if (stripos($keyLower, 'polimorfonucleares') !== false || stripos($keyLower, 'mononucleares') !== false) {
                                if (stripos($keyLower, 'polimorfonucleares') !== false && stripos($labelLower, 'polimorfonucleares') !== false) {
                                    $r = $result;
                                    break;
                                }
                                if (stripos($keyLower, 'mononucleares') !== false && stripos($labelLower, 'mononucleares') !== false) {
                                    $r = $result;
                                    break;
                                }
                            }
                        }
                        // Si aún no se encuentra, buscar por cualquier coincidencia parcial
                        if (!$r && $resultsByParam->isNotEmpty()) {
                            foreach ($resultsByParam as $key => $result) {
                                $keyLower = strtolower($key);
                                $labelLower = strtolower($label);
                                if (stripos($keyLower, substr($labelLower, 0, 10)) !== false) {
                                    $r = $result;
                                    break;
                                }
                            }
                        }
                    }
                    $resVal = $r ? ($r->resultado ?? '') : '';
                    $u = $r ? ($r->unidad_medida ?? $unidadCfg) : $unidadCfg;
                    $vr = $refCfg['rango'] ?? '';
                @endphp
                <tr>
                    <td class="exam-name">{{ $label }}:</td>
                    <td style="text-align: left;">
                        @if($resVal !== '')
                            {{ $resVal }} @if($u) {{ $u }} @endif
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td class="reference-range">
                        @if($vr !== '')
                            {!! nl2br(e($vr)) !!}
                        @else
                            &nbsp;
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="validation-section">
        <div>Validado por:</div>
        <div class="signature-container">
            @if($order->estado === 'completado' && isset($doctor) && $doctor->firma_digital)
                <img src="{{ $doctor->firma_digital }}" alt="Firma" class="signature-image">
                <div class="signature-name">{{ $doctor->nombres }} {{ $doctor->apellidos }}</div>
            @else
                <div style="border-bottom: 1px solid #000; width: 150px; margin: 0 auto; height: 50px;"></div>
            @endif
        </div>
    </div>

    <div class="footer-line"></div>

    <div class="footer-text">
        <div class="footer-left">LABORATORIO CLINICO PRO-MEDIC</div>
        <div class="footer-right">Teléfonos: 2420-4997 y 6303-3392</div>
    </div>
</body>
</html>
