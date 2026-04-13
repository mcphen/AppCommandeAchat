<?php

namespace Database\Seeders;

use App\Models\ValidationLevel;
use Illuminate\Database\Seeder;

class ValidationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Responsable Approvisionnement', 'order' => 1, 'description' => 'Première validation par le responsable approvisionnement du dépôt'],
            ['name' => 'Directeur Administratif et Financier', 'order' => 2, 'description' => 'Validation financière et budgétaire par le DAF'],
            ['name' => 'Direction Générale',            'order' => 3, 'description' => 'Validation finale par la Direction Générale (commandes > seuil ou stratégiques)'],
        ];

        foreach ($levels as $level) {
            ValidationLevel::firstOrCreate(['order' => $level['order']], $level);
        }
    }
}
