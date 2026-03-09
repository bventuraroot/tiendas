<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen - {{ $exam->nombre ?? 'Perfil Químico' }}</title>
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
        }

        .lab-address {
            font-size: 12px;
            font-weight: bold;
            margin-top: 8%;
            margin-bottom: 2%;
            text-align: center;
            font-family: "Courier New", monospace;
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
            border: none;
        }

        .results-table thead {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .results-table th {
            border: none;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 15px;
            background-color: transparent;
        }

        .results-table th:first-child {
            text-align: left;
            border-right: none;
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
            border: none;
        }

        .results-table td {
            border: 0;
            padding: 10px;
            vertical-align: top;
            font-size: 12px;
        }

        .results-table td:first-child {
            text-align: left;
        }

        .results-table td:nth-child(2) {
            text-align: center;
        }

        .results-table td:nth-child(3) {
            text-align: center;
        }

        .exam-category {
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
        }

        .exam-name {
            padding-left: 0;
            font-weight: normal;
        }

        .reference-range {
            line-height: 1;
            font-size: 11px;
            white-space: pre-line;
        }

        .estado-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 6px;
        }
        .estado-badge-normal { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .estado-badge-alto { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .estado-badge-bajo { color: #004085; background-color: #cce5ff; border: 1px solid #b8daff; }
        .estado-badge-critico { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

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
            padding-bottom: 0;
            margin: 0 auto;
        }

        .signature-name {
            margin-top: 0;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
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

        $valRefConfig = config('lab_exam_templates.perfil_bioquimica_clinica.valores_referencia', []);
        $resultsByParam = $results->keyBy('parametro');

        $paramsPage1 = [
            ['key' => 'glucosa_ayunas'],
            ['key' => 'colesterol'],
            ['key' => 'trigliceridos'],
            ['key' => 'acido_urico'],
            ['key' => 'creatinina'],
        ];

        $paramsPage2 = [
            ['key' => 'urea'],
            ['key' => 'nitrogeno_ureico'],
        ];
    @endphp

    {{-- PRIMERA HOJA --}}
    <div class="separator-line"></div>

    <div class="header-container">
        <div class="header-left">
            <div class="lab-name-large">LABORATORIO</div>
            <div class="lab-name-medium"> &nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>
            <div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>
        </div>
        <div class="header-center">
            <div class="lab-slogan">Calidad, Rapidez y Veracidad en sus resultados es lo que nos caracteriza.</div>
            <div class="lab-address">
                Dirección: {{ $labInfo['direccion'] ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)' }}
            </div>
        </div>
        <div class="header-right">
            <div class="horario-label">HORARIO</div>
            <div class="horario-text">Lunes a Sábado de:</div>
            <div class="horario-text" style="font-weight: bold;">7:00 a.m. - 3:00 p.m.</div>
        </div>
    </div>

    <div class="separator-line"></div>

    <div class="patient-box">
        <div class="patient-row">
            <div class="patient-cell">Paciente: <strong>{{ $patientName }}</strong></div>
            <div class="patient-cell">Edad: <strong>{{ $patientAge }}</strong></div>
        </div>
        <div class="patient-row">
            <div class="patient-cell">Medico: <strong>{{ $doctorName }}</strong></div>
            <div class="patient-cell">Fecha: <strong>{{ $orderDate }}</strong></div>
        </div>
    </div>

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
                <td class="exam-category" colspan="3" style="padding-top: 1%; padding-bottom: 1%;">BIOQUIMICA CLINICA</td>
            </tr>
            @foreach($paramsPage1 as $p)
                @php
                    $refCfg = $valRefConfig[$p['key']] ?? null;
                    $label = $refCfg['label'] ?? ucfirst(str_replace('_', ' ', $p['key']));
                    $unidadCfg = $refCfg['unidad'] ?? ($exam->unidad_medida ?? '');
                    $r = $resultsByParam->get($label);
                    $resVal = $r ? $r->resultado : '';
                    $u = $r && $r->unidad_medida
                        ? str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $r->unidad_medida)
                        : str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $unidadCfg);
                    $vr = $r && $r->valor_referencia
                        ? $r->valor_referencia
                        : ($refCfg['rango'] ?? '');
                    $vr = str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $vr);
                    $estado = $r ? ($r->estado_resultado ?? 'normal') : 'normal';
                    if (!in_array($estado, ['normal', 'alto', 'bajo', 'critico'])) {
                        $estado = 'normal';
                    }
                    $estadoLabel = $estado === 'critico' ? 'Crítico' : ucfirst($estado);
                    $claseBadge = 'estado-badge estado-badge-' . $estado;
                @endphp
                <tr>
                    <td class="exam-name">{{ $label }}:</td>
                    <td>
                        @if($resVal !== '')
                            {{ $resVal }} @if($u) {{ $u }} @endif
                            <span class="{{ $claseBadge }}">{{ $estadoLabel }}</span>
                        @endif
                    </td>
                    <td class="reference-range">{!! nl2br(e($vr)) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $authorizedDoctorName = $doctorName ?? '';
        $orderAuthorized = $order && $order->estado === 'completada';
        $doctorHasSignature = $doctor && $doctor->firma && \Storage::disk('public')->exists($doctor->firma);
    @endphp

    <div class="validation-section">
        <div style="margin-bottom: 0; margin-top: 0;">Validado por:</div>

        @if($orderAuthorized && $doctorHasSignature)
            <div class="signature-container">
                <img src="{{ storage_path('app/public/' . $doctor->firma) }}" alt="Firma del médico" class="signature-image">
                <div class="signature-name">{{ $authorizedDoctorName }}</div>
            </div>
        @else
            <div style="border-bottom: 1px solid #000; width: 300px; margin: 0 auto; margin-top: 5px; padding-bottom: 0;"></div>
        @endif
    </div>

    <div class="footer-line"></div>

    <div class="footer-text">
        <div class="footer-left"><strong>LABORATORIO CLINICO PRO-MEDIC</strong></div>
        <div class="footer-right">Teléfonos: {{ $labInfo['telefonos'] ?? '2420-4997 y 6303-3392' }}</div>
    </div>

    <div class="page-break"></div>

    {{-- SEGUNDA HOJA --}}
    <div class="separator-line"></div>

    <div class="header-container">
        <div class="header-left">
            <div class="lab-name-large">LABORATORIO</div>
            <div class="lab-name-medium"> &nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>
            <div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>
        </div>
        <div class="header-center">
            <div class="lab-slogan">Calidad, Rapidez y Veracidad en sus resultados es lo que nos caracteriza.</div>
            <div class="lab-address">
                Dirección: {{ $labInfo['direccion'] ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)' }}
            </div>
        </div>
        <div class="header-right">
            <div class="horario-label">HORARIO</div>
            <div class="horario-text">Lunes a Sábado de:</div>
            <div class="horario-text" style="font-weight: bold;">7:00 a.m. - 3:00 p.m.</div>
        </div>
    </div>

    <div class="separator-line"></div>

    <div class="patient-box">
        <div class="patient-row">
            <div class="patient-cell">Paciente: <strong>{{ $patientName }}</strong></div>
            <div class="patient-cell">Edad: <strong>{{ $patientAge }}</strong></div>
        </div>
        <div class="patient-row">
            <div class="patient-cell">Medico: <strong>{{ $doctorName }}</strong></div>
            <div class="patient-cell">Fecha: <strong>{{ $orderDate }}</strong></div>
        </div>
    </div>

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
                <td class="exam-category" colspan="3" style="padding-top: 1%; padding-bottom: 1%;">BIOQUIMICA CLINICA</td>
            </tr>
            @foreach($paramsPage2 as $p)
                @php
                    $refCfg = $valRefConfig[$p['key']] ?? null;
                    $label = $refCfg['label'] ?? ucfirst(str_replace('_', ' ', $p['key']));
                    $unidadCfg = $refCfg['unidad'] ?? ($exam->unidad_medida ?? '');
                    $r = $resultsByParam->get($label);
                    $resVal = $r ? $r->resultado : '';
                    $u = $r && $r->unidad_medida
                        ? str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $r->unidad_medida)
                        : str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $unidadCfg);
                    $vr = $r && $r->valor_referencia
                        ? $r->valor_referencia
                        : ($refCfg['rango'] ?? '');
                    $vr = str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $vr);
                    $estado = $r ? ($r->estado_resultado ?? 'normal') : 'normal';
                    if (!in_array($estado, ['normal', 'alto', 'bajo', 'critico'])) {
                        $estado = 'normal';
                    }
                    $estadoLabel = $estado === 'critico' ? 'Crítico' : ucfirst($estado);
                    $claseBadge = 'estado-badge estado-badge-' . $estado;
                @endphp
                <tr>
                    <td class="exam-name">{{ $label }}:</td>
                    <td>
                        @if($resVal !== '')
                            {{ $resVal }} @if($u) {{ $u }} @endif
                            <span class="{{ $claseBadge }}">{{ $estadoLabel }}</span>
                        @endif
                    </td>
                    <td class="reference-range">{!! nl2br(e($vr)) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="validation-section">
        <div style="margin-bottom: 0; margin-top: 0;">Validado por:</div>

        @if($orderAuthorized && $doctorHasSignature)
            <div class="signature-container">
                <img src="{{ storage_path('app/public/' . $doctor->firma) }}" alt="Firma del médico" class="signature-image">
                <div class="signature-name">{{ $authorizedDoctorName }}</div>
            </div>
        @else
            <div style="border-bottom: 1px solid #000; width: 300px; margin: 0 auto; margin-top: 5px; padding-bottom: 0;"></div>
        @endif
    </div>

    <div class="footer-line"></div>

    <div class="footer-text">
        <div class="footer-left"><strong>LABORATORIO CLINICO PRO-MEDIC</strong></div>
        <div class="footer-right">Teléfonos: {{ $labInfo['telefonos'] ?? '2420-4997 y 6303-3392' }}</div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen - {{ $exam->nombre ?? 'Perfil Bioquímica Clínica' }}</title>
    <style>
        @page {
            margin: 1.5cm;
            size: Letter;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .separator-line {
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
            letter-spacing: 0.5px;
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
            width: 40%;
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
            font-size: 16px;
            font-weight: bold;
            line-height: 1.2;
        }

        .lab-name-medium {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }

        .lab-slogan {
            font-size: 10px;
            margin: 5px 0;
        }

        .lab-address {
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
        }

        .horario-label {
            font-size: 12px;
            font-weight: bold;
        }

        .horario-text {
            font-size: 10px;
        }

        .patient-box {
            border: 1px solid #000;
            padding: 8px;
            margin: 15px 0;
            font-size: 11px;
        }

        .patient-row {
            display: table;
            width: 100%;
        }

        .patient-cell {
            display: table-cell;
            width: 50%;
            padding: 3px 5px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }

        .results-table th {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }

        .results-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }

        .results-table td:first-child {
            text-align: left;
        }

        .results-table td:nth-child(2) {
            text-align: center;
        }

        .results-table td:last-child {
            text-align: center;
        }

        .exam-category {
            font-weight: bold;
        }

        .exam-name {
            padding-left: 10px;
        }

        .reference-range {
            line-height: 1.5;
            font-size: 10px;
            text-align: center;
        }

        .spacer {
            height: 15px;
        }

        .validation-section {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
        }
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
        }

        .footer-line {
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .footer-text {
            text-align: center;
            font-size: 10px;
            margin-top: 5px;
        }

        .controlled-data {
            font-weight: bold;
            font-style: italic;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Línea superior -->
    <div class="separator-line">_______________________________________________________________________________</div>

    <!-- Encabezado con tres columnas -->
    <div class="header-container">
        <div class="header-left">
            <div class="lab-name-large">LABORATORIO</div>
            <div class="lab-name-medium">&nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>
            <div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>
        </div>
        <div class="header-center">
            <div class="lab-slogan">Calidad, Rapidez y Veracidad en sus resultados</div>
            <div class="lab-slogan">es lo que nos caracteriza.</div>
            <div class="lab-address">Dirección: Final Avenida El Calvario, Calle principal</div>
            <div class="lab-address">Nahuizalco (Frente a parada de buses del chorro Público)</div>
        </div>
        <div class="header-right">
            <div class="horario-label">HORARIO</div>
            <div class="horario-text">Lunes a Sábado de:</div>
            <div class="horario-text" style="font-weight: bold;">7:00 a.m. - 3:00 p.m.</div>
        </div>
    </div>

    <!-- Línea separadora -->
    <div class="separator-line">_______________________________________________________________________________</div>
    <div class="spacer"></div>

    <!-- Cuadro de información del paciente -->
    <div class="patient-box">
        @php
            $patientName = $patient ? ($patient->primer_nombre . ' ' . $patient->primer_apellido) : '';
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
            <div class="patient-cell">
                <strong>Paciente:</strong> {{ $patientName }}
            </div>
            <div class="patient-cell">
                <strong>Edad:</strong> {{ $patientAge }}
            </div>
        </div>
        <div class="patient-row">
            <div class="patient-cell">
                <strong>Medico:</strong> {{ $doctorName }}
            </div>
            <div class="patient-cell">
                <strong>Fecha:</strong> {{ $orderDate }}
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
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
                <td class="exam-category">{{ $valoresReferencia['categoria'] ?? 'BIOQUIMICA CLINICA' }}</td>
                <td></td>
                <td></td>
            </tr>
            @php
                $resultadoGlucosa = $results->firstWhere('parametro', 'Glucosa en Ayunas');
                $resultadoColesterol = $results->firstWhere('parametro', 'Colesterol');
                $resultadoTrigliceridos = $results->firstWhere('parametro', 'Triglicéridos');
                $resultadoAcidoUrico = $results->firstWhere('parametro', 'Acido Úrico');
                $resultadoCreatinina = $results->firstWhere('parametro', 'Creatinina');
            @endphp

            <tr>
                <td class="exam-name">Glucosa en Ayunas:</td>
                <td style="text-align: center;">
                    @if($resultadoGlucosa)
                        {{ $resultadoGlucosa->resultado }} {{ $resultadoGlucosa->unidad_medida ?? 'mg/dL' }}
                    @endif
                </td>
                <td class="reference-range">{{ $resultadoGlucosa->valor_referencia ?? '60 a 110 mg/dL' }}</td>
            </tr>
            <tr>
                <td class="exam-name">Colesterol:</td>
                <td style="text-align: center;">
                    @if($resultadoColesterol)
                        {{ $resultadoColesterol->resultado }} {{ $resultadoColesterol->unidad_medida ?? 'mg/dL' }}
                    @endif
                </td>
                <td class="reference-range">{{ $resultadoColesterol->valor_referencia ?? 'Hasta 200 mg/dL' }}</td>
            </tr>
            <tr>
                <td class="exam-name">Triglicéridos:</td>
                <td style="text-align: center;">
                    @if($resultadoTrigliceridos)
                        {{ $resultadoTrigliceridos->resultado }} {{ $resultadoTrigliceridos->unidad_medida ?? 'mg/dL' }}
                    @endif
                </td>
                <td class="reference-range">{{ $resultadoTrigliceridos->valor_referencia ?? 'Hasta 150 mg/dL' }}</td>
            </tr>
            <tr>
                <td class="exam-name">Acido Úrico:</td>
                <td style="text-align: center;">
                    @if($resultadoAcidoUrico)
                        {{ $resultadoAcidoUrico->resultado }} {{ $resultadoAcidoUrico->unidad_medida ?? 'mg/dL' }}
                    @endif
                </td>
                <td class="reference-range" style="white-space: pre-line;">{{ $resultadoAcidoUrico->valor_referencia ?? "Hombre de 3.0 a 7.0 mg/dL\nMujeres de 2.0 a 5.7 mg/dL" }}</td>
            </tr>
            <tr>
                <td class="exam-name">Creatinina:</td>
                <td style="text-align: center;">
                    @if($resultadoCreatinina)
                        {{ $resultadoCreatinina->resultado }} {{ $resultadoCreatinina->unidad_medida ?? 'mg/dL' }}
                    @endif
                </td>
                <td class="reference-range" style="white-space: pre-line;">{{ $resultadoCreatinina->valor_referencia ?? "Hombres: 0.7 a 1.4 mg/dL\nMujeres: 0.5 a 1.1 mg/dL" }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Espacios en blanco -->
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>
    <div class="spacer"></div>

    <!-- Nota de dato controlado -->
    <div class="controlled-data">*DATO CONTROLADO*</div>

    <!-- Validación -->
    <div class="validation-section">
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
        @endif
    </div>

    <!-- Línea separadora final -->
    <div class="footer-line">________________________________________________________________________________________</div>

    <!-- Pie de página -->
    <div class="footer-text">
        <strong>LABORATORIO CLINICO PRO-MEDIC</strong>      -      Teléfonos: {{ $labInfo['telefonos'] ?? '2420-4997 y 6303-3392' }}
    </div>
</body>
</html>

