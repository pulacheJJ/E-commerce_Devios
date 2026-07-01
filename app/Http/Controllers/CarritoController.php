<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreElementoCarritoRequest;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    protected $carritoService;

    public function __construct(CarritoService $carritoService)
    {
        $this->carritoService = $carritoService;
    }

    /**
     * Muestra la vista principal del carrito.
     */
    public function index()
    {
        $carrito = $this->carritoService->obtenerCarritoActual();
        return view('tienda.carrito', compact('carrito'));
    }

    /**
     * POST: Agrega un nuevo producto desde la vista de detalle.
     */
    public function agregar(StoreElementoCarritoRequest $request): JsonResponse
    {
        $resultado = $this->carritoService->agregarItem($request->variante_id, $request->cantidad);

        // Si excede el stock o límite, devolvemos error 422 para que el JS lance el alert
        if (!$resultado['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $resultado['message']
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Producto agregado al carrito',
            'total_items' => $resultado['total_items']
        ]);
    }

    /**
     * PUT: Actualiza la cantidad usando los botones de (+ y -) en el carrito.
     */
    public function actualizar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1'
        ]);

        $resultado = $this->carritoService->actualizarCantidad($id, $request->cantidad);

        if (!$resultado['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $resultado['message']
            ], 422);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * DELETE: Elimina la fila entera del carrito.
     */
    public function eliminar(int $id): JsonResponse
    {
        $this->carritoService->eliminarItem($id);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Producto removido del carrito'
        ]);
    }
}