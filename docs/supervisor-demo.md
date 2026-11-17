# Demostracion para supervisores

La demostracion presenta el flujo completo del ecommerce sin utilizar dinero
real. Mercado Pago Checkout Pro funciona como pasarela principal y el simulador
interno permanece disponible como contingencia.

## Alcance de la demostracion

El recorrido permite verificar:

1. consulta del catalogo y seleccion de variantes;
2. carrito con control de stock;
3. datos de entrega y tarifa de envio;
4. creacion de una reserva de inventario;
5. redireccion a Mercado Pago;
6. aprobacion o rechazo de un pago de prueba;
7. confirmacion del pedido y conciliacion del importe;
8. devolucion de stock ante rechazo, cancelacion o expiracion.

## Mercado Pago en modo de prueba

La configuracion se mantiene como secreto de ejecucion:

```env
MERCADOPAGO_ENABLED=true
MERCADOPAGO_MODE=test
MERCADOPAGO_ALLOW_LIVE=false
MERCADOPAGO_TEST_PUBLIC_KEY=VALOR_PRIVADO
MERCADOPAGO_TEST_ACCESS_TOKEN=VALOR_PRIVADO
MERCADOPAGO_TEST_WEBHOOK_SECRET=VALOR_PRIVADO
```

La notificacion se registra con la URL publica del despliegue:

```text
https://tienda.ejemplo.com/webhooks/pagos/mercadopago
```

Las credenciales del comprador de prueba se entregan por separado. No forman
parte de capturas, documentos, repositorios ni de la imagen publicada.

Los retornos desde Checkout Pro no acreditan un pedido por confianza en los
parametros del navegador. DEVIOZ consulta el pago con el proveedor y valida la
referencia externa, el importe y la moneda. El webhook realiza la misma
conciliacion de forma idempotente.

## Catalogo y administrador de prueba

La inicializacion opcional incorpora productos, variantes, stock, promocion,
slider e imagenes suficientes para completar el recorrido. El rol `admin`
conserva todos los permisos de la Etapa 2.

La direccion de acceso depende de la infraestructura:

- hostname unico: `https://HOST/admin`;
- dominio separado: `https://admin.DOMINIO/login`.

El correo y la contrasena se comunican de forma privada. La imagen de Docker Hub
no contiene credenciales predeterminadas.

## Simulador de contingencia

Ante una interrupcion de Internet o indisponibilidad del sandbox externo, el
simulador puede habilitarse sin pagos reales:

```env
MERCADOPAGO_ENABLED=false
PAYMENT_SIMULATOR_ENABLED=true
PAYMENT_SIMULATOR_SECRET=SECRETO_ALEATORIO_DE_32_CARACTERES_O_MAS
```

Este respaldo permite demostrar estados aprobados, pendientes, rechazados y
cancelados, incluida la idempotencia y la devolucion de inventario.

## Controles previos

- `MERCADOPAGO_ALLOW_LIVE=false`.
- credenciales de Mercado Pago correspondientes al ambiente de prueba;
- `APP_DEBUG=false`;
- `APP_URL` usa el hostname HTTPS definitivo y `FORCE_HTTPS=true`;
- URL del webhook actualizada al hostname vigente;
- scheduler activo;
- administrador creado desde secretos;
- almacenamiento persistente montado cuando se probaran cargas de imagenes.
- Cloudinary configurado cuando el proveedor utiliza disco efimero.
