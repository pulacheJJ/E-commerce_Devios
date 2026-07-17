# Etapa 2: autorización y contratos HTTP

`User` con Spatie Laravel Permission es la única fuente de autenticación y autorización.

## Roles administrativos

- `admin`: conserva los 23 permisos mediante el rol.
- `gestor`: no hereda permisos del rol; recibe permisos directos seleccionados para cada usuario.

Las rutas administrativas comparten `auth` y cada acción de módulo conserva su middleware
`permission:*`. No se exige `role:admin`, por lo que un gestor puede acceder únicamente a las
acciones que tenga asignadas. Crear o editar usuarios usa transacciones, `syncRoles()` y
`syncPermissions()`; solo un administrador puede asignar el rol `admin`.

## Permisos aplicados

- Usuarios: ver, crear, editar y cambiar estado.
- Productos: ver, crear, editar y cambiar estado.
- Categorías: ver, crear, editar y cambiar estado.
- Promociones: ver, crear, editar y cambiar estado.
- Sliders: ver, crear y editar. La eliminación existente se protege con `sliders.editar`.

Las eliminaciones existentes de promociones se protegen con `promociones.editar`, porque el mapa aprobado no define una capacidad de eliminación separada.

## Permisos preparados sin rutas inventadas

- `sliders.cambiar_estado`: no existe actualmente una acción independiente para alternar el estado.
- `pedidos.ver`, `pedidos.actualizar_estado` y `pedidos.cancelar`: no existe actualmente un módulo administrativo de pedidos.

Estos permisos forman parte del mapa y del rol `admin`, pero no exponen endpoints nuevos.

Los cambios de estado comparten un Form Request contextual que autoriza según la ruta aunque no
reciban campos. El catálogo público y la actualización de cantidad del carrito también tienen
requests específicos porque antes consumían entrada genérica o validación inline.

## Rutas retiradas del contrato

Las rutas generadas automáticamente para métodos inexistentes fueron eliminadas: `destroy` de usuarios, productos y categorías; `show` de categorías; y `create` de sliders. No se eliminó ningún registro ni funcionalidad implementada.
