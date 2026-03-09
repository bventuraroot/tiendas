<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden #{{ $order->numero_orden }}</title>
    <style>
        @page { margin: 1.5cm; size: Letter; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .separator { text-align: center; margin: 8px 0; font-size: 11px; letter-spacing: 0.5px; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .header-left, .header-center, .header-right { display: table-cell; vertical-align: top; }
        .header-left { width: 30%; text-align: left; }
        .header-center { width: 40%; text-align: center; padding: 0 10px; }
        .header-right { width: 30%; text-align: right; }
        .lab-name-large { font-size: 16px; font-weight: bold; line-height: 1.2; }
        .lab-name-medium { font-size: 14px; font-weight: bold; line-height: 1.2; }
        .lab-slogan { font-size: 10px; margin: 5px 0; }
        .lab-address { font-size: 10px; font-weight: bold; margin-top: 5px; }
        .horario-label { font-size: 12px; font-weight: bold; }
        .horario-text { font-size: 11px; }
        .info-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 12px;
        }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .info-label { font-weight: bold; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            font-size: 11px;
        }
        .table th { background: #f3f3f3; }
        .spacer { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="separator">_______________________________________________________________________________</div>

    <div class="header">
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

    <div class="separator">_______________________________________________________________________________</div>

    <div class="info-box">
        <div class="info-row">
            <div><span class="info-label">Orden:</span> {{ $order->numero_orden }}</div>
            <div><span class="info-label">Fecha:</span> {{ \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="info-row">
            <div>
                <span class="info-label">Paciente:</span>
                {{ optional($order->patient)->primer_nombre }} {{ optional($order->patient)->primer_apellido }}
            </div>
            <div>
                <span class="info-label">Edad:</span>
                @if(optional($order->patient)->fecha_nacimiento)
                    {{ \Carbon\Carbon::parse($order->patient->fecha_nacimiento)->age }} años
                @else
                    N/A
                @endif
            </div>
        </div>
        <div class="info-row">
            <div>
                <span class="info-label">Médico:</span>
                {{ optional($order->doctor)->nombres }} {{ optional($order->doctor)->apellidos }}
            </div>
            <div>
                <span class="info-label">Empresa:</span>
                {{ optional($order->company)->nombre ?? 'Particular' }}
            </div>
        </div>
        <div class="info-row">
            <div><span class="info-label">Estado:</span> {{ ucfirst(str_replace('_',' ', $order->estado)) }}</div>
            <div><span class="info-label">Tel. Paciente:</span> {{ optional($order->patient)->telefono ?? 'N/D' }}</div>
        </div>
    </div>

    <div class="info-box">
        <div class="info-label" style="margin-bottom:6px;">Exámenes solicitados</div>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Examen</th>
                    <th>Muestra</th>
                    <th>Precio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->exams as $index => $orderExam)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $orderExam->exam->nombre }}</td>
                    <td>{{ $orderExam->exam->tipo_muestra ?? 'N/A' }}</td>
                    <td>${{ number_format($orderExam->precio, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_',' ', $orderExam->estado)) }}</td>
                </tr>
                @endforeach
                @if($order->exams->isEmpty())
                <tr>
                    <td colspan="5" style="text-align:center;">No hay exámenes registrados</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="info-box">
        <div class="info-row">
            <div><span class="info-label">Total exámenes:</span> {{ $order->exams->count() }}</div>
            <div><span class="info-label">Monto total:</span> ${{ number_format($order->exams->sum('precio'), 2) }}</div>
        </div>
    </div>

    <div class="spacer"></div>
    <div class="spacer"></div>

    <div class="separator">________________________________________________________________________________________</div>
    <div style="text-align:center; font-weight:bold; margin-top:8px;">
        LABORATORIO CLINICO PRO-MEDIC - Teléfonos: 2420-4997 y 6303-3392
    </div>
</body>
</html>
