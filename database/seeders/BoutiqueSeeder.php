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
                'code' => 'DKR-PLT',
                'name' => 'Boutique Plateau',
                'address' => 'Avenue Léopold Sédar Senghor',
                'city' => 'Dakar',
                'is_active' => true,
            ],
            [
                'code' => 'DKR-MED',
                'name' => 'Boutique Médina',
                'address' => 'Rue 19 x Rue 10, Médina',
                'city' => 'Dakar',
                'is_active' => true,
            ],
            [
                'code' => 'DKR-ALM',
                'name' => 'Boutique Almadies',
                'address' => 'Route des Almadies, Ngor',
                'city' => 'Dakar',
                'is_active' => true,
            ],
            [
                'code' => 'DKR-GRD',
                'name' => 'Boutique Grand Dakar',
                'address' => 'Avenue Cheikh Anta Diop, Grand Dakar',
                'city' => 'Dakar',
                'is_active' => true,
            ],
            [
                'code' => 'DKR-GUL',
                'name' => 'Boutique Guédiawaye',
                'address' => 'Avenue Bourguiba, Guédiawaye',
                'city' => 'Dakar',
                'is_active' => true,
            ],
            [
                'code' => 'DKR-PIK',
                'name' => 'Boutique Pikine',
                'address' => 'Marché de Pikine, Rue Lamine Guèye',
                'city' => 'Dakar',
                'is_active' => true,
            ],
        ];

        foreach ($boutiques as $boutique) {
            Boutique::updateOrCreate(
                ['code' => $boutique['code']],
                $boutique,
            );
        }
    }
}
