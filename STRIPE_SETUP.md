# Configuración de Stripe para Pagos

## Descripción
Stripe es una plataforma de pagos que permite aceptar tarjetas de crédito/débito, Apple Pay, Google Pay y otros métodos de pago de forma segura.

## Características
- Checkout Session con interfaz pre-construida
- Soporte para múltiples métodos de pago
- Pagos en USD
- Webhooks para confirmación automática de pagos
- Manejo de sesiones expiradas y pagos fallidos
- Envío automático de correos al cliente

## Configuración

### 1. Crear cuenta en Stripe

1. Regístrate en [Stripe](https://stripe.com/)
2. Completa la verificación de tu cuenta
3. Accede al Dashboard

### 2. Obtener credenciales

#### Modo Test (Desarrollo)
1. Ve a **Developers** > **API keys**
2. Copia la **Publishable key** (comienza con `pk_test_`)
3. Copia la **Secret key** (comienza con `sk_test_`)

#### Modo Live (Producción)
1. Activa tu cuenta completando la información requerida
2. Ve a **Developers** > **API keys**
3. Activa "View live data"
4. Copia la **Publishable key** (comienza con `pk_live_`)
5. Copia la **Secret key** (comienza con `sk_live_`)

### 3. Configurar Webhook

1. Ve a **Developers** > **Webhooks**
2. Click en **Add endpoint**
3. URL del endpoint: `https://tu-dominio.com/api/webhooks/stripe`
4. Selecciona los eventos:
   - `checkout.session.completed`
   - `checkout.session.expired`
   - `payment_intent.payment_failed`
5. Copia el **Signing secret** (comienza con `whsec_`)

### 4. Configurar variables de entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Stripe Configuration
STRIPE_SECRET_KEY=sk_test_tu_secret_key_aqui
STRIPE_PUBLISHABLE_KEY=pk_test_tu_publishable_key_aqui
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui
```

## Uso

### Crear una orden con Stripe

```bash
POST /api/orders
```

```json
{
  "address_id": "uuid-del-address",
  "items": [
    {
      "product_id": "uuid-del-producto",
      "product_variant_id": "uuid-de-la-variante",
      "quantity": 1,
      "customization_name": "MESSI",
      "customization_number": "10"
    }
  ],
  "payment_gateway": "stripe",
  "redirect_url": "https://tu-frontend.com/order-confirmation",
  "cancel_url": "https://tu-frontend.com/checkout"
}
```

### Respuesta

```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order": { ... },
    "payment_gateway": "stripe",
    "payment_data": {
      "session_id": "cs_test_...",
      "payment_status": "unpaid",
      "amount_total": 5000,
      "currency": "usd",
      "url": "https://checkout.stripe.com/c/pay/cs_test_..."
    },
    "payment_urls": {
      "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_...",
      "session_id": "cs_test_..."
    },
    "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_..."
  }
}
```

### Flujo de pago

1. **Cliente crea orden** → Backend crea Checkout Session en Stripe
2. **Backend retorna checkout_url** → Frontend redirige al cliente
3. **Cliente completa pago** → Stripe procesa el pago
4. **Stripe envía webhook** → Backend actualiza estado de la orden a "paid"
5. **Cliente es redirigido** → Frontend muestra confirmación
6. **Correo enviado** → Cliente recibe confirmación por email

## Webhooks

### Eventos procesados

#### checkout.session.completed
Se dispara cuando el cliente completa el pago exitosamente.
- Actualiza el estado de la orden a `paid`
- Registra información del pago
- Envía correo de confirmación al cliente

#### checkout.session.expired
Se dispara cuando la sesión de checkout expira (24 horas).
- Actualiza el estado de la orden a `failed`
- Registra la expiración

#### payment_intent.payment_failed
Se dispara cuando el pago falla.
- Actualiza el estado de la orden a `failed`
- Registra el motivo del fallo
- Envía correo de pago fallido al cliente

### URL del Webhook
```
POST https://tu-dominio.com/api/webhooks/stripe
```

### Testing de Webhooks

Stripe CLI permite probar webhooks localmente:

```bash
# Instalar Stripe CLI
# https://stripe.com/docs/stripe-cli

# Login
stripe login

# Escuchar webhooks
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# Disparar evento de prueba
stripe trigger checkout.session.completed
```

## Tarjetas de prueba

En modo test, usa estas tarjetas:

### Pago exitoso
- Número: `4242 4242 4242 4242`
- Fecha: Cualquier fecha futura
- CVC: Cualquier 3 dígitos
- ZIP: Cualquier 5 dígitos

### Pago rechazado
- Número: `4000 0000 0000 0002`

### Requiere autenticación 3D Secure
- Número: `4000 0025 0000 3155`

[Más tarjetas de prueba](https://stripe.com/docs/testing)

## Monedas soportadas

Actualmente configurado para USD. Para agregar más monedas:

1. Modifica `StripeService::formatLineItems()` para aceptar diferentes monedas
2. Actualiza la lógica en `OrderController::store()` para determinar la moneda según el país

## Seguridad

- Las claves secretas nunca se exponen al frontend
- Los webhooks verifican la firma para prevenir falsificaciones
- Los pagos se procesan completamente en Stripe (PCI compliant)
- No almacenamos información de tarjetas

## Logs

Todos los eventos se registran en `storage/logs/laravel.log`:
- Creación de checkout sessions
- Webhooks recibidos
- Actualizaciones de estado
- Errores de procesamiento

## Producción

Antes de ir a producción:

1. ✅ Completa la verificación de tu cuenta en Stripe
2. ✅ Cambia a las credenciales de producción (pk_live_, sk_live_)
3. ✅ Configura el webhook en producción con la URL real
4. ✅ Actualiza STRIPE_WEBHOOK_SECRET con el secret de producción
5. ✅ Prueba el flujo completo con tarjetas reales
6. ✅ Configura notificaciones de Stripe para monitorear pagos
7. ✅ Revisa las tarifas de Stripe para tu región

## Tarifas de Stripe

- Tarjeta nacional: 2.9% + $0.30 USD por transacción exitosa
- Tarjeta internacional: 3.9% + $0.30 USD por transacción exitosa
- Sin costos de setup o mensuales
- [Más información sobre precios](https://stripe.com/pricing)

## Soporte

- [Documentación de Stripe](https://stripe.com/docs)
- [API Reference](https://stripe.com/docs/api)
- [Checkout Session](https://stripe.com/docs/payments/checkout)
- [Webhooks](https://stripe.com/docs/webhooks)
