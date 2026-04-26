<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ModeReglementSeeder::class,
            ValidationLevelSeeder::class,
            BoutiqueSeeder::class,
            AdminUserSeeder::class,
            AppSettingSeeder::class,
            CategorySeeder::class,
            FournisseurSeeder::class,
            ArticleSeeder::class,
            FournisseurArticleSeeder::class,
            BudgetSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
