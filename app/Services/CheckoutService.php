<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Pago;
use App\Models\Carrito;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{
    private $carritoService;

    // Inyectamos el servicio del carrito
    public function __construct(CarritoService $carritoService)
    {
        $this->carritoService = $carritoService;
    }

    /* =========================================================
       MÉTODOS PÚBLICOS (Orquestadores de Casos de Uso)
       ========================================================= */

    /**
     * Valida y prepara los datos para renderizar la vista de checkout (Paso 2).
     */
    public function prepararVistaCheckout(): array
    {
        $carrito = $this->carritoService->obtenerCarritoActual();

        if (!$carrito || $carrito->elementos->count() === 0) {
            return ['es_valido' => false, 'carrito' => null];
        }

        return ['es_valido' => true, 'carrito' => $carrito];
    }

    /**
     * Procesa la orden de compra y reserva el pedido (Paso 2).
     */
    public function procesarCompra(array $datosValidados): Pedido
    {
        $carrito = $this->carritoService->obtenerCarritoActual();

        if (!$carrito || $carrito->elementos->count() === 0) {
            throw new Exception('El carrito está vacío o la sesión expiró.');
        }

        return DB::transaction(function () use ($datosValidados, $carrito) {

            $cliente = $this->guardarCliente($datosValidados);
            $pedido = $this->generarPedido($cliente->id, $datosValidados, $carrito->total);

            $this->procesarDetallesYStock($pedido->id, $carrito);
            $this->limpiarCarrito($carrito);

            return $pedido;
        });
    }

    /**
     * Registra el pago en la base de datos y actualiza el pedido (Paso 3).
     */
    public function registrarPago(Pedido $pedido, string $metodoPago): Pago
    {
        return DB::transaction(function () use ($pedido, $metodoPago) {

            // 1. Crear el registro en la tabla pagos
            $pago = Pago::create([
                'pedido_id' => $pedido->id,
                'metodo_pago' => $metodoPago,
                'id_transaccion' => 'TRX-' . strtoupper(Str::random(10)), // Simulación del ID de pasarela
                'monto' => $pedido->total,
                'estado_pago' => 'aprobado',
                'respuesta_pasarela' => 'Pago procesado exitosamente'
            ]);

            // 2. Actualizar el estado del pedido maestro a 'pagado'
            $pedido->update(['estado' => 'pagado']);

            return $pago;
        });
    }

    /* =========================================================
       MÉTODOS PRIVADOS (Responsabilidades Aisladas - SRP)
       ========================================================= */

    private function guardarCliente(array $datos): Cliente
    {
        return Cliente::updateOrCreate(
            ['email' => $datos['email']],
            [
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'telefono' => $datos['telefono'],
                'tipo_documento' => 'DNI',
                'numero_documento' => $datos['documento'],
                'direccion_predeterminada' => $datos['direccion_envio']
            ]
        );
    }

    private function generarPedido(int $clienteId, array $datos, float $totalCarrito): Pedido
    {
        return Pedido::create([
            'cliente_id' => $clienteId,
            'numero_pedido' => 'PED-' . strtoupper(Str::random(8)),
            'direccion_envio' => $datos['direccion_envio'],
            'referencia' => $datos['referencia'] ?? null,
            'total' => $totalCarrito,
            'estado' => 'pendiente', // Nace pendiente hasta que pague en el Paso 3
        ]);
    }

    private function procesarDetallesYStock(int $pedidoId, Carrito $carrito): void
    {
        foreach ($carrito->elementos as $item) {

            if ($item->variante->stock < $item->cantidad) {
                throw new Exception("Stock insuficiente para: " . $item->variante->producto->nombre);
            }

            DetallePedido::create([
                'pedido_id' => $pedidoId,
                'variante_id' => $item->variante_id,
                'cantidad' => $item->cantidad,
                'precio_unitario' => $item->precio_unitario_real,
            ]);

            // Reducimos el inventario físico al instante
            $item->variante->decrement('stock', $item->cantidad);
        }
    }

    private function limpiarCarrito(Carrito $carrito): void
    {
        $carrito->elementos()->delete();
        $carrito->delete();
        session()->forget('carrito_token');
    }
}