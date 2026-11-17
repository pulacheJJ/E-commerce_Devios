# Almacenamiento de fotografias con Cloudflare R2

Las fotografias cargadas desde los formularios administrativos se almacenan en
un disco multimedia independiente. El entorno local utiliza el disco `public` y
los despliegues pueden utilizar Cloudflare R2 mediante su API compatible con S3.

## Recursos incluidos

La integracion cubre:

- galeria de productos;
- banners de promociones;
- sliders;
- recursos del catalogo demostrativo.

La base de datos conserva solamente claves como `products/archivo.webp`. La URL
se genera en tiempo de ejecucion desde el disco configurado, por lo que no queda
acoplada a Render ni a un dominio determinado.

## Creacion del bucket

1. En Cloudflare se abre **Storage & databases > R2**.
2. Se activa R2 y se crea un bucket Standard, por ejemplo `devioz-media`.
3. En **Manage R2 API Tokens** se crea un token con permiso **Object Read & Write**
   limitado exclusivamente al bucket.
4. Se copian una vez el Access Key ID, Secret Access Key y endpoint S3.
5. Se habilita acceso publico mediante un dominio personalizado o el subdominio
   `r2.dev` para la demostracion.

El token de API nunca se incorpora a Git, Docker Hub o la documentacion de
entrega.

## Configuracion de Render

Las siguientes variables se guardan como secretos del Web Service:

```env
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=VALOR_PRIVADO
AWS_SECRET_ACCESS_KEY=VALOR_PRIVADO
AWS_DEFAULT_REGION=auto
AWS_BUCKET=devioz-media
AWS_ENDPOINT=https://ID_CUENTA.r2.cloudflarestorage.com
AWS_URL=https://DOMINIO_PUBLICO_DEL_BUCKET
AWS_USE_PATH_STYLE_ENDPOINT=false
```

`AWS_ENDPOINT` corresponde a la API privada utilizada por Laravel para escribir.
`AWS_URL` corresponde al dominio publico utilizado por el navegador para mostrar
las fotografias. Ninguno debe terminar en `/`.

No se necesita Persistent Disk de Render cuando `MEDIA_DISK=s3`. El contenedor
puede reiniciarse o reemplazarse sin afectar los objetos almacenados en R2.

## Desarrollo local

El desarrollo puede seguir utilizando el almacenamiento actual:

```env
MEDIA_DISK=public
```

En este modo los archivos se guardan en `storage/app/public` y se muestran a
traves de `public/storage`.

## Copia de fotografias existentes

Antes del primer despliegue con R2, las credenciales S3 se configuran localmente
y se ejecuta:

```bash
php artisan config:clear
php artisan media:copy --from=public --to=s3
```

El comando:

- copia productos, promociones y recursos demo;
- migra los sliders creados con la estructura anterior;
- actualiza solamente la ruta de los sliders antiguos despues de copiarlos;
- no elimina ningun archivo local;
- omite los objetos que ya existen.

Para reemplazar objetos existentes de manera explicita:

```bash
php artisan media:copy --from=public --to=s3 --force
```

La copia debe finalizar correctamente antes de activar `MEDIA_DISK=s3` en el
despliegue.

## Verificacion

1. Se registra un producto con una fotografia desde el panel.
2. La clave aparece dentro de `products/` en el bucket.
3. La tienda presenta la fotografia desde `AWS_URL`.
4. Se reinicia o redespliega el servicio.
5. La fotografia continua disponible.
6. La actualizacion de una promocion o slider reemplaza el objeto anterior.

Referencias oficiales:

- [Cloudflare R2 mediante S3](https://developers.cloudflare.com/r2/get-started/s3/)
- [Precios y limites incluidos](https://developers.cloudflare.com/r2/pricing/)
