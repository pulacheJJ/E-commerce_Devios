# Entrega de la imagen mediante Docker Hub

La imagen de DEVIOZ contiene el codigo y las dependencias de ejecucion, pero no
contiene `.env`, claves de Laravel, credenciales administrativas, accesos a
TiDB, Cloudinary ni Mercado Pago. La empresa puede ejecutar exactamente la
misma version en Render o en cualquier plataforma compatible con contenedores.

Esta entrega esta preparada para demostracion con Mercado Pago en modo de
prueba. Antes de habilitar trafico productivo o cobros reales se debe actualizar
la base Laravel 10 a una version con soporte de seguridad vigente y repetir
`composer audit`; el entorno local actual usa PHP 8.1 y esa actualizacion del
framework requiere primero PHP 8.2 o superior.

## Imagen que se entrega

Se construye para `linux/amd64` y se publica con una etiqueta inmutable:

```bash
docker build --platform linux/amd64 -t USUARIO/devioz-ecommerce:3.0.0 .
docker login
docker push USUARIO/devioz-ecommerce:3.0.0
```

El push se realiza solamente cuando la empresa solicita la entrega. No se usa
unicamente `latest`, porque una version numerada permite repetir la demostracion
y volver a una imagen anterior.

## Responsabilidades de quien despliega

La empresa debe suministrar en su gestor de secretos:

- `APP_KEY` y el hostname HTTPS definitivo en `APP_URL`;
- conexion MySQL o TiDB y su certificado CA;
- credenciales de Cloudinary para las imagenes persistentes;
- usuario y clave iniciales del administrador, si la base esta vacia;
- credenciales de prueba y secreto de webhook de Mercado Pago.

La plantilla completa se encuentra en `.env.production.example`. Ningun valor
privado debe agregarse a Docker Hub, a una captura ni al comando de construccion.

## Contrato seguro para la demostracion

Estas opciones deben conservarse:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://HOST_PUBLICO
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

MEDIA_DISK=cloudinary

MERCADOPAGO_ENABLED=true
MERCADOPAGO_MODE=test
MERCADOPAGO_ALLOW_LIVE=false
```

`MERCADOPAGO_ALLOW_LIVE=false` mantiene bloqueados los cobros productivos aun
cuando la aplicacion esta desplegada publicamente. El webhook se registra en:

```text
https://HOST_PUBLICO/webhooks/pagos/mercadopago
```

## Primera ejecucion

Para una base vacia de demostracion se habilitan temporalmente o durante el
arranque idempotente:

```env
RUN_MIGRATIONS=true
RUN_DEMO_SEEDERS=true
SEED_ADMIN_NAME=VALOR_PRIVADO
SEED_ADMIN_EMAIL=VALOR_PRIVADO
SEED_ADMIN_PASSWORD=VALOR_PRIVADO
```

Las migraciones son incrementales y el seeder conserva los registros demo que
ya existen. En una base empresarial con datos propios,
`RUN_DEMO_SEEDERS=false`.

La expiracion de reservas requiere `php artisan schedule:work`. En una demo de
una sola instancia puede usarse `RUN_SCHEDULER=true`; en un despliegue escalado
se ejecuta una segunda instancia de la imagen como worker y se mantiene esa
variable en `false` en el servicio web.

## Puertos, proxy y HTTPS

El contenedor escucha `PORT` cuando el proveedor la define y utiliza el puerto
80 en caso contrario. El certificado se instala en el proxy del proveedor, no
dentro de la imagen. El proxy debe enviar `X-Forwarded-Proto=https`; Laravel lo
reconoce, genera enlaces seguros y devuelve HSTS.

Render proporciona certificado TLS y redireccion HTTP a HTTPS. Para otro
proveedor se debe habilitar su terminacion TLS equivalente. El endpoint
`GET /health` permanece disponible para el health check interno.

## Direcciones de acceso

Con un hostname:

```text
Tienda: https://HOST_PUBLICO/
Panel:  https://HOST_PUBLICO/admin
Login:  https://HOST_PUBLICO/login
```

Con dominios propios separados se define:

```env
APP_URL=https://tienda.empresa.com
ADMIN_DOMAIN=admin.empresa.com
```

Ambos DNS apuntan al mismo servicio. La separacion no exige una segunda imagen
ni una segunda base de datos.

## Validacion de la entrega

1. `GET /health` responde `200`.
2. La tienda, los assets y Cloudinary usan HTTPS.
3. El panel exige autenticacion y permisos.
4. El catalogo y sus imagenes sobreviven a un reinicio.
5. Mercado Pago muestra el entorno sandbox.
6. Un pago de prueba aprobado se concilia por webhook.
7. Un rechazo o una expiracion devuelve el stock una sola vez.
8. La respuesta publica incluye `Strict-Transport-Security`.
9. No existe `.env` dentro de la imagen.
