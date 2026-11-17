<?php

namespace App\Http\Controllers;

use App\Domain\Checkout\ShippingRateUnavailable;
use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentProviderRegistry;
use App\Services\Payments\PaymentService;
use App\Services\Payments\WebhookPaymentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private CarritoService $carritoService,
        private PaymentProviderRegistry $paymentProviders,
        private PaymentService $payments,
        private WebhookPaymentService $webhooks,
    ) {}

    public function index()
    {
        $datosCheckout = $this->checkoutService->prepararVistaCheckout();

        if (! $datosCheckout['es_valido']) {
            $pedido = $this->checkoutService->obtenerPedidoRecuperable();
            if ($pedido) {
                return view('tienda.checkout-retry', compact('pedido'));
            }

            return redirect()->route('carrito.index');
        }

        return view('tienda.checkout', ['carrito' => $datosCheckout['carrito']]);
    }

    public function procesar(StoreCheckoutRequest $request): JsonResponse
    {
        try {
            $pedido = $this->checkoutService->procesarCompra($request->validated());
            $redirectUrl = $this->iniciarPago($pedido);

            session()->forget('carrito_token');

            return response()->json([
                'status' => 'success',
                'message' => 'Orden generada de forma segura.',
                'numero_pedido' => $pedido->numero_pedido,
                'redirect_url' => $redirectUrl,
            ]);
        } catch (ShippingRateUnavailable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 409);
        } catch (DomainException) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fue posible completar la compra. Revisa tu carrito y vuelve a intentarlo.',
            ], 409);
        } catch (Throwable $exception) {
            $incidentId = (string) Str::uuid();
            Log::error('Checkout processing failed.', [
                'incident_id' => $incidentId,
                'exception' => $exception,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al procesar la compra.',
                'incident_id' => $incidentId,
            ], 500);
        }
    }

    public function reintentarPago(string $numeroPedido): RedirectResponse
    {
        $pedido = $this->checkoutService->obtenerPedidoRecuperable($numeroPedido);
        abort_unless($pedido, 404);

        try {
            $redirectUrl = $this->iniciarPago($pedido);
            session()->forget('carrito_token');

            return redirect()->to($redirectUrl);
        } catch (Throwable $exception) {
            $incidentId = (string) Str::uuid();
            Log::error('Checkout payment retry failed.', [
                'incident_id' => $incidentId,
                'pedido_id' => $pedido->id,
                'exception' => $exception,
            ]);

            return back()->with(
                'error',
                "No pudimos conectar con Mercado Pago. Intenta nuevamente. Incidente: {$incidentId}",
            );
        }
    }

    private function iniciarPago(Pedido $pedido): string
    {
        if ($this->paymentProviders->has('mercadopago')) {
            $this->payments->start($pedido, 'mercadopago', 'checkout_pro');

            return route('payments.mercadopago.show', $pedido->numero_pedido);
        }

        if ($this->paymentProviders->has('simulated')) {
            $payment = $this->payments->start($pedido, 'simulated', 'simulated');

            return route('payments.simulator.show', $payment);
        }

        return route('checkout.pago', $pedido->numero_pedido);
    }

    public function mercadoPago(string $numeroPedido): View
    {
        abort_unless(
            config('checkout.mercadopago.enabled')
                && config('checkout.mercadopago.mode') === 'test'
                && ! config('checkout.mercadopago.allow_live'),
            404,
        );

        $pedido = $this->checkoutService->obtenerPedidoRecuperable($numeroPedido);
        abort_unless($pedido, 404);

        $pago = $pedido->pagos
            ->where('proveedor', 'mercadopago')
            ->sortByDesc('id')
            ->first();
        abort_unless($pago, 404);

        $preferenceId = data_get($pago->respuesta_pasarela, 'preference_id');
        $publicKey = config('checkout.mercadopago.test.public_key');
        abort_unless(
            is_string($preferenceId)
                && preg_match('/\A[0-9]+-[A-Za-z0-9-]+\z/', $preferenceId) === 1
                && is_string($publicKey)
                && trim($publicKey) !== '',
            503,
        );

        return view('tienda.pagos.mercadopago', compact(
            'pedido',
            'pago',
            'preferenceId',
            'publicKey',
        ));
    }

    public function pago(Request $request, string $numero_pedido)
    {
        $pedido = $this->pedidoDeLaSesion($numero_pedido);

        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        if ($pedido->estado === 'pendiente_pago'
            && is_scalar($paymentId)
            && ctype_digit((string) $paymentId)
            && $this->paymentProviders->has('mercadopago')) {
            try {
                $this->webhooks->reconcile('mercadopago', (string) $paymentId);
                $pedido->refresh();
            } catch (Throwable $exception) {
                Log::warning('Mercado Pago return could not be reconciled yet.', [
                    'pedido_id' => $pedido->id,
                    'payment_id' => (string) $paymentId,
                    'exception' => $exception::class,
                ]);
            }
        }

        return view('tienda.pago', compact('pedido'));
    }

    public function estadoPago(string $numeroPedido): JsonResponse
    {
        $pedido = $this->pedidoDeLaSesion($numeroPedido);
        $pago = $pedido->pagos()->latest('id')->first();

        return response()->json([
            'estado_pedido' => $pedido->estado,
            'estado_pago' => $pago?->estado_pago,
            'confirmado' => $pedido->estado === 'pagado',
            'finalizado' => in_array($pedido->estado, ['pagado', 'pago_fallido', 'expirado', 'cancelado'], true),
        ]);
    }

    private function pedidoDeLaSesion(string $numeroPedido): Pedido
    {
        $sessionTokenHash = $this->carritoService->obtenerHashPropietarioActual();
        abort_unless($sessionTokenHash, 404);

        return Pedido::where('numero_pedido', $numeroPedido)
            ->where('session_token_hash', $sessionTokenHash)
            ->firstOrFail();
    }
}
