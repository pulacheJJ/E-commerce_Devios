<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\ElementoCarrito;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private CarritoService $carritoService,
        private DireccionService $direccionService
    ) {}

    public function prepararVistaCheckout(): array
    {
        $carrito = $this->carritoService->obtenerCarritoActual();

        if (! $carrito || $carrito->elementos->isEmpty()) {
            return ['es_valido' => false, 'carrito' => null];
        }

        return ['es_valido' => true, 'carrito' => $carrito];
    }

    public function procesarCompra(array $datosValidados): Pedido
    {
        $sessionTokenHash = $this->carritoService->obtenerHashPropietarioActual(true);
        $carritoActual = $this->carritoService->obtenerCarritoActual();

        if (! $carritoActual && session()->has('last_checkout_order_id')) {
            $pedidoAnterior = Pedido::whereKey(session()->get('last_checkout_order_id'))
                ->where('session_token_hash', $sessionTokenHash)
                ->first();

            if ($pedidoAnterior) {
                return $pedidoAnterior;
            }
        }

        if (! $carritoActual || ! $sessionTokenHash) {
            throw new DomainException('No existe un carrito válido para esta sesión.');
        }

        $pedido = DB::transaction(function () use ($datosValidados, $carritoActual, $sessionTokenHash) {
            $carrito = Carrito::whereKey($carritoActual->id)
                ->where('token_sesion', $carritoActual->token_sesion)
                ->lockForUpdate()
                ->first();

            if (! $carrito) {
                throw new DomainException('El carrito ya no está disponible.');
            }

            $pedidoExistente = Pedido::where('carrito_id', $carrito->id)->first();

            if ($pedidoExistente) {
                if (! hash_equals((string) $pedidoExistente->session_token_hash, $sessionTokenHash)) {
                    throw new DomainException('El pedido pertenece a otra sesión.');
                }

                return $pedidoExistente;
            }

            $elementos = ElementoCarrito::where('carrito_id', $carrito->id)
                ->orderBy('variante_id')
                ->get();

            if ($elementos->isEmpty()) {
                throw new DomainException('El carrito está vacío.');
            }

            $variantes = VarianteProducto::with([
                'producto.promociones',
                'producto.categoria.promociones',
            ])
                ->whereIn('id', $elementos->pluck('variante_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $detalles = [];
            $total = 0.0;

            foreach ($elementos as $item) {
                $variante = $variantes->get($item->variante_id);

                if (! $variante || ! $variante->producto || ! $variante->producto->esta_activo) {
                    throw new DomainException('Uno de los productos ya no está disponible.');
                }

                if ($variante->stock < $item->cantidad) {
                    throw new DomainException('El stock disponible cambió. Revisa tu carrito.');
                }

                $item->setRelation('variante', $variante);
                $precioUnitario = (float) $item->precio_unitario_real;
                $total += $precioUnitario * $item->cantidad;
                $detalles[] = [$item, $variante, $precioUnitario];
            }

            $cliente = $this->guardarClienteSinSobrescribir($datosValidados);
            if ($datosValidados['metodo_entrega'] === 'domicilio') {
                $this->direccionService->guardarComoPredeterminada($cliente, $datosValidados);
            }
            $pedido = $this->generarPedido(
                $cliente->id,
                $datosValidados,
                $carrito,
                $sessionTokenHash,
                $total
            );

            foreach ($detalles as [$item, $variante, $precioUnitario]) {
                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'variante_id' => $variante->id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $precioUnitario,
                ]);

                $variante->decrement('stock', $item->cantidad);
            }

            $carrito->elementos()->delete();

            return $pedido;
        }, 3);

        session()->put('last_checkout_order_id', $pedido->id);
        session()->forget('carrito_token');

        return $pedido;
    }

    /**
     * Entrada reservada para un webhook de pasarela previamente verificado.
     * No debe exponerse a una solicitud iniciada por el navegador.
     */
    public function registrarPagoVerificado(
        Pedido $pedido,
        string $metodoPago,
        string $idTransaccion,
        array $respuestaPasarela = []
    ): Pago {
        if ($idTransaccion === '' || ! in_array($metodoPago, ['tarjeta', 'yape', 'plin', 'transferencia'], true)) {
            throw new InvalidArgumentException('Datos de pago verificado inválidos.');
        }

        return DB::transaction(function () use ($pedido, $metodoPago, $idTransaccion, $respuestaPasarela) {
            $pagoExistente = Pago::where('id_transaccion', $idTransaccion)->first();

            if ($pagoExistente) {
                if ($pagoExistente->pedido_id !== $pedido->id) {
                    throw new DomainException('La transacción ya está asociada a otro pedido.');
                }

                return $pagoExistente;
            }

            $pedidoBloqueado = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();

            if ($pedidoBloqueado->estado !== 'pendiente') {
                throw new DomainException('El pedido no admite un nuevo pago.');
            }

            if ($pedidoBloqueado->expires_at && $pedidoBloqueado->expires_at->isPast()) {
                throw new DomainException('El pedido venció y ya no admite pagos.');
            }

            $pago = Pago::create([
                'pedido_id' => $pedidoBloqueado->id,
                'metodo_pago' => $metodoPago,
                'id_transaccion' => $idTransaccion,
                'monto' => $pedidoBloqueado->total,
                'estado_pago' => 'aprobado',
                'respuesta_pasarela' => json_encode($respuestaPasarela, JSON_THROW_ON_ERROR),
            ]);

            $pedidoBloqueado->update(['estado' => 'pagado']);

            return $pago;
        }, 3);
    }

    private function guardarClienteSinSobrescribir(array $datos): Cliente
    {
        return Cliente::firstOrCreate(
            ['email' => $datos['email']],
            [
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'telefono' => $datos['telefono'],
                'tipo_documento' => 'DNI',
                'numero_documento' => $datos['documento'],
            ]
        );
    }

    private function generarPedido(
        int $clienteId,
        array $datos,
        Carrito $carrito,
        string $sessionTokenHash,
        float $total
    ): Pedido {
        return Pedido::create([
            'cliente_id' => $clienteId,
            'carrito_id' => $carrito->id,
            'carrito_token_hash' => hash('sha256', $carrito->token_sesion),
            'session_token_hash' => $sessionTokenHash,
            'numero_pedido' => 'PED-'.Str::upper((string) Str::ulid()),
            'direccion_envio' => $datos['direccion_envio'],
            'referencia' => $datos['referencia'] ?? null,
            'total' => round($total, 2),
            'estado' => 'pendiente',
            'expires_at' => now()->addMinutes(config('pedidos.expiration_minutes', 30)),
        ]);
    }
}
