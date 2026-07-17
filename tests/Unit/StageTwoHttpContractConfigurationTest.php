<?php

namespace Tests\Unit;

use Database\Seeders\PermissionSeeder;
use Tests\TestCase;

class StageTwoHttpContractConfigurationTest extends TestCase
{
    public function test_permission_map_contains_all_declared_modules(): void
    {
        foreach (['usuarios', 'productos', 'categorias', 'promociones', 'sliders', 'pedidos'] as $module) {
            $this->assertNotEmpty(array_filter(PermissionSeeder::PERMISSIONS, fn (string $permission) => str_starts_with($permission, $module.'.')));
        }
    }

    public function test_admin_routes_have_action_permissions(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->keyBy(fn ($route) => $route->getName());
        $expected = [
            'admin.usuarios.index' => 'permission:usuarios.ver',
            'admin.productos.store' => 'permission:productos.crear',
            'admin.categorias.update' => 'permission:categorias.editar',
            'admin.promociones.toggle' => 'permission:promociones.cambiar_estado',
            'admin.sliders.store' => 'permission:sliders.crear',
        ];

        foreach ($expected as $name => $middleware) {
            $this->assertContains($middleware, $routes[$name]->gatherMiddleware());
            $this->assertNotContains('role:admin', $routes[$name]->gatherMiddleware());
        }
    }
}
