<?php

namespace App\Domain\Orders;

use App\Models\Pedido;
use DomainException;

class PedidoStateMachine
{
    private const TRANSITIONS = [
        'creado' => ['reservado', 'cancelado'],
        'reservado' => ['pendiente_pago', 'expirado', 'cancelado'],
        'pendiente_pago' => ['pagado', 'pago_fallido', 'expirado', 'cancelado'],
        'pago_fallido' => ['expirado', 'cancelado'],
        'pagado' => [],
        'expirado' => [],
        'cancelado' => [],
        'enviado' => [],
    ];

    public function can(string $from, PedidoEstado|string $to): bool
    {
        $from = $from === 'pendiente' ? PedidoEstado::PendientePago->value : $from;
        $to = $to instanceof PedidoEstado ? $to->value : $to;

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(Pedido $pedido, PedidoEstado|string $to): void
    {
        $to = $to instanceof PedidoEstado ? $to->value : $to;
        if (! $this->can($pedido->estado, $to)) {
            throw new DomainException("La transición de {$pedido->estado} a {$to} no está permitida.");
        }

        $pedido->estado = $to;
        $pedido->save();
    }
}
