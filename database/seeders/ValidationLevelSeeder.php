<?php

namespace Database\Seeders;

use App\Models\ValidationLevel;
use Illuminate\Database\Seeder;

class ValidationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Responsable Approvisionnement', 'order' => 1, 'type' => 'validation', 'description' => 'Première validation par le responsable approvisionnement du dépôt'],
            ['name' => 'Directeur Administratif et Financier', 'order' => 2, 'type' => 'validation', 'description' => 'Validation financière et budgétaire par le DAF'],
            ['name' => 'DGA', 'order' => 3, 'type' => 'approbation', 'description' => 'Approbation par la Direction Générale Adjointe avant validation finale'],
            ['name' => 'Direction Générale',            'order' => 4, 'type' => 'validation', 'description' => 'Validation finale par la Direction Générale (commandes > seuil ou stratégiques)'],
        ];

        foreach ($levels as $level) {
            ValidationLevel::firstOrCreate(['order' => $level['order']], $level);
        }
    }
}
