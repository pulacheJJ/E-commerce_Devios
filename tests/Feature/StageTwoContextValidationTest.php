<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\StorePromocionRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Promocion;
use App\Models\VarianteProducto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StageTwoContextValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_update_rejects_a_variant_owned_by_another_product(): void
    {
        $variant = VarianteProducto::with('producto')->firstOrFail();
        $other = VarianteProducto::where('producto_id', '<>', $variant->producto_id)->firstOrFail();
        $request = UpdateProductoRequest::create('/admin/productos/'.$variant->producto_id, 'PUT', [
            'nombre' => $variant->producto->nombre,
            'slug' => $variant->producto->slug,
            'categoria_id' => $variant->producto->categoria_id,
            'variantes' => [[
                'id' => $other->id,
                'sku' => $other->sku,
                'precio' => $other->precio,
                'stock' => $other->stock,
            ]],
        ]);
        $this->setRouteParameter($request, 'producto', $variant->producto);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('variantes.0.id'));
    }

    public function test_category_update_rejects_a_descendant_as_parent(): void
    {
        $parent = Categoria::has('hijos')->firstOrFail();
        $child = $parent->hijos()->firstOrFail();
        $request = StoreCategoriaRequest::create('/admin/categorias/'.$parent->id, 'PUT', [
            'nombre' => $parent->nombre,
            'slug' => $parent->slug,
            'parent_id' => $child->id,
        ]);
        $this->setRouteParameter($request, 'categoria', $parent);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('ciclo', $validator->errors()->first('parent_id'));
    }

    public function test_percentage_discount_cannot_exceed_one_hundred(): void
    {
        $request = StorePromocionRequest::create('/admin/promociones', 'POST', [
            'titulo' => 'Promoción inválida',
            'tipo_descuento' => 'porcentaje',
            'valor_descuento' => 101,
            'fecha_inicio' => now()->toDateTimeString(),
            'fecha_fin' => now()->addDay()->toDateTimeString(),
        ]);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('valor_descuento'));
    }

    public function test_inactive_or_expired_promotion_is_hidden_from_public_catalog(): void
    {
        $promotion = Promocion::firstOrFail();
        $promotion->update([
            'esta_activa' => false,
            'fecha_inicio' => now()->subDays(2),
            'fecha_fin' => now()->subDay(),
        ]);

        $this->get(route('tienda.promocion', $promotion->id))->assertNotFound();
    }

    public function test_soft_deleted_variant_and_oversized_search_are_rejected(): void
    {
        $variant = VarianteProducto::where('stock', '>', 0)->firstOrFail();
        $variant->delete();

        $this->postJson(route('carrito.agregar'), ['variante_id' => $variant->id, 'cantidad' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variante_id');
        $this->getJson(route('tienda.inicio', ['buscar' => str_repeat('x', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('buscar');
    }

    private function setRouteParameter($request, string $key, mixed $value): void
    {
        $request->setRouteResolver(fn () => new class($key, $value)
        {
            public function __construct(private string $key, private mixed $value) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === $this->key ? $this->value : $default;
            }
        });
    }
}
