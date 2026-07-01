<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'nombres'         => 'required|string|max:255',
            'apellidos'       => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'telefono'        => 'required|string|max:20',
            'documento'       => 'required|string|max:20',
            'direccion_envio' => 'required|string|max:255',
            'referencia'      => 'nullable|string|max:255',
            'distrito'        => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'documento.required' => 'El número de DNI o CE es obligatorio.',
            'email.required'     => 'Necesitamos un correo para enviarte el comprobante.',
            'email.email'        => 'El formato del correo electrónico no es válido.',
        ];
    }
}