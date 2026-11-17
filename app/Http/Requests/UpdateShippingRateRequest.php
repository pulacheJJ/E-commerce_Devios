<?php

namespace App\Http\Requests;

class UpdateShippingRateRequest extends StoreShippingRateRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('envios.editar') ?? false;
    }
}
