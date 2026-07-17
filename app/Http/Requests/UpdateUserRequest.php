<?php

namespace App\Http\Requests;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        if (! ($actor?->can('usuarios.editar') ?? false)) {
            return false;
        }

        $target = User::withTrashed()->find($this->route('usuario'));
        if ($target?->hasRole('admin') && ! $actor->hasRole('admin')) {
            return false;
        }
        if ($this->input('role') === 'admin' && ! $actor->hasRole('admin')) {
            return false;
        }
        if ($target && $target->id === $actor->id && $this->input('role') !== 'admin') {
            return false;
        }

        return $actor->hasRole('admin') || empty(array_diff($this->requestedPermissions(), $actor->getAllPermissions()->pluck('name')->all()));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($this->route('usuario')),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['admin', 'gestor']), Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => [Rule::requiredIf(fn () => $this->input('role') === 'gestor'), 'array', 'min:1', 'max:23'],
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionSeeder::PERMISSIONS)],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Selecciona al menos un permiso para el gestor.',
            'permissions.min' => 'Selecciona al menos un permiso para el gestor.',
            'permissions.*.distinct' => 'No se permiten permisos duplicados.',
            'permissions.*.in' => 'Uno de los permisos seleccionados no es válido.',
            'role.in' => 'El rol seleccionado no es válido.',
        ];
    }

    private function requestedPermissions(): array
    {
        return is_array($this->input('permissions'))
            ? array_filter($this->input('permissions'), 'is_string')
            : [];
    }
}
