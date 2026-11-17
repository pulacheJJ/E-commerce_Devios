<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentStartData;
use App\Domain\Orders\PedidoEstado;
use App\Domain\Orders\PedidoStateMachine;
use App\Models\Pago;
use App\Models\Pedido;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private PaymentProviderRegistry $providers,
        private PedidoStateMachine $states,
    ) {}

    public function start(Pedido $order, string $providerName, string $paymentMethod = 'external'): Pago
    {
        $provider = $this->providers->get($providerName);
        $idempotencyKey = hash('sha256', "payment:{$providerName}:{$order->id}");
        $existing = Pago::where('clave_idempotencia', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }
        if ($order->estado !== PedidoEstado::Reservado->value) {
            throw new DomainException('Solo un pedido reservado puede iniciar un pago.');
        }

        $initiation = $provider->start(new PaymentStartData(
            $order->numero_pedido,
            (string) $order->total,
            config('checkout.currency', 'PEN'),
            $idempotencyKey,
        ));
        if ($initiation->externalId === '') {
            throw new DomainException('El proveedor no devolvió un identificador externo.');
        }

        return DB::transaction(function () use ($order, $provider, $paymentMethod, $idempotencyKey, $initiation): Pago {
            $lockedOrder = Pedido::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $existing = Pago::where('clave_idempotencia', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
            if ($lockedOrder->estado !== PedidoEstado::Reservado->value) {
                throw new DomainException('El pedido dejó de estar disponible para iniciar el pago.');
            }

            $payment = Pago::create([
                'pedido_id' => $lockedOrder->id,
                'proveedor' => $provider->name(),
                'metodo_pago' => $paymentMethod,
                'id_transaccion' => $initiation->externalId,
                'clave_idempotencia' => $idempotencyKey,
                'monto' => $lockedOrder->total,
                'moneda' => config('checkout.currency', 'PEN'),
                'estado_pago' => 'pendiente',
                'iniciado_at' => now(),
                'respuesta_pasarela' => $initiation->providerData,
            ]);

            $this->states->transition($lockedOrder, PedidoEstado::PendientePago);

            return $payment;
        }, 3);
    }
}
