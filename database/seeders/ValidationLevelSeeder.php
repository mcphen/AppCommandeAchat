<?php

namespace Database\Seeders;

use App\Models\ValidationLevel;
use Illuminate\Database\Seeder;

class ValidationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Chef de service', 'order' => 1, 'description' => 'Première validation par le chef de service'],
            ['name' => 'DAF',             'order' => 2, 'description' => 'Validation financière par le DAF'],
            ['name' => 'Direction',       'order' => 3, 'description' => 'Validation finale par la Direction Générale'],
        ];

        foreach ($levels as $level) {
            ValidationLevel::firstOrCreate(['order' => $level['order']], $level);
        }
    }
}
