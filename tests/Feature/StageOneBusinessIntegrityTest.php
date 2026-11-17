<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Provincia;
use App\Models\VarianteProducto;
use App\Services\CarritoService;
use App\Services\DireccionService;
use App\Services\PedidoInventarioService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StageOneBusinessIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_page_loads_the_cart_script_once(): void
    {
        $product = Producto::where('esta_activo', true)->firstOrFail();

        $response = $this->get(route('tienda.producto', $product->slug))->assertSuccessful();

        $this->assertSame(1, substr_count($response->getContent(), 'js/tienda/producto.js'));
    }

    public function test_checkout_rejects_a_province_from_another_department(): void
    {
        $province = Provincia::with('distritos')->has('distritos')->firstOrFail();
        $wrongDepartment = Departamento::whereKeyNot($province->departamento_id)->firstOrFail();

        $this->postJson('/checkout/procesar', [
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'email' => 'stage-one@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'Av. Prueba 123',
            'referencia' => null,
            'metodo_entrega' => 'domicilio',
            'departamento' => $wrongDepartment->id,
            'provincia' => $province->id,
            'distrito' => $province->distritos->first()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('provincia');
    }

    public function test_selecting_a_new_default_address_preserves_all_previous_addresses(): void
    {
        $cliente = Cliente::has('direcciones')->firstOrFail();
        $before = $cliente->direcciones()->count();
        $district = Distrito::firstOrFail();

        $direccion = app(DireccionService::class)->guardarComoPredeterminada($cliente, [
            'distrito' => $district->id,
            'direccion_envio' => 'Dirección temporal de prueba '.uniqid(),
            'referencia' => 'Solo durante la prueba',
        ]);

        $this->assertSame($before + 1, $cliente->direcciones()->count());
        $this->assertSame(1, $cliente->direcciones()->where('is_predeterminada', true)->count());
        $this->assertTrue($direccion->fresh()->is_predeterminada);
    }

    public function test_expiration_releases_stock_exactly_once(): void
    {
        $pedido = Pedido::whereIn('estado', ['reservado', 'pendiente_pago'])->has('detalles')->firstOrFail();
        $pedido->update(['expires_at' => now()->subMinute(), 'inventory_released_at' => null]);
        $quantities = $pedido->detalles()
            ->select('variante_id', DB::raw('SUM(cantidad) AS cantidad_total'))
            ->groupBy('variante_id')
            ->pluck('cantidad_total', 'variante_id');
        $before = VarianteProducto::withTrashed()->whereIn('id', $quantities->keys())->pluck('stock', 'id');

        $service = app(PedidoInventarioService::class);
        $this->assertTrue($service->expirar($pedido->id));
        $this->assertFalse($service->expirar($pedido->id));

        foreach ($quantities as $variantId => $quantity) {
            $stock = VarianteProducto::withTrashed()->findOrFail($variantId)->stock;
            $this->assertSame((int) $before[$variantId] + (int) $quantity, $stock);
        }

        $pedido->refresh();
        $this->assertSame('expirado', $pedido->estado);
        $this->assertNotNull($pedido->inventory_released_at);
    }

    public function test_adding_the_same_variant_increments_one_cart_row(): void
    {
        $variant = VarianteProducto::where('stock', '>=', 3)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $service = app(CarritoService::class);

        $this->assertTrue($service->agregarItem($variant->id, 1)['success']);
        $cart = $service->obtenerCarritoActual();
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->count());
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->value('cantidad'));

        $this->assertTrue($service->agregarItem($variant->id, 1)['success']);

        $cart->refresh();
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->count());
        $this->assertSame(2, $cart->elementos()->where('variante_id', $variant->id)->value('cantidad'));
    }

    public function test_one_http_request_adds_exactly_one_unit(): void
    {
        $variant = VarianteProducto::where('stock', '>=', 2)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();

        $this->postJson('/carrito/agregar', ['variante_id' => $variant->id, 'cantidad' => 1])
            ->assertSuccessful();

        $cart = app(CarritoService::class)->obtenerCarritoActual();
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->count());
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->value('cantidad'));
    }

    public function test_two_independent_http_requests_increment_without_creating_another_row(): void
    {
        $variant = VarianteProducto::where('stock', '>=', 2)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();

        $payload = ['variante_id' => $variant->id, 'cantidad' => 1];
        $this->postJson('/carrito/agregar', $payload)->assertSuccessful();
        $this->postJson('/carrito/agregar', $payload)->assertSuccessful();

        $cart = app(CarritoService::class)->obtenerCarritoActual();
        $this->assertSame(1, $cart->elementos()->where('variante_id', $variant->id)->count());
        $this->assertSame(2, $cart->elementos()->where('variante_id', $variant->id)->value('cantidad'));
    }

    public function test_legacy_pending_orders_are_not_expired_without_an_expiration_date(): void
    {
        $legacyIds = Pedido::where('estado', 'pendiente_pago')->whereNull('expires_at')->pluck('id');
        $this->artisan('pedidos:expirar-pendientes')->assertSuccessful();
        $this->assertSame($legacyIds->count(), Pedido::whereIn('id', $legacyIds)->where('estado', 'pendiente_pago')->count());
    }
}
