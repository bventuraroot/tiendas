<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket DTE #{{ $sale->id }}</title>
    <style>
        @media print {
            @page { width: 80mm; margin: 0; }
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }

        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 18px;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 6px;
        }

        .logo {
            max-width: 50px;
            max-height: 50px;
            height: auto;
            width: auto;
        }

        .company-name {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sale-info {
            margin-bottom: 12px;
            font-size: 16px;
        }

        .sale-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        /* Sección DTE */
        .dte-section {
            border: 1px solid #000;
            padding: 6px;
            margin: 8px 0;
            font-size: 9px;
            background: white;
        }

        .dte-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }

        .dte-info {
            margin-bottom: 1px;
        }

        .dte-info strong {
            font-size: 8px;
        }

        /* QR Code para ticket minimal */
        .qr-section {
            text-align: center;
            margin: 6px 0;
            padding: 4px;
            border: 1px dashed #000;
        }

        .qr-section svg {
            width: 50px !important;
            height: 50px !important;
            border: 1px solid #000;
            background: white;
        }

        .products {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 12px 0;
        }

        .product-item {
            margin-bottom: 6px;
            font-size: 14px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 1px;
        }

        .product-details {
            display: flex;
            justify-content: space-between;
        }

        .totals {
            margin-top: 12px;
            font-size: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .total-final {
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 6px;
        }

        .footer {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            border-top: 1px dashed #000;
            padding-top: 12px;
        }

        .no-print {
            margin: 20px 0;
            text-align: center;
        }

        button {
            padding: 10px 20px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-warning {
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>
    <!-- Controles (no se imprimen) -->
    <div class="no-print">
        <h3>🎫 Ticket DTE #{{ $sale->id }}</h3>

        <div style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
            <strong>🖨️ Impresora:</strong> Se usará la impresora predeterminada del sistema<br>
            <small>Configure su impresora de 80mm como predeterminada para mejores resultados</small>
        </div>

        <button onclick="window.print()">🖨️ Imprimir Ticket</button>
        <button onclick="window.close()" class="btn-warning">❌ Cerrar</button>
        <button onclick="testPrint()" class="btn-success">🧪 Prueba</button>

        @if(!$autoprint)
            <div style="margin-top: 10px;">
                <span style="background-color: #ffc107; color: black; padding: 5px 10px; border-radius: 3px;">
                    👁️ Modo Vista - No se imprimirá automáticamente
                </span>
            </div>
        @else
            <div style="margin-top: 10px;">
                <span style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 3px;">
                    🖨️ Auto-Impresión Activada
                </span>
            </div>
        @endif
    </div>

    <!-- Contenido del ticket -->
    <div class="ticket-container">
        <!-- Encabezado -->
        <div class="header">
            <!-- Logo de la empresa -->
            <div class="logo-container">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="logo">
            </div>

            <div class="company-name">{{ $sale->company->name ?? 'FARMACIA JERUSALEM' }}</div>
            <div style="font-size: 12px;">
                @if($sale->company->addres ?? false)
                    {{ $sale->company->addres }}<br>
                @endif
                @if($sale->company->number_phone ?? false)
                    Tel: {{ $sale->company->number_phone }}<br>
                @endif
                @if($sale->company->email ?? false)
                    {{ $sale->company->email }}
                @endif
            </div>
        </div>

        <!-- Información de la venta -->
        <div class="sale-info">
            <div class="sale-row">
                <span><strong>Ticket #:</strong></span>
                <span>{{ $sale->id }}</span>
            </div>
            <div class="sale-row">
                <span><strong>Fecha:</strong></span>
                <span>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="sale-row">
                <span><strong>Tipo de Documento:</strong></span>
                <span>
                    @if($sale->typedocument)
                        {{ $sale->typedocument->description }}
                        @if($sale->typedocument->type)
                            ({{ $sale->typedocument->type }})
                        @endif
                    @else
                        FACTURA
                    @endif
                </span>
            </div>
            @if($sale->nu_doc)
            <div class="sale-row">
                <span><strong>Número Doc:</strong></span>
                <span>{{ $sale->nu_doc }}</span>
            </div>
            @endif
            <div class="sale-row">
                <span><strong>Cliente:</strong></span>
                <span>
                    @if($sale->client)
                        @if($sale->client->tpersona == 'N')
                            {{ $sale->client->firstname }} {{ $sale->client->secondname ?? '' }} {{ $sale->client->firstlastname }} {{ $sale->client->secondlastname ?? '' }}
                        @else
                            {{ $sale->client->name_contribuyente ?? $sale->client->comercial_name ?? 'Cliente Empresa' }}
                        @endif
                    @else
                        Venta al menudeo
                    @endif
                </span>
            </div>
            @if($sale->client && $sale->client->nit)
            <div class="sale-row">
                <span><strong>NIT/DUI:</strong></span>
                <span>{{ $sale->client->nit }}</span>
            </div>
            @endif
            @if($sale->client && $sale->client->ncr)
            <div class="sale-row">
                <span><strong>NCR:</strong></span>
                <span>{{ $sale->client->ncr }}</span>
            </div>
            @endif
            @if($sale->client && $sale->client->giro)
            <div class="sale-row">
                <span><strong>Giro:</strong></span>
                <span>{{ $sale->client->giro }}</span>
            </div>
            @endif
            @if($sale->client && $sale->client->email)
            <div class="sale-row">
                <span><strong>Email:</strong></span>
                <span>{{ $sale->client->email }}</span>
            </div>
            @endif
            @if($sale->client && $sale->client->phone)
            <div class="sale-row">
                <span><strong>Teléfono:</strong></span>
                <span>
                    @if(is_string($sale->client->phone))
                        {{ $sale->client->phone }}
                    @elseif(is_object($sale->client->phone))
                        @if($sale->client->phone->phone)
                            {{ $sale->client->phone->phone }}
                        @endif
                        @if($sale->client->phone->phone_fijo)
                            @if($sale->client->phone->phone) / @endif
                            {{ $sale->client->phone->phone_fijo }}
                        @endif
                    @else
                        {{ $sale->client->phone }}
                    @endif
                </span>
            </div>
            @endif
            <div class="sale-row">
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
                DOCUMENTO TRIBUTARIO ELECTRÓNICO
            </div>
            @if($hasDte && $sale->dte)
                @if($sale->dte->codigoGeneracion)
                <div class="dte-info">
                    <strong>Código Generación:</strong><br>
                    {{ $sale->dte->codigoGeneracion }}
                </div>
                @endif
                @if($sale->dte->id_doc)
                <div class="dte-info">
                    <strong>Número Control:</strong><br>
                    {{ $sale->dte->id_doc }}
                </div>
                @endif
                @if($sale->dte->Estado)
                <div class="dte-info">
                    <strong>Estado:</strong><br>
                    {{ $sale->dte->Estado }}
                </div>
                @endif
                @if($sale->dte->fhRecibido)
                <div class="dte-info">
                    <strong>Fecha Recepción:</strong><br>
                    {{ \Carbon\Carbon::parse($sale->dte->fhRecibido)->format('d/m/Y H:i:s') }}
                </div>
                @endif
                @if($sale->dte->selloRecibido)
                <div class="dte-info">
                    <strong>Sello Recepción:</strong><br>
                    {{ substr($sale->dte->selloRecibido, 0, 30) }}...
                </div>
                @endif
            @else
                <div class="dte-info">
                    <strong>Estado:</strong><br>
                    PENDIENTE DE PROCESAMIENTO
                </div>
                <div class="dte-info">
                    <strong>Información:</strong><br>
                    El documento será procesado por Hacienda
                </div>
            @endif
        </div>
        @endif

        <!-- Productos -->
        <div class="products">
            <div style="text-align: center; font-weight: bold; margin-bottom: 5px;">
                PRODUCTOS
            </div>

            @foreach($sale->details as $detail)
                <div class="product-item">
                    <div class="product-name">
                        {{ ($detail->ruta && trim($detail->ruta) !== '' && $detail->product && $detail->product->code == 'LAB') ? $detail->ruta : ($detail->product->name ?? 'Producto') }} {{ ($detail->product && $detail->product->code != 'LAB') ? ($detail->product->marca->name ?? '') : '' }}
                    </div>
                    <div class="product-details">
                        <span>{{ $detail->amountp ?? 1 }} x ${{ number_format($detail->priceunit ?? 0, 2) }}</span>
                        <span>${{ number_format(($detail->pricesale ?? 0) + ($detail->nosujeta ?? 0) + ($detail->exempt ?? 0), 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($subtotal, 2) }}</span>
            </div>

            @if($totalIva > 0)
                <div class="total-row">
                    <span>IVA (13%):</span>
                    <span>${{ number_format($totalIva, 2) }}</span>
                </div>
            @endif

            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span>${{ number_format($total, 2) }}</span>
            </div>
        </div>

        <!-- Código QR -->
        @if(isset($isFacturaOrCredito) && $isFacturaOrCredito)
        <div class="qr-section">
            @if($hasDte && $sale->dte && isset($qrCode) && $qrCode)
                <div style="text-align: center; margin: 6px 0;">
                    {!! $qrCode !!}
                </div>
                <div style="font-size: 8px; text-align: center; margin-top: 2px;">
                    DOCUMENTO TRIBUTARIO ELECTRÓNICO
                </div>
                <div style="font-size: 7px; text-align: center;">
                    Escanea para verificar
                </div>
            @else
                <div style="text-align: center; margin: 6px 0;">
                    <div style="width: 50px; height: 50px; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 7px;">
                        QR DTE
                    </div>
                </div>
                <div style="font-size: 8px; text-align: center; margin-top: 2px;">
                    DOCUMENTO TRIBUTARIO ELECTRÓNICO
                </div>
                <div style="font-size: 7px; text-align: center;">
                    Disponible después del procesamiento
                </div>
            @endif
        </div>
        @endif

        <!-- Pie del ticket -->
        <div class="footer">
            <div>¡Gracias por su compra!</div>
            <div style="margin-top: 6px; font-size: 11px;">
                {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
            </div>
            @if($sale->acuenta && $sale->acuenta != 'Venta al menudeo')
                <div style="margin-top: 6px; font-size: 11px;">
                    {{ $sale->acuenta }}
                </div>
            @endif
            @if(isset($isFacturaOrCredito) && $isFacturaOrCredito)
            <div style="margin-top: 6px; font-size: 10px;">
                <strong>DTE</strong> - Válido para efectos fiscales
            </div>
            @endif
        </div>
    </div>

    <script>
        console.log('🎫 Ticket DTE cargado para venta #{{ $sale->id }}');

        // Auto-impresión si está habilitada
        const autoprint = {{ $autoprint ? 'true' : 'false' }};
        let hasAutoprinted = false;

        if (autoprint) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    if (!hasAutoprinted) {
                        hasAutoprinted = true;
                        console.log('🖨️ Auto-imprimiendo ticket DTE...');
                        window.print();
                    }
                }, 1000);
            });
        } else {
            console.log('👁️ Modo vista - Auto-impresión deshabilitada');
        }

        // Función de prueba
        function testPrint() {
            const testContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Prueba 80mm DTE</title>
                    <style>
                        @page { width: 80mm; margin: 0; }
                        body { font-family: 'Courier New', monospace; font-size: 12px; margin: 5mm; text-align: center; }
                    </style>
                </head>
                <body>
                    <h3>PRUEBA DE IMPRESIÓN DTE</h3>
                    <p>══════════════════════</p>
                    <p>Ancho: 80mm</p>
                    <p>Ticket DTE: #{{ $sale->id }}</p>
                    <p>Fecha: ${new Date().toLocaleString()}</p>
                    <p>══════════════════════</p>
                    <p>✅ Si ve este texto bien,<br>su impresora está OK</p>
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 1000);
                        }
                    </script>
                </body>
                </html>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(testContent);
            printWindow.document.close();
        }

        // Cerrar después de imprimir (opcional)
        window.addEventListener('afterprint', function() {
            // Uncomment para cerrar automáticamente después de imprimir
            // setTimeout(function() { window.close(); }, 2000);
        });
    </script>
</body>
</html>
