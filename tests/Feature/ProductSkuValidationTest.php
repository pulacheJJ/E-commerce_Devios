<?php

namespace Tests\Feature;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\User;
use App\Models\VarianteProducto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSkuValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creation_with_a_duplicate_sku_is_rejected_with_a_clear_message(): void
    {
        $existing = VarianteProducto::with('producto')->firstOrFail();
        $request = StoreProductoRequest::create('/admin/productos', 'POST', [
            'nombre' => 'Producto de prueba',
            'slug' => 'producto-prueba-'.uniqid(),
            'categoria_id' => $existing->producto->categoria_id,
            'variantes' => [[
                'sku' => $existing->sku,
                'precio' => 10,
                'stock' => 1,
            ]],
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'El SKU ingresado ya está registrado en otra variante.',
            $validator->errors()->first('variantes.0.sku')
        );
    }

    public function test_json_validation_response_identifies_the_nested_sku_field(): void
    {
        $existing = VarianteProducto::with('producto')->firstOrFail();
        RouteFacade::post('/_test/product-sku-validation', fn (StoreProductoRequest $request) => response()->noContent());

        $response = $this->postJson('/_test/product-sku-validation', [
            'nombre' => 'Producto de prueba',
            'slug' => 'producto-json-'.uniqid(),
            'categoria_id' => $existing->producto->categoria_id,
            'variantes' => [[
                'sku' => $existing->sku,
                'precio' => 10,
                'stock' => 1,
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('variantes.0.sku');

        $this->assertSame(
            'El SKU ingresado ya está registrado en otra variante.',
            $response->json('errors')['variantes.0.sku'][0]
        );
    }

    public function test_traditional_redirect_preserves_the_sku_and_displays_its_error(): void
    {
        $existing = VarianteProducto::with('producto')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $response = $this->actingAs($user)
            ->from(route('admin.productos.create'))
            ->post(route('admin.productos.store'), [
                'nombre' => 'Producto de prueba',
                'slug' => 'producto-redirect-'.uniqid(),
                'categoria_id' => $existing->producto->categoria_id,
                'variantes' => [[
                    'sku' => $existing->sku,
                    'precio' => 10,
                    'stock' => 1,
                ]],
            ]);

        $response->assertRedirect(route('admin.productos.create'))
            ->assertSessionHasErrors([
                'variantes.0.sku' => 'El SKU ingresado ya está registrado en otra variante.',
            ]);

        $this->get(route('admin.productos.create'))
            ->assertSuccessful()
            ->assertSee('Existen campos por corregir.')
            ->assertSee('El SKU ingresado ya está registrado en otra variante.')
            ->assertSee('value="'.$existing->sku.'"', false);
    }

    public function test_update_can_keep_the_same_variant_sku(): void
    {
        $variant = VarianteProducto::with('producto')->firstOrFail();
        $request = $this->updateRequest($variant, $variant->sku);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_update_rejects_the_sku_of_another_variant(): void
    {
        $variant = VarianteProducto::with('producto')->firstOrFail();
        $other = VarianteProducto::whereKeyNot($variant->id)->firstOrFail();
        $request = $this->updateRequest($variant, $other->sku);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'El SKU ingresado ya está registrado en otra variante.',
            $validator->errors()->first('variantes.0.sku')
        );
    }

    public function test_update_redirect_displays_the_error_under_the_conflicting_sku(): void
    {
        $variant = VarianteProducto::with('producto')->firstOrFail();
        $other = VarianteProducto::whereKeyNot($variant->id)->firstOrFail();
        $request = $this->updateRequest($variant, $other->sku);
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $response = $this->actingAs($user)
            ->from(route('admin.productos.edit', $variant->producto))
            ->put(route('admin.productos.update', $variant->producto), $request->all());

        $response->assertRedirect(route('admin.productos.edit', $variant->producto))
            ->assertSessionHasErrors([
                'variantes.0.sku' => 'El SKU ingresado ya está registrado en otra variante.',
            ]);

        $this->get(route('admin.productos.edit', $variant->producto))
            ->assertSuccessful()
            ->assertSee('Existen campos por corregir.')
            ->assertSee('El SKU ingresado ya está registrado en otra variante.')
            ->assertSee('value="'.$other->sku.'"', false);
    }

    private function updateRequest(VarianteProducto $variant, string $sku): UpdateProductoRequest
    {
        $product = $variant->producto;
        $request = UpdateProductoRequest::create('/admin/productos/'.$product->id, 'PUT', [
            'nombre' => $product->nombre,
            'slug' => $product->slug,
            'categoria_id' => $product->categoria_id,
            'descripcion' => $product->descripcion,
            'variantes' => [[
                'id' => $variant->id,
                'sku' => $sku,
                'color' => $variant->color,
                'especificacion' => $variant->especificacion,
                'precio' => $variant->precio,
                'precio_oferta' => $variant->precio_oferta,
                'stock' => $variant->stock,
            ]],
        ]);
        $request->setRouteResolver(fn () => new class($product)
        {
            public function __construct(private $product) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'producto' ? $this->product : $default;
            }
        });

        return $request;
    }
}
