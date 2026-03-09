<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalles de Ventas por Cliente</title>

    <style type="text/css">
        * {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 12px;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .logo-section {
            width: 25%;
            vertical-align: top;
            padding: 10px;
            text-align: center;
        }

        .logo-img {
            max-width: 150px;
            max-height: 150px;
            height: auto;
        }

        .company-info {
            width: 45%;
            vertical-align: top;
            padding: 10px;
        }

        .report-info {
            width: 30%;
            vertical-align: top;
            padding: 10px;
            border: 2px solid #1EBB51;
            background-color: #f8f9fa;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1EBB51;
            margin-bottom: 5px;
        }

        .company-giro {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
        }

        .company-details {
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }

        .company-details div {
            margin-bottom: 2px;
        }

        .company-details strong {
            color: #1EBB51;
            font-weight: 600;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #1EBB51;
            text-align: center;
            margin-bottom: 10px;
        }

        .report-number {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }

        .report-details {
            font-size: 10px;
        }

        .client-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .client-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .client-info {
            display: block;
            width: 100%;
        }

        .client-info div {
            margin-bottom: 3px;
        }

        .meta {
            text-align: center;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .summary-card {
            flex: 1;
            background: #1EBB51;
            color: white;
            padding: 12px;
            margin: 0 5px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            font-weight: normal;
            opacity: 0.9;
        }

        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
        }

        .card-ventas { background: #1EBB51; }
        .card-monto { background: #28a745; }
        .card-promedio { background: #17a2b8; }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }

        .products-table th {
            background-color: #1EBB51;
            color: white;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .products-table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .products-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .products-table .text-left {
            text-align: left;
        }

        .products-table .text-right {
            text-align: right;
        }

        .sale-id {
            font-weight: bold;
            color: #2c3e50;
        }

        .product-name {
            font-weight: bold;
            color: #34495e;
        }

        .amount {
            font-weight: bold;
            color: #27ae60;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 8px;
        }

        .generated-info {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 8px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        @page {
            margin: 2cm 1.5cm;
        }
    </style>
</head>

<body>
    <!-- Header con información de la empresa y reporte -->
    <table class="header-table">
        <tr>
            <td class="logo-section">
                @php
                    $logoPath = public_path('assets/img/logo-pdf-large.png');
                    $showLogo = false;
                    $logoBase64 = '';

                    if (file_exists($logoPath)) {
                        try {
                            $logoData = @file_get_contents($logoPath);
                            if ($logoData !== false && strlen($logoData) > 0) {
                                if (strlen($logoData) < 2000000) {
                                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                                    $showLogo = true;
                                }
                            }
                        } catch (\Exception $e) {
                            $showLogo = false;
                        }
                    }
                @endphp

                @if($showLogo)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="logo-img">
                @else
                    <div style="font-size: 14px; color: #1EBB51; font-weight: bold;">
                        LOGO
                    </div>
                @endif
            </td>

            <td class="company-info">
                <div class="company-name">{{ $heading->name ?? 'Agroservicio Milagro de Dios' }}</div>
                <div class="company-giro">Sistema de Gestión Comercial</div>
                <div class="company-details">
                    <div><strong>📍 Dirección:</strong> {{ $heading->address ?? 'Dirección no disponible' }}</div>
                    <div><strong>📞 Teléfono:</strong> {{ $heading->phone ?? 'Teléfono no disponible' }}</div>
                    <div><strong>📧 Email:</strong> {{ $heading->email ?? 'Email no disponible' }}</div>
                    <div><strong>🌐 Web:</strong> {{ $heading->website ?? 'Web no disponible' }}</div>
                </div>
            </td>

            <td class="report-info">
                <div class="report-title">DETALLES DE VENTAS</div>
                <div class="report-number">POR CLIENTE</div>
                <div class="report-details">
                    <div><strong>📅 Fecha:</strong> {{ now()->format('d/m/Y') }}</div>
                    <div><strong>🕒 Hora:</strong> {{ now()->format('H:i:s') }}</div>
                    @php
                        $meses = [
                            '01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril',
                            '05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto',
                            '09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'
                        ];
                    @endphp
                    @if($yearB && $period)
                        <div><strong>📊 Período:</strong> {{ $meses[$period] ?? $period }} {{ $yearB }}</div>
                    @elseif($yearB)
                        <div><strong>📊 Año:</strong> {{ $yearB }}</div>
                    @else
                        <div><strong>📊 Período:</strong> Todos</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @php
        $firstSale = $salesDetails->first();
        $clientName = $firstSale->client_name;
        $totalAmount = $salesDetails->sum('totalamount');
        $totalSales = $salesDetails->count();
        $completedSales = $salesDetails->where('state', 1)->count();
        $cancelledSales = $salesDetails->where('state', 0)->count();
    @endphp

    <!-- Información del Cliente -->
    <div class="client-section">
        <div class="client-title">👤 Información del Cliente</div>
        <div class="client-info">
            <div><strong>Nombre:</strong> {{ $clientName }}</div>
            @if($yearB && $period)
                <div><strong>Período:</strong> {{ $meses[$period] ?? $period }} {{ $yearB }}</div>
            @elseif($yearB)
                <div><strong>Año:</strong> {{ $yearB }}</div>
            @else
                <div><strong>Período:</strong> Todos los períodos</div>
            @endif
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card card-ventas">
            <h3>📈 Total Ventas</h3>
            <div class="value">{{ $totalSales }}</div>
        </div>
        <div class="summary-card card-monto">
            <h3>💰 Monto Total</h3>
            <div class="value">${{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="summary-card card-promedio">
            <h3>📊 Promedio por Venta</h3>
            <div class="value">${{ number_format($totalSales ? ($totalAmount / $totalSales) : 0, 2) }}</div>
        </div>
    </div>

    @if($salesDetails->count() > 0)
        <table class="products-table">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">🆔 ID Venta</th>
                    <th class="text-center">📅 Fecha</th>
                    <th class="text-left">📄 Tipo Doc.</th>
                    <th class="text-left">🛍️ Producto</th>
                    <th class="text-right">📊 Cantidad</th>
                    <th class="text-right">💰 Precio Unit.</th>
                    <th class="text-center">🔓 Exento</th>
                    <th class="text-center">📋 Ret. 13%</th>
                    <th class="text-right">💵 Total</th>
                    <th class="text-center">✅ Estado</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 1; @endphp
                @foreach($salesDetails as $detail)
                    <tr>
                        <td class="text-center">{{ $i++ }}</td>
                        <td class="text-center">
                            <div class="sale-id">#{{ $detail->sale_id }}</div>
                        </td>
                        <td class="text-center">{{ $detail->formatted_date }}</td>
                        <td class="text-left">{{ $detail->document_type }}</td>
                        <td class="text-left">
                            <div class="product-name">{{ $detail->product_name }}</div>
                        </td>
                        <td class="text-right amount">{{ number_format($detail->quantity, 2) }}</td>
                        <td class="text-right">${{ number_format($detail->pricesale, 2) }}</td>
                        <td class="text-center">{{ $detail->exempt ? '✅ Sí' : '❌ No' }}</td>
                        <td class="text-center">{{ $detail->detained13 ? '✅ Sí' : '❌ No' }}</td>
                        <td class="text-right amount">${{ number_format($detail->quantity * $detail->pricesale, 2) }}</td>
                        <td class="text-center">
                            <span class="status-badge {{ $detail->state == 1 ? 'status-completed' : 'status-cancelled' }}">
                                {{ $detail->state == 1 ? '✅ Completada' : '❌ Cancelada' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>📭 No se encontraron resultados</h3>
            <p>No hay datos disponibles con los filtros aplicados para el período seleccionado.</p>
        </div>
    @endif

    <div class="footer">
        <div class="generated-info">
            <strong>📋 Resumen del Reporte:</strong><br>
            Este reporte muestra los detalles de todas las ventas del cliente <strong>{{ $clientName }}</strong><br>
            Empresa: {{ $heading->name ?? 'N/A' }}<br>
            Período: {{ $yearB ?: 'Todos los años' }} - {{ $period ? ($meses[$period] ?? $period) : 'Todos los meses' }}<br>
            Total de transacciones analizadas: {{ $totalSales }}<br>
            Reporte generado automáticamente el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}
        </div>

        <div style="margin-top: 10px;">
            <strong>Agroservicio Milagro de Dios</strong> - Sistema de Gestión Comercial<br>
            📞 Teléfono: [Teléfono] | 📧 Email: [Email] | 🌐 Web: [Website]
        </div>
    </div>
</body>
</html>
