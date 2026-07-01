<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromocionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:150',
            'subtitulo' => 'nullable|string|max:255',
            'ruta_banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Nullable aquí
            'url_destino' => 'nullable|url|max:255',
            'tipo_descuento' => 'required|in:porcentaje,fijo',
            'valor_descuento' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'esta_activa' => 'nullable|boolean',
            'destacada_en_carrusel' => 'nullable|boolean',
            'categorias' => 'nullable|array',
            'categorias.*' => 'exists:categorias,id',
            'productos' => 'nullable|array',
            'productos.*' => 'exists:productos,id',
        ];
    }
}