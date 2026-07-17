<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('categoria') ? 'categorias.editar' : 'categorias.crear';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria')?->id;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('categorias', 'slug')->ignore($categoriaId)],
            'parent_id' => [
                'nullable',
                Rule::exists('categorias', 'id')->where(fn ($query) => $query->where('esta_activa', true)),
                function (string $attribute, mixed $value, \Closure $fail) use ($categoriaId): void {
                    if (! $categoriaId || ! $value) {
                        return;
                    }

                    $parent = Categoria::find($value);
                    $visited = [];
                    while ($parent) {
                        if ($parent->id === $categoriaId) {
                            $fail('La categoría padre seleccionada produciría un ciclo en la jerarquía.');

                            return;
                        }
                        if (isset($visited[$parent->id])) {
                            $fail('La jerarquía de categorías seleccionada contiene un ciclo.');

                            return;
                        }
                        $visited[$parent->id] = true;
                        $parent = $parent->padre;
                    }
                },
            ],
            'esta_activa' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'slug.unique' => 'Esta URL ya está en uso por otra categoría.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe o no está disponible.',
        ];
    }
}
