<?php

namespace Tests\Feature;

use App\Data\Payments\VerifiedWebhook;
use App\Domain\Orders\PedidoEstado;
use App\Domain\Orders\PedidoStateMachine;
use App\Models\EventoWebhookPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentProviderRegistry;
use App\Services\Payments\PaymentService;
use App\Services\PedidoInventarioService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fakes\FakePaymentProvider;
use Tests\TestCase;

class StageThreeCheckoutPaymentTest extends TestCase
{
    use DatabaseTransactions;

    private FakePaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new FakePaymentProvider;
        app(PaymentProviderRegistry::class)->register($this->provider);
    }

    public function test_order_creation_and_stock_reservation_are_separate_and_idempotent(): void
    {
        [$variant, $before] = $this->cartWithOneUnit();
        $checkout = app(CheckoutService::class);
        $order = $checkout->crearPedido($this->checkoutData());

        $this->assertSame(PedidoEstado::Creado->value, $order->estado);
        $this->assertSame($before, $variant->fresh()->stock);
        $this->assertNull($order->stock_reserved_at);
        $this->assertCount(1, $order->detalles);

        $inventory = app(PedidoInventarioService::class);
        $this->assertTrue($inventory->reservar($order));
        $this->assertFalse($inventory->reservar($order));
        $this->assertSame($before - 1, $variant->fresh()->stock);
        $this->assertSame(PedidoEstado::Reservado->value, $order->fresh()->estado);
    }

    public function test_checkout_is_idempotent_and_never_marks_order_as_paid(): void
    {
        $this->cartWithOneUnit();
        $checkout = app(CheckoutService::class);
        $first = $checkout->procesarCompra($this->checkoutData() + ['total' => '0.01']);
        $second = $checkout->procesarCompra($this->checkoutData() + ['total' => '999999.99']);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(PedidoEstado::Reservado->value, $second->estado);
        $this->assertSame(1, Pedido::where('carrito_id', $first->carrito_id)->count());
        $this->assertSame(
            (int) round(((float) $first->subtotal - (float) $first->descuento_total + (float) $first->costo_envio) * 100),
            (int) round((float) $first->total * 100)
        );
    }

    public function test_payment_start_is_unique_and_does_not_approve_payment(): void
    {
        $order = $this->reservedOrder();
        $payments = app(PaymentService::class);
        $first = $payments->start($order, 'fake');
        $second = $payments->start($order->fresh(), 'fake');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('pendiente', $first->estado_pago);
        $this->assertNotEmpty($first->id_transaccion);
        $this->assertSame(PedidoEstado::PendientePago->value, $order->fresh()->estado);
        $this->assertSame(1, Pago::where('pedido_id', $order->id)->count());
    }

    public function test_only_valid_webhook_approves_and_duplicate_is_idempotent_without_session(): void
    {
        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');
        $payload = [
            'event_id' => 'evt-approved-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ];

        session()->flush();
        $first = $this->sendWebhook($payload)->assertSuccessful()->assertJsonPath('duplicate', false);
        $this->sendWebhook($payload)->assertSuccessful()->assertJsonPath('duplicate', true);

        $this->assertSame(PedidoEstado::Pagado->value, $order->fresh()->estado);
        $this->assertSame('aprobado', $payment->fresh()->estado_pago);
        $this->assertNotNull($payment->fresh()->confirmado_at);
        $this->assertSame(1, EventoWebhookPago::where('evento_externo_id', $payload['event_id'])->count());
        $this->assertNotEmpty($first->json('status'));
    }

    public function test_invalid_or_unknown_webhook_changes_nothing(): void
    {
        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');
        $payload = [
            'event_id' => 'evt-invalid-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->call('POST', route('payments.webhook', 'fake'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => 'invalid',
        ], $raw)->assertUnauthorized();

        $payload['event_id'] = 'evt-unknown-'.uniqid();
        $payload['transaction_id'] = 'unknown-transaction';
        $this->sendWebhook($payload)->assertNotFound();
        $this->assertSame(PedidoEstado::PendientePago->value, $order->fresh()->estado);
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);
    }

    public function test_webhook_with_a_different_amount_reference_or_currency_is_rejected(): void
    {
        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');

        foreach ([
            ['transaction_amount' => (float) $payment->monto + 1],
            ['external_reference' => 'PEDIDO-DIFERENTE'],
            ['currency_id' => 'USD'],
        ] as $index => $providerData) {
            $this->sendWebhook([
                'event_id' => 'evt-mismatch-'.$index.'-'.uniqid(),
                'transaction_id' => $payment->id_transaccion,
                'status' => VerifiedWebhook::APPROVED,
                'provider_data' => $providerData,
            ])->assertConflict();
        }

        $this->assertSame(PedidoEstado::PendientePago->value, $order->fresh()->estado);
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);
    }

    public function test_malformed_and_transition_incompatible_webhooks_are_rejected_without_http_500(): void
    {
        $raw = '{invalid-json';
        $this->call('POST', route('payments.webhook', 'fake'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => $this->provider->sign($raw),
        ], $raw)->assertUnauthorized();

        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');
        app(PedidoInventarioService::class)->cancelar($order);

        $this->sendWebhook([
            'event_id' => 'evt-incompatible-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ])->assertConflict();

        $this->assertSame(PedidoEstado::Cancelado->value, $order->fresh()->estado);
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);
    }

    public function test_rejected_payment_releases_reserved_stock_immediately_and_only_once(): void
    {
        [$variant, $before] = $this->cartWithOneUnit();
        $order = app(CheckoutService::class)->procesarCompra($this->checkoutData());
        $payment = app(PaymentService::class)->start($order, 'fake');
        $payload = [
            'event_id' => 'evt-rejected-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::REJECTED,
        ];
        $this->sendWebhook($payload)->assertSuccessful()->assertJsonPath('duplicate', false);

        $this->assertSame(PedidoEstado::PagoFallido->value, $order->fresh()->estado);
        $this->assertSame('rechazado', $payment->fresh()->estado_pago);
        $this->assertNotNull($order->fresh()->inventory_released_at);
        $this->assertSame($before, $variant->fresh()->stock);

        $this->sendWebhook($payload)->assertSuccessful()->assertJsonPath('duplicate', true);
        $this->assertSame($before, $variant->fresh()->stock);

        $inventory = app(PedidoInventarioService::class);
        $this->assertTrue($inventory->cancelar($order->fresh()));
        $this->assertFalse($inventory->cancelar($order->fresh()));
        $this->assertSame($before, $variant->fresh()->stock);
    }

    public function test_legacy_failed_payment_with_unreleased_reservation_can_be_repaired_once(): void
    {
        [$variant, $before] = $this->cartWithOneUnit();
        $order = app(CheckoutService::class)->procesarCompra($this->checkoutData());
        $order->update(['estado' => PedidoEstado::PagoFallido->value, 'inventory_released_at' => null]);

        $inventory = app(PedidoInventarioService::class);
        $this->assertTrue($inventory->rechazarPago($order->fresh()));
        $this->assertFalse($inventory->rechazarPago($order->fresh()));
        $this->assertSame($before, $variant->fresh()->stock);
        $this->assertNotNull($order->fresh()->inventory_released_at);
        $this->assertSame(PedidoEstado::PagoFallido->value, $order->fresh()->estado);
    }

    public function test_paid_order_rejects_cancel_expiration_and_backward_transition(): void
    {
        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');
        $this->sendWebhook([
            'event_id' => 'evt-terminal-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ])->assertSuccessful();

        foreach ([
            fn () => app(PedidoInventarioService::class)->cancelar($order->fresh()),
            function () use ($order) {
                $order->update(['expires_at' => now()->subMinute()]);

                return app(PedidoInventarioService::class)->expirar($order->fresh());
            },
            fn () => app(PedidoStateMachine::class)->transition($order->fresh(), PedidoEstado::Reservado),
        ] as $invalidOperation) {
            try {
                $invalidOperation();
                $this->fail('Una operación terminal inválida no fue rechazada.');
            } catch (DomainException) {
                $this->assertSame(PedidoEstado::Pagado->value, $order->fresh()->estado);
            }
        }
    }

    public function test_snapshots_and_totals_do_not_change_with_catalog_updates(): void
    {
        [$variant] = $this->cartWithOneUnit();
        $order = app(CheckoutService::class)->procesarCompra($this->checkoutData());
        $detail = $order->detalles()->firstOrFail();
        $snapshot = $detail->only([
            'producto_nombre', 'sku', 'variante_descripcion', 'precio_base_unitario',
            'descuento_unitario', 'precio_unitario', 'cantidad', 'subtotal_linea',
        ]);

        $variant->producto->update(['nombre' => 'Nombre cambiado después de comprar']);
        $variant->update(['sku' => 'CHANGED-'.uniqid(), 'color' => 'Otro color', 'precio' => 9999]);

        $this->assertSame($snapshot, $detail->fresh()->only(array_keys($snapshot)));
        $this->assertSame(
            (int) round(((float) $detail->precio_base_unitario - (float) $detail->descuento_unitario) * 100),
            (int) round((float) $detail->precio_unitario * 100)
        );
        $this->assertSame(
            (int) round((float) $detail->precio_unitario * $detail->cantidad * 100),
            (int) round((float) $detail->subtotal_linea * 100)
        );
    }

    public function test_invalid_state_transition_is_rejected(): void
    {
        $order = $this->reservedOrder();
        $this->expectException(DomainException::class);
        app(PedidoStateMachine::class)->transition($order, PedidoEstado::Pagado);
    }

    public function test_database_rejects_duplicate_external_transaction_identifier(): void
    {
        $firstOrder = $this->reservedOrder();
        $firstPayment = app(PaymentService::class)->start($firstOrder, 'fake');

        session()->forget(['carrito_token', 'last_checkout_order_id']);
        $secondOrder = $this->reservedOrder();

        $this->expectException(QueryException::class);
        Pago::create([
            'pedido_id' => $secondOrder->id,
            'proveedor' => 'fake',
            'metodo_pago' => 'external',
            'id_transaccion' => $firstPayment->id_transaccion,
            'clave_idempotencia' => hash('sha256', 'different-key-'.$secondOrder->id),
            'monto' => $secondOrder->total,
            'moneda' => 'PEN',
            'estado_pago' => 'pendiente',
        ]);
    }

    public function test_pending_and_already_paid_webhooks_are_safe(): void
    {
        $order = $this->reservedOrder();
        $payment = app(PaymentService::class)->start($order, 'fake');

        $this->sendWebhook([
            'event_id' => 'evt-pending-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::PENDING,
        ])->assertSuccessful();
        $this->assertSame(PedidoEstado::PendientePago->value, $order->fresh()->estado);
        $this->assertSame('pendiente', $payment->fresh()->estado_pago);

        $this->sendWebhook([
            'event_id' => 'evt-first-approved-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ])->assertSuccessful();
        $this->sendWebhook([
            'event_id' => 'evt-second-approved-'.uniqid(),
            'transaction_id' => $payment->id_transaccion,
            'status' => VerifiedWebhook::APPROVED,
        ])->assertSuccessful();

        $this->assertSame(PedidoEstado::Pagado->value, $order->fresh()->estado);
        $this->assertSame(1, Pago::whereKey($payment->id)->where('estado_pago', 'aprobado')->count());
    }

    public function test_cancellation_releases_stock_exactly_once(): void
    {
        [$variant, $before] = $this->cartWithOneUnit();
        $order = app(CheckoutService::class)->procesarCompra($this->checkoutData());
        $inventory = app(PedidoInventarioService::class);

        $this->assertSame($before - 1, $variant->fresh()->stock);
        $this->assertTrue($inventory->cancelar($order));
        $this->assertFalse($inventory->cancelar($order->fresh()));
        $this->assertSame($before, $variant->fresh()->stock);
        $this->assertSame(PedidoEstado::Cancelado->value, $order->fresh()->estado);
    }

    public function test_browser_payment_confirmation_route_remains_absent(): void
    {
        $this->postJson('/checkout/pago/confirmar', [])->assertMethodNotAllowed();
    }

    private function reservedOrder(): Pedido
    {
        $this->cartWithOneUnit();

        return app(CheckoutService::class)->procesarCompra($this->checkoutData());
    }

    private function cartWithOneUnit(): array
    {
        $variant = VarianteProducto::where('stock', '>=', 5)
            ->whereHas('producto', fn ($query) => $query->where('esta_activo', true))
            ->firstOrFail();
        $before = $variant->stock;
        $result = app(CarritoService::class)->agregarItem($variant->id, 1);
        $this->assertTrue($result['success']);

        return [$variant, $before];
    }

    private function checkoutData(): array
    {
        return [
            'nombres' => 'Etapa',
            'apellidos' => 'Tres',
            'email' => 'stage-three-'.uniqid().'@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA',
            'referencia' => null,
            'metodo_entrega' => 'retiro',
        ];
    }

    private function sendWebhook(array $payload)
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', route('payments.webhook', 'fake'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => $this->provider->sign($raw),
        ], $raw);
    }
}
