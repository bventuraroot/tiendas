<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen - {{ $exam->nombre ?? 'Fibrinogeno' }}</title>
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
            text-align: left;
        }

        .exam-category {
            font-weight: bold;
        }

        .exam-name {
            padding-left: 10px;
        }

        .spacer {
            height: 15px;
        }

        .validation-section {
            margin-top: 40px;
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

        .observaciones-section {
            margin-top: 15px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="separator-line">_______________________________________________________________________________</div>

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

    <div class="separator-line">_______________________________________________________________________________</div>
    <div class="spacer"></div>

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
                <td class="exam-category">COAGULACION</td>
                <td></td>
                <td></td>
            </tr>
            @php
                $result = $results->first();
                $resultValue = $result ? $result->resultado : '';
            @endphp

            <tr>
                <td class="exam-name">Fibrinogeno</td>
                <td style="text-align: center;">
                    @if($result)
                        {{ $resultValue }}
                    @endif
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="validation-section">
        @php
            $authorizedDoctorName = $doctorName;
        @endphp
        Validado por: {{ $authorizedDoctorName }}
    </div>

    <div class="footer-line">________________________________________________________________________________________</div>

    <div class="footer-text">
        <strong>LABORATORIO CLINICO PRO-MEDIC</strong>      -      Teléfonos: {{ $labInfo['telefonos'] }}
    </div>
</body>
</html>


