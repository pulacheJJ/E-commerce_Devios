<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeAdminResourceStatusRequest extends FormRequest
{
    private const PERMISSIONS_BY_ROUTE = [
        'admin.usuarios.toggle' => 'usuarios.cambiar_estado',
        'admin.productos.toggle' => 'productos.cambiar_estado',
        'admin.categorias.toggle' => 'categorias.cambiar_estado',
        'admin.promociones.toggle' => 'promociones.cambiar_estado',
        'admin.envios.toggle' => 'envios.cambiar_estado',
    ];

    public function authorize(): bool
    {
        $permission = self::PERMISSIONS_BY_ROUTE[$this->route()?->getName()] ?? null;

        return $permission !== null && ($this->user()?->can($permission) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
