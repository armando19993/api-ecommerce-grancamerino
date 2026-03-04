# Instrucciones para Ejecutar Migraciones

## Migraciones Pendientes

Se han creado dos nuevas migraciones para soportar Stripe y el estado `failed`:

1. `2026_03_04_000001_add_stripe_to_payment_gateway_enum.php`
   - Agrega `stripe` como opción válida en el campo `payment_gateway`

2. `2026_03_04_000002_add_failed_to_order_status_enum.php`
   - Agrega `failed` como opción válida en el campo `status`

## Ejecutar Migraciones

### Opción 1: Ejecutar todas las migraciones pendientes

```bash
php artisan migrate
```

### Opción 2: Ejecutar migraciones específicas

```bash
php artisan migrate --path=/database/migrations/2026_03_04_000001_add_stripe_to_payment_gateway_enum.php
php artisan migrate --path=/database/migrations/2026_03_04_000002_add_failed_to_order_status_enum.php
```

### Opción 3: Ejecutar SQL directamente (si prefieres)

Puedes ejecutar estos comandos SQL directamente en tu base de datos:

```sql
-- Agregar 'stripe' al enum de payment_gateway
ALTER TABLE `orders` 
MODIFY COLUMN `payment_gateway` 
ENUM('wompi', 'paymentnow', 'stripe', 'cash') NULL;

-- Agregar 'failed' al enum de status
ALTER TABLE `orders` 
MODIFY COLUMN `status` 
ENUM('pending', 'paid', 'preparing', 'shipped', 'in_transit', 'delivered', 'cancelled', 'failed') 
DEFAULT 'pending';
```

## Verificar Migraciones

Después de ejecutar las migraciones, verifica que se aplicaron correctamente:

```bash
php artisan migrate:status
```

## Revertir Migraciones (si es necesario)

Si necesitas revertir estas migraciones:

```bash
php artisan migrate:rollback --step=2
```

## Notas Importantes

- ⚠️ Estas migraciones modifican la estructura de la tabla `orders`
- ⚠️ Asegúrate de hacer un backup de tu base de datos antes de ejecutar
- ⚠️ En producción, ejecuta las migraciones durante una ventana de mantenimiento
- ✅ Estas migraciones son seguras y no afectan datos existentes
- ✅ Solo agregan nuevas opciones a los enums existentes

## Después de Migrar

Una vez ejecutadas las migraciones, podrás:
- Crear órdenes con `payment_gateway: 'stripe'`
- Los webhooks podrán actualizar órdenes al estado `failed`
- El sistema funcionará correctamente con Stripe

## Troubleshooting

### Error: "SQLSTATE[01000]: Warning: 1265 Data truncated"
Este error significa que las migraciones aún no se han ejecutado. Ejecuta:
```bash
php artisan migrate
```

### Error: "Migration not found"
Asegúrate de que los archivos de migración existen en `database/migrations/`

### Error: "Nothing to migrate"
Las migraciones ya fueron ejecutadas. Verifica con:
```bash
php artisan migrate:status
```
