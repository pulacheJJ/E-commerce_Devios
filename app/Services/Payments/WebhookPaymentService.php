<?php

namespace App\Services\Payments;

use App\Contracts\Payments\ReconcilesPaymentStatus;
use App\Data\Payments\VerifiedWebhook;
use App\Domain\Orders\PedidoEstado;
use App\Domain\Orders\PedidoStateMachine;
use App\Models\EventoWebhookPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\PedidoInventarioService;
use App\Support\Money;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class WebhookPaymentService
{
    public function __construct(
        private PaymentProviderRegistry $providers,
        private PedidoStateMachine $states,
        private PedidoInventarioService $inventory,
    ) {}

    public function process(string $providerName, string $rawPayload, array $headers): array
    {
        $provider = $this->providers->get($providerName);
        $verified = $provider->verifyWebhook($rawPayload, $headers);

        return $this->processVerified($providerName, $rawPayload, $verified);
    }

    public function reconcile(string $providerName, string $paymentId): array
    {
        $provider = $this->providers->get($providerName);
        if (! $provider instanceof ReconcilesPaymentStatus) {
            throw new DomainException('El proveedor no permite consultar el estado del pago.');
        }

        $verified = $provider->verifyPaymentStatus($paymentId);
        $payload = json_encode([
            'source' => 'provider_status_reconciliation',
            'payment_id' => $paymentId,
            'status' => $verified->status,
        ], JSON_THROW_ON_ERROR);

        return $this->processVerified($providerName, $payload, $verified);
    }

    private function processVerified(string $providerName, string $rawPayload, VerifiedWebhook $verified): array
    {
        return DB::transaction(function () use ($providerName, $rawPayload, $verified): array {
            $existingEvent = EventoWebhookPago::where('proveedor', $providerName)
                ->where('evento_externo_id', $verified->eventId)
                ->lockForUpdate()
                ->first();
            if ($existingEvent) {
                return ['duplicate' => true, 'status' => $existingEvent->estado_normalizado];
            }

            $payment = Pago::where('proveedor', $providerName)
                ->where('id_transaccion', $verified->externalTransactionId)
                ->lockForUpdate()
                ->first();
            if (! $payment) {
                throw (new ModelNotFoundException)->setModel(Pago::class);
            }

            $order = Pedido::whereKey($payment->pedido_id)->lockForUpdate()->firstOrFail();
            $this->assertProviderPaymentMatches($payment, $order, $verified);
            $event = EventoWebhookPago::create([
                'pago_id' => $payment->id,
                'proveedor' => $providerName,
                'evento_externo_id' => $verified->eventId,
                'payload_hash' => hash('sha256', $rawPayload),
                'estado_normalizado' => $verified->status,
            ]);

            if ($verified->status === VerifiedWebhook::APPROVED) {
                if (! ($payment->estado_pago === 'aprobado' && $order->estado === PedidoEstado::Pagado->value)) {
                    if ($order->estado !== PedidoEstado::PendientePago->value) {
                        throw new DomainException('El pedido no admite confirmación de pago.');
                    }
                    $payment->update([
                        'estado_pago' => 'aprobado',
                        'confirmado_at' => now(),
                        'evento_confirmacion_id' => $verified->eventId,
                        'respuesta_pasarela' => $verified->providerData,
                    ]);
                    $this->states->transition($order, PedidoEstado::Pagado);
                }
            } elseif ($verified->status === VerifiedWebhook::REJECTED) {
                if ($order->estado !== PedidoEstado::PendientePago->value) {
                    throw new DomainException('El pedido no admite un rechazo de pago.');
                }
                $payment->update([
                    'estado_pago' => 'rechazado',
                    'evento_confirmacion_id' => $verified->eventId,
                    'respuesta_pasarela' => $verified->providerData,
                ]);
                $this->inventory->rechazarPago($order);
            }

            $event->update(['procesado_at' => now()]);

            return ['duplicate' => false, 'status' => $verified->status];
        }, 3);
    }

    private function assertProviderPaymentMatches(Pago $payment, Pedido $order, VerifiedWebhook $verified): void
    {
        $providerData = $verified->providerData;
        if (array_key_exists('external_reference', $providerData)
            && $providerData['external_reference'] !== $order->numero_pedido) {
            throw new DomainException('La referencia del pago no corresponde al pedido.');
        }
        if (array_key_exists('currency_id', $providerData)
            && $providerData['currency_id'] !== $payment->moneda) {
            throw new DomainException('La moneda del pago no corresponde al pedido.');
        }
        if (array_key_exists('transaction_amount', $providerData)
            && Money::cents($providerData['transaction_amount']) !== Money::cents($payment->monto)) {
            throw new DomainException('El importe del pago no corresponde al pedido.');
        }
    }
}
