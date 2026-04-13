<?php

namespace Database\Seeders;

use App\Models\Boutique;
use Illuminate\Database\Seeder;

class BoutiqueSeeder extends Seeder
{
    public function run(): void
    {
        $boutiques = [
            [
                'code'      => 'SCN-DKR',
                'name'      => 'Siège Dalifort – Dakar',
                'address'   => 'Parc Mbaye Touré, Dalifort',
                'city'      => 'Dakar',
                'is_active' => true,
            ],
            [
                'code'      => 'SCN-TBA',
                'name'      => 'Dépôt Touba',
                'address'   => 'Route de Mbacké, Touba',
                'city'      => 'Touba',
                'is_active' => true,
            ],
            [
                'code'      => 'SCN-PLT',
                'name'      => 'Agence Plateau – Dakar',
                'address'   => 'Avenue Léopold Sédar Senghor, Plateau',
                'city'      => 'Dakar',
                'is_active' => true,
            ],
            [
                'code'      => 'SCN-MED',
                'name'      => 'Dépôt Médina',
                'address'   => 'Rue 19 x Rue 10, Médina',
                'city'      => 'Dakar',
                'is_active' => true,
            ],
        ];

        foreach ($boutiques as $boutique) {
            Boutique::updateOrCreate(
                ['code' => $boutique['code']],
                $boutique,
            );
        }

        $this->command->info('✓ ' . Boutique::count() . ' dépôts / agences créés.');
    }
}
