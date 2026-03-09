<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cuentas por Pagar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .info {
            margin-bottom: 15px;
        }
        .info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #000;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .totals {
            margin-top: 15px;
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE CUENTAS POR PAGAR</h1>
        <h2>{{ $company->name ?? 'N/A' }}</h2>
    </div>

    <div class="info">
        <p><strong>Generado el:</strong> {{ $generated_at }}</p>
        @if(isset($filters['year']) && isset($filters['period']))
            <p><strong>Período:</strong> {{ $filters['period'] }}/{{ $filters['year'] }}</p>
        @endif
        @if(isset($filters['date_from']) || isset($filters['date_to']))
            <p><strong>Rango de fechas:</strong>
                @if(isset($filters['date_from']))
                    Desde: {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}
                @endif
                @if(isset($filters['date_to']))
                    Hasta: {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}
                @endif
            </p>
        @endif
        @if(isset($filters['payment_status']))
            <p><strong>Estado de pago:</strong>
                @if($filters['payment_status'] == 0) Pendiente
                @elseif($filters['payment_status'] == 1) Parcial
                @elseif($filters['payment_status'] == 2) Pagado
                @endif
            </p>
        @endif
    </div>

    @if($purchases->count() > 0)
        <!-- Resumen de totales -->
        <div class="summary">
            <div class="summary-row">
                <div class="summary-cell" style="width: 25%;"><strong>Total Facturado:</strong></div>
                <div class="summary-cell text-right" style="width: 25%;">$ {{ number_format($totals['total_amount'] ?? 0, 2, '.', ',') }}</div>
                <div class="summary-cell" style="width: 25%;"><strong>Total Pagado:</strong></div>
                <div class="summary-cell text-right" style="width: 25%;">$ {{ number_format($totals['total_paid'] ?? 0, 2, '.', ',') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell"><strong>Saldo Pendiente:</strong></div>
                <div class="summary-cell text-right" style="color: #dc3545;">$ {{ number_format($totals['total_balance'] ?? 0, 2, '.', ',') }}</div>
                <div class="summary-cell"><strong>Pendientes:</strong> {{ $totals['pending_count'] ?? 0 }} | <strong>Parciales:</strong> {{ $totals['partial_count'] ?? 0 }} | <strong>Pagadas:</strong> {{ $totals['paid_count'] ?? 0 }}</div>
                <div class="summary-cell"></div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Número</th>
                    <th style="width: 8%;">Fecha</th>
                    <th style="width: 8%;">Tipo Doc</th>
                    <th style="width: 20%;">Proveedor</th>
                    <th style="width: 10%;">NIT</th>
                    <th style="width: 10%;" class="text-right">Total</th>
                    <th style="width: 10%;" class="text-right">Pagado</th>
                    <th style="width: 10%;" class="text-right">Saldo</th>
                    <th style="width: 8%;" class="text-center">Estado</th>
                    <th style="width: 8%;">Último Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->number }}</td>
                        <td>{{ $purchase->formatted_date ?? \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</td>
                        <td>{{ $purchase->document_type ?? 'N/A' }}</td>
                        <td>{{ $purchase->provider_name }}</td>
                        <td>{{ $purchase->provider_nit ?? 'N/A' }}</td>
                        <td class="text-right">$ {{ number_format($purchase->total, 2, '.', ',') }}</td>
                        <td class="text-right">$ {{ number_format($purchase->paid_amount ?? 0, 2, '.', ',') }}</td>
                        <td class="text-right" style="color: {{ ($purchase->current_balance ?? 0) > 0 ? '#dc3545' : '#28a745' }};">
                            $ {{ number_format($purchase->current_balance ?? 0, 2, '.', ',') }}
                        </td>
                        <td class="text-center">
                            @if($purchase->payment_status_display == 'PAGADO')
                                <span class="badge badge-success">{{ $purchase->payment_status_display }}</span>
                            @elseif($purchase->payment_status_display == 'PARCIAL')
                                <span class="badge badge-warning">{{ $purchase->payment_status_display }}</span>
                            @else
                                <span class="badge badge-danger">{{ $purchase->payment_status_display }}</span>
                            @endif
                        </td>
                        <td>
                            @if($purchase->last_payment_date)
                                {{ \Carbon\Carbon::parse($purchase->last_payment_date)->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="5" class="text-right">TOTALES:</td>
                    <td class="text-right">$ {{ number_format($totals['total_amount'] ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right">$ {{ number_format($totals['total_paid'] ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right" style="color: #dc3545;">$ {{ number_format($totals['total_balance'] ?? 0, 2, '.', ',') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; margin-top: 20px; font-weight: bold;">
            No se encontraron compras con los filtros seleccionados.
        </p>
    @endif
</body>
</html>

