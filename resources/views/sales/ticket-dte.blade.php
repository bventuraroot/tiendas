<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket DTE #{{ $sale->id }}</title>
    <!-- SweetAlert2 para notificaciones -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print {
            @page {
                width: 80mm;
                height: auto;
                margin: 0;
                margin-top: 0;
                margin-bottom: 0;
                margin-left: 0;
                margin-right: 0;
                size: 80mm auto;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 16px !important;
                background: white !important;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* Ocultar cualquier control del navegador */
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 16px;
            line-height: 1.4;
        }

        .ticket-container {
            width: 100%;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo {
            max-width: 60px;
            max-height: 60px;
            height: auto;
            width: auto;
        }

        .company-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 12px;
            line-height: 1.2;
        }

        .sale-info {
            margin-bottom: 10px;
        }

        .sale-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 14px;
        }

        .products {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 8px 0;
            margin: 10px 0;
        }

        .product-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .product-item {
            margin-bottom: 5px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 1px;
            font-size: 14px;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .totals {
            margin-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 14px;
        }

        .total-final {
            font-weight: bold;
            font-size: 16px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }

        .dte-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            font-size: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dte-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            color: #007bff;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #007bff;
            padding-bottom: 5px;
        }

        .dte-info {
            margin-bottom: 6px;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }

        .dte-info:last-child {
            border-bottom: none;
        }

        .dte-info strong {
            font-size: 11px;
            color: #495057;
            display: inline-block;
            min-width: 120px;
        }

        .dte-value {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: #212529;
            word-break: break-all;
            background-color: rgba(255,255,255,0.7);
            padding: 2px 4px;
            border-radius: 3px;
        }

        .dte-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .dte-status.procesado {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .dte-status.enviado {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .dte-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .qr-section {
            margin: 15px 0;
            text-align: center;
        }

        .qr-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .qr-code {
            flex-shrink: 0;
        }

        .qr-code svg {
            width: 60px;
            height: 60px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
        }

        .qr-text {
            text-align: left;
            font-size: 11px;
            color: #495057;
        }

        .qr-text strong {
            color: #007bff;
            font-size: 12px;
        }

        .qr-text small {
            color: #6c757d;
            font-size: 9px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Estilos para el botón de impresión */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    @if($autoprint)
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    @endif

    <div class="ticket-container">
        <!-- Encabezado -->
        <div class="header">
            <!-- Logo de la empresa -->
            <div class="logo-container">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="logo">
            </div>

            <div class="company-name">{{ $sale->company->name ?? 'FARMACIA JERUSALEM' }}</div>
            <div class="company-info">
                @if($sale->company->addres)
                    {{ $sale->company->addres }}<br>
                @endif
                @if($sale->company->number_phone)
                    Tel: {{ $sale->company->number_phone }}<br>
                @endif
                @if($sale->company->email)
                    {{ $sale->company->email }}
                @endif
            </div>
        </div>

        <!-- Información de la venta -->
        <div class="sale-info">
            <div class="sale-info-row">
                <span><strong>Ticket #:</strong></span>
                <span>{{ $sale->id }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Fecha:</strong></span>
                <span>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Tipo:</strong></span>
                <span>{{ $sale->typedocument->description ?? 'Venta' }}</span>
            </div>
            <div class="sale-info-row">
                <span><strong>Cliente:</strong></span>
                <span>
                    @if($sale->client)
                        @if($sale->client->tpersona == 'N')
                            {{ $sale->client->firstname }} {{ $sale->client->secondname ?? '' }} {{ $sale->client->firstlastname }}
                        @else
                            {{ $sale->client->name_contribuyente ?? $sale->client->comercial_name ?? 'Cliente Empresa' }}
                        @endif
                    @else
                        Venta al menudeo
                    @endif
                </span>
            </div>
            <div class="sale-info-row">
                <span><strong>Pago:</strong></span>
                <span>
                    @switch($sale->waytopay)
                        @case(1) CONTADO @break
                        @case(2) CRÉDITO @break
                        @case(3) OTRO @break
                        @default CONTADO
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Sección DTE -->
        @if(isset($isFacturaOrCredito) && $isFacturaOrCredito)
        <div class="dte-section">
            <div class="dte-header">
                📄 Documento Tributario Electrónico
            </div>
            @if($hasDte && $sale->dte)
                <div class="dte-info">
                    <strong>🔑 Código de Generación:</strong><br>
                    <span class="dte-value">{{ $sale->dte->codigoGeneracion ?? 'N/A' }}</span>
                </div>
                <div class="dte-info">
                    <strong>📋 Número de Control:</strong><br>
                    <span class="dte-value">{{ $sale->dte->id_doc ?? 'N/A' }}</span>
                </div>
                <div class="dte-info">
                    <strong>📊 Estado:</strong><br>
                    @php
                        $estado = $sale->dte->Estado ?? 'N/A';
                        $estadoClass = 'dte-status ';
                        if (strtoupper($estado) === 'PROCESADO') {
                            $estadoClass .= 'procesado';
                        } elseif (strtoupper($estado) === 'ENVIADO') {
                            $estadoClass .= 'enviado';
                        } else {
                            $estadoClass .= 'error';
                        }
                    @endphp
                    <span class="{{ $estadoClass }}">{{ $estado }}</span>
                </div>
                @if($sale->dte->fhRecibido)
                <div class="dte-info">
                    <strong>📅 Fecha Recepción:</strong><br>
                    <span class="dte-value">{{ \Carbon\Carbon::parse($sale->dte->fhRecibido)->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
                @if($sale->dte->selloRecibido)
                <div class="dte-info">
                    <strong>🔐 Sello de Recepción:</strong><br>
                    <span class="dte-value">{{ substr($sale->dte->selloRecibido, 0, 40) }}...</span>
                </div>
                @endif
            @else
                <div class="dte-info">
                    <strong>📋 Estado:</strong><br>
                    <span class="dte-status error">PENDIENTE DE PROCESAMIENTO</span>
                </div>
                <div class="dte-info">
                    <strong>ℹ️ Información:</strong><br>
                    <span class="dte-value">El documento será procesado por Hacienda</span>
                </div>
            @endif
        </div>
        @endif

        <!-- Productos -->
        <div class="products">
            <div class="text-center product-header">
                PRODUCTOS
            </div>

            @foreach($sale->details as $detail)
                <div class="product-item">
                    <div class="product-name">
                        {{ ($detail->ruta && trim($detail->ruta) !== '' && $detail->product && $detail->product->code == 'LAB') ? $detail->ruta : ($detail->product->name ?? 'Producto') }} {{ ($detail->product && $detail->product->code != 'LAB') ? ($detail->product->marca->name ?? '') : '' }}
                    </div>
                    <div class="product-details">
                        <span>{{ $detail->amountp }} x ${{ number_format($detail->priceunit, 2) }}</span>
                        <span class="text-right">${{ number_format($detail->pricesale + $detail->nosujeta + $detail->exempt, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span class="text-right">${{ number_format($subtotal, 2) }}</span>
            </div>

            @if($totalIva > 0)
                <div class="total-row">
                    <span>IVA (13%):</span>
                    <span class="text-right">${{ number_format($totalIva, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span class="text-right">${{ number_format($total, 2) }}</span>
            </div>
        </div>

        <!-- Código QR -->
        @if(isset($isFacturaOrCredito) && $isFacturaOrCredito)
        <div class="qr-section">
            <div class="qr-container">
                @if($hasDte && $sale->dte && $qrCode)
                    <div class="qr-code">
                        {!! $qrCode !!}
                    </div>
                    <div class="qr-text">
                        <strong>📱 Código QR</strong><br>
                        <small>Escanea para verificar el documento</small>
                    </div>
                @else
                    <div class="qr-code">
                        <div style="width: 60px; height: 60px; border: 1px solid #000; display: flex; align-items: center; justify-content: center; font-size: 8px; background: white;">
                            QR DTE
                        </div>
                    </div>
                    <div class="qr-text">
                        <strong>📱 Código QR</strong><br>
                        <small>Disponible después del procesamiento</small>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Pie del ticket -->
        <div class="footer">
            <div>¡Gracias por su compra!</div>
            <div>Conserve este ticket</div>
            @if($hasDte && $sale->dte)
            <div style="margin-top: 5px; font-size: 10px;">
                <strong>Documento Tributario Electrónico</strong><br>
                Válido para efectos fiscales
            </div>
            @endif
            <div style="margin-top: 5px; font-size: 10px;">
                {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

    <script>
        // Función para manejar errores de impresión
        window.addEventListener('error', function(e) {
            console.error('Error en el ticket:', e);
        });

        // Función para detectar si la impresión fue cancelada
        window.addEventListener('beforeprint', function() {
            console.log('Iniciando impresión...');
        });

        window.addEventListener('afterprint', function() {
            console.log('Impresión completada');
        });
    </script>
</body>
</html>
