<?php

namespace App\Services;

use App\Contracts\ShippingCostCalculator;
use App\Domain\Checkout\ShippingRateUnavailable;
use App\Models\Distrito;
use App\Models\TarifaEnvio;
use App\Support\Money;
use DomainException;

class DatabaseShippingCostCalculator implements ShippingCostCalculator
{
    public function calculate(array $validatedCheckoutData): int
    {
        if (($validatedCheckoutData['metodo_entrega'] ?? null) === 'retiro') {
            return 0;
        }

        $district = Distrito::with('provincia')->find($validatedCheckoutData['distrito'] ?? null);
        if (! $district || ! $district->provincia) {
            throw new DomainException('La dirección seleccionada no tiene una provincia válida.');
        }

        $rate = TarifaEnvio::where('esta_activa', true)
            ->where('provincia_id', $district->provincia_id)
            ->first()
            ?? TarifaEnvio::where('esta_activa', true)->where('es_predeterminada', true)->first();

        if (! $rate) {
            throw new ShippingRateUnavailable;
        }

        return Money::cents($rate->costo);
    }
}
