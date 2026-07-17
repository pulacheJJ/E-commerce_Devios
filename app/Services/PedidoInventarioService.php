<?php

namespace App\Services;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\VarianteProducto;
use DomainException;
use Illuminate\Support\Facades\DB;

class PedidoInventarioService
{
    public function expirar(Pedido|int $pedido): bool
    {
        return $this->liberar($pedido, 'expirado', true);
    }

    public function cancelar(Pedido|int $pedido): bool
    {
        return $this->liberar($pedido, 'cancelado', false);
    }

    private function liberar(Pedido|int $pedido, string $estadoDestino, bool $validarExpiracion): bool
    {
        $pedidoId = $pedido instanceof Pedido ? $pedido->id : $pedido;

        return DB::transaction(function () use ($pedidoId, $estadoDestino, $validarExpiracion): bool {
            $pedido = Pedido::whereKey($pedidoId)->lockForUpdate()->firstOrFail();

            if ($pedido->inventory_released_at !== null) {
                return false;
            }
            if (! in_array($pedido->estado, config('pedidos.expirable_states', ['pendiente']), true)) {
                throw new DomainException('El estado actual del pedido no permite liberar inventario.');
            }
            if ($validarExpiracion && (! $pedido->expires_at || $pedido->expires_at->isFuture())) {
                return false;
            }

            $cantidades = DetallePedido::where('pedido_id', $pedido->id)
                ->select('variante_id', DB::raw('SUM(cantidad) AS cantidad_total'))
                ->groupBy('variante_id')
                ->orderBy('variante_id')
                ->get();

            $variantes = VarianteProducto::withTrashed()
                ->whereIn('id', $cantidades->pluck('variante_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cantidades as $cantidad) {
                $variante = $variantes->get($cantidad->variante_id);
                if (! $variante) {
                    throw new DomainException('No se puede liberar inventario porque falta una variante histórica.');
                }
                $variante->increment('stock', (int) $cantidad->cantidad_total);
            }

            $pedido->update([
                'estado' => $estadoDestino,
                'inventory_released_at' => now(),
            ]);

            return true;
        }, 3);
    }
}
