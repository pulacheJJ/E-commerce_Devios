<?php

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentInitiationResult;
use App\Data\Payments\PaymentStartData;
use App\Data\Payments\VerifiedWebhook;

interface PaymentProvider
{
    public function name(): string;

    public function start(PaymentStartData $data): PaymentInitiationResult;

    public function verifyWebhook(string $rawPayload, array $headers): VerifiedWebhook;
}
