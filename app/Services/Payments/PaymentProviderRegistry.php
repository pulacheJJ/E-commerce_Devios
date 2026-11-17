<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use DomainException;

class PaymentProviderRegistry
{
    /** @var array<string, PaymentProvider> */
    private array $providers = [];

    public function register(PaymentProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function get(string $name): PaymentProvider
    {
        return $this->providers[$name]
            ?? throw new DomainException('Proveedor de pago no disponible.');
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }
}
