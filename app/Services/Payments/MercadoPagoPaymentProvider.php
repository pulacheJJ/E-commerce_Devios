<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\ReconcilesPaymentStatus;
use App\Data\Payments\PaymentInitiationResult;
use App\Data\Payments\PaymentStartData;
use App\Data\Payments\VerifiedWebhook;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use LogicException;

class MercadoPagoPaymentProvider implements PaymentProvider, ReconcilesPaymentStatus
{
    private const PREFERENCES_URL = 'https://api.mercadopago.com/checkout/preferences';

    private const PAYMENTS_URL = 'https://api.mercadopago.com/v1/payments/';

    public function __construct(
        private readonly string $accessToken,
        private readonly string $mode,
        private readonly bool $allowLive,
        private readonly ?string $webhookSecret = null,
    ) {
        if (trim($this->accessToken) === '') {
            throw new InvalidArgumentException('Falta el Access Token de prueba de Mercado Pago.');
        }

        if ($this->mode !== 'test') {
            if (! $this->allowLive) {
                throw new LogicException('Los pagos reales de Mercado Pago están bloqueados.');
            }

            throw new LogicException('El modo productivo de Mercado Pago no está implementado.');
        }
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function start(PaymentStartData $data): PaymentInitiationResult
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        if (! str_starts_with($baseUrl, 'https://')) {
            throw new LogicException('Mercado Pago requiere que APP_URL utilice HTTPS.');
        }

        $localReference = 'mp_'.substr(hash('sha256', $data->idempotencyKey), 0, 40);
        $returnUrl = $baseUrl.'/checkout/pago/'.rawurlencode($data->orderNumber);

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken)
            ->withHeaders(['X-Idempotency-Key' => $data->idempotencyKey])
            ->retry(3, 500, fn ($exception): bool => $exception instanceof ConnectionException)
            ->timeout(15)
            ->post(self::PREFERENCES_URL, [
                'items' => [[
                    'id' => $data->orderNumber,
                    'title' => 'Pedido '.$data->orderNumber,
                    'quantity' => 1,
                    'currency_id' => $data->currency,
                    'unit_price' => round((float) $data->amount, 2),
                ]],
                'external_reference' => $data->orderNumber,
                'metadata' => [
                    'local_payment_reference' => $localReference,
                ],
                'back_urls' => [
                    'success' => $returnUrl.'?resultado=aprobado',
                    'pending' => $returnUrl.'?resultado=pendiente',
                    'failure' => $returnUrl.'?resultado=rechazado',
                ],
                'auto_return' => 'approved',
            ])
            ->throw()
            ->json();

        $preferenceId = $response['id'] ?? null;
        if (! is_string($preferenceId) || $preferenceId === '') {
            throw new LogicException('Mercado Pago no devolvió una preferencia de prueba válida.');
        }

        return new PaymentInitiationResult($localReference, [
            'mode' => 'test',
            'real_charge' => false,
            'preference_id' => $preferenceId,
        ]);
    }

    public function verifyWebhook(string $rawPayload, array $headers): VerifiedWebhook
    {
        if (! is_string($this->webhookSecret) || trim($this->webhookSecret) === '') {
            throw new InvalidArgumentException('Falta la clave secreta del webhook de Mercado Pago.');
        }

        try {
            $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Webhook de Mercado Pago inválido.', previous: $exception);
        }

        $dataId = $headers['x-provider-data-id'][0] ?? data_get($payload, 'data.id');
        $requestId = $headers['x-request-id'][0] ?? null;
        $signature = $headers['x-signature'][0] ?? null;
        if (! is_scalar($dataId) || ! is_string($requestId) || ! is_string($signature)) {
            throw new InvalidArgumentException('Webhook de Mercado Pago incompleto.');
        }
        $dataId = strtolower((string) $dataId);
        if ($dataId === '' || ! ctype_digit($dataId)) {
            throw new InvalidArgumentException('Identificador de pago de Mercado Pago inválido.');
        }

        $signatureParts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if (is_string($key) && is_string($value)) {
                $signatureParts[$key] = $value;
            }
        }
        $timestamp = $signatureParts['ts'] ?? null;
        $receivedHash = $signatureParts['v1'] ?? null;
        if (! is_string($timestamp) || ! ctype_digit($timestamp)
            || ! is_string($receivedHash) || ! ctype_xdigit($receivedHash)) {
            throw new InvalidArgumentException('Firma de Mercado Pago inválida.');
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expectedHash = hash_hmac('sha256', $manifest, $this->webhookSecret);
        if (! hash_equals($expectedHash, strtolower($receivedHash))) {
            throw new InvalidArgumentException('Firma de Mercado Pago inválida.');
        }
        if (($payload['type'] ?? null) !== 'payment') {
            throw new InvalidArgumentException('El webhook no corresponde a un pago de prueba.');
        }

        $verified = $this->verifyPaymentStatus($dataId);
        $eventId = $payload['id'] ?? null;
        if (! is_scalar($eventId) || (string) $eventId === '') {
            $eventId = 'mp_evt_'.hash('sha256', $requestId.':'.$dataId.':'.$verified->status);
        }

        return new VerifiedWebhook(
            (string) $eventId,
            $verified->externalTransactionId,
            $verified->status,
            $verified->providerData,
        );
    }

    public function verifyPaymentStatus(string $paymentId): VerifiedWebhook
    {
        $paymentId = strtolower(trim($paymentId));
        if ($paymentId === '' || ! ctype_digit($paymentId)) {
            throw new InvalidArgumentException('Identificador de pago de Mercado Pago invÃ¡lido.');
        }

        $payment = Http::acceptJson()
            ->withToken($this->accessToken)
            ->retry(3, 500, fn ($exception): bool => $exception instanceof ConnectionException)
            ->timeout(15)
            ->get(self::PAYMENTS_URL.rawurlencode($paymentId))
            ->throw()
            ->json();

        if ((string) ($payment['id'] ?? '') !== $paymentId
            || ! is_bool($payment['live_mode'] ?? null)) {
            throw new InvalidArgumentException('Mercado Pago devolvió un pago de prueba inválido.');
        }
        $localReference = data_get($payment, 'metadata.local_payment_reference');
        if (! is_string($localReference) || ! str_starts_with($localReference, 'mp_')) {
            throw new InvalidArgumentException('El pago no contiene la referencia local esperada.');
        }

        $normalizedStatus = match ($payment['status'] ?? null) {
            'approved' => VerifiedWebhook::APPROVED,
            'rejected', 'cancelled' => VerifiedWebhook::REJECTED,
            'pending', 'in_process', 'in_mediation', 'authorized' => VerifiedWebhook::PENDING,
            default => throw new InvalidArgumentException('Estado de Mercado Pago no soportado.'),
        };

        return new VerifiedWebhook(
            'mp_status_'.$paymentId.'_'.$normalizedStatus,
            $localReference,
            $normalizedStatus,
            [
                'mode' => 'test',
                'real_charge' => false,
                'live_mode' => $payment['live_mode'],
                'payment_id' => $paymentId,
                'status' => $payment['status'],
                'status_detail' => $payment['status_detail'] ?? null,
                'external_reference' => $payment['external_reference'] ?? null,
                'transaction_amount' => $payment['transaction_amount'] ?? null,
                'currency_id' => $payment['currency_id'] ?? null,
            ],
        );
    }
}
