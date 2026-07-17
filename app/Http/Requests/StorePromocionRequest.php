<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromocionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('promociones.crear') ?? false;
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
            'valor_descuento' => ['required', 'numeric', 'gt:0', 'max:99999999.99', Rule::when($this->input('tipo_descuento') === 'porcentaje', ['lte:100'])],
            'fecha_inicio' => 'required|date',
            // Regla de oro: La fecha de fin DEBE ser después de la de inicio
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'esta_activa' => 'nullable|boolean',
            'destacada_en_carrusel' => 'nullable|boolean',

            // Validamos los arrays que vendrán de tu multiselect
            'categorias' => 'nullable|array',
            'categorias.*' => ['distinct', Rule::exists('categorias', 'id')->where(fn ($query) => $query->where('esta_activa', true))],
            'productos' => 'nullable|array',
            'productos.*' => ['distinct', Rule::exists('productos', 'id')->where(fn ($query) => $query->where('esta_activo', true)->whereNull('deleted_at'))],
        ];
    }
}
