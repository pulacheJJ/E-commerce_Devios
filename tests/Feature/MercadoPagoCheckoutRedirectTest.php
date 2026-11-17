<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\VarianteProducto;
use App\Services\CarritoService;
use App\Services\Payments\MercadoPagoPaymentProvider;
use App\Services\Payments\PaymentProviderRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoCheckoutRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_checkout_creates_one_test_preference_and_opens_the_official_wallet_brick(): void
    {
        config([
            'app.url' => 'https://devioz-tunnel.test',
            'checkout.mercadopago.enabled' => true,
            'checkout.mercadopago.mode' => 'test',
            'checkout.mercadopago.allow_live' => false,
            'checkout.mercadopago.test.public_key' => 'TEST_PUBLIC_KEY',
        ]);
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => '123456789-test-preference-checkout',
                'init_point' => 'https://www.mercadopago.com/real-checkout-must-not-be-used',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/test-preference-checkout',
            ], 201),
        ]);

        $registry = new PaymentProviderRegistry;
        $registry->register(new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false));
        $this->app->instance(PaymentProviderRegistry::class, $registry);

        $variant = VarianteProducto::where('stock', '>=', 1)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);

        $response = $this->postJson(route('checkout.procesar'), [
            'nombres' => 'Mercado',
            'apellidos' => 'Pago',
            'email' => uniqid('mp-').'@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA',
            'referencia' => null,
            'metodo_entrega' => 'retiro',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath(
                'redirect_url',
                route('payments.mercadopago.show', $response->json('numero_pedido')),
            );

        $payment = Pago::where('proveedor', 'mercadopago')->latest('id')->firstOrFail();
        $this->assertSame('checkout_pro', $payment->metodo_pago);
        $this->assertSame('pendiente', $payment->estado_pago);
        $this->assertSame('test', data_get($payment->respuesta_pasarela, 'mode'));
        $this->assertFalse(data_get($payment->respuesta_pasarela, 'real_charge'));
        $this->assertArrayNotHasKey('checkout_url', $payment->respuesta_pasarela);
        $this->assertSame(1, Pago::where('pedido_id', $payment->pedido_id)->count());
        $this->assertFalse(session()->has('carrito_token'));

        $this->get(route('payments.mercadopago.show', $payment->pedido->numero_pedido))
            ->assertSuccessful()
            ->assertSee('https://sdk.mercadopago.com/js/v2', false)
            ->assertSee('TEST_PUBLIC_KEY', false)
            ->assertSee('123456789-test-preference-checkout', false)
            ->assertSee("js/tienda/pagos/mercadopago.js", false)
            ->assertDontSee('sandbox_init_point', false);

        $this->getJson(route('checkout.pago.estado', $payment->pedido->numero_pedido))
            ->assertSuccessful()
            ->assertJsonPath('estado_pedido', 'pendiente_pago')
            ->assertJsonPath('estado_pago', 'pendiente')
            ->assertJsonPath('confirmado', false)
            ->assertJsonPath('finalizado', false);

        $this->get(route('checkout.index'))
            ->assertSuccessful()
            ->assertSee('Pago preparado');
        $this->post(route('checkout.reintentar', $payment->pedido->numero_pedido))
            ->assertRedirect(route('payments.mercadopago.show', $payment->pedido->numero_pedido));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://api.mercadopago.com/checkout/preferences'
            && str_starts_with($request['back_urls']['success'], 'https://')
            && ! array_key_exists('notification_url', $request->data())
        );

        $this->flushSession();
        $this->get(route('payments.mercadopago.show', $payment->pedido->numero_pedido))
            ->assertNotFound();
        $this->getJson(route('checkout.pago.estado', $payment->pedido->numero_pedido))
            ->assertNotFound();
    }

    public function test_failed_gateway_start_keeps_the_cart_and_reuses_the_reserved_order(): void
    {
        config([
            'app.url' => 'https://devioz-tunnel.test',
            'checkout.mercadopago.enabled' => true,
            'checkout.mercadopago.mode' => 'test',
            'checkout.mercadopago.allow_live' => false,
            'checkout.mercadopago.test.public_key' => 'TEST_PUBLIC_KEY',
        ]);
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::sequence()
                ->push(['message' => 'temporary gateway failure'], 503)
                ->push([
                    'id' => '123456789-test-preference-retry',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com.pe/checkout/test-preference-retry',
                ], 201),
        ]);

        $registry = new PaymentProviderRegistry;
        $registry->register(new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false));
        $this->app->instance(PaymentProviderRegistry::class, $registry);

        $variant = VarianteProducto::where('stock', '>=', 1)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);
        $cartToken = session('carrito_token');
        $data = [
            'nombres' => 'Reintento',
            'apellidos' => 'Seguro',
            'email' => uniqid('retry-').'@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA',
            'referencia' => null,
            'metodo_entrega' => 'retiro',
        ];

        $this->postJson(route('checkout.procesar'), $data)->assertServerError();
        $this->assertSame($cartToken, session('carrito_token'));
        $this->assertSame(1, \App\Models\Pedido::where('carrito_token_hash', hash('sha256', $cartToken))->count());

        $order = \App\Models\Pedido::where('carrito_token_hash', hash('sha256', $cartToken))->firstOrFail();
        $this->assertSame(0, Pago::where('pedido_id', $order->id)->count());
        $this->assertSame($order->id, session('last_checkout_order_id'));
        $this->assertSame($order->session_token_hash, hash('sha256', session('checkout_owner_token')));
        $this->assertTrue($order->expires_at->isFuture());
        $this->assertNotNull($order->carrito);
        $this->assertFalse($order->carrito->elementos()->exists());

        session()->forget('carrito_token');
        $this->get(route('checkout.index'))
            ->assertSuccessful()
            ->assertSee('Tu pedido continúa reservado');

        $this->post(route('checkout.reintentar', $order->numero_pedido))
            ->assertRedirect(route('payments.mercadopago.show', $order->numero_pedido));

        $this->get(route('payments.mercadopago.show', $order->numero_pedido))
            ->assertSuccessful()
            ->assertSee('123456789-test-preference-retry', false);

        $this->assertSame(1, \App\Models\Pedido::where('carrito_token_hash', hash('sha256', $cartToken))->count());
        $this->assertSame(1, Pago::where('pedido_id', $order->id)->where('proveedor', 'mercadopago')->count());
        $this->assertFalse(session()->has('carrito_token'));
    }

    public function test_wallet_brick_route_does_not_expose_unknown_orders(): void
    {
        config([
            'checkout.mercadopago.enabled' => true,
            'checkout.mercadopago.mode' => 'test',
            'checkout.mercadopago.allow_live' => false,
            'checkout.mercadopago.test.public_key' => 'TEST_PUBLIC_KEY',
        ]);

        $this->flushSession();

        $this->get(route('payments.mercadopago.show', 'PED-NO-EXISTE'))
            ->assertNotFound();
    }

    public function test_signed_webhook_simulator_event_with_fake_payment_id_is_acknowledged(): void
    {
        $secret = 'webhook-test-secret';
        $dataId = '123456';
        $requestId = 'simulator-request-id';
        $timestamp = '1780000000';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";

        Http::fake([
            "api.mercadopago.com/v1/payments/{$dataId}" => Http::response([
                'message' => 'Payment not found',
                'error' => 'not_found',
                'status' => 404,
            ], 404),
        ]);

        $registry = new PaymentProviderRegistry;
        $registry->register(new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false, $secret));
        $this->app->instance(PaymentProviderRegistry::class, $registry);

        $response = $this->withHeaders([
            'x-request-id' => $requestId,
            'x-signature' => 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, $secret),
        ])->postJson(
            route('payments.webhook', ['provider' => 'mercadopago']).'?data_id='.$dataId,
            [
                'action' => 'payment.updated',
                'api_version' => 'v1',
                'data' => ['id' => $dataId],
                'date_created' => '2021-11-01T02:02:02Z',
                'id' => $dataId,
                'live_mode' => false,
                'type' => 'payment',
                'user_id' => 3558923620,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('received', true)
            ->assertJsonPath('ignored', true)
            ->assertJsonPath('reason', 'provider_resource_not_found');

        Http::assertSentCount(1);
    }

    public function test_approved_test_payment_is_reconciled_securely_when_mercado_pago_returns(): void
    {
        config([
            'app.url' => 'https://devioz-tunnel.test',
            'checkout.mercadopago.enabled' => true,
            'checkout.mercadopago.mode' => 'test',
            'checkout.mercadopago.allow_live' => false,
            'checkout.mercadopago.test.public_key' => 'TEST_PUBLIC_KEY',
        ]);
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => '123456789-return-reconciliation',
            ], 201),
        ]);

        $registry = new PaymentProviderRegistry;
        $registry->register(new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false, 'secret'));
        $this->app->instance(PaymentProviderRegistry::class, $registry);

        $variant = VarianteProducto::where('stock', '>=', 1)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $this->assertTrue(app(CarritoService::class)->agregarItem($variant->id, 1)['success']);

        $checkout = $this->postJson(route('checkout.procesar'), [
            'nombres' => 'Retorno',
            'apellidos' => 'Seguro',
            'email' => uniqid('mp-return-').'@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA',
            'referencia' => null,
            'metodo_entrega' => 'retiro',
        ])->assertSuccessful();

        $payment = Pago::where('proveedor', 'mercadopago')->latest('id')->firstOrFail();
        $paymentId = '170824279236';
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'id' => (int) $paymentId,
                'live_mode' => true,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'external_reference' => $payment->pedido->numero_pedido,
                'transaction_amount' => (float) $payment->monto,
                'currency_id' => $payment->moneda,
                'metadata' => ['local_payment_reference' => $payment->id_transaccion],
            ]),
        ]);

        $this->get(route('checkout.pago', $checkout->json('numero_pedido')).'?resultado=aprobado&collection_id='.$paymentId)
            ->assertOk()
            ->assertViewIs('tienda.pago')
            ->assertSee('paymentSuccessModal', false)
            ->assertSee('data-confirmed="true"', false)
            ->assertSee('Pago realizado con éxito');

        $this->assertSame('pagado', $payment->pedido->refresh()->estado);
        $this->assertSame('aprobado', $payment->refresh()->estado_pago);
        $this->assertSame($paymentId, data_get($payment->respuesta_pasarela, 'payment_id'));
    }
}
