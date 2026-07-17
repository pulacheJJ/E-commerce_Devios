<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StageTwoGranularPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    private Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRole = Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    }

    public function test_admin_can_create_manager_with_only_product_and_category_view_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Gestor Catálogo',
            'email' => 'gestor-catalogo@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'gestor',
            'permissions' => ['productos.ver', 'categorias.ver'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $manager = User::where('email', 'gestor-catalogo@example.test')->firstOrFail();
        $this->assertTrue($manager->hasRole('gestor'));
        $this->assertEqualsCanonicalizing(
            ['productos.ver', 'categorias.ver'],
            $manager->getDirectPermissions()->pluck('name')->all()
        );
    }

    public function test_limited_manager_can_only_read_products_and_categories(): void
    {
        $manager = $this->manager(['productos.ver', 'categorias.ver']);
        $product = Producto::firstOrFail();
        $category = Categoria::firstOrFail();

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertSuccessful();
        $this->get(route('admin.productos.index'))->assertSuccessful();
        $this->get(route('admin.categorias.index'))->assertSuccessful();

        foreach ([
            ['get', route('admin.productos.create')],
            ['get', route('admin.productos.edit', $product)],
            ['patch', route('admin.productos.toggle', $product)],
            ['get', route('admin.categorias.create')],
            ['get', route('admin.categorias.edit', $category)],
            ['patch', route('admin.categorias.toggle', $category)],
            ['get', route('admin.usuarios.index')],
            ['get', route('admin.promociones.index')],
            ['get', route('admin.sliders.index')],
        ] as [$method, $url]) {
            $this->{$method}($url)->assertForbidden();
        }
    }

    public function test_manager_views_hide_actions_and_modules_without_permission(): void
    {
        $manager = $this->manager(['productos.ver', 'categorias.ver']);
        $product = Producto::firstOrFail();
        $category = Categoria::firstOrFail();

        $productResponse = $this->actingAs($manager)->get(route('admin.productos.index'));
        $productResponse->assertSuccessful()
            ->assertDontSee(route('admin.productos.create'), false)
            ->assertDontSee(route('admin.productos.edit', $product), false)
            ->assertDontSee(route('admin.productos.toggle', $product), false)
            ->assertDontSee(route('admin.usuarios.index'), false)
            ->assertDontSee(route('admin.promociones.index'), false)
            ->assertDontSee(route('admin.sliders.index'), false);

        $this->get(route('admin.categorias.index'))
            ->assertSuccessful()
            ->assertDontSee(route('admin.categorias.create'), false)
            ->assertDontSee(route('admin.categorias.edit', $category), false)
            ->assertDontSee(route('admin.categorias.toggle', $category), false);
    }

    public function test_editing_manager_synchronizes_the_selected_permissions(): void
    {
        $admin = $this->admin();
        $manager = $this->manager(['productos.ver', 'categorias.ver']);

        $editResponse = $this->actingAs($admin)->get(route('admin.usuarios.edit', $manager));
        $editResponse->assertSuccessful()
            ->assertSee('Administrador')
            ->assertSee('Gestor')
            ->assertSee('productos.ver', false)
            ->assertSee('categorias.ver', false);
        $this->assertMatchesRegularExpression('/value="productos\.ver"[^>]*checked/', $editResponse->getContent());

        $this->actingAs($admin)->put(route('admin.usuarios.update', $manager), [
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => 'gestor',
            'permissions' => ['productos.ver', 'promociones.ver'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertEqualsCanonicalizing(
            ['productos.ver', 'promociones.ver'],
            $manager->fresh()->getDirectPermissions()->pluck('name')->all()
        );
    }

    public function test_role_transitions_clear_or_replace_direct_permissions(): void
    {
        $admin = $this->admin();
        $manager = $this->manager(['productos.ver', 'categorias.ver']);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $manager), [
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => 'admin',
        ])->assertRedirect(route('admin.usuarios.index'));

        $manager = $manager->fresh();
        $this->assertTrue($manager->hasRole('admin'));
        $this->assertCount(0, $manager->getDirectPermissions());

        $this->put(route('admin.usuarios.update', $manager), [
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => 'gestor',
            'permissions' => ['productos.ver'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $manager = $manager->fresh();
        $this->assertTrue($manager->hasRole('gestor'));
        $this->assertSame(['productos.ver'], $manager->getDirectPermissions()->pluck('name')->all());
    }

    public function test_invalid_duplicate_and_escalated_permissions_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.usuarios.store'), [
            'name' => 'Manipulado',
            'email' => 'manipulado@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'gestor',
            'permissions' => ['productos.ver', 'sistema.control_total'],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions.1');

        $this->postJson(route('admin.usuarios.store'), [
            'name' => 'Duplicado',
            'email' => 'duplicado@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'gestor',
            'permissions' => ['productos.ver', 'productos.ver'],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions.1');

        $this->postJson(route('admin.usuarios.store'), [
            'name' => 'Sin Permisos',
            'email' => 'sin-permisos@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'gestor',
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions');

        $manager = $this->manager(['usuarios.crear', 'productos.ver']);
        $this->actingAs($manager)->postJson(route('admin.usuarios.store'), [
            'name' => 'Admin Ilegal',
            'email' => 'admin-ilegal@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ])->assertForbidden();

        $this->postJson(route('admin.usuarios.store'), [
            'name' => 'Gestor Escalado',
            'email' => 'gestor-escalado@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'gestor',
            'permissions' => ['usuarios.crear', 'productos.editar'],
        ])->assertForbidden();
    }

    public function test_user_without_permissions_is_forbidden_and_guest_is_redirected(): void
    {
        $withoutPermissions = $this->manager([]);

        $this->actingAs($withoutPermissions)
            ->get(route('admin.productos.index'))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('admin.productos.index'))->assertRedirect(route('login'));
    }

    public function test_main_admin_keeps_all_permissions_and_full_access(): void
    {
        $admin = $this->admin();

        $this->assertEmpty(array_diff(
            PermissionSeeder::PERMISSIONS,
            $admin->getAllPermissions()->pluck('name')->all()
        ));

        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertSuccessful();
        $this->get(route('admin.productos.create'))->assertSuccessful();
        $this->get(route('admin.categorias.create'))->assertSuccessful();
        $this->get(route('admin.promociones.index'))->assertSuccessful();
        $this->get(route('admin.sliders.index'))->assertSuccessful();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));

        return $user;
    }

    private function manager(array $permissions): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$this->managerRole]);
        $user->syncPermissions(Permission::whereIn('name', $permissions)->get());

        return $user;
    }
}
