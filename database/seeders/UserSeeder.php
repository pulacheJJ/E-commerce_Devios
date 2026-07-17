<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');
        $name = env('SEED_ADMIN_NAME', 'Administrador');

        if (! $email || ! $password) {
            throw new RuntimeException(
                'Define SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD de forma segura antes de ejecutar UserSeeder.'
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SEED_ADMIN_EMAIL no contiene un correo válido.');
        }

        $hasRequiredComplexity = strlen($password) >= 14
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^a-zA-Z0-9]/', $password);

        if (! $hasRequiredComplexity) {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD debe tener al menos 14 caracteres, mayúsculas, minúsculas, números y símbolos.'
            );
        }

        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $admin->syncRoles([$roleAdmin]);
    }
}
