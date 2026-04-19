<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use App\Models\Groupe;
use Illuminate\Database\Seeder;

class GroupeSeeder extends Seeder
{
    public function run(): void
    {
        $groupe = Groupe::firstOrCreate(
            ['code' => 'FC'],
            ['nom' => 'Fortune Capital', 'is_active' => true]
        );

        $societes = [
            ['code' => 'FIM', 'nom' => 'Fimoluse'],
            ['code' => 'ATR', 'nom' => 'ATRA'],
            ['code' => 'ELY', 'nom' => 'Elycargo'],
            ['code' => 'SNG', 'nom' => 'Senegui'],
        ];

        foreach ($societes as $s) {
            Entreprise::firstOrCreate(
                ['code' => $s['code']],
                ['groupe_id' => $groupe->id, 'nom' => $s['nom'], 'is_active' => true]
            );
        }
    }
}
