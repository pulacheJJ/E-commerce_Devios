<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromocionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('promociones.editar') ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:150',
            'subtitulo' => 'nullable|string|max:255',
            'ruta_banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Nullable aquí
            'url_destino' => 'nullable|url|max:255',
            'tipo_descuento' => 'required|in:porcentaje,fijo',
            'valor_descuento' => ['required', 'numeric', 'gt:0', 'max:99999999.99', Rule::when($this->input('tipo_descuento') === 'porcentaje', ['lte:100'])],
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'esta_activa' => 'nullable|boolean',
            'destacada_en_carrusel' => 'nullable|boolean',
            'categorias' => 'nullable|array',
            'categorias.*' => ['distinct', Rule::exists('categorias', 'id')->where(fn ($query) => $query->where('esta_activa', true))],
            'productos' => 'nullable|array',
            'productos.*' => ['distinct', Rule::exists('productos', 'id')->where(fn ($query) => $query->where('esta_activo', true)->whereNull('deleted_at'))],
        ];
    }
}
