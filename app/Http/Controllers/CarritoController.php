<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreElementoCarritoRequest;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CarritoController extends Controller
{
    public function __construct(private CarritoService $carritoService)
    {
    }

    public function index()
    {
        $carrito = $this->carritoService->obtenerCarritoActual();

        return view('tienda.carrito', compact('carrito'));
    }

    public function agregar(StoreElementoCarritoRequest $request): JsonResponse
    {
        try {
            $resultado = $this->carritoService->agregarItem($request->variante_id, $request->cantidad);

            if (!$resultado['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultado['message'],
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Producto agregado al carrito',
                'total_items' => $resultado['total_items'],
            ]);
        } catch (Throwable $exception) {
            return $this->respuestaErrorInterno($exception);
        }
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $request->validate(['cantidad' => 'required|integer|min:1']);

        try {
            $resultado = $this->carritoService->actualizarCantidad($id, $request->cantidad);

            if (!$resultado['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultado['message'],
                ], 422);
            }

            return response()->json(['status' => 'success']);
        } catch (Throwable $exception) {
            return $this->respuestaErrorInterno($exception);
        }
    }

    public function eliminar(int $id): JsonResponse
    {
        try {
            if (!$this->carritoService->eliminarItem($id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El elemento no existe en tu carrito.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Producto removido del carrito',
            ]);
        } catch (Throwable $exception) {
            return $this->respuestaErrorInterno($exception);
        }
    }

    private function respuestaErrorInterno(Throwable $exception): JsonResponse
    {
        $incidentId = (string) Str::uuid();
        Log::error('Cart operation failed.', [
            'incident_id' => $incidentId,
            'exception' => $exception,
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'No fue posible procesar el carrito.',
            'incident_id' => $incidentId,
        ], 500);
    }
}
