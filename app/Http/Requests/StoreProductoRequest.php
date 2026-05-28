<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            // Datos Base
            'nombre'       => 'required|string|max:200',
            'slug'         => 'required|string|max:250|unique:productos,slug',
            'marca'        => 'nullable|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'descripcion'  => 'nullable|string',
            'esta_activo'  => 'nullable|boolean',

            // Validar que las variantes sean un array correcto
            'variantes'                  => 'required|array|min:1',
            'variantes.*.sku'            => 'nullable|string|max:50',
            'variantes.*.color'          => 'nullable|string|max:50',
            'variantes.*.especificacion' => 'nullable|string|max:100',
            'variantes.*.precio'         => 'required|numeric|min:0',
            'variantes.*.precio_oferta'  => 'nullable|numeric|min:0|lt:variantes.*.precio',
            'variantes.*.stock'          => 'required|integer|min:0',

            // Validar que suban imágenes reales
            'imagenes'   => 'required|array|min:1',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'variantes.*.precio.required' => 'Todas las variantes deben tener un precio.',
            'variantes.*.precio_oferta.lt' => 'La oferta debe ser menor al precio normal.',
            'imagenes.required' => 'Debes subir al menos una imagen.',
            'imagenes.*.image' => 'Los archivos deben ser imágenes (JPG, PNG, WEBP).',
        ];
    }
}