<?php

namespace App\Contracts;

interface ShippingCostCalculator
{
    public function calculate(array $validatedCheckoutData): int;
}
