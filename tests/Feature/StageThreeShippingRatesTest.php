<?php

namespace Tests\Feature;

use App\Models\Distrito;
use App\Models\Provincia;
use App\Models\TarifaEnvio;
use App\Models\User;
use App\Models\VarianteProducto;
use App\Services\CarritoService;
use App\Services\DatabaseShippingCostCalculator;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StageThreeShippingRatesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_edit_and_toggle_provincial_rate(): void
    {
        $admin = $this->admin();
        $province = $this->provinceWithoutRate();

        $this->actingAs($admin)->post(route('admin.envios.store'), [
            'tipo' => 'provincia', 'provincia_id' => $province->id, 'costo' => '15.50', 'esta_activa' => '1',
        ])->assertRedirect(route('admin.envios.index'));
        $rate = TarifaEnvio::where('provincia_id', $province->id)->firstOrFail();
        $this->assertSame('15.50', $rate->costo);

        $this->put(route('admin.envios.update', $rate), [
            'tipo' => 'provincia', 'provincia_id' => $province->id, 'costo' => '0.00', 'esta_activa' => '1',
        ])->assertRedirect(route('admin.envios.index'));
        $this->assertSame('0.00', $rate->fresh()->costo);

        $this->patch(route('admin.envios.toggle', $rate))->assertRedirect();
        $this->assertFalse($rate->fresh()->esta_activa);
        $this->patch(route('admin.envios.toggle', $rate))->assertRedirect();
        $this->assertTrue($rate->fresh()->esta_activa);
    }

    public function test_default_rate_and_contextual_calculation_work(): void
    {
        $admin = $this->admin();
        $district = Distrito::whereDoesntHave('provincia.tarifaEnvio')->firstOrFail();
        TarifaEnvio::where('es_predeterminada', true)->update(['esta_activa' => false]);
        $this->actingAs($admin)->post(route('admin.envios.store'), [
            'tipo' => 'predeterminada', 'provincia_id' => null, 'costo' => '22.40', 'esta_activa' => '1',
        ])->assertRedirect();

        $calculator = app(DatabaseShippingCostCalculator::class);
        $this->assertSame(2240, $calculator->calculate(['metodo_entrega' => 'domicilio', 'distrito' => $district->id]));
        $this->assertSame(0, $calculator->calculate(['metodo_entrega' => 'retiro']));
    }

    public function test_only_one_active_default_rate_is_accepted(): void
    {
        TarifaEnvio::where('es_predeterminada', true)->update(['esta_activa' => false]);
        TarifaEnvio::create([
            'provincia_id' => null, 'costo' => '12.00',
            'es_predeterminada' => true, 'esta_activa' => true,
        ]);

        $this->actingAs($this->admin())->postJson(route('admin.envios.store'), [
            'tipo' => 'predeterminada', 'provincia_id' => null,
            'costo' => '18.00', 'esta_activa' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors('tipo');

        $this->assertSame(1, TarifaEnvio::where('es_predeterminada', true)->where('esta_activa', true)->count());
    }

    public function test_checkout_calculates_shipping_server_side_and_keeps_historical_amount(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => '123456789-shipping-rate-test',
            ], 201),
        ]);

        $district = Distrito::with('provincia.departamento')->firstOrFail();
        TarifaEnvio::where('provincia_id', $district->provincia_id)->update(['esta_activa' => false]);
        $rate = TarifaEnvio::updateOrCreate(
            ['provincia_id' => $district->provincia_id],
            ['costo' => '17.35', 'es_predeterminada' => false, 'esta_activa' => true]
        );
        $variant = VarianteProducto::where('stock', '>=', 1)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);

        $response = $this->postJson(route('checkout.procesar'), [
            'nombres' => 'Costo', 'apellidos' => 'Servidor',
            'email' => 'shipping-'.uniqid().'@example.test',
            'telefono' => '999999999', 'documento' => '12345678',
            'direccion_envio' => 'Dirección de prueba 123', 'referencia' => null,
            'metodo_entrega' => 'domicilio',
            'departamento' => $district->provincia->departamento_id,
            'provincia' => $district->provincia_id, 'distrito' => $district->id,
            'costo_envio' => '0.01', 'total' => '0.01',
        ])->assertSuccessful();

        $order = \App\Models\Pedido::where('numero_pedido', $response->json('numero_pedido'))->firstOrFail();
        $this->assertSame('17.35', $order->costo_envio);
        $this->assertSame(
            (int) round(((float) $order->subtotal - (float) $order->descuento_total + 17.35) * 100),
            (int) round((float) $order->total * 100)
        );

        $historicalShipping = $order->costo_envio;
        $historicalTotal = $order->total;
        $rate->update(['costo' => '31.90']);
        $this->assertSame($historicalShipping, $order->fresh()->costo_envio);
        $this->assertSame($historicalTotal, $order->fresh()->total);
    }

    public function test_validation_rejects_invalid_negative_missing_and_duplicate_rates(): void
    {
        $admin = $this->admin();
        $province = $this->provinceWithoutRate();
        TarifaEnvio::create(['provincia_id' => $province->id, 'costo' => '10.00', 'es_predeterminada' => false, 'esta_activa' => true]);

        $base = ['tipo' => 'provincia', 'provincia_id' => $province->id, 'costo' => '10.00', 'esta_activa' => '1'];
        $this->actingAs($admin)->postJson(route('admin.envios.store'), $base)
            ->assertUnprocessable()->assertJsonValidationErrors('provincia_id');
        $this->postJson(route('admin.envios.store'), array_merge($base, ['provincia_id' => '9999']))
            ->assertUnprocessable()->assertJsonValidationErrors('provincia_id');
        $this->postJson(route('admin.envios.store'), array_merge($base, ['provincia_id' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('provincia_id');
        $this->postJson(route('admin.envios.store'), array_merge($base, ['provincia_id' => $this->provinceWithoutRate()->id, 'costo' => '-1']))
            ->assertUnprocessable()->assertJsonValidationErrors('costo');
        $this->postJson(route('admin.envios.store'), [
            'tipo' => 'predeterminada', 'provincia_id' => $province->id, 'costo' => '1.00', 'esta_activa' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors('provincia_id');
    }

    public function test_inactive_rate_is_ignored_and_missing_fallback_blocks_checkout_calculation(): void
    {
        TarifaEnvio::query()->update(['esta_activa' => false]);
        $district = Distrito::firstOrFail();
        TarifaEnvio::create([
            'provincia_id' => $district->provincia_id,
            'costo' => '9.00',
            'es_predeterminada' => false,
            'esta_activa' => false,
        ]);

        $this->expectException(DomainException::class);
        app(DatabaseShippingCostCalculator::class)->calculate([
            'metodo_entrega' => 'domicilio', 'distrito' => $district->id,
        ]);
    }

    public function test_checkout_returns_a_clear_conflict_when_no_shipping_rate_exists(): void
    {
        TarifaEnvio::query()->update(['esta_activa' => false]);
        $district = Distrito::with('provincia')->firstOrFail();
        $variant = VarianteProducto::where('stock', '>=', 1)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);

        $this->postJson(route('checkout.procesar'), [
            'nombres' => 'Sin', 'apellidos' => 'Tarifa',
            'email' => 'without-rate-'.uniqid().'@example.test',
            'telefono' => '999999999', 'documento' => '12345678',
            'direccion_envio' => 'Dirección sin tarifa', 'referencia' => null,
            'metodo_entrega' => 'domicilio',
            'departamento' => $district->provincia->departamento_id,
            'provincia' => $district->provincia_id, 'distrito' => $district->id,
        ])->assertConflict()->assertJsonPath(
            'message',
            'No existe una tarifa de envío configurada para la dirección seleccionada.'
        );
    }

    public function test_shipping_permissions_protect_routes_and_hide_actions(): void
    {
        $viewer = User::factory()->create();
        $viewer->syncRoles([Role::findByName('gestor')]);
        $viewer->syncPermissions([Permission::findByName('envios.ver')]);

        $province = $this->provinceWithoutRate();
        $rate = TarifaEnvio::create([
            'provincia_id' => $province->id, 'costo' => '5.00',
            'es_predeterminada' => false, 'esta_activa' => true,
        ]);
        $response = $this->actingAs($viewer)->get(route('admin.envios.index'))->assertSuccessful();
        $response->assertDontSee(route('admin.envios.create'), false);
        $this->get(route('admin.envios.create'))->assertForbidden();
        $response->assertDontSee(route('admin.envios.edit', $rate), false)
            ->assertDontSee(route('admin.envios.toggle', $rate), false);
        $this->get(route('admin.envios.edit', $rate))->assertForbidden();
        $this->patch(route('admin.envios.toggle', $rate))->assertForbidden();
    }

    public function test_shipping_permissions_are_granular_and_not_automatic_for_manager(): void
    {
        $managerRole = Role::findByName('gestor');
        $manager = User::factory()->create();
        $manager->assignRole($managerRole);
        $this->assertFalse($manager->hasAnyPermission([
            'envios.ver', 'envios.crear', 'envios.editar', 'envios.cambiar_estado',
        ]));
        $this->actingAs($manager)->get(route('admin.envios.index'))->assertForbidden();

        $province = $this->provinceWithoutRate();
        $creator = $this->managerWith('envios.crear');
        $this->actingAs($creator)->post(route('admin.envios.store'), [
            'tipo' => 'provincia', 'provincia_id' => $province->id,
            'costo' => '8.00', 'esta_activa' => '1',
        ])->assertRedirect(route('admin.envios.index'));
        $rate = TarifaEnvio::where('provincia_id', $province->id)->firstOrFail();

        $editor = $this->managerWith('envios.editar');
        $this->actingAs($editor)->put(route('admin.envios.update', $rate), [
            'tipo' => 'provincia', 'provincia_id' => $province->id,
            'costo' => '9.00', 'esta_activa' => '1',
        ])->assertRedirect(route('admin.envios.index'));
        $this->assertSame('9.00', $rate->fresh()->costo);

        $toggler = $this->managerWith('envios.cambiar_estado');
        $this->actingAs($toggler)->patch(route('admin.envios.toggle', $rate))->assertRedirect();
        $this->assertFalse($rate->fresh()->esta_activa);

        $admin = $this->admin();
        $this->assertTrue($admin->hasAllPermissions([
            'envios.ver', 'envios.crear', 'envios.editar', 'envios.cambiar_estado',
        ]));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin'));

        return $user;
    }

    private function provinceWithoutRate(): Provincia
    {
        return Provincia::whereDoesntHave('tarifaEnvio')->firstOrFail();
    }

    private function managerWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('gestor'));
        $user->givePermissionTo(Permission::findByName($permission));

        return $user;
    }
}
