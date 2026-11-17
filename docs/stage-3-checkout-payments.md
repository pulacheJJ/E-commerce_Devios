# Etapa 3: checkout y arquitectura de pagos

## Flujo

1. `CheckoutService::crearPedido()` valida el carrito de la sesión, calcula importes y persiste
   el pedido en `creado` junto con snapshots inmutables.
2. `PedidoInventarioService::reservar()` bloquea pedido y variantes, descuenta stock una vez y
   cambia el pedido a `reservado`.
3. `PaymentService::start()` solo acepta pedidos reservados, reutiliza una clave idempotente,
   delega el inicio al proveedor registrado y cambia a `pendiente_pago`.
4. `PaymentWebhookController` recibe el evento sin depender de sesión. El proveedor lo verifica y
   `WebhookPaymentService` procesa una sola vez el identificador externo del evento.

La pantalla pública de pago es informativa cuando no existe un proveedor habilitado. No existe
una ruta ni JavaScript que actualice directamente un pedido o pago.

## Estados

- `creado` → `reservado`, `cancelado`
- `reservado` → `pendiente_pago`, `expirado`, `cancelado`
- `pendiente_pago` → `pagado`, `pago_fallido`, `expirado`, `cancelado`
- `pago_fallido` → `expirado`, `cancelado`
- `pagado`, `expirado`, `cancelado` y el histórico `enviado` son terminales.

Un pago pendiente conserva la reserva. Un rechazo definitivo cambia el pedido a `pago_fallido`
y devuelve inmediatamente el inventario reservado. La liberación usa `inventory_released_at`,
ocurre dentro de la transacción del webhook y es idempotente ante eventos repetidos, cancelación
o cualquier intento posterior de liberación.

## Idempotencia

- Pedido: `carrito_id` y `checkout_idempotency_key` únicos.
- Reserva: `stock_reserved_at`, transacción y bloqueos de filas.
- Pago: `clave_idempotencia` e `id_transaccion` únicos.
- Webhook: índice único por `proveedor` y `evento_externo_id`.
- Liberación: `inventory_released_at`.

## Proveedores

`PaymentProvider` define inicio y verificación del webhook con DTOs normalizados. El registro de
proveedores está vacío por defecto; no existe una pasarela falsa habilitada en la aplicación.
`Tests\Fakes\FakePaymentProvider` solo se registra explícitamente desde pruebas y usa un HMAC de
prueba controlado para simular eventos válidos e inválidos.

### Mercado Pago Checkout Pro en pruebas

Cuando `MERCADOPAGO_ENABLED=true`, el proveedor crea una preferencia idempotente con el Access
Token de prueba. El navegador no se redirige directamente a `sandbox_init_point`: primero abre
`/checkout/mercadopago/{numeroPedido}`, una ruta protegida por la sesión propietaria, y allí
inicializa el Wallet Brick oficial con MercadoPago.js v2, la Public Key y el `preferenceId`.

La Public Key y el identificador de preferencia pueden llegar al navegador; el Access Token y la
clave del webhook permanecen exclusivamente en el servidor. Los reintentos reutilizan el mismo
pago y la misma preferencia, por lo que no crean otro pedido ni vuelven a descontar stock.

La confirmación nunca depende del retorno del navegador. Solo el webhook firmado consulta el pago
en la API de Mercado Pago y puede aprobarlo, dejarlo pendiente o rechazarlo. El modo productivo
permanece bloqueado con `MERCADOPAGO_ALLOW_LIVE=false`.

Mercado Pago puede devolver `live_mode: true` en operaciones simuladas de Checkout Pro creadas con
sus credenciales y cuentas de prueba. Por ello ese campo se registra como dato informativo, pero no
decide el entorno. La aplicación controla el entorno mediante las credenciales de prueba, el modo
`test` y el bloqueo explícito de pagos productivos.

### Proveedor demostrativo

`SimulatedPaymentProvider` permite recorrer el flujo desde el navegador sin conectarse a servicios
externos. Está deshabilitado por defecto. En un entorno local o académico se habilita con:

```dotenv
PAYMENT_SIMULATOR_ENABLED=true
PAYMENT_SIMULATOR_SECRET=una-clave-demostrativa-aleatoria-de-al-menos-32-caracteres
```

La clave no debe versionarse. Si la opción está desactivada o la clave es corta, el proveedor no
se registra y sus rutas responden `404`. La pantalla advierte que es una simulación, no solicita
datos bancarios y ofrece escenarios aprobado, rechazado, pendiente y cancelado. Cada resultado se
firma con HMAC-SHA256, se verifica como webhook y se procesa mediante `WebhookPaymentService`; el
controlador del simulador nunca escribe estados directamente.

Para integrar una pasarela real se necesita su documentación oficial para implementar:

- creación idempotente de transacciones;
- identificador externo;
- autenticación y firma oficial del webhook;
- estados externos y su normalización;
- moneda e importes admitidos;
- política de reintentos y timeouts.

Las credenciales deberán vivir fuera del repositorio. Ningún dato de tarjeta debe persistirse.

## Envío e importes

`DatabaseShippingCostCalculator` obtiene la provincia a través del distrito validado. Para entrega
a domicilio aplica primero la tarifa provincial activa y luego la predeterminada activa. Si no
encuentra ninguna, bloquea el checkout; no asume envío gratuito. Retiro en tienda cuesta cero.
Los importes se calculan en centavos y se guardan como `DECIMAL(12,2)`:

`total = subtotal - descuento_total + costo_envio`

Los detalles guardan producto, SKU, variante, precio base, descuento, precio final, cantidad y
subtotal de línea, sin depender posteriormente del catálogo.

La tarifa aplicada queda copiada en `pedidos.costo_envio`, por lo que editar una tarifa no altera
pedidos históricos. El panel `/admin/envios` solo permite crear, editar y activar/desactivar; no
ofrece eliminación física. Sus permisos son `envios.ver`, `envios.crear`, `envios.editar` y
`envios.cambiar_estado`.
