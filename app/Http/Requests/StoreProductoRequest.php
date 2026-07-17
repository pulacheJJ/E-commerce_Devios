<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('productos.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            // Datos Base
            'nombre' => 'required|string|max:200',
            'slug' => 'required|string|max:250|unique:productos,slug',
            'marca' => 'nullable|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'descripcion' => 'nullable|string|max:10000',
            'esta_activo' => 'nullable|boolean',

            // Validar que las variantes sean un array correcto
            'variantes' => 'required|array|min:1',
            'variantes.*.sku' => ['required', 'string', 'max:100', 'distinct:ignore_case', Rule::unique('variantes_producto', 'sku')],
            'variantes.*.color' => 'nullable|string|max:50',
            'variantes.*.especificacion' => 'nullable|string|max:100',
            'variantes.*.precio' => 'required|numeric|gt:0|max:99999999.99',
            'variantes.*.precio_oferta' => 'nullable|numeric|gt:0|max:99999999.99|lt:variantes.*.precio',
            'variantes.*.stock' => 'required|integer|min:0|max:1000000',

            // Validar que suban imágenes reales
            'imagenes' => 'required|array|min:1',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'variantes.*.sku.required' => 'El SKU es obligatorio.',
            'variantes.*.sku.distinct' => 'El SKU ingresado ya está registrado en otra variante.',
            'variantes.*.sku.unique' => 'El SKU ingresado ya está registrado en otra variante.',
            'variantes.*.precio.required' => 'Todas las variantes deben tener un precio.',
            'variantes.*.precio_oferta.lt' => 'La oferta debe ser menor al precio normal.',
            'imagenes.required' => 'Debes subir al menos una imagen.',
            'imagenes.*.image' => 'Los archivos deben ser imágenes (JPG, PNG, WEBP).',
        ];
    }
}
