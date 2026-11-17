<?php

namespace Tests\Fakes;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentInitiationResult;
use App\Data\Payments\PaymentStartData;
use App\Data\Payments\VerifiedWebhook;
use InvalidArgumentException;
use JsonException;

class FakePaymentProvider implements PaymentProvider
{
    public function __construct(private string $secret = 'stage-three-test-secret') {}

    public function name(): string
    {
        return 'fake';
    }

    public function start(PaymentStartData $data): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            'fake_'.substr($data->idempotencyKey, 0, 32),
            ['test_provider' => true]
        );
    }

    public function verifyWebhook(string $rawPayload, array $headers): VerifiedWebhook
    {
        $signature = $headers['x-fake-signature'][0] ?? null;
        if (! is_string($signature) || ! hash_equals($this->sign($rawPayload), $signature)) {
            throw new InvalidArgumentException('Firma falsa de prueba inválida.');
        }

        try {
            $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Evento falso de prueba inválido.', previous: $exception);
        }
        foreach (['event_id', 'transaction_id', 'status'] as $required) {
            if (! isset($payload[$required]) || ! is_string($payload[$required]) || $payload[$required] === '') {
                throw new InvalidArgumentException('Evento falso de prueba incompleto.');
            }
        }

        return new VerifiedWebhook(
            $payload['event_id'],
            $payload['transaction_id'],
            $payload['status'],
            ['test_provider' => true] + ($payload['provider_data'] ?? [])
        );
    }

    public function sign(string $rawPayload): string
    {
        return hash_hmac('sha256', $rawPayload, $this->secret);
    }
}
