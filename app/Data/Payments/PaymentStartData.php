<?php

namespace App\Data\Payments;

final class PaymentStartData
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $idempotencyKey,
    ) {}
}
