# Despliegue de demostracion en Render y TiDB

Render puede ejecutar la imagen publicada en Docker Hub y TiDB puede mantenerse
como base MySQL externa. El despliegue sigue siendo un entorno demostrativo
aunque `APP_ENV` tenga el valor `production`; dicho valor endurece Laravel y no
activa cobros reales.

El alcance de esta guia es una demostracion. La auditoria de dependencias debe
quedar sin avisos antes de convertir el servicio en produccion real.

## Creacion del servicio desde el repositorio

El archivo `render.yaml` deja preparado un Blueprint para una demostracion de
una sola instancia. Al crear el Blueprint, Render solicita los valores marcados
como secretos y construye el `Dockerfile`. No se deben escribir esos valores en
el archivo YAML.

La primera sincronizacion requiere completar `APP_KEY`, `APP_URL`, TiDB,
Cloudinary, el administrador y las credenciales de prueba de Mercado Pago. El
health check `/health`, el modo de pruebas, el scheduler demostrativo y HTTPS ya
estan declarados.

## Creacion del servicio desde Docker Hub

1. Se crea un **Web Service** en Render mediante **Existing Image**.
2. Se indica `docker.io/USUARIO/devioz-ecommerce:VERSION`.
3. El contenedor se inicia con su comando predeterminado.
4. El health check se configura como `/health`.
5. Las variables se agregan en la seccion **Environment**.

Render admite imagenes publicas o privadas de Docker Hub. Las imagenes para esta
entrega se construyen como `linux/amd64`, formato requerido por Render.

## Variables principales

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VALOR_PRIVADO
APP_URL=https://NOMBRE.onrender.com
FORCE_HTTPS=true
ADMIN_DOMAIN=

DB_CONNECTION=mysql
DB_HOST=HOST_TIDB
DB_PORT=4000
DB_DATABASE=BASE_TIDB
DB_USERNAME=USUARIO_TIDB
DB_PASSWORD=CLAVE_TIDB
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

RUN_MIGRATIONS=true
RUN_DEMO_SEEDERS=true
RUN_SCHEDULER=true
SEED_ADMIN_NAME=Administrador DEVIOZ
SEED_ADMIN_EMAIL=CORREO_PRIVADO
SEED_ADMIN_PASSWORD=CLAVE_PRIVADA_COMPLEJA

MERCADOPAGO_ENABLED=true
MERCADOPAGO_MODE=test
MERCADOPAGO_ALLOW_LIVE=false
MERCADOPAGO_TEST_PUBLIC_KEY=VALOR_PRIVADO
MERCADOPAGO_TEST_ACCESS_TOKEN=VALOR_PRIVADO
MERCADOPAGO_TEST_WEBHOOK_SECRET=VALOR_PRIVADO

MEDIA_DISK=cloudinary
CLOUDINARY_CLOUD_NAME=VALOR_PRIVADO
CLOUDINARY_API_KEY=VALOR_PRIVADO
CLOUDINARY_API_SECRET=VALOR_PRIVADO

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Las variables sensibles se cargan directamente en Render y no se agregan al
repositorio ni a Docker Hub. `RUN_MIGRATIONS` utiliza migraciones incrementales;
no borra tablas. `RUN_DEMO_SEEDERS` es idempotente y conserva el stock ya usado
por variantes demo existentes.

## Imagenes y disco persistente

La configuracion recomendada para la demostracion utiliza Cloudinary:

```env
MEDIA_DISK=cloudinary
```

De esta manera, las fotografias de productos, promociones y sliders no dependen
del disco de Render. La configuracion completa figura en
[Almacenamiento multimedia con Cloudinary](cloudinary-media-storage.md). R2
permanece disponible como alternativa compatible con S3.

Como alternativa, Render utiliza un sistema de archivos efimero por defecto.
Para conservar imagenes localmente se agrega un Persistent Disk con:

```text
Mount path: /var/www/html/storage/app/public
Size:       1 GB o la capacidad necesaria
```

