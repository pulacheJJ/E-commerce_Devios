<?php

namespace App\Data\Payments;

use InvalidArgumentException;

final class VerifiedWebhook
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public function __construct(
        public readonly string $eventId,
        public readonly string $externalTransactionId,
        public readonly string $status,
        public readonly array $providerData = [],
    ) {
        if (! in_array($status, [self::PENDING, self::APPROVED, self::REJECTED], true)) {
            throw new InvalidArgumentException('Estado normalizado de webhook inválido.');
        }
    }
}
