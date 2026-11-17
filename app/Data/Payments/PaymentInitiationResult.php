<?php

namespace App\Data\Payments;

final class PaymentInitiationResult
{
    public function __construct(
        public readonly string $externalId,
        public readonly array $providerData = [],
    ) {}
}
