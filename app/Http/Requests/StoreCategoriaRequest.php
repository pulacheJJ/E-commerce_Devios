<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria') ? $this->route('categoria')->id : null;

        return [
            'nombre' => [
                'required', 
                'string', 
                'max:100'
            ],
            'slug' => [
                'required', 
                'string', 
                'max:120',
                Rule::unique('categorias', 'slug')->ignore($categoriaId),
            ],
            'parent_id' => [
                'nullable', 
                'exists:categorias,id',
                function ($attribute, $value, $fail) use ($categoriaId) {
                    if ($categoriaId && $value == $categoriaId) {
                        $fail('Una categoría no puede ser padre de sí misma.');
                    }
                },
            ],
            'esta_activa' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'slug.unique' => 'Esta URL ya está en uso por otra categoría.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
        ];
    }
}