<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // Deve rodar primeiro (cria roles e permissões)
            DemoDataSeeder::class,        // Depois os dados de demonstração
        ]);
    }
}