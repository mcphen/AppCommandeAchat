<?php

namespace Database\Seeders;

use App\Models\BudgetAnnuel;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class BudgetAnnuelSeeder extends Seeder
{
    public function run(): void
    {
        $budgets = [
            'FIM' => [
                2025 => ['libelle' => 'Budget opérationnel Fimoluse 2025',  'montant' => 75_000_000],
                2026 => ['libelle' => 'Budget opérationnel Fimoluse 2026',  'montant' => 90_000_000],
            ],
            'ATR' => [
                2025 => ['libelle' => 'Budget opérationnel ATRA 2025',      'montant' => 50_000_000],
                2026 => ['libelle' => 'Budget opérationnel ATRA 2026',      'montant' => 60_000_000],
            ],
            'ELY' => [
                2025 => ['libelle' => 'Budget opérationnel Elycargo 2025',  'montant' => 120_000_000],
                2026 => ['libelle' => 'Budget opérationnel Elycargo 2026',  'montant' => 150_000_000],
            ],
            'SNG' => [
                2025 => ['libelle' => 'Budget opérationnel Senegui 2025',   'montant' => 35_000_000],
                2026 => ['libelle' => 'Budget opérationnel Senegui 2026',   'montant' => 45_000_000],
            ],
        ];

        foreach ($budgets as $code => $annees) {
            $entreprise = Entreprise::where('code', $code)->first();
            foreach ($annees as $annee => $data) {
                BudgetAnnuel::firstOrCreate(
                    ['entreprise_id' => $entreprise->id, 'annee' => $annee],
                    [
                        'libelle'          => $data['libelle'],
                        'montant_total'    => $data['montant'],
                        'montant_consomme' => 0,
                        'is_active'        => true,
                    ]
                );
            }
        }
    }
}
