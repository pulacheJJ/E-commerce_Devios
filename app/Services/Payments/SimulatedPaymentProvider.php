<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentInitiationResult;
use App\Data\Payments\PaymentStartData;
use App\Data\Payments\VerifiedWebhook;
use App\Models\Pago;
use InvalidArgumentException;
use JsonException;

class SimulatedPaymentProvider implements PaymentProvider
{
    public function __construct(private string $secret)
    {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('El secreto del simulador debe tener al menos 32 caracteres.');
        }
    }

    public function name(): string
    {
        return 'simulated';
    }

    public function start(PaymentStartData $data): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            'sim_'.substr(hash('sha256', $data->idempotencyKey), 0, 40),
            ['mode' => 'demonstration', 'real_charge' => false]
        );
    }

    public function createSignedEvent(Pago $payment, string $scenario): array
    {
        $statuses = [
            'aprobado' => VerifiedWebhook::APPROVED,
            'rechazado' => VerifiedWebhook::REJECTED,
            'pendiente' => VerifiedWebhook::PENDING,
        ];
        $status = $statuses[$scenario] ?? throw new InvalidArgumentException('Escenario simulado inválido.');
        $payload = [
            'event_id' => 'sim_evt_'.substr(hash('sha256', $payment->id_transaccion.':'.$status), 0, 40),
            'transaction_id' => $payment->id_transaccion,
            'status' => $status,
            'mode' => 'demonstration',
        ];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        return [
            'raw_payload' => $rawPayload,
            'headers' => ['x-simulator-signature' => [$this->sign($rawPayload)]],
        ];
    }

    public function verifyWebhook(string $rawPayload, array $headers): VerifiedWebhook
    {
        $signature = $headers['x-simulator-signature'][0] ?? null;
        if (! is_string($signature) || ! hash_equals($this->sign($rawPayload), $signature)) {
            throw new InvalidArgumentException('Firma del simulador inválida.');
        }

        try {
            $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Evento del simulador inválido.', previous: $exception);
        }
        foreach (['event_id', 'transaction_id', 'status'] as $field) {
            if (! isset($payload[$field]) || ! is_string($payload[$field]) || $payload[$field] === '') {
                throw new InvalidArgumentException('Evento del simulador incompleto.');
            }
        }

        return new VerifiedWebhook(
            $payload['event_id'],
            $payload['transaction_id'],
            $payload['status'],
            ['mode' => 'demonstration', 'real_charge' => false]
        );
    }

    public function sign(string $rawPayload): string
    {
        return hash_hmac('sha256', $rawPayload, $this->secret);
    }
}
