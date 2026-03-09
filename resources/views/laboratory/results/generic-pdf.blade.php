<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen - {{ $exam->nombre ?? 'Examen' }}</title>
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
            margin: 15px 0;
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
            border: none;
            padding: 6px;
            vertical-align: top;
            font-size: 16px;
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
            line-height: 1.4;
            font-size: 16px;
            white-space: pre-line;
        }

        /* Indicador de estado al lado del resultado */
        .estado-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
        }
        .estado-badge-normal { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .estado-badge-alto { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .estado-badge-bajo { color: #004085; background-color: #cce5ff; border: 1px solid #b8daff; }
        .estado-badge-critico { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

        .spacer {
            height: 15px;
        }

        .validation-section {
            margin-top: 30px;
            text-align: center;
            font-size: 15px;
        }

        .signature-container {
            margin-top: 10px;
            text-align: center;
        }

        .signature-image {
            max-width: 200px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin: 0 auto;
        }

        .signature-name {
            margin-top: 3px;
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

        .observaciones-section {
            margin-top: 15px;
            text-align: center;
            font-size: 15px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <!-- Línea superior -->
    <div class="separator-line"></div>

    <!-- Encabezado con tres columnas -->
    <div class="header-container">
        <div class="header-left">
            <div class="lab-name-large">LABORATORIO</div>
            <div class="lab-name-medium"> &nbsp;&nbsp;&nbsp;&nbsp;- CLINICO -</div>
            <div class="lab-name-large">&nbsp;&nbsp;PRO-MEDIC</div>
        </div>
        <div class="header-center">
            <div class="lab-slogan">Calidad, Rapidez y Veracidad en sus resultados es lo que nos caracteriza.</div>
            <div class="lab-address">Dirección: {{ $labInfo['direccion'] }}</div>
        </div>
        <div class="header-right">
            <div class="horario-label">HORARIO</div>
            <div class="horario-text">Lunes a Sábado de:</div>
            <div class="horario-text" style="font-weight: bold;">7:00 a.m. - 3:00 p.m.</div>
        </div>
    </div>

    <!-- Línea separadora -->
    <div class="separator-line"></div>

    <!-- Información del Paciente -->
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
            @if($results->count() > 0)
                @php
                    // Extraer campos adicionales del primer resultado si existen
                    $camposAdicionales = [];
                    $firstResult = $results->first();
                    if ($firstResult && $firstResult->observaciones && str_contains($firstResult->observaciones, '__CAMPOS_ADICIONALES__')) {
                        $parts = explode('__CAMPOS_ADICIONALES__', $firstResult->observaciones);
                        if (count($parts) > 1) {
                            $jsonData = json_decode($parts[1], true);
                            if ($jsonData) {
                                $camposAdicionales = $jsonData;
                            }
                        }
                    }

                    // Obtener categoría del examen si existe
                    $categoria = null;

                    // Primero intentar desde valores_referencia_especificos
                    if ($valoresReferencia && isset($valoresReferencia['categoria'])) {
                        $categoria = $valoresReferencia['categoria'];
                    } elseif ($exam->valores_referencia_especificos && isset($exam->valores_referencia_especificos['categoria'])) {
                        $categoria = $exam->valores_referencia_especificos['categoria'];
                    }

                    // Si no está en valores_referencia_especificos, obtener de la relación category
                    if (!$categoria && $exam->category) {
                        $categoria = $exam->category->nombre;
                    }
                @endphp

                @if($categoria)
                <tr>
                    <td class="exam-category" colspan="3" style="border: none; padding-top: 3%; padding-bottom: 2%;">{{ strtoupper($categoria) }}</td>
                </tr>
                @endif

                @foreach($results as $result)
                    @php
                        $estado = $result->estado_resultado ?? 'normal';
                        if (!in_array($estado, ['normal', 'alto', 'bajo', 'critico'])) {
                            $estado = 'normal';
                        }
                        $estadoLabel = ucfirst($estado);
                        if ($estado === 'critico') {
                            $estadoLabel = 'Crítico';
                        }
                        $claseBadge = 'estado-badge estado-badge-' . $estado;
                    @endphp
                    <tr>
                        <td class="exam-name">{{ $result->parametro }}:</td>
                        <td>
                            @if($result->resultado)
                                @php
                                    $u = $result->unidad_medida ?? '';
                                    $u = str_replace(['μg', 'μmol', 'μL'], ['ug', 'umol', 'uL'], $u);
                                @endphp
                                {{ $result->resultado }} {{ $u }}
                                <span class="{{ $claseBadge }}">{{ $estadoLabel }}</span>
                            @endif
                        </td>
                        <td class="reference-range">{{ $result->valor_referencia ?? 'N/A' }}</td>
                    </tr>
                @endforeach

                @if(!empty($camposAdicionales))
                    @foreach($camposAdicionales as $campo => $valor)
                        @if(!empty($valor))
                            <tr>
                                <td style="font-style: italic;">{{ ucwords(str_replace('_', ' ', $campo)) }}:</td>
                                <td colspan="2">{{ $valor }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            @else
                <tr>
                    <td colspan="3" style="text-align: center;">No hay resultados disponibles</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Espacios en blanco -->
    <div class="spacer"></div>
    <div class="spacer"></div>

    <!-- Observaciones -->
    @php
        $observaciones = '';
        if ($results->count() > 0) {
            $firstResult = $results->first();
            if ($firstResult && $firstResult->observaciones) {
                $observaciones = $firstResult->observaciones;
                // Limpiar campos adicionales JSON si existen
                if (str_contains($observaciones, '__CAMPOS_ADICIONALES__')) {
                    $parts = explode('__CAMPOS_ADICIONALES__', $observaciones);
                    $observaciones = trim($parts[0]);
                }
                //$observaciones = str_replace('**', '', $observaciones);
            }
        }
        if (empty($observaciones)) {
            $observaciones = 'DATOS CONTROLADOS';
        }
    @endphp
    @if(!empty($observaciones) && $observaciones !== 'DATOS CONTROLADOS')
    <div class="observaciones-section">
        {{ $observaciones }}
    </div>
    @endif

    <!-- Validación -->
    <div class="validation-section">
        @php
            // Usar siempre el médico que autorizó/ordenó el examen
            $authorizedDoctorName = $doctorName;
            $orderAuthorized = $order && $order->estado === 'completada';
            $doctorHasSignature = $doctor && $doctor->firma && \Storage::disk('public')->exists($doctor->firma);
        @endphp

        <div style="margin-bottom: 3%; margin-top: 1%;">Validado por:</div>

        @if($orderAuthorized && $doctorHasSignature)
        <div class="signature-container">
            <img src="{{ storage_path('app/public/' . $doctor->firma) }}" alt="Firma del médico" class="signature-image">
            <div class="signature-name">{{ $authorizedDoctorName }}</div>
        </div>
        @else
        <div style="border-bottom: 1px solid #000; width: 300px; margin: 0 auto; margin-top: 5px; padding-bottom: 7%;"></div>
        @endif
    </div>

    </div>

    <!-- Línea separadora final -->
    <div class="footer-line"></div>

    <!-- Pie de página -->
    <div class="footer-text">
        <div class="footer-left"><strong>LABORATORIO CLINICO PRO-MEDIC</strong></div>
        <div class="footer-right">Teléfonos: {{ $labInfo['telefonos'] ?? '2420-4997 y 6303-3392' }}</div>
    </div>
</body>
</html>
