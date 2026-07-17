<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function toggle(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && $actor->can('usuarios.cambiar_estado');
    }
}
