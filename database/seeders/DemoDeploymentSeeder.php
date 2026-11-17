<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDeploymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DemoCatalogSeeder::class,
        ]);
    }
}
