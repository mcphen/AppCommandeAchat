<?php

namespace Database\Seeders;

use App\Models\NiveauValidation;
use App\Models\SeuilValidation;
use Illuminate\Database\Seeder;

class NiveauValidationSeeder extends Seeder
{
    public function run(): void
    {
        $niveaux = [
            ['nom' => 'Comptabilité',         'slug' => 'compta', 'ordre' => 1, 'seuil' => 0],
            ['nom' => 'Directeur Financier',  'slug' => 'df',     'ordre' => 2, 'seuil' => 0],
            ['nom' => 'Directeur Général',    'slug' => 'dg',     'ordre' => 3, 'seuil' => 500000],
            ['nom' => 'PDG',                  'slug' => 'pdg',    'ordre' => 4, 'seuil' => 2000000],
        ];

        foreach ($niveaux as $data) {
            $niveau = NiveauValidation::firstOrCreate(
                ['slug' => $data['slug']],
                ['nom' => $data['nom'], 'ordre' => $data['ordre']]
            );

            SeuilValidation::firstOrCreate(
                ['niveau_validation_id' => $niveau->id],
                ['montant_seuil' => $data['seuil']]
            );
        }
    }
}
