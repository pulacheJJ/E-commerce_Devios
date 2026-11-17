<?php

namespace App\Http\Requests;

use App\Models\TarifaEnvio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('envios.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['provincia', 'predeterminada'])],
            'provincia_id' => [
                'nullable',
                'required_if:tipo,provincia',
                'prohibited_if:tipo,predeterminada',
                Rule::exists('provincias', 'id'),
                Rule::unique('tarifas_envio', 'provincia_id')->ignore($this->shippingRateId()),
            ],
            'costo' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'esta_activa' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('tipo') !== 'predeterminada' || ! $this->boolean('esta_activa')) {
                return;
            }

            $exists = TarifaEnvio::where('es_predeterminada', true)
                ->where('esta_activa', true)
                ->when($this->shippingRateId(), fn ($query, $id) => $query->whereKeyNot($id))
                ->exists();
            if ($exists) {
                $validator->errors()->add('tipo', 'Ya existe una tarifa predeterminada activa.');
            }
        }];
    }

    protected function shippingRateId(): ?int
    {
        $rate = $this->route('tarifa_envio');

        return $rate instanceof TarifaEnvio ? $rate->id : (is_numeric($rate) ? (int) $rate : null);
    }
}
