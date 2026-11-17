<?php

namespace App\Console\Commands;

use App\Models\Pedido;
use App\Services\PedidoInventarioService;
use Illuminate\Console\Command;
use Throwable;

class ExpirarPedidosPendientes extends Command
{
    protected $signature = 'pedidos:expirar-pendientes {--limit=500 : Máximo de pedidos a procesar}';

    protected $description = 'Expira pedidos pendientes vencidos y libera su inventario exactamente una vez';

    public function handle(PedidoInventarioService $inventario): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $procesados = 0;
        $errores = 0;

        Pedido::query()
            ->whereIn('estado', config('pedidos.expirable_states', ['reservado', 'pendiente_pago']))
            ->whereNull('inventory_released_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->each(function (int $pedidoId) use ($inventario, &$procesados, &$errores): void {
                try {
                    if ($inventario->expirar($pedidoId)) {
                        $procesados++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $errores++;
                }
            });

        $this->info("Pedidos expirados: {$procesados}. Errores: {$errores}.");

        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
