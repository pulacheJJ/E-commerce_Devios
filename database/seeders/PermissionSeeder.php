<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.cambiar_estado',
        'productos.ver', 'productos.crear', 'productos.editar', 'productos.cambiar_estado',
        'categorias.ver', 'categorias.crear', 'categorias.editar', 'categorias.cambiar_estado',
        'promociones.ver', 'promociones.crear', 'promociones.editar', 'promociones.cambiar_estado',
        'sliders.ver', 'sliders.crear', 'sliders.editar', 'sliders.cambiar_estado',
        'pedidos.ver', 'pedidos.actualizar_estado', 'pedidos.cancelar',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)->map(
            fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
        );

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);

        // Los permisos de un gestor son asignaciones directas por usuario.
        Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
