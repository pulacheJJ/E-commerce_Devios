<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($this->usuario) // Ignora el email del usuario actual
            ],
            'password' => 'nullable|string|min:8|confirmed', // Nullable para no obligar a cambiarla
            'role' => 'required|string|exists:roles,name',
        ];
    }
}