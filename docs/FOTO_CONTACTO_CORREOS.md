# 📧 Configuración de Foto de Contacto en Correos

Este documento explica las diferentes formas de configurar una foto de contacto que aparezca en los correos enviados desde el sistema.

## 🎯 Métodos Disponibles

### **Método 1: Configuración en el Cliente de Correo (Recomendado)**

Esta es la forma más efectiva y funciona con todos los clientes de correo:

#### **Para Gmail:**
1. Ve a Gmail → Configuración (⚙️) → Ver toda la configuración
2. Pestaña "Cuentas e importación"
3. En "Enviar correo como" → Editar información
4. Sube una foto de perfil (logo de tu empresa)

#### **Para Outlook:**
1. Configuración → Perfil → Cambiar foto
2. Sube el logo de tu empresa

#### **Para otros clientes:**
- **Thunderbird**: Herramientas → Configuración de cuenta → Identidad → Adjuntar mi tarjeta de visita
- **Apple Mail**: Preferencias → Cuentas → Seleccionar cuenta → Información de la cuenta

### **Método 2: Implementación Técnica (Ya Implementado)**

El sistema ya incluye implementación técnica que agrega headers personalizados a los correos:

#### **Características:**
- ✅ Headers personalizados (`X-Contact-Photo`, `X-Avatar`, `X-Profile-Image`)
- ✅ Logo embebido en base64
- ✅ Compatibilidad con múltiples clientes de correo
- ✅ Fallback automático si el logo no existe
- ✅ Límite de tamaño (2MB máximo)

#### **Archivos Modificados:**
- `app/Mail/QuotationMail.php`
- `app/Mail/EnviarFacturaOffline.php`
- `app/Mail/EnviarCorreo.php`

#### **Helpers Creados:**
- `app/Helpers/EmailContactPhotoHelper.php`
- `app/Helpers/EmailEmbeddedImageHelper.php`

### **Método 3: Imagen Embebida en Plantillas**

Las plantillas de correo ya incluyen el logo embebido:

#### **Plantillas Actualizadas:**
- `resources/views/emails/quotation.blade.php`
- `resources/views/emails/factura-offline.blade.php`
- `resources/views/emails/comprobante_electronico.blade.php`
- `resources/views/emails/reset-password.blade.php`
- `resources/views/emails/nuevo_cliente.blade.php`
- `resources/views/emails/contact.blade.php`

## 🧪 Pruebas

### **Comando de Prueba:**
```bash
php artisan email:test-contact-photo tu-email@ejemplo.com
```

### **Verificación Manual:**
1. Envía un correo desde el sistema
2. Revisa en diferentes clientes de correo:
   - Gmail
   - Outlook
   - Apple Mail
   - Thunderbird

## 📁 Estructura de Archivos

```
public/assets/img/logo/
├── logo.png                    # Logo principal (usado por defecto)
├── logogrises.png             # Logo alternativo
└── 1754424991_nuevo logo recargado2025.png

app/Helpers/
├── EmailContactPhotoHelper.php    # Helper para headers de foto
└── EmailEmbeddedImageHelper.php   # Helper para imágenes embebidas

app/Console/Commands/
└── TestEmailWithContactPhoto.php  # Comando de prueba
```

## ⚙️ Configuración

### **Variables de Entorno:**
```env
MAIL_FROM_ADDRESS=tu-email@agroserviciomilagrodedios.com
MAIL_FROM_NAME=Agroservicio Milagro de Dios
```

### **Ruta del Logo:**
Por defecto usa: `public/assets/img/logo/logo.png`

## 🔧 Personalización

### **Cambiar el Logo:**
1. Reemplaza el archivo `public/assets/img/logo/logo.png`
2. O modifica la ruta en los helpers

### **Usar Logo Diferente:**
```php
// En las clases de correo
EmailContactPhotoHelper::addContactPhotoHeaders($message, '/ruta/personalizada/logo.png');
```

## 📊 Compatibilidad

| Cliente de Correo | Headers Personalizados | Imagen Embebida | Configuración Manual |
|-------------------|----------------------|-----------------|-------------------|
| Gmail             | ⚠️ Limitado          | ✅ Sí           | ✅ Recomendado    |
| Outlook           | ⚠️ Limitado          | ✅ Sí           | ✅ Recomendado    |
| Apple Mail        | ❌ No                | ✅ Sí           | ✅ Recomendado    |
| Thunderbird       | ❌ No                | ✅ Sí           | ✅ Recomendado    |
| Yahoo Mail        | ❌ No                | ✅ Sí           | ✅ Recomendado    |

## 🚨 Limitaciones

1. **Headers Personalizados**: No todos los clientes de correo respetan headers personalizados
2. **Tamaño de Imagen**: Máximo 2MB para evitar problemas de rendimiento
3. **Formato**: Solo se soporta PNG por defecto
4. **Cliente de Correo**: La compatibilidad depende del cliente del destinatario

## 💡 Recomendaciones

1. **Usa el Método 1** (configuración manual) como principal
2. **Mantén el Método 2** (implementación técnica) como respaldo
3. **Prueba en múltiples clientes** antes de usar en producción
4. **Optimiza el logo** para que sea pequeño pero visible
5. **Usa formato PNG** para mejor compatibilidad

## 🔍 Troubleshooting

### **El logo no aparece:**
1. Verifica que el archivo existe en `public/assets/img/logo/logo.png`
2. Verifica que el archivo es menor a 2MB
3. Prueba con el comando de prueba
4. Revisa los logs de Laravel

### **Error al enviar correo:**
1. Verifica la configuración de correo en `.env`
2. Revisa que el servidor SMTP esté funcionando
3. Verifica los permisos de archivos

### **Logo aparece distorsionado:**
1. Usa una imagen cuadrada (ej: 200x200px)
2. Optimiza el tamaño del archivo
3. Usa formato PNG con transparencia

## 📞 Soporte

Si tienes problemas con la configuración de foto de contacto, revisa:
1. Los logs de Laravel en `storage/logs/`
2. La configuración de correo en `config/mail.php`
3. Los permisos de archivos en `public/assets/img/logo/`
