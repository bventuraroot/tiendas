<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo con Foto de Contacto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .content {
            padding: 40px 30px;
        }
        .footer {
            background-color: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            @php
                // Ejemplo de cómo usar el helper para obtener el logo
                $logoBase64 = \App\Helpers\EmailContactPhotoHelper::getCompanyLogoBase64();
            @endphp

            @if($logoBase64)
                <div style="margin-bottom: 20px;">
                    <img src="{{ $logoBase64 }}"
                         alt="Logo Agroservicio Milagro de Dios"
                         style="max-width: 120px; max-height: 60px; height: auto; border-radius: 5px; background-color: rgba(255,255,255,0.1); padding: 5px;">
                </div>
            @endif

            <h1>🌾 Agroservicio Milagro de Dios</h1>
            <p>Tu aliado en el campo</p>
        </div>

        <div class="content">
            <h2>¡Hola!</h2>
            <p>Este es un ejemplo de cómo se vería un correo con la foto de contacto configurada.</p>

            <p>La foto de contacto aparecerá en:</p>
            <ul>
                <li>✅ El encabezado del correo (como imagen embebida)</li>
                <li>✅ Los headers personalizados (para compatibilidad con clientes de correo)</li>
                <li>✅ Como imagen de perfil en algunos clientes de correo</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>Agroservicio Milagro de Dios</strong></p>
            <p>Este correo fue enviado automáticamente</p>
        </div>
    </div>
</body>
</html>
