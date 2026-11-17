# Despliegue portable con Docker

La entrega de DEVIOZ se distribuye como una imagen independiente del proveedor de
infraestructura. La configuracion del dominio, la base de datos, Mercado Pago y
el administrador se inyecta durante la ejecucion.

## Contenido y seguridad de la imagen

- La imagen no contiene `.env`, contrasenas ni credenciales de servicios externos.
- Las dependencias de desarrollo y las pruebas quedan fuera de la imagen final.
- Los recursos del catalogo demostrativo se incluyen como archivos publicables,
  pero sus registros solo se crean cuando el despliegue lo solicita.
- Los pagos reales permanecen bloqueados con `MERCADOPAGO_ALLOW_LIVE=false`.
- Las migraciones y los seeders no se ejecutan salvo activacion explicita.

## Construccion y publicacion

La imagen se construye para la arquitectura requerida por el proveedor y se
publica con una etiqueta inmutable:

```bash
docker build --platform linux/amd64 -t USUARIO_DOCKERHUB/devioz-ecommerce:1.0.0 .
docker login
docker push USUARIO_DOCKERHUB/devioz-ecommerce:1.0.0
```

El uso de una version como `1.0.0` permite identificar exactamente la entrega y
evita que una actualizacion accidental de `latest` cambie una demostracion.

## Configuracion en tiempo de ejecucion

El archivo `.env.production.example` contiene el contrato completo. Como minimo,
el proveedor debe definir:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:CLAVE_GENERADA
APP_URL=https://tienda.ejemplo.com
FORCE_HTTPS=true
DB_CONNECTION=mysql
DB_HOST=servidor-mysql
DB_PORT=3306
DB_DATABASE=devioz
DB_USERNAME=usuario
DB_PASSWORD=secreto
```

La clave de Laravel se genera fuera de la imagen:

```bash
docker run --rm USUARIO_DOCKERHUB/devioz-ecommerce:1.0.0 php artisan key:generate --show
```

El contenedor escucha el puerto indicado por `PORT` y usa `80` cuando la
variable no existe. Esto permite que Render y otros proveedores elijan el
puerto sin reconstruir la imagen.

## Inicializacion demostrativa

Una base vacia puede inicializarse durante el arranque con:

```env
RUN_MIGRATIONS=true
RUN_DEMO_SEEDERS=true
SEED_ADMIN_NAME=Administrador DEVIOZ
SEED_ADMIN_EMAIL=admin-demo@dominio-privado.test
SEED_ADMIN_PASSWORD=CONTRASENA_PRIVADA_COMPLEJA
```

`DemoDeploymentSeeder` crea o conserva:

- permisos y rol `admin`;
- un administrador con acceso completo;
- dos categorias;
- tres productos y cuatro variantes con stock;
- una promocion activa;
- un slider demostrativo;
- imagenes demostrativas dentro del disco publico.

El proceso es repetible y no duplica los registros. Las credenciales del
administrador se entregan por un canal privado y nunca se incorporan a Docker
Hub. La contrasena debe tener al menos 14 caracteres, mayusculas, minusculas,
numeros y simbolos.

En una base con informacion real, ambos indicadores deben permanecer en `false`
y la inicializacion se ejecuta de forma controlada. No se utilizan
`migrate:fresh`, `db:wipe` ni comandos equivalentes.

## Persistencia de imagenes

Productos, promociones y sliders escriben en el disco indicado por
`MEDIA_DISK`. Existen tres configuraciones compatibles:

- `MEDIA_DISK=public`: almacenamiento en `/var/www/html/storage/app/public`;
- `MEDIA_DISK=cloudinary`: almacenamiento multimedia externo con CDN HTTPS;
- `MEDIA_DISK=s3`: almacenamiento externo S3 o Cloudflare R2.

El contenedor crea automaticamente el enlace `public/storage` para la primera
opcion. La segunda no depende del sistema de archivos del contenedor.

El proveedor debe montar un volumen persistente en esa ruta cuando se requiera
conservar las imagenes subidas desde el panel. Sin volumen, el catalogo demo se
puede reconstruir al reiniciar si `RUN_DEMO_SEEDERS=true`, pero las cargas nuevas
del administrador se perderan junto con el sistema de archivos efimero.

Para la demostracion sin tarjeta se recomienda Cloudinary. La configuracion se
encuentra en [Almacenamiento multimedia con Cloudinary](cloudinary-media-storage.md).
R2 permanece disponible como alternativa S3.

Ejemplo de ejecucion con volumen:

```bash
docker run -d \
  --name devioz-app \
  --env-file /ruta/segura/devioz.env \
  -p 8080:80 \
  -v devioz-storage:/var/www/html/storage/app/public \
  USUARIO_DOCKERHUB/devioz-ecommerce:1.0.0
```

## Scheduler

La expiracion de reservas y la devolucion de stock dependen del scheduler. Una
segunda instancia de la misma imagen puede ejecutar:

```bash
docker run -d \
  --name devioz-scheduler \
  --env-file /ruta/segura/devioz.env \
  USUARIO_DOCKERHUB/devioz-ecommerce:1.0.0 \
  php artisan schedule:work
```

En el scheduler se mantienen `RUN_MIGRATIONS=false` y
`RUN_DEMO_SEEDERS=false` para evitar tareas de inicializacion duplicadas. Para
una demostracion de una sola instancia tambien puede establecerse
`RUN_SCHEDULER=true` en el contenedor web; al escalar se debe usar el proceso
separado.

## HTTPS detras de un proxy

El proxy publico es responsable del certificado TLS. El contenedor recibe la
peticion interna y Laravel interpreta `X-Forwarded-Proto` mediante
`TrustProxies`. Con `FORCE_HTTPS=true` se generan URLs seguras, se redirige una
entrada HTTP directa, se agrega HSTS y se mantienen las cookies de sesion solo
en HTTPS. El endpoint `/health` se excluye de la redireccion para permitir
comprobaciones internas.

El proveedor debe conservar el encabezado `X-Forwarded-Proto`. Si termina TLS
pero elimina ese encabezado, Laravel no puede distinguir la conexion publica
HTTPS de la conexion privada HTTP.

## Direcciones de tienda y administracion

Con un solo hostname, la tienda se publica en `/` y el panel en `/admin`. La
ruta administrativa no constituye por si misma una debilidad: el servidor sigue
exigiendo autenticacion, permisos y respuestas 403.

Cuando existen dos DNS, ambos apuntan al mismo contenedor:

```env
APP_URL=https://tienda.ejemplo.com
ADMIN_DOMAIN=admin.ejemplo.com
SESSION_DOMAIN=
```

- `https://tienda.ejemplo.com/` contiene el catalogo y el checkout.
- `https://admin.ejemplo.com/` contiene el panel.
- `https://admin.ejemplo.com/login` contiene el acceso administrativo.

El valor de `ADMIN_DOMAIN` corresponde solo al hostname, sin `https://` ni ruta.

## Verificacion operativa

- `GET /health` debe responder `200` con `{"status":"ok"}`.
- `APP_DEBUG` debe permanecer en `false`.
- `APP_URL` comienza con `https://` y `FORCE_HTTPS=true`.
- La tienda debe cargar las imagenes desde `/storage`.
- El panel debe exigir autenticacion.
- El scheduler debe ejecutar la expiracion de pedidos reservados.
- Los secretos deben existir unicamente en el entorno del proveedor.