Los discos persistentes solo estan disponibles para servicios de pago, se
asocian a una unica instancia y no pueden compartirse con otro servicio. Para
una demostracion gratuita sin disco, los recursos incluidos se regeneran durante
el arranque mediante `RUN_DEMO_SEEDERS=true`; las nuevas cargas administrativas
no sobreviven a un reinicio cuando se usa `MEDIA_DISK=public`.

Referencia oficial: [Persistent Disks](https://render.com/docs/disks).

## Scheduler

El Blueprint inicia `schedule:work` junto con Apache mediante
`RUN_SCHEDULER=true`. Esta modalidad es apropiada para la demostracion gratuita
de una sola instancia. Si el servicio se escala o pasa a operacion productiva,
esa variable se cambia a `false` y se crea un Background Worker con:

La expiracion automatica puede configurarse como un cron job que ejecute:

```bash
php artisan schedule:run
```

El cron utiliza la misma imagen y las mismas variables de base de datos, con:

```env
RUN_MIGRATIONS=false
RUN_DEMO_SEEDERS=false
```

Los cron jobs de Render no acceden al disco persistente, lo cual no afecta la
expiracion porque esa tarea opera sobre TiDB. Referencia oficial:
[Cron Jobs](https://render.com/docs/cronjobs).

## Mercado Pago

La URL de prueba se registra como:

```text
https://NOMBRE.onrender.com/webhooks/pagos/mercadopago
```

El modo de prueba funciona mediante HTTPS en Render y no genera cobros reales.
La bandera `MERCADOPAGO_ALLOW_LIVE=false` impide que una credencial productiva
active pagos reales por error.

Despues de conocer el hostname definitivo se debe actualizar `APP_URL` en
Render y registrar exactamente la misma base HTTPS en Mercado Pago. Cambiar el
hostname no exige reconstruir la imagen.

## HTTPS y proxy de Render

Render entrega gratuitamente el certificado TLS para el subdominio
`onrender.com` y para los dominios personalizados, y redirige HTTP a HTTPS. La
aplicacion confia en los encabezados `X-Forwarded-*`, por lo que reconoce que la
conexion original fue segura aunque Apache reciba HTTP dentro de la red privada.

`FORCE_HTTPS=true` obliga a Laravel a generar assets, formularios, retornos y
callbacks con `https://`. Tambien activa HSTS y, junto con
`SESSION_SECURE_COOKIE=true`, evita que la sesion viaje por HTTP. `/health`
permanece accesible internamente para que el chequeo del contenedor no entre en
un ciclo de redireccion.

No se debe instalar un certificado dentro del contenedor en Render: TLS termina
en el proxy administrado por Render. Un error de contenido mixto normalmente
indica que `APP_URL` sigue usando `http://`, que `FORCE_HTTPS` esta apagado o que
la cache de configuracion pertenece a valores anteriores. El entrypoint vuelve
a generar esa cache en cada arranque.

## Panel administrativo

El hostname gratuito de Render permite la operacion completa con:

```text
Tienda: https://NOMBRE.onrender.com/
Panel:  https://NOMBRE.onrender.com/admin
Login:  https://NOMBRE.onrender.com/login
```

El formulario de acceso se conserva en `/login` por compatibilidad con Laravel;
el dashboard permanece protegido en `/admin`.

Dos direcciones visibles requieren dominios personalizados asociados al mismo
Web Service:

```env
APP_URL=https://tienda.ejemplo.com
ADMIN_DOMAIN=admin.ejemplo.com
```

No se crean dos aplicaciones ni dos bases. La separacion es de hostname y la
autorizacion continua aplicandose en el servidor. Referencia oficial:
[Docker on Render](https://render.com/docs/docker).

## Verificacion final

1. `/health` responde 200.
2. La portada presenta el catalogo demo y sus imagenes.
3. `/admin` solicita autenticacion.
4. El administrador accede con la credencial privada.
5. Mercado Pago muestra la marca de sandbox.
6. Un pago aprobado actualiza el pedido.
7. Un rechazo o expiracion devuelve el stock.
8. Un reinicio conserva TiDB y, cuando existe disco, las imagenes cargadas.
9. Los recursos y formularios del navegador usan exclusivamente `https://`.
10. La respuesta HTTPS incluye `Strict-Transport-Security`.
