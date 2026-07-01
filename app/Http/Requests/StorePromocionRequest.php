<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromocionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permitimos que el admin pase
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:150',
            'subtitulo' => 'nullable|string|max:255',
            // Obligamos a que sea imagen, max 2MB
            'ruta_banner' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', 
            'url_destino' => 'nullable|url|max:255',
            'tipo_descuento' => 'required|in:porcentaje,fijo',
            'valor_descuento' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            // Regla de oro: La fecha de fin DEBE ser después de la de inicio
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'esta_activa' => 'nullable|boolean',
            'destacada_en_carrusel' => 'nullable|boolean',
            
            // Validamos los arrays que vendrán de tu multiselect
            'categorias' => 'nullable|array',
            'categorias.*' => 'exists:categorias,id',
            'productos' => 'nullable|array',
            'productos.*' => 'exists:productos,id',
        ];
    }
}