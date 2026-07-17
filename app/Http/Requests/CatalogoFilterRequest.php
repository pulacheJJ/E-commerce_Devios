<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogoFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['buscar' => $this->filled('buscar') ? trim((string) $this->input('buscar')) : null]);
    }

    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:250', Rule::exists('categorias', 'slug')->where(fn ($query) => $query->where('esta_activa', true))],
            'marcas' => ['nullable', 'array', 'max:20'],
            'marcas.*' => ['string', 'max:100', 'distinct'],
            'precio' => ['nullable', Rule::in(['0-100', '100-500', '500-1000', '1000+'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
