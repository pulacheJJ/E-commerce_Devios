<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'required|string|max:20',
            'documento' => 'required|string|max:20',
            'direccion_envio' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'metodo_entrega' => 'required|in:retiro,domicilio',
            'departamento' => ['required_if:metodo_entrega,domicilio', 'nullable', 'string', Rule::exists('departamentos', 'id')],
            'provincia' => [
                'required_if:metodo_entrega,domicilio', 'nullable', 'string',
                Rule::exists('provincias', 'id')->where(fn ($query) => $query->where('departamento_id', $this->input('departamento'))),
            ],
            'distrito' => [
                'required_if:metodo_entrega,domicilio', 'nullable', 'string',
                Rule::exists('distritos', 'id')->where(fn ($query) => $query->where('provincia_id', $this->input('provincia'))),
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'documento.required' => 'El número de DNI o CE es obligatorio.',
            'email.required' => 'Necesitamos un correo para enviarte el comprobante.',
            'email.email' => 'El formato del correo electrónico no es válido.',
        ];
    }
}
