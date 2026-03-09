<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas por Clientes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1EBB51;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1EBB51;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }

        .report-info {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }

        .client-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .client-header {
            background-color: #1EBB51;
            color: white;
            padding: 10px;
            font-weight: bold;
            font-size: 14px;
        }

        .client-info {
            background-color: #f5f5f5;
            padding: 8px;
            border-left: 4px solid #1EBB51;
            margin-bottom: 5px;
        }

        .client-stats {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .stat-cell {
            display: table-cell;
            width: 25%;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        .stat-label {
            font-size: 10px;
            color: #666;
        }

        .stat-value {
            font-size: 12px;
            color: #1EBB51;
        }

        .credit-highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 5px;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            font-size: 9px;
        }

        th {
            background-color: #1EBB51;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .credit-row {
            background-color: #fff3cd !important;
        }

        .days-overdue {
            color: #d32f2f;
            font-weight: bold;
        }

        .days-warning {
            color: #f57c00;
            font-weight: bold;
        }

        .days-ok {
            color: #388e3c;
            font-weight: bold;
        }


        @media print {
            body { margin: 0; }
            .client-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">AGROSERVICIO MILAGRO DE DIOS</div>
        <div class="report-title">REPORTE DE VENTAS POR CLIENTES</div>
        <div class="report-info">
            Fecha: {{ now()->format('d/m/Y H:i:s') }} |
            @if(isset($dateRange) && $dateRange)
                Período: {{ \Carbon\Carbon::parse(explode(' to ', $dateRange)[0])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(explode(' to ', $dateRange)[1])->format('d/m/Y') }}
            @else
                Período: {{ $yearB ?: 'Todos los años' }} - {{ $period ? ($meses[str_pad($period, 2, '0', STR_PAD_LEFT)] ?? $period) : 'Todos los meses' }}
            @endif
        </div>
    </div>

    @php
        $meses = [
            '01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril',
            '05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto',
            '09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'
        ];
    @endphp


    @if($salesByClient && $salesByClient->count() > 0)
        @foreach($salesByClient as $client)
            <div class="client-section">
                <div class="client-header">
                    Cliente: {{ $client->tpersona === 'J' ? $client->comercial_name : $client->firstname . ' ' . $client->firstlastname }}
                    <span style="float: right;">{{ $client->tpersona === 'J' ? 'Persona Jurídica' : 'Persona Natural' }}</span>
                </div>

                <div class="client-info">
                    <div class="client-stats">
                        <div class="stat-cell">
                            <div class="stat-label">NIT/DUI</div>
                            <div class="stat-value">{{ $client->nit ?: 'N/A' }}</div>
                        </div>
                        <div class="stat-cell">
                            <div class="stat-label">Email</div>
                            <div class="stat-value">{{ $client->email ?: 'N/A' }}</div>
                        </div>
                        <div class="stat-cell">
                            <div class="stat-label">Total Ventas</div>
                            <div class="stat-value">{{ number_format($client->total_sales) }}</div>
                        </div>
                        <div class="stat-cell">
                            <div class="stat-label">Monto Total</div>
                            <div class="stat-value">${{ number_format($client->total_amount, 2) }}</div>
                        </div>
                    </div>

                    @php
                        // Obtener ventas del cliente
                        $clientSales = \App\Models\Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
                            ->leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
                            ->select(
                                'sales.id',
                                'sales.date',
                                'sales.totalamount',
                                'sales.state',
                                'sales.waytopay',
                                'typedocuments.description as document_type',
                                'dte.codigoGeneracion'
                            )
                            ->where('sales.client_id', $client->client_id)
                            ->where('sales.company_id', $heading->id)
                            ->where('sales.state', 1) // Solo ventas completadas (no anuladas)
                            ->when(isset($dateRange) && $dateRange, function($query) use ($dateRange) {
                                $dates = explode(' to ', $dateRange);
                                if (count($dates) === 2) {
                                    $query->whereBetween('sales.date', [$dates[0], $dates[1]]);
                                }
                            })
                            ->when(!isset($dateRange) && isset($yearB) && $yearB, function($query) use ($yearB) {
                                $query->whereRaw('YEAR(sales.date) = ?', [$yearB]);
                            })
                            ->when(!isset($dateRange) && isset($period) && $period, function($query) use ($period) {
                                $query->whereRaw('MONTH(sales.date) = ?', [$period]);
                            })
                            ->orderBy('sales.date', 'desc')
                            ->get();

                        // Calcular información de crédito
                        $ventasCredito = $clientSales->where('waytopay', 2);
                        $totalCredito = $ventasCredito->sum('totalamount');
                        $cantidadCredito = $ventasCredito->count();
                        $ultimaVenta = $clientSales->sortByDesc('date')->first();
                        $diasUltimaVenta = $ultimaVenta ? now()->diffInDays($ultimaVenta->date) : 'N/A';
                    @endphp

                    @if($cantidadCredito > 0)
                        <div class="credit-highlight">
                            <strong>INFORMACIÓN DE CRÉDITO:</strong>
                            Ventas a Crédito: {{ number_format($cantidadCredito) }} |
                            Monto en Crédito: ${{ number_format($totalCredito, 2) }} |
                            Días desde Última Venta: {{ $diasUltimaVenta }}
                        </div>
                    @endif
                </div>

                @if($clientSales->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Correlativo</th>
                                <th>Tipo Doc</th>
                                <th>Forma Pago</th>
                                <th>Código DTE</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $saleNum = 1; @endphp
                            @foreach($clientSales as $sale)
                                @php
                                    $paymentMethods = [1 => 'Contado', 2 => 'Crédito', 3 => 'TC'];
                                    $diasTranscurridos = now()->diffInDays($sale->date);
                                    $isCredit = $sale->waytopay == 2;
                                @endphp
                                <tr class="{{ $isCredit ? 'credit-row' : '' }}">
                                    <td>{{ $saleNum++ }}</td>
                                    <td>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                                    <td><strong>#{{ $sale->id }}</strong></td>
                                    <td>{{ $sale->document_type }}</td>
                                    <td>
                                        @if($isCredit)
                                            <strong style="color: #d32f2f;">{{ $paymentMethods[$sale->waytopay] }}</strong>
                                        @else
                                            {{ $paymentMethods[$sale->waytopay] }}
                                        @endif
                                    </td>
                                    <td style="font-size: 8px;">
                                        @if($sale->codigoGeneracion)
                                            {{ substr($sale->codigoGeneracion, 0, 20) }}...
                                        @else
                                            Sin DTE
                                        @endif
                                    </td>
                                    <td><strong>${{ number_format($sale->totalamount, 2) }}</strong></td>
                                    <td>
                                        @if($sale->state == 1)
                                            <span style="color: #388e3c; font-weight: bold;">Completada</span>
                                        @else
                                            <span style="color: #d32f2f; font-weight: bold;">Cancelada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #e8f5e8; font-weight: bold;">
                                <td colspan="6" style="text-align: right;">TOTAL:</td>
                                <td>${{ number_format($clientSales->where('state', 1)->sum('totalamount'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>No se encontraron resultados</h3>
            <p>No hay datos disponibles con los filtros aplicados.</p>
        </div>
    @endif
</body>
</html>
