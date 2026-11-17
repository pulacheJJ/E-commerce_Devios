<?php

namespace App\Services;

use App\Contracts\ShippingCostCalculator;
use App\Domain\Orders\PedidoEstado;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\ElementoCarrito;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use App\Support\Money;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private CarritoService $carritoService,
        private DireccionService $direccionService,
        private PedidoInventarioService $inventory,
        private CheckoutPricingService $pricing,
        private ShippingCostCalculator $shipping,
    ) {}

    public function prepararVistaCheckout(): array
    {
        $cart = $this->carritoService->obtenerCarritoActual();

        return $cart && $cart->elementos->isNotEmpty()
            ? ['es_valido' => true, 'carrito' => $cart]
            : ['es_valido' => false, 'carrito' => null];
    }

    public function obtenerPedidoRecuperable(?string $numeroPedido = null): ?Pedido
    {
        $orderId = session()->get('last_checkout_order_id');
        $ownerHash = $this->carritoService->obtenerHashPropietarioActual();

        if ((! $orderId && ! $numeroPedido) || ! $ownerHash) {
            return null;
        }

        $query = Pedido::with(['detalles.variante.producto.imagenes', 'pagos'])
            ->where('session_token_hash', $ownerHash)
            ->whereIn('estado', [
                PedidoEstado::Creado->value,
                PedidoEstado::Reservado->value,
                PedidoEstado::PendientePago->value,
            ])
            ->where('expires_at', '>', now());

        if ($numeroPedido) {
            $query->where('numero_pedido', $numeroPedido);
        } else {
            $query->whereKey($orderId);
        }

        return $query->first();
    }

    public function procesarCompra(array $validatedData): Pedido
    {
        $order = $this->crearPedido($validatedData);
        $this->inventory->reservar($order);

        session()->put('last_checkout_order_id', $order->id);

        return $order->fresh();
    }

    public function crearPedido(array $validatedData): Pedido
    {
        $sessionTokenHash = $this->carritoService->obtenerHashPropietarioActual(true);
        $currentCart = $this->carritoService->obtenerCarritoActual();

        if (! $currentCart && session()->has('last_checkout_order_id')) {
            $previousOrder = Pedido::whereKey(session()->get('last_checkout_order_id'))
                ->where('session_token_hash', $sessionTokenHash)
                ->first();
            if ($previousOrder) {
                return $previousOrder;
            }
        }
        if (! $currentCart || ! $sessionTokenHash) {
            throw new DomainException('No existe un carrito válido para esta sesión.');
        }

        return DB::transaction(function () use ($validatedData, $currentCart, $sessionTokenHash): Pedido {
            $cart = Carrito::whereKey($currentCart->id)
                ->where('token_sesion', $currentCart->token_sesion)
                ->lockForUpdate()
                ->first();
            if (! $cart) {
                throw new DomainException('El carrito ya no está disponible.');
            }

            $existingOrder = Pedido::where('carrito_id', $cart->id)->first();
            if ($existingOrder) {
                if (! hash_equals((string) $existingOrder->session_token_hash, $sessionTokenHash)) {
                    throw new DomainException('El pedido pertenece a otra sesión.');
                }

                return $existingOrder;
            }

            $items = ElementoCarrito::where('carrito_id', $cart->id)->orderBy('variante_id')->get();
            if ($items->isEmpty()) {
                throw new DomainException('El carrito está vacío.');
            }

            $variants = VarianteProducto::with([
                'producto.promociones',
                'producto.categoria.promociones',
            ])->whereIn('id', $items->pluck('variante_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $snapshots = [];
            $subtotalCents = 0;
            $discountCents = 0;
            foreach ($items as $item) {
                $variant = $variants->get($item->variante_id);
                if (! $variant || ! $variant->producto || ! $variant->producto->esta_activo) {
                    throw new DomainException('Uno de los productos ya no está disponible.');
                }
                if ($variant->stock < $item->cantidad) {
                    throw new DomainException('El stock disponible cambió. Revisa tu carrito.');
                }

                $price = $this->pricing->snapshot($variant);
                $subtotalCents += $price['base_cents'] * $item->cantidad;
                $discountCents += $price['discount_cents'] * $item->cantidad;
                $snapshots[] = [$item, $variant, $price];
            }

            $shippingCents = $this->shipping->calculate($validatedData);
            $totalCents = max(0, $subtotalCents - $discountCents + $shippingCents);
            $client = $this->guardarClienteSinSobrescribir($validatedData);
            if ($validatedData['metodo_entrega'] === 'domicilio') {
                $this->direccionService->guardarComoPredeterminada($client, $validatedData);
            }

            $order = Pedido::create([
                'cliente_id' => $client->id,
                'carrito_id' => $cart->id,
                'carrito_token_hash' => hash('sha256', $cart->token_sesion),
                'session_token_hash' => $sessionTokenHash,
                'checkout_idempotency_key' => hash('sha256', 'checkout:'.$cart->id.':'.$sessionTokenHash),
                'numero_pedido' => 'PED-'.Str::upper((string) Str::ulid()),
                'direccion_envio' => $validatedData['direccion_envio'],
                'referencia' => $validatedData['referencia'] ?? null,
                'subtotal' => Money::decimal($subtotalCents),
                'descuento_total' => Money::decimal($discountCents),
                'costo_envio' => Money::decimal($shippingCents),
                'total' => Money::decimal($totalCents),
                'estado' => PedidoEstado::Creado->value,
                'expires_at' => now()->addMinutes(config('pedidos.expiration_minutes', 30)),
            ]);

            foreach ($snapshots as [$item, $variant, $price]) {
                DetallePedido::create([
                    'pedido_id' => $order->id,
                    'variante_id' => $variant->id,
                    'producto_nombre' => $variant->producto->nombre,
                    'sku' => $variant->sku,
                    'variante_descripcion' => collect([$variant->color, $variant->especificacion])->filter()->implode(' / ') ?: null,
                    'precio_base_unitario' => Money::decimal($price['base_cents']),
                    'descuento_unitario' => Money::decimal($price['discount_cents']),
                    'precio_unitario' => Money::decimal($price['unit_cents']),
                    'cantidad' => $item->cantidad,
                    'subtotal_linea' => Money::decimal($price['unit_cents'] * $item->cantidad),
                ]);
            }

            return $order;
        }, 3);
    }

    private function guardarClienteSinSobrescribir(array $data): Cliente
    {
        return Cliente::firstOrCreate(
            ['email' => $data['email']],
            [
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'],
                'tipo_documento' => 'DNI',
                'numero_documento' => $data['documento'],
            ]
        );
    }
}
