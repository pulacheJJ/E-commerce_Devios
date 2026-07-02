<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role; // Importante agregar esto

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos el rol (si no existe)
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);

        // 2. Creamos tu usuario
        $admin = User::updateOrCreate(
            ['email' => 'admin@devioz.com'],
            [
                'name' => 'Administrador DEVIOZ',
                'password' => Hash::make('Admin123!'), 
            ]
        );

        // 3. Le asignamos el rol al usuario
        $admin->assignRole($roleAdmin);
    }
}