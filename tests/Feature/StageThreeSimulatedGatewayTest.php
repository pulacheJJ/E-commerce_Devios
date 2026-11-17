<?php

namespace Tests\Feature;

use App\Models\EventoWebhookPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentProviderRegistry;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SimulatedPaymentProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StageThreeSimulatedGatewayTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'simulator-test-secret-with-32-characters-minimum';

    public function test_simulator_routes_are_hidden_when_disabled(): void
    {
        config(['checkout.simulator.enabled' => false]);
        $this->app->instance(PaymentProviderRegistry::class, new PaymentProviderRegistry);

        $this->get('/demo/pagos/1')->assertNotFound();
        $this->post('/demo/pagos/1/simular', ['scenario' => 'aprobado'])->assertNotFound();
    }

    public function test_simulator_implements_contract_and_screen_is_session_protected(): void
    {
        $provider = $this->enableSimulator();
        $this->assertInstanceOf(\App\Contracts\Payments\PaymentProvider::class, $provider);
        $payment = $this->simulatedPayment();

        $this->get(route('payments.simulator.show', $payment))
            ->assertSuccessful()
            ->assertSee('MODO DEMOSTRACIÓN')
            ->assertSee('no realiza cobros reales')
            ->assertSee('No ingrese información bancaria real')
            ->assertSee($payment->id_transaccion);

        session()->flush();
        app(CarritoService::class)->obtenerHashPropietarioActual(true);
        $this->get(route('payments.simulator.show', $payment))->assertNotFound();
    }

    public function test_approved_scenario_uses_verified_webhook_and_is_idempotent(): void
    {
        $this->enableSimulator();
        $payment = $this->simulatedPayment();
        $variant = $payment->pedido->detalles()->firstOrFail()->variante;
        $stockAfterReservation = $variant->stock;
        $payload = ['scenario' => 'aprobado', 'total' => '0.01', 'provider' => 'manipulated'];

        $this->post(route('payments.simulator.simulate', $payment), $payload)
            ->assertRedirect(route('payments.simulator.show', $payment));
        $this->post(route('payments.simulator.simulate', $payment), $payload)
            ->assertRedirect(route('payments.simulator.show', $payment));

        $payment->refresh();
        $this->assertSame('simulated', $payment->proveedor);
        $this->assertSame('aprobado', $payment->estado_pago);
        $this->assertSame('pagado', $payment->pedido->fresh()->estado);
        $this->assertSame($payment->pedido->total, $payment->monto);
        $this->assertSame(1, EventoWebhookPago::where('pago_id', $payment->id)->count());
        $this->assertSame(1, Pago::where('pedido_id', $payment->pedido_id)->count());
        $this->assertSame($stockAfterReservation, $variant->fresh()->stock);
        $this->assertNull(data_get($payment->respuesta_pasarela, 'card'));

        $reused = app(PaymentService::class)->start($payment->pedido->fresh(), 'simulated', 'simulated');
        $this->assertSame($payment->id, $reused->id);
        $this->assertSame($payment->id_transaccion, $reused->id_transaccion);
    }

    public function test_pending_scenario_can_later_be_approved_and_get_cannot_confirm(): void
    {
        $this->enableSimulator();
        $payment = $this->simulatedPayment();

        $this->post(route('payments.simulator.simulate', $payment), ['scenario' => 'pendiente'])
            ->assertRedirect();
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);
        $this->assertSame('pendiente_pago', $payment->pedido->fresh()->estado);

        $this->post(route('payments.simulator.simulate', $payment), ['scenario' => 'aprobado'])
            ->assertRedirect();
        $this->assertSame('aprobado', $payment->fresh()->estado_pago);
        $this->assertSame('pagado', $payment->pedido->fresh()->estado);
        $this->assertSame(2, EventoWebhookPago::where('pago_id', $payment->id)->count());

        $this->get(route('payments.simulator.simulate', $payment))->assertMethodNotAllowed();
    }

    public function test_rejected_and_cancelled_scenarios_never_mark_order_paid(): void
    {
        $this->enableSimulator();
        $rejected = $this->simulatedPayment();
        $detail = $rejected->pedido->detalles()->firstOrFail();
        $variant = $detail->variante;
        $reservedStock = $variant->stock;
        $this->post(route('payments.simulator.simulate', $rejected), ['scenario' => 'rechazado'])->assertRedirect();
        $this->assertSame('rechazado', $rejected->fresh()->estado_pago);
        $this->assertSame('pago_fallido', $rejected->pedido->fresh()->estado);
        $this->assertNotNull($rejected->pedido->fresh()->inventory_released_at);
        $this->assertSame($reservedStock + $detail->cantidad, $variant->fresh()->stock);

        $this->post(route('payments.simulator.simulate', $rejected), ['scenario' => 'rechazado'])->assertRedirect();
        $this->assertSame($reservedStock + $detail->cantidad, $variant->fresh()->stock);

        session()->forget(['carrito_token', 'last_checkout_order_id']);
        $cancelled = $this->simulatedPayment();
        $this->post(route('payments.simulator.cancel', $cancelled))->assertRedirect();
        $this->assertSame('pendiente', $cancelled->fresh()->estado_pago);
        $this->assertSame('cancelado', $cancelled->pedido->fresh()->estado);
    }

    public function test_invalid_simulator_signature_is_rejected(): void
    {
        $this->enableSimulator();
        $payment = $this->simulatedPayment();
        $payload = json_encode([
            'event_id' => 'tampered-event',
            'transaction_id' => $payment->id_transaccion,
            'status' => 'approved',
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('payments.webhook', 'simulated'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIMULATOR_SIGNATURE' => 'invalid',
        ], $payload)->assertUnauthorized();
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);
    }

    public function test_incompatible_simulator_actions_return_conflict_instead_of_http_500(): void
    {
        $this->enableSimulator();
        $payment = $this->simulatedPayment();
        $this->post(route('payments.simulator.simulate', $payment), ['scenario' => 'aprobado'])
            ->assertRedirect();

        $this->post(route('payments.simulator.simulate', $payment), ['scenario' => 'rechazado'])
            ->assertConflict();
        $this->post(route('payments.simulator.cancel', $payment))->assertConflict();
        $this->assertSame('pagado', $payment->pedido->fresh()->estado);
        $this->assertSame('aprobado', $payment->fresh()->estado_pago);
    }

    public function test_simulator_hides_a_payment_whose_order_is_not_in_a_payment_state(): void
    {
        $this->enableSimulator();
        $payment = $this->simulatedPayment();
        $payment->pedido->update(['estado' => 'reservado']);

        $this->get(route('payments.simulator.show', $payment))->assertNotFound();
        $this->post(route('payments.simulator.simulate', $payment), ['scenario' => 'aprobado'])
            ->assertNotFound();
    }

    private function enableSimulator(): SimulatedPaymentProvider
    {
        config(['checkout.simulator.enabled' => true, 'checkout.simulator.secret' => self::SECRET]);
        $provider = new SimulatedPaymentProvider(self::SECRET);
        $registry = new PaymentProviderRegistry;
        $registry->register($provider);
        $this->app->instance(PaymentProviderRegistry::class, $registry);

        return $provider;
    }

    private function simulatedPayment(): Pago
    {
        $variant = VarianteProducto::where('stock', '>=', 2)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);
        $order = app(CheckoutService::class)->procesarCompra([
            'nombres' => 'Demo', 'apellidos' => 'Pago', 'email' => uniqid().'@demo.test',
            'telefono' => '999999999', 'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA', 'referencia' => null, 'metodo_entrega' => 'retiro',
        ]);

        return app(PaymentService::class)->start($order, 'simulated', 'simulated');
    }
}
