<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllWithRoles()
    {
        // Traemos todos, incluso los eliminados lógicamente, para poder restaurarlos
        return User::withTrashed()->with('roles')->latest()->paginate(10);
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->assignRole($data['role']);
        
        return $user;
    }

    public function updateUser(User $user, array $data)
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);

        return $user;
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