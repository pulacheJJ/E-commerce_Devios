<?php

namespace App\Http\Controllers;

use App\Domain\Orders\PedidoEstado;
use App\Http\Requests\SimulatePaymentRequest;
use App\Models\Pago;
use App\Services\CarritoService;
use App\Services\Payments\PaymentProviderRegistry;
use App\Services\Payments\SimulatedPaymentProvider;
use App\Services\Payments\WebhookPaymentService;
use App\Services\PedidoInventarioService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SimulatedPaymentController extends Controller
{
    public function __construct(
        private CarritoService $carts,
        private PaymentProviderRegistry $providers,
        private WebhookPaymentService $webhooks,
        private PedidoInventarioService $inventory,
    ) {}

    public function show(Pago $pago): View
    {
        $payment = $this->ownedPayment($pago);

        return view('tienda.pagos.simulador', ['pago' => $payment, 'pedido' => $payment->pedido]);
    }

    public function simulate(SimulatePaymentRequest $request, Pago $pago): RedirectResponse
    {
        $payment = $this->ownedPayment($pago);
        $scenario = $request->validated('scenario');
        $orderState = $payment->pedido->estado;
        $repeatOfTerminalResult =
            ($orderState === PedidoEstado::Pagado->value && $scenario === 'aprobado')
            || ($orderState === PedidoEstado::PagoFallido->value && $scenario === 'rechazado');
        if ($orderState !== PedidoEstado::PendientePago->value && ! $repeatOfTerminalResult) {
            abort(409, 'El pedido no admite este escenario simulado.');
        }

        $provider = $this->providers->get('simulated');
        if (! $provider instanceof SimulatedPaymentProvider) {
            abort(404);
        }
        $event = $provider->createSignedEvent($payment, $scenario);
        try {
            $this->webhooks->process('simulated', $event['raw_payload'], $event['headers']);
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        return redirect()->route('payments.simulator.show', $payment)
            ->with('simulated_result', $scenario);
    }

    public function cancel(Pago $pago): RedirectResponse
    {
        $payment = $this->ownedPayment($pago);
        try {
            $this->inventory->cancelar($payment->pedido);
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        return redirect()->route('payments.simulator.show', $payment)
            ->with('simulated_result', 'cancelado');
    }

    private function ownedPayment(Pago $payment): Pago
    {
        $sessionHash = $this->carts->obtenerHashPropietarioActual();
        abort_unless($sessionHash, 404);

        $payment->load('pedido');
        abort_unless(
            $payment->proveedor === 'simulated'
            && $payment->pedido
            && hash_equals((string) $payment->pedido->session_token_hash, $sessionHash),
            404
        );
        abort_unless(in_array($payment->pedido->estado, [
            PedidoEstado::PendientePago->value,
            PedidoEstado::Pagado->value,
            PedidoEstado::PagoFallido->value,
            PedidoEstado::Expirado->value,
            PedidoEstado::Cancelado->value,
        ], true), 404);

        return $payment;
    }
}
