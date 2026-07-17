<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cualquiera puede intentar loguearse
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo corporativo es obligatorio.',
            'email.email' => 'Ingresa un formato de correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
