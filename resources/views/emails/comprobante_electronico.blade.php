<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title></title>
    <!--[if mso]>
  <style>
    table {border-collapse:collapse;border-spacing:0;border:none;margin:0;}
    div, td {padding:0;}
    div {margin:0 !important;}
	</style>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->
    <style>
        table,
        td,
        div,
        h1,
        p {
            font-family: Arial, sans-serif;
        }

        @media screen and (max-width: 530px) {
            .unsub {
                display: block;
                padding: 8px;
                margin-top: 14px;
                border-radius: 6px;
                background-color: #555555;
                text-decoration: none !important;
                font-weight: bold;
            }

            .col-lge {
                max-width: 100% !important;
            }
        }

        @media screen and (min-width: 531px) {
            .col-sml {
                max-width: 27% !important;
            }

            .col-lge {
                max-width: 73% !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;background-color:#939297;">
    <div role="article" aria-roledescription="email" lang="en"
        style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#939297;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:0;">
                    <!--[if mso]>
          <table role="presentation" align="center" style="width:600px;">
          <tr>
          <td>
          <![endif]-->
                    <table role="presentation"
                        style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">
                        <tr>
                            <td style="padding:40px 30px 30px 30px;text-align:center;font-size:24px;font-weight:bold;background-color:#ffffff;">
                                @php
                                    $logoPath = public_path('assets/img/logo/logo.png');
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
                                    <div style="margin-bottom: 20px;">
                                        <img src="{{ $logoBase64 }}"
                                             alt="Logo Empresa"
                                             style="max-width: 150px; max-height: 80px; height: auto;">
                                    </div>
                                @endif

                                <a href="#" style="text-decoration:none;">Comprobante Electrónico</a>
                            </td>
                        </tr>
                        <tr>

                            <td style="padding:30px;background-color:#ffffff;">
                                <h3
                                    style="margin-top:0;margin-bottom:16px;font-size:18px;line-height:32px;font-weight:bold;letter-spacing:-0.02em;">
                                    Estimado Cliente: {{$data["nombre"] ?? 'Cliente'}}</h3>
                                <p style="margin:0;"><br>
                                Adjunto encontrará su documento fiscal electrónico, por la compra realizada de acuerdo al siguiente detalle.
                                <br>
                                @if(isset($data["json"]) && is_object($data["json"]))
                                    @if(isset($data["json"]->identificacion))
                                        <p><strong>Número de Control:</strong> {{$data["json"]->identificacion->numeroControl ?? 'N/A'}}</p>
                                        <p><strong>Código de Generación:</strong> {{$data["json"]->identificacion->codigoGeneracion ?? 'N/A'}}</p>
                                        <p><strong>Fecha de Emisión:</strong> {{$data["json"]->identificacion->fecEmi ?? 'N/A'}}</p>
                                    @endif
                                    @if(isset($data["json"]->resumen))
                                        <p><strong>Monto de Operación:</strong> ${{number_format($data["json"]->resumen->montoTotalOperacion ?? 0, 2)}}</p>
                                    @endif
                                    <p><strong>Sello Recibido:</strong> {{$data["json"]->selloRecibido ?? 'N/A'}}</p>
                                @elseif(isset($data["json"]) && is_array($data["json"]))
                                    @if(isset($data["json"]["identificacion"]))
                                        <p><strong>Número de Control:</strong> {{$data["json"]["identificacion"]["numeroControl"] ?? 'N/A'}}</p>
                                        <p><strong>Código de Generación:</strong> {{$data["json"]["identificacion"]["codigoGeneracion"] ?? 'N/A'}}</p>
                                        <p><strong>Fecha de Emisión:</strong> {{$data["json"]["identificacion"]["fecEmi"] ?? 'N/A'}}</p>
                                    @endif
                                    @if(isset($data["json"]["resumen"]))
                                        <p><strong>Monto de Operación:</strong> ${{number_format($data["json"]["resumen"]["montoTotalOperacion"] ?? 0, 2)}}</p>
                                    @endif
                                    <p><strong>Sello Recibido:</strong> {{$data["json"]["selloRecibido"] ?? 'N/A'}}</p>
                                @else
                                    <p><strong>Documento:</strong> {{$numeroFactura ?? 'N/A'}}</p>
                                    <p><strong>Fecha de Emisión:</strong> {{now()->format('d/m/Y')}}</p>
                                @endif
                                <br>

                               </p>
                            </td>
                        </tr>


                        <tr>
                            <td>
                                <br>
                                <p style="color:red;">Este correo fue generado automáticamente favor no contestar, si tiene alguna duda o consulta comunicarse con proveedor</p>
                                <br>
                            </td>
                        </tr>

                        <tr>
                            <td
                                style="padding:30px;text-align:center;font-size:12px;background-color:#404040;color:#cccccc;">
                                <p style="margin:0 0 8px 0;"><a href="#"
                                        style="text-decoration:none;"><img
                                            src="https://assets.codepen.io/210284/facebook_1.png" width="40" height="40"
                                            alt="f" style="display:inline-block;color:#cccccc;"></a> <a
                                        href="#" style="text-decoration:none;"><img
                                            src="https://assets.codepen.io/210284/twitter_1.png" width="40" height="40"
                                            alt="t" style="display:inline-block;color:#cccccc;"></a></p>
                                <p style="margin:0;font-size:14px;line-height:20px;">&reg; <a  href="www.emprecam.com">Power by Inet 4.0</a> &copy; 2024<br></p>
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]>
          </td>
          </tr>
          </table>
          <![endif]-->
                </td>
            </tr>
        </table>
    </div>
</body>

</html>







