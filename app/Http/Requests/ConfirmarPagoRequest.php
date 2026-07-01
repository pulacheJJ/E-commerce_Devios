<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Verifica que no inyecten un código falso en el HTML
            'numero_pedido' => 'required|string|exists:pedidos,numero_pedido', 
            // Verifica que solo envíen los métodos permitidos
            'metodo_pago'   => 'required|string|in:tarjeta,yape', 
        ];
    }

    public function messages(): array
    {
        return [
            'numero_pedido.exists' => 'El pedido especificado no existe o es inválido.',
            'metodo_pago.in'       => 'El método de pago seleccionado no es válido.',
        ];
    }
}