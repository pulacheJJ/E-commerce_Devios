<?php

namespace App\Domain\Checkout;

use DomainException;

class ShippingRateUnavailable extends DomainException
{
    public function __construct()
    {
        parent::__construct('No existe una tarifa de envío configurada para la dirección seleccionada.');
    }
}
