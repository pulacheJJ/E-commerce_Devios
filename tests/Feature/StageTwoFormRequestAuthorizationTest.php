<?php

namespace Tests\Feature;

use App\Http\Requests\ChangeAdminResourceStatusRequest;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\StorePromocionRequest;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Requests\UpdatePromocionRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Models\User;
use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StageTwoFormRequestAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @dataProvider administrativeRequestProvider
     */
    public function test_administrative_form_requests_authorize_using_the_expected_permission(
        string $requestClass,
        string $permission
    ): void {
        $user = User::factory()->create();
        $request = $this->requestFor($requestClass, $user);

        $this->assertFalse($request->authorize());

        $user->givePermissionTo(Permission::findByName($permission));
        $user->unsetRelation('permissions');

        $this->assertTrue($request->authorize());
    }

    public static function administrativeRequestProvider(): array
    {
        return [
            [StoreUserRequest::class, 'usuarios.crear'],
            [StoreCategoriaRequest::class, 'categorias.crear'],
            [StoreProductoRequest::class, 'productos.crear'],
            [UpdateProductoRequest::class, 'productos.editar'],
            [StorePromocionRequest::class, 'promociones.crear'],
            [UpdatePromocionRequest::class, 'promociones.editar'],
            [StoreSliderRequest::class, 'sliders.crear'],
            [UpdateSliderRequest::class, 'sliders.editar'],
        ];
    }

    public function test_category_request_uses_edit_permission_when_a_resource_is_present(): void
    {
        $user = User::factory()->create();
        $category = Categoria::firstOrFail();
        $request = $this->requestFor(StoreCategoriaRequest::class, $user);
        $route = new Route(['PUT'], '/admin/categorias/{categoria}', []);
        $route->bind(HttpRequest::create('/admin/categorias/'.$category->id, 'PUT'));
        $route->setParameter('categoria', $category);
        $request->setRouteResolver(fn (): Route => $route);

        $user->givePermissionTo(Permission::findByName('categorias.crear'));
        $this->assertFalse($request->authorize());

        $user->givePermissionTo(Permission::findByName('categorias.editar'));
        $user->unsetRelation('permissions');
        $this->assertTrue($request->authorize());
    }

    /**
     * @dataProvider statusRequestProvider
     */
    public function test_status_request_authorizes_contextually_by_route(string $routeName, string $permission): void
    {
        $user = User::factory()->create();
        $request = $this->requestFor(ChangeAdminResourceStatusRequest::class, $user);
        $route = new Route(['PATCH'], '/admin/resource/toggle', []);
        $route->bind(HttpRequest::create('/admin/resource/toggle', 'PATCH'));
        $route->name($routeName);
        $request->setRouteResolver(fn (): Route => $route);

        $this->assertFalse($request->authorize());

        $user->givePermissionTo(Permission::findByName($permission));
        $user->unsetRelation('permissions');
        $this->assertTrue($request->authorize());
    }

    public static function statusRequestProvider(): array
    {
        return [
            ['admin.usuarios.toggle', 'usuarios.cambiar_estado'],
            ['admin.productos.toggle', 'productos.cambiar_estado'],
            ['admin.categorias.toggle', 'categorias.cambiar_estado'],
            ['admin.promociones.toggle', 'promociones.cambiar_estado'],
        ];
    }

    private function requestFor(string $requestClass, User $user): FormRequest
    {
        /** @var FormRequest $request */
        $request = new $requestClass;
        $request->setUserResolver(fn (): User => $user);

        return $request;
    }
}
