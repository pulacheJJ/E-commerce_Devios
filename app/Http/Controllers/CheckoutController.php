<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private CarritoService $carritoService
    ) {
    }

    public function index()
    {
        $datosCheckout = $this->checkoutService->prepararVistaCheckout();

        if (!$datosCheckout['es_valido']) {
            return redirect()->route('carrito.index');
        }

        return view('tienda.checkout', ['carrito' => $datosCheckout['carrito']]);
    }

    public function procesar(StoreCheckoutRequest $request): JsonResponse
    {
        try {
            $pedido = $this->checkoutService->procesarCompra($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Orden generada de forma segura.',
                'numero_pedido' => $pedido->numero_pedido,
                'redirect_url' => route('checkout.pago', $pedido->numero_pedido),
            ]);
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

    public function pago(string $numero_pedido)
    {
        $sessionTokenHash = $this->carritoService->obtenerHashPropietarioActual();

        abort_unless($sessionTokenHash, 404);

        $pedido = Pedido::where('numero_pedido', $numero_pedido)
            ->where('session_token_hash', $sessionTokenHash)
            ->firstOrFail();

        if ($pedido->estado === 'pagado') {
            return redirect()->route('tienda.inicio')
                ->with('success', 'Este pedido ya fue pagado exitosamente.');
        }

        return view('tienda.pago', compact('pedido'));
    }
}
