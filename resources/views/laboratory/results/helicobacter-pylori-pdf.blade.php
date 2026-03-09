<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Laboratorio - Ac. Anti-Helicobacter pylori</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .lab-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .motto {
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .address {
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        .schedule {
            font-size: 9px;
            text-align: right;
            margin-top: -50px;
        }
        
        .schedule strong {
            font-size: 10px;
        }
        
        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 10px 0;
        }
        
        .patient-info {
            margin-bottom: 15px;
        }
        
        .patient-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .patient-info td {
            padding: 5px;
            border: 1px solid #000;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .results-table th,
        .results-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .results-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .exam-category {
            font-weight: bold;
            background-color: #e8e8e8;
        }
        
        .exam-name {
            font-weight: normal;
            padding-left: 10px;
        }
        
        .reference-range {
            background-color: #d4edda;
            font-size: 10px;
            white-space: pre-line;
        }
        
        .spacer {
            height: 15px;
        }
        
        .validation-section {
            margin-top: 30px;
            text-align: center;
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
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer .lab-name-footer {
            font-size: 11px;
            font-weight: bold;
        }
        
        .footer .contact {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="lab-name">LABORATORIO - CLINICO - PRO-MEDIC</div>
        <div class="motto">Calidad, Rapidez y Veracidad en sus resultados es lo que nos caracteriza.</div>
        <div class="address">Dirección: Final Avenida El Calvario, Calle principal Nahuizalco (Frente a parada de buses del chorro Público)</div>
        <div class="schedule">
            <strong>HORARIO</strong><br>
            Lunes a Sábado de: 7:00 a.m. - 3:00 p.m.
        </div>
    </div>
    
    <hr>
    
    <!-- Patient Information -->
    <div class="patient-info">
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
        <table>
            <tr>
                <td style="width: 50%;">Paciente: {{ $patientName }}</td>
                <td style="width: 50%;">Edad: {{ $patientAge }}</td>
            </tr>
            <tr>
                <td>Medico: {{ $doctorName }}</td>
                <td>Fecha: {{ $orderDate }}</td>
            </tr>
        </table>
    </div>
    
    <hr>
    
    <!-- Results Table -->
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
                <td class="exam-category">{{ $valoresReferencia['categoria'] ?? 'INMUNOLOGIA INFECCIOSAS' }}</td>
                <td></td>
                <td></td>
            </tr>
            @php
                // Obtener los dos resultados
                $resultadoIgg = $results->firstWhere('parametro', 'Ac. Anti-Helicobacter pylori IgG');
                $resultadoIgm = $results->firstWhere('parametro', 'Ac. Anti-Helicobacter pylori IgM');
                
                $unidad = $valoresReferencia['unidad_medida'] ?? 'RLU';
                
                // Obtener rangos de referencia
                $rangoIgg = '';
                $rangoIgm = '';
                if ($valoresReferencia && isset($valoresReferencia['igg'])) {
                    $rangoIgg = $valoresReferencia['igg']['rango'] ?? '';
                } else {
                    $rangoIgg = "Negativo: Menor de 0.9 RLU\nDudoso: 0.9 a 1.1 RLU\nPositivo: Mayor de 1.1 RLU";
                }
                if ($valoresReferencia && isset($valoresReferencia['igm'])) {
                    $rangoIgm = $valoresReferencia['igm']['rango'] ?? '';
                } else {
                    $rangoIgm = "Negativo: Menor de 0.9 RLU\nDudoso: 0.9 a 1.1 RLU\nPositivo: Mayor de 1.1 RLU";
                }
            @endphp
            
            <tr>
                <td class="exam-name">Ac. Anti-Helicobacter pylori IgG :</td>
                <td style="text-align: center;">
                    @if($resultadoIgg)
                        {{ $resultadoIgg->resultado }} {{ $unidad }}
                    @endif
                </td>
                <td class="reference-range">{{ $rangoIgg }}</td>
            </tr>
            <tr>
                <td class="exam-name">Ac. Anti-Helicobacter pylori IgM:</td>
                <td style="text-align: center;">
                    @if($resultadoIgm)
                        {{ $resultadoIgm->resultado }} {{ $unidad }}
                    @endif
                </td>
                <td class="reference-range">{{ $rangoIgm }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Espacios en blanco -->
    <div class="spacer"></div>

        <!-- Observaciones -->
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

<!-- Validación -->
    <div class="validation-section">
        @php
            // Usar siempre el médico que autorizó/ordenó el examen
            $authorizedDoctorName = $doctor
                ? ($doctor->nombres . ' ' . $doctor->apellidos)
                : '';
        @endphp
        <p><strong>Validado por:</strong> {{ $authorizedDoctorName }}</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="lab-name-footer">LABORATORIO CLINICO PRO-MEDIC</div>
        <div class="contact">- Teléfonos: 2420-4997 y 6303-3392</div>
    </div>
</body>
</html>

