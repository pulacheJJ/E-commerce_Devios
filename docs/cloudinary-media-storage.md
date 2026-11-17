# Almacenamiento multimedia con Cloudinary

El proyecto admite Cloudinary como almacenamiento persistente para las
fotografias de productos y los banners de promociones y sliders. Los archivos
no dependen del sistema de archivos del contenedor y sobreviven a reinicios y
nuevos despliegues.

## Credenciales

Desde `Cloudinary Console > Configuracion del entorno del producto > Claves API`
se crea una clave dedicada para la aplicacion. El secreto no se incorpora al
repositorio, a la imagen Docker ni a capturas compartidas.

Las variables se configuran en el entorno que ejecuta el contenedor:

```env
MEDIA_DISK=cloudinary
CLOUDINARY_CLOUD_NAME=nombre_de_nube
CLOUDINARY_API_KEY=valor_privado
CLOUDINARY_API_SECRET=valor_privado
```

`CLOUDINARY_DELIVERY_URL` es opcional. Si se omite, la aplicacion genera la URL
HTTPS publica mediante el nombre de la nube.

Despues de cambiar variables se limpia la configuracion cacheada:

```bash
php artisan config:clear
```

## Copia inicial sin perdida

Los archivos locales existentes pueden copiarse sin borrar el origen:

```bash
php artisan media:copy --from=public --to=cloudinary
```

El comando omite archivos ya existentes. `--force` permite reemplazarlos de
forma explicita. Los sliders antiguos ubicados en `public/img/sliders` tambien
se copian y sus registros solo se actualizan despues de una carga correcta.

## Docker y Render

La misma imagen Docker funciona localmente y en cualquier proveedor. Cloudinary
se activa exclusivamente mediante variables de ejecucion; por ello las claves
no quedan guardadas en Docker Hub.

En Render se agregan las cuatro variables anteriores en `Environment`. No se
necesita Persistent Disk para las imagenes cuando `MEDIA_DISK=cloudinary`.

La aplicacion debe verificar despues del despliegue:

1. que las imagenes existentes se muestran mediante `https://res.cloudinary.com`;
2. que crear un producto carga sus fotografias;
3. que reemplazar un banner conserva el nuevo archivo;
4. que eliminar o reemplazar contenido limpia el recurso anterior.

El plan gratuito de Cloudinary no requiere tarjeta, pero su consumo debe
supervisarse desde el panel de la cuenta.
