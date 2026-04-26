<?php

namespace Database\Seeders;

use App\Models\ModeReglement;
use Illuminate\Database\Seeder;

class ModeReglementSeeder extends Seeder
{
    public function run(): void
    {
        $modes = [
            ['name' => 'Espèces',      'slug' => 'especes',      'icon' => 'banknote',   'order' => 1],
            ['name' => 'Wave',         'slug' => 'wave',         'icon' => 'smartphone', 'order' => 2],
            ['name' => 'Orange Money', 'slug' => 'orange_money', 'icon' => 'smartphone', 'order' => 3],
            ['name' => 'Chèque',       'slug' => 'cheque',       'icon' => 'credit-card','order' => 4],
        ];

        foreach ($modes as $mode) {
            ModeReglement::firstOrCreate(['slug' => $mode['slug']], $mode);
        }
    }
}
