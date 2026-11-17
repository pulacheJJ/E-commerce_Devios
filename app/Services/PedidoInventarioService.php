<?php

namespace App\Services;

use App\Domain\Orders\PedidoEstado;
use App\Domain\Orders\PedidoStateMachine;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use DomainException;
use Illuminate\Support\Facades\DB;

class PedidoInventarioService
{
    public function __construct(private PedidoStateMachine $states) {}

    public function reservar(Pedido|int $order): bool
    {
        $orderId = $order instanceof Pedido ? $order->id : $order;

        return DB::transaction(function () use ($orderId): bool {
            $order = Pedido::whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->stock_reserved_at !== null) {
                return false;
            }
            if ($order->estado !== PedidoEstado::Creado->value) {
                throw new DomainException('El estado actual no permite reservar inventario.');
            }

            $quantities = $this->quantities($order->id);
            if ($quantities->isEmpty()) {
                throw new DomainException('El pedido no contiene detalles para reservar.');
            }
            $variants = VarianteProducto::with('producto')
                ->whereIn('id', $quantities->pluck('variante_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $quantity) {
                $variant = $variants->get($quantity->variante_id);
                if (! $variant || ! $variant->producto?->esta_activo) {
                    throw new DomainException('Un producto del pedido ya no está disponible.');
                }
                if ($variant->stock < $quantity->cantidad_total) {
                    throw new DomainException('No existe stock suficiente para reservar el pedido.');
                }
            }
            foreach ($quantities as $quantity) {
                $variants->get($quantity->variante_id)->decrement('stock', (int) $quantity->cantidad_total);
            }

            $order->stock_reserved_at = now();
            $order->save();
            $this->states->transition($order, PedidoEstado::Reservado);
            $order->carrito?->elementos()->delete();

            return true;
        }, 3);
    }

    public function expirar(Pedido|int $order): bool
    {
        return $this->release($order, PedidoEstado::Expirado, true);
    }

    public function cancelar(Pedido|int $order): bool
    {
        return $this->release($order, PedidoEstado::Cancelado, false);
    }

    public function rechazarPago(Pedido|int $order): bool
    {
        return $this->release($order, PedidoEstado::PagoFallido, false);
    }

    private function release(Pedido|int $order, PedidoEstado $target, bool $validateExpiration): bool
    {
        $orderId = $order instanceof Pedido ? $order->id : $order;

        return DB::transaction(function () use ($orderId, $target, $validateExpiration): bool {
            $order = Pedido::whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->estado === $target->value
                && ($order->stock_reserved_at === null || $order->inventory_released_at !== null)) {
                return false;
            }
            if ($validateExpiration && (! $order->expires_at || $order->expires_at->isFuture())) {
                return false;
            }
            if ($order->estado !== $target->value && ! $this->states->can($order->estado, $target)) {
                throw new DomainException('El estado actual del pedido no permite liberar inventario.');
            }

            if ($order->stock_reserved_at === null) {
                if ($order->estado !== $target->value) {
                    $this->states->transition($order, $target);
                }

                return true;
            }
            if ($order->inventory_released_at !== null) {
                $this->states->transition($order, $target);

                return true;
            }

            $quantities = $this->quantities($order->id);
            $variants = VarianteProducto::withTrashed()
                ->whereIn('id', $quantities->pluck('variante_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            foreach ($quantities as $quantity) {
                $variant = $variants->get($quantity->variante_id);
                if (! $variant) {
                    throw new DomainException('No se puede liberar inventario porque falta una variante histórica.');
                }
                $variant->increment('stock', (int) $quantity->cantidad_total);
            }

            $order->inventory_released_at = now();
            $order->save();
            if ($order->estado !== $target->value) {
                $this->states->transition($order, $target);
            }

            return true;
        }, 3);
    }

    private function quantities(int $orderId)
    {
        return DetallePedido::where('pedido_id', $orderId)
            ->select('variante_id', DB::raw('SUM(cantidad) AS cantidad_total'))
            ->groupBy('variante_id')
            ->orderBy('variante_id')
            ->get();
    }
}
