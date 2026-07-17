<?php

namespace App\Http\Requests;

use App\Models\VarianteProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreElementoCarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variante_id' => ['required', 'integer', Rule::exists('variantes_producto', 'id')->whereNull('deleted_at')],
            'cantidad' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->has('variante_id') || $validator->errors()->has('cantidad')) {
                return;
            }

            $variante = VarianteProducto::with('producto')->find($this->integer('variante_id'));
            if (! $variante || ! $variante->producto || ! $variante->producto->esta_activo) {
                $validator->errors()->add('variante_id', 'La variante seleccionada no está disponible.');

                return;
            }
            if ($variante->stock < 1 || $this->integer('cantidad') > $variante->stock) {
                $validator->errors()->add('cantidad', 'La cantidad solicitada supera el stock disponible.');
            }
        }];
    }
}
