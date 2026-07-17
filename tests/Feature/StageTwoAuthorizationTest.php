<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VarianteProducto;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StageTwoAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_permission_map_exists_and_admin_has_the_complete_map(): void
    {
        $this->assertSame(count(PermissionSeeder::PERMISSIONS), Permission::whereIn('name', PermissionSeeder::PERMISSIONS)->count());
        $admin = Role::findByName('admin');
        $this->assertEmpty(array_diff(PermissionSeeder::PERMISSIONS, $admin->permissions->pluck('name')->all()));
    }

    public function test_guest_is_redirected_to_login_and_user_without_permission_gets_forbidden(): void
    {
        $this->get(route('admin.productos.index'))->assertRedirect(route('login'));

        $role = Role::findByName('admin');
        $permission = Permission::findByName('productos.ver');
        $role->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.productos.index'));

        $role->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $response->assertForbidden();
    }

    public function test_admin_with_permission_can_access_the_module(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));

        $this->actingAs($user)->get(route('admin.productos.index'))->assertSuccessful();
    }

    public function test_product_actions_are_hidden_when_admin_only_has_view_permission(): void
    {
        $role = Role::findByName('admin');
        $hiddenPermissions = Permission::whereIn('name', [
            'productos.crear',
            'productos.editar',
            'productos.cambiar_estado',
        ])->get();
        $role->revokePermissionTo($hiddenPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($role);

        try {
            $response = $this->actingAs($user)->get(route('admin.productos.index'));

            $response->assertSuccessful()
                ->assertDontSee(route('admin.productos.create'), false)
                ->assertDontSee('Nuevo Producto');

            $product = VarianteProducto::with('producto')->firstOrFail()->producto;
            $response
                ->assertDontSee(route('admin.productos.edit', $product), false)
                ->assertDontSee(route('admin.productos.toggle', $product), false);
        } finally {
            $role->givePermissionTo($hiddenPermissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function test_administrator_cannot_disable_own_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));

        $this->actingAs($user)
            ->patchJson(route('admin.usuarios.toggle', $user))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->trashed());
    }

    public function test_administrator_cannot_remove_own_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));
        $otherRole = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->putJson(route('admin.usuarios.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $otherRole->name,
            ])
            ->assertForbidden();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_nonexistent_resource_actions_are_not_exposed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));
        $variant = VarianteProducto::with('producto')->firstOrFail();

        $this->actingAs($user)
            ->delete('/admin/productos/'.$variant->producto_id)
            ->assertMethodNotAllowed();
        $this->get('/admin/categorias/999999')->assertMethodNotAllowed();
    }

    public function test_cart_contract_rejects_excessive_quantity_and_inactive_product(): void
    {
        $variant = VarianteProducto::with('producto')->where('stock', '>', 0)->firstOrFail();

        $this->postJson(route('carrito.agregar'), ['variante_id' => $variant->id, 'cantidad' => 11])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');

        $variant->producto->update(['esta_activo' => false]);
        $this->postJson(route('carrito.agregar'), ['variante_id' => $variant->id, 'cantidad' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_id');
    }
}
