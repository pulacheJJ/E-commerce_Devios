<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Capturamos el ID del producto que estamos editando desde la URL
        $productoId = $this->route('producto') ? $this->route('producto')->id : null;

        return [
            // 1. Datos Base
            'nombre' => 'required|string|max:200',
            'slug' => [
                'required',
                'string',
                'max:250',
                // REGLA CLAVE: Que sea único, pero ignorando el ID del producto actual
                Rule::unique('productos', 'slug')->ignore($productoId),
            ],
            'marca' => 'nullable|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'descripcion' => 'nullable|string',
            'esta_activo' => 'nullable|boolean',

            // 2. Validación de Variantes
            'variantes' => 'required|array|min:1',
            'variantes.*.id' => [
                'nullable',
                Rule::exists('variantes_producto', 'id')->where(fn ($query) => $query->where('producto_id', $productoId)),
            ],
            'variantes.*.sku' => [
                'required',
                'string',
                'max:100',
                'distinct:ignore_case',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    preg_match('/variantes\.(\d+)\.sku/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    $varianteId = $index !== null ? $this->input("variantes.{$index}.id") : null;
                    $exists = \App\Models\VarianteProducto::withTrashed()
                        ->where('sku', trim((string) $value))
                        ->when($varianteId, fn ($query) => $query->whereKeyNot($varianteId))
                        ->exists();

                    if ($exists) {
                        $fail('El SKU ingresado ya está registrado en otra variante.');
                    }
                },
            ],
            'variantes.*.color' => 'nullable|string|max:50',
            'variantes.*.especificacion' => 'nullable|string|max:100',
            'variantes.*.precio' => 'required|numeric|min:0',
            'variantes.*.precio_oferta' => 'nullable|numeric|min:0|lt:variantes.*.precio',
            'variantes.*.stock' => 'required|integer|min:0',

            // 3. Validación de Imágenes
            // REGLA CLAVE: 'nullable' porque el usuario puede actualizar un precio sin subir nuevas fotos
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Este URL ya está siendo usado por otro producto.',
            'variantes.*.sku.required' => 'El SKU es obligatorio.',
            'variantes.*.sku.distinct' => 'El SKU ingresado ya está registrado en otra variante.',
            'variantes.*.precio.required' => 'Todas las variantes deben tener un precio.',
            'variantes.*.precio_oferta.lt' => 'La oferta debe ser menor al precio normal.',
            'imagenes.*.image' => 'Si subes archivos nuevos, deben ser imágenes (JPG, PNG, WEBP).',
        ];
    }
}
