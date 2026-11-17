<?php

namespace Tests\Unit;

use App\Data\Payments\PaymentStartData;
use App\Services\Payments\MercadoPagoPaymentProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class MercadoPagoPaymentProviderTest extends TestCase
{
    public function test_it_creates_a_test_preference_for_the_frontend_sdk_with_idempotency(): void
    {
        config(['app.url' => 'https://devioz-tunnel.test']);
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => '123456789-test-preference-123',
            ], 201),
        ]);

        $provider = new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false);
        $result = $provider->start(new PaymentStartData(
            'PED-TEST-001',
            '149.90',
            'PEN',
            'checkout-idempotency-key',
        ));

        $this->assertSame('mercadopago', $provider->name());
        $this->assertStringStartsWith('mp_', $result->externalId);
        $this->assertSame('123456789-test-preference-123', $result->providerData['preference_id']);
        $this->assertArrayNotHasKey('checkout_url', $result->providerData);
        $this->assertFalse($result->providerData['real_charge']);

        Http::assertSent(function (Request $request) use ($result): bool {
            return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $request->hasHeader('Authorization', 'Bearer TEST_ACCESS_TOKEN')
                && $request->hasHeader('X-Idempotency-Key', 'checkout-idempotency-key')
                && $request['external_reference'] === 'PED-TEST-001'
                && $request['metadata']['local_payment_reference'] === $result->externalId
                && $request['items'][0]['currency_id'] === 'PEN'
                && $request['items'][0]['unit_price'] === 149.9
                && ! array_key_exists('notification_url', $request->data());
        });
    }

    public function test_it_rejects_a_missing_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MercadoPagoPaymentProvider('', 'test', false);
    }

    public function test_it_rejects_live_mode_even_if_someone_changes_the_environment(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('pagos reales');
        new MercadoPagoPaymentProvider('LIVE_TOKEN', 'live', false);
    }

    public function test_it_refuses_insecure_return_urls(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);
        Http::fake();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('HTTPS');

        (new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false))->start(
            new PaymentStartData('PED-1', '10.00', 'PEN', 'idempotency-key')
        );
    }

    public function test_it_verifies_a_signed_test_webhook_and_reads_the_payment_from_the_api(): void
    {
        $secret = 'webhook-test-secret';
        $dataId = '123456789';
        $requestId = 'request-id-123';
        $timestamp = '1780000000';
        $localReference = 'mp_'.str_repeat('a', 40);
        $payload = json_encode([
            'id' => 987654321,
            'type' => 'payment',
            'live_mode' => true,
            'data' => ['id' => $dataId],
        ], JSON_THROW_ON_ERROR);
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";

        Http::fake([
            "api.mercadopago.com/v1/payments/{$dataId}" => Http::response([
                'id' => (int) $dataId,
                'live_mode' => true,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'external_reference' => 'PED-TEST-001',
                'transaction_amount' => 149.90,
                'currency_id' => 'PEN',
                'metadata' => ['local_payment_reference' => $localReference],
            ]),
        ]);

        $verified = (new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false, $secret))
            ->verifyWebhook($payload, [
                'x-provider-data-id' => [$dataId],
                'x-request-id' => [$requestId],
                'x-signature' => [
                    'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, $secret),
                ],
            ]);

        $this->assertSame('987654321', $verified->eventId);
        $this->assertSame($localReference, $verified->externalTransactionId);
        $this->assertSame('approved', $verified->status);
        $this->assertSame('PED-TEST-001', $verified->providerData['external_reference']);
        $this->assertTrue($verified->providerData['live_mode']);
        $this->assertSame(149.90, $verified->providerData['transaction_amount']);
        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'GET'
            && $request->url() === "https://api.mercadopago.com/v1/payments/{$dataId}"
            && $request->hasHeader('Authorization', 'Bearer TEST_ACCESS_TOKEN')
        );
    }

    public function test_it_rejects_an_invalid_mercado_pago_webhook_signature_without_calling_the_api(): void
    {
        Http::fake();
        $payload = json_encode([
            'id' => 1,
            'type' => 'payment',
            'live_mode' => false,
            'data' => ['id' => '123456789'],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(InvalidArgumentException::class);
        try {
            (new MercadoPagoPaymentProvider('TEST_ACCESS_TOKEN', 'test', false, 'secret'))
                ->verifyWebhook($payload, [
                    'x-provider-data-id' => ['123456789'],
                    'x-request-id' => ['request-id'],
                    'x-signature' => ['ts=1780000000,v1='.str_repeat('0', 64)],
                ]);
        } finally {
            Http::assertNothingSent();
        }
    }
}
