<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            NiveauValidationSeeder::class,
            GroupeSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            BudgetAnnuelSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
