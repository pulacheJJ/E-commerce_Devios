<?php

namespace App\Contracts\Payments;

use App\Data\Payments\VerifiedWebhook;

interface ReconcilesPaymentStatus
{
    public function verifyPaymentStatus(string $paymentId): VerifiedWebhook;
}
