<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Requests\ConfirmarPagoRequest; // El nuevo Request que creaste
use App\Services\CheckoutService;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    private $checkoutService;

    // Inyectamos el CheckoutService
    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /* =========================================================
       PASO 2: ENTREGA (Datos del cliente y dirección)
       ========================================================= */

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
            // El servicio procesa la compra y devuelve el pedido generado
            $pedido = $this->checkoutService->procesarCompra($request->validated());

            return response()->json([
                'status'        => 'success',
                'message'       => 'Orden generada. Redirigiendo a la pasarela de pago...',
                'numero_pedido' => $pedido->numero_pedido,
                // Redirigimos a la vista de pago (Paso 3)
                'redirect_url'  => route('checkout.pago', $pedido->numero_pedido) 
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /* =========================================================
       PASO 3: PAGO (Selección de método y confirmación)
       ========================================================= */

    public function pago($numero_pedido)
    {
        $pedido = Pedido::where('numero_pedido', $numero_pedido)->firstOrFail();
        
        // Evitar doble pago si el cliente recarga la página
        if ($pedido->estado === 'pagado') {
            return redirect()->route('tienda.inicio')->with('success', 'Este pedido ya fue pagado exitosamente.');
        }

        return view('tienda.pago', compact('pedido'));
    }

    public function confirmarPago(ConfirmarPagoRequest $request): JsonResponse
    {
        // El $request ya pasó por las reglas de validación de ConfirmarPagoRequest
        $pedido = Pedido::where('numero_pedido', $request->numero_pedido)->firstOrFail();

        try {
            // El servicio registra la transacción en la tabla pagos y cambia el estado
            $this->checkoutService->registrarPago($pedido, $request->metodo_pago);

            return response()->json([
                'status'       => 'success',
                'message'      => '¡Pago procesado con éxito!',
                'redirect_url' => route('tienda.inicio') // Fin del flujo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error', 
                'message' => 'Ocurrió un error interno al registrar el pago.'
            ], 500);
        }
    }
}