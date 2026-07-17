<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllWithRoles()
    {
        // Traemos todos, incluso los eliminados lógicamente, para poder restaurarlos
        return User::withTrashed()->with('roles')->latest()->paginate(10);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $attributes = Arr::only($data, ['name', 'email', 'password']);
            $attributes['password'] = Hash::make($attributes['password']);

            $user = User::create($attributes);
            $user->syncRoles([$data['role']]);
            $user->syncPermissions($data['role'] === 'gestor' ? ($data['permissions'] ?? []) : []);

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = Arr::only($data, ['name', 'email']);
            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $user->update($attributes);
            $user->syncRoles([$data['role']]);
            $user->syncPermissions($data['role'] === 'gestor' ? ($data['permissions'] ?? []) : []);

            return $user->load('roles', 'permissions');
        });
    }

    public function toggleStatus($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        // No permitimos que el administrador principal se elimine a sí mismo
        if ($user->id === auth()->id()) {
            throw new \Exception('No puedes desactivar tu propia cuenta.');
        }

        if ($user->trashed()) {
            $user->restore(); // Si está eliminado lógicamente, lo restaura
            return 'restaurado';
        } else {
            $user->delete();  // Si está activo, lo elimina lógicamente
            return 'desactivado';
        }
    }
}
