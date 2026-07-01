<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSliderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titulo'     => 'nullable|string|max:255',
            'subtitulo'  => 'nullable|string|max:255',
            'url_enlace' => 'nullable|string|max:255',
            'imagen'     => 'required|image|mimes:webp,jpg,jpeg,png|max:2048',
            'orden'      => 'required|integer|min:0',
            'activo'     => 'nullable'
        ];
    }
}