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
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
5. Click en **Add endpoint**
6. Una vez creado, click en el endpoint
7. En la sección **Signing secret**, click en **Reveal**
8. Copia el **Signing secret** (comienza con `whsec_`)

**IMPORTANTE**: 
- El webhook secret es diferente para cada endpoint
- El webhook secret de la CLI de Stripe (`stripe listen`) es diferente al del Dashboard
- Nunca uses el webhook secret de test en producción o viceversa

### 4. Testing Local con Stripe CLI (Opcional)

Para probar webhooks localmente:

```bash
# Instalar Stripe CLI
# https://stripe.com/docs/stripe-cli

# Login
stripe login

# Escuchar webhooks y reenviarlos a tu servidor local
stripe listen --forward-to http://localhost:8000/api/webhooks/stripe

# Esto mostrará un webhook secret temporal (whsec_...)
# Usa este secret en tu .env local
```

El CLI mostrará algo como:
```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx
```

Usa ese secret en tu `.env` local para testing.

### 4. Configurar variables de entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Stripe Configuration
STRIPE_SECRET_KEY=sk_test_tu_secret_key_aqui
STRIPE_PUBLISHABLE_KEY=pk_test_tu_publishable_key_aqui
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui
```

**IMPORTANTE**: 
- Asegúrate de copiar el webhook secret completo, incluyendo el prefijo `whsec_`
- El webhook secret debe coincidir con el endpoint configurado en el Dashboard
- Si usas Stripe CLI para testing local, usa el secret que muestra la CLI
- Nunca compartas tu webhook secret públicamente

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

#### payment_intent.succeeded
Se dispara cuando el pago se procesa exitosamente (alternativa a checkout.session.completed).
- Actualiza el estado de la orden a `paid`
- Registra información del payment intent
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


## Troubleshooting

### Error: "Invalid signature"

Este error significa que la verificación de la firma del webhook falló. Posibles causas:

1. **Webhook secret incorrecto**
   - Verifica que `STRIPE_WEBHOOK_SECRET` en `.env` sea correcto
   - Debe comenzar con `whsec_`
   - Copia el secret desde el Dashboard de Stripe (Developers > Webhooks > tu endpoint > Reveal signing secret)
   - Si usas Stripe CLI, usa el secret que muestra `stripe listen`

2. **Endpoint incorrecto**
   - Asegúrate de que la URL del webhook en Stripe apunte a `https://tu-dominio.com/api/webhooks/stripe`
   - Verifica que no haya middleware que modifique el request body antes del webhook

3. **Request body modificado**
   - El body debe ser el raw request body sin modificaciones
   - Laravel lo maneja correctamente por defecto
   - No uses `$request->all()` o `$request->json()` antes de verificar la firma

4. **Timestamp muy antiguo**
   - Stripe rechaza eventos con más de 5 minutos de antigüedad
   - Verifica que la hora del servidor sea correcta

### Verificar configuración

Revisa los logs en `storage/logs/laravel.log` para ver detalles de la verificación:

```
Stripe: Signature verification details
- timestamp: 1772634780
- expected_signature: abc123...
- computed_signature: abc123...
- signatures_match: true/false
```

Si `signatures_match: false`, el webhook secret es incorrecto.

### Testing sin verificación (solo desarrollo)

Si necesitas probar sin verificación (NO USAR EN PRODUCCIÓN):

1. Deja `STRIPE_WEBHOOK_SECRET` vacío en `.env`
2. Asegúrate de que `APP_ENV=local`
3. El webhook aceptará requests sin verificar la firma

**⚠️ NUNCA hagas esto en producción**

### Verificar que el webhook secret es correcto

1. Ve a Stripe Dashboard > Developers > Webhooks
2. Click en tu endpoint
3. Click en "Reveal" en la sección "Signing secret"
4. Copia el valor completo (debe empezar con `whsec_`)
5. Pégalo en tu `.env` como `STRIPE_WEBHOOK_SECRET=whsec_...`
6. Reinicia tu servidor para que cargue la nueva variable

### Algoritmo de verificación de Stripe

Stripe usa HMAC-SHA256 para firmar webhooks:

1. Construye el string firmado: `timestamp.payload`
2. Calcula HMAC-SHA256 del string usando el webhook secret
3. Compara la firma calculada con la firma en el header `Stripe-Signature`

El header `Stripe-Signature` tiene este formato:
```
t=1492774577,v1=5257a869e7ecebeda32affa62cdca3fa51cad7e77a0e56ff536d0ce8e108d8bd
```

Donde:
- `t` = timestamp Unix cuando se generó el evento
- `v1` = firma HMAC-SHA256 del string `timestamp.payload`
