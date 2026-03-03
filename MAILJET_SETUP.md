# Configuración de Mailjet para Notificaciones de Órdenes

## Descripción
Este sistema envía correos electrónicos automáticos a los clientes cuando:
- Se crea una nueva orden
- El estado de una orden cambia (pagado, enviado, entregado, etc.)
- Se cancela una orden

## Configuración

### 1. Obtener credenciales de Mailjet

1. Crea una cuenta en [Mailjet](https://www.mailjet.com/)
2. Ve a **Account Settings** > **API Keys**
3. Copia tu **API Key** y **Secret Key**

### 2. Configurar variables de entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Mailjet Configuration
MAILJET_API_KEY=tu_api_key_aqui
MAILJET_API_SECRET=tu_secret_key_aqui
MAILJET_FROM_EMAIL=noreply@tudominio.com
MAILJET_FROM_NAME="Tu Tienda"
```

### 3. Verificar el remitente en Mailjet

Antes de enviar correos en producción, debes verificar tu dominio o email en Mailjet:

1. Ve a **Account Settings** > **Sender Addresses & Domains**
2. Agrega y verifica tu email o dominio
3. Usa ese email verificado en `MAILJET_FROM_EMAIL`

## Funcionamiento

### Correo de Confirmación de Orden
Se envía automáticamente cuando se crea una orden nueva. Incluye:
- Número de orden
- Detalles de los productos
- Cantidades y precios
- Total de la orden
- Estado actual

### Correo de Actualización de Estado
Se envía cuando un administrador cambia el estado de una orden. Incluye:
- Número de orden
- Estado anterior
- Estado nuevo
- Mensaje personalizado según el estado
- Total de la orden

### Estados soportados
- `pending` - Pendiente
- `paid` - Pagado
- `preparing` - Preparando
- `shipped` - Enviado
- `in_transit` - En tránsito
- `delivered` - Entregado
- `cancelled` - Cancelado
- `failed` - Fallido

## Manejo de Errores

Si el envío de correo falla:
- Se registra en los logs (`storage/logs/laravel.log`)
- La operación principal (crear orden o actualizar estado) NO falla
- El sistema continúa funcionando normalmente

## Testing

Para probar el envío de correos en desarrollo:

1. Configura tus credenciales de Mailjet en `.env`
2. Crea una orden de prueba
3. Verifica que llegue el correo de confirmación
4. Actualiza el estado de la orden como admin
5. Verifica que llegue el correo de actualización

## Logs

Todos los eventos de correo se registran en los logs:
- Correos enviados exitosamente
- Errores al enviar correos
- Detalles de destinatario y asunto

Revisa los logs en: `storage/logs/laravel.log`

## Personalización

Para personalizar los correos, edita el archivo:
`app/Services/MailjetService.php`

Puedes modificar:
- Plantillas HTML
- Mensajes según el estado
- Etiquetas de estado
- Estructura del correo

## Producción

En producción, asegúrate de:
1. Usar credenciales de producción de Mailjet
2. Verificar tu dominio en Mailjet
3. Configurar SPF y DKIM para mejor deliverability
4. Monitorear los logs de envío
5. Revisar las estadísticas en el dashboard de Mailjet
