<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ValidationLevelSeeder::class,
            BoutiqueSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            FournisseurSeeder::class,
            ArticleSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
