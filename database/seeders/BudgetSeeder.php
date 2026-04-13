<?php

namespace Database\Seeders;

use App\Models\Boutique;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();
        $adminId  = $admin?->id ?? 1;

        $boutiques  = Boutique::all()->keyBy('code');
        $categories = Category::whereNull('parent_id')->get()->keyBy('name'); // catégories racines

        // ── Budgets annuels 2026 par boutique × catégorie parent ─────────────
        // Logique SCN : le siège consomme plus, Touba est un relais important,
        // les agences Plateau et Médina ont des budgets plus limités.

        $budgetMatrix = [
            //  [code_boutique, catégorie parent, montant annuel, note]
            ['SCN-DKR', 'Produits Alimentaires',               45_000_000, 'Budget annuel 2026 — approvisionnement alimentaire siège Dalifort'],
            ['SCN-DKR', 'Quincaillerie & Équipements Industriels', 12_000_000, 'Budget annuel 2026 — quincaillerie et pièces siège'],
            ['SCN-DKR', 'Lubrifiants & Huiles Techniques',      6_500_000, 'Budget annuel 2026 — lubrifiants flotte véhicules siège'],
            ['SCN-DKR', 'Matériaux de Construction',           18_000_000, 'Budget annuel 2026 — matériaux chantiers Dakar'],

            ['SCN-TBA', 'Produits Alimentaires',               32_000_000, 'Budget annuel 2026 — approvisionnement alimentaire dépôt Touba'],
            ['SCN-TBA', 'Quincaillerie & Équipements Industriels',  5_000_000, 'Budget annuel 2026 — quincaillerie dépôt Touba'],
            ['SCN-TBA', 'Lubrifiants & Huiles Techniques',      3_000_000, 'Budget annuel 2026 — lubrifiants Touba'],
            ['SCN-TBA', 'Matériaux de Construction',           10_000_000, 'Budget annuel 2026 — matériaux chantiers Touba'],

            ['SCN-PLT', 'Produits Alimentaires',               15_000_000, 'Budget annuel 2026 — agence Plateau'],
            ['SCN-PLT', 'Quincaillerie & Équipements Industriels',  2_500_000, 'Budget annuel 2026 — quincaillerie Plateau'],
            ['SCN-PLT', 'Matériaux de Construction',            4_000_000, 'Budget annuel 2026 — matériaux Plateau'],

            ['SCN-MED', 'Produits Alimentaires',               12_000_000, 'Budget annuel 2026 — dépôt Médina'],
            ['SCN-MED', 'Quincaillerie & Équipements Industriels',  2_000_000, 'Budget annuel 2026 — quincaillerie Médina'],
            ['SCN-MED', 'Matériaux de Construction',            3_500_000, 'Budget annuel 2026 — matériaux Médina'],
        ];

        $created = 0;
        foreach ($budgetMatrix as [$codeBoutique, $catName, $amount, $notes]) {
            $boutique = $boutiques->get($codeBoutique);
            $category = $categories->get($catName);

            if (! $boutique || ! $category) {
                $this->command->warn("⚠ Ignoré : boutique={$codeBoutique} catégorie={$catName}");
                continue;
            }

            Budget::firstOrCreate(
                [
                    'boutique_id'  => $boutique->id,
                    'category_id'  => $category->id,
                    'year'         => 2026,
                    'month'        => null, // budget annuel
                ],
                [
                    'amount'     => $amount,
                    'notes'      => $notes,
                    'created_by' => $adminId,
                ]
            );
            $created++;
        }

        $this->command->info("✓ {$created} budgets annuels 2026 créés.");
    }
}
