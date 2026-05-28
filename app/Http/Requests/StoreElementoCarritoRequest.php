<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreElementoCarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'variante_id' => 'required|exists:variantes_producto,id',
            'cantidad'    => 'required|integer|min:1'
        ];
    }
}