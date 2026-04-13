<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Boutique;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Role;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles      = Role::pluck('id', 'slug');
        $levels     = ValidationLevel::orderBy('order')->get();
        $boutiques  = Boutique::orderBy('name')->get()->values();
        $fournisseurs = Fournisseur::where('is_active', true)->get();
        $articles     = Article::where('is_active', true)->get();

        // ── Validateurs ───────────────────────────────────────────────────────
        $validateurs = [
            [
                'name'                => 'Pape Ibrahima Ndiaye',
                'email'               => 'p.ndiaye@scndienne.sn',
                'validation_level_id' => $levels->firstWhere('order', 1)?->id,
            ],
            [
                'name'                => 'Aminata Diop',
                'email'               => 'a.diop@scndienne.sn',
                'validation_level_id' => $levels->firstWhere('order', 2)?->id,
            ],
            [
                'name'                => 'Moussa Sall',
                'email'               => 'm.sall@scndienne.sn',
                'validation_level_id' => $levels->firstWhere('order', 3)?->id,
            ],
        ];

        $validateurUsers = [];
        foreach ($validateurs as $data) {
            $validateurUsers[] = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'role_id'  => $roles['validateur'],
                ])
            );
        }

        // ── Demandeurs ─────────────────────────────────────────────────────────
        $demandeurs = [
            ['name' => 'Seydou Diallo',       'email' => 's.diallo@scndienne.sn',    'boutique_idx' => 0], // Siège Dalifort
            ['name' => 'Fatou Mbaye',         'email' => 'f.mbaye@scndienne.sn',     'boutique_idx' => 0], // Siège Dalifort
            ['name' => 'Oumar Ba',            'email' => 'o.ba@scndienne.sn',        'boutique_idx' => 1], // Dépôt Touba
            ['name' => 'Rokhaya Touré',       'email' => 'r.toure@scndienne.sn',     'boutique_idx' => 2], // Agence Plateau
            ['name' => 'Ibrahima Ly',         'email' => 'i.ly@scndienne.sn',        'boutique_idx' => 3], // Dépôt Médina
        ];

        $demandeurUsers = [];
        foreach ($demandeurs as $data) {
            $boutique = $boutiques[$data['boutique_idx']] ?? $boutiques->first();
            $demandeurUsers[] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'        => $data['name'],
                    'email'       => $data['email'],
                    'password'    => Hash::make('password'),
                    'role_id'     => $roles['demandeur'],
                    'boutique_id' => $boutique?->id,
                ]
            );
        }

        // ── Templates de commandes SCN-SUARL ────────────────────────────────
        $orderTemplates = [
            // Alimentaire
            ['title' => 'Réapprovisionnement huile végétale 50L',         'amount_min' =>  450000,  'amount_max' =>  2500000],
            ['title' => 'Achat huile en sachets – lot 260 unités',         'amount_min' =>  280000,  'amount_max' =>   840000],
            ['title' => 'Commande farine de blé 50 kg – lot mensuel',     'amount_min' =>  390000,  'amount_max' =>  1560000],
            ['title' => 'Approvisionnement riz brisé importé',            'amount_min' =>  480000,  'amount_max' =>  2400000],
            ['title' => 'Achat sachets alimentaires 100 ml',              'amount_min' =>  120000,  'amount_max' =>   480000],
            ['title' => 'Réapprovisionnement mil et maïs local',          'amount_min' =>  260000,  'amount_max' =>  1040000],
            // Quincaillerie / pompes
            ['title' => 'Achat pompe centrifuge eau pour dépôt Touba',    'amount_min' =>  125000,  'amount_max' =>   500000],
            ['title' => 'Remplacement pompe hydraulique atelier',         'amount_min' =>  285000,  'amount_max' =>   855000],
            ['title' => 'Commande vannes et clapets DN50',                'amount_min' =>   85000,  'amount_max' =>   340000],
            ['title' => 'Achat pièces détachées mécaniques stock',        'amount_min' =>   95000,  'amount_max' =>   380000],
            ['title' => 'Réapprovisionnement peinture acrylique locaux',  'amount_min' =>  160000,  'amount_max' =>   640000],
            // Lubrifiants
            ['title' => 'Achat huile moteur 15W40 flotte véhicules',      'amount_min' =>  180000,  'amount_max' =>   720000],
            ['title' => 'Commande huile hydraulique ISO 46 – stock',      'amount_min' =>  140000,  'amount_max' =>   560000],
            ['title' => 'Réapprovisionnement graisse multi-usage',        'amount_min' =>   96000,  'amount_max' =>   288000],
            // Construction
            ['title' => 'Commande ciment CPA 285 – chantier Dalifort',    'amount_min' =>  225000,  'amount_max' =>  1800000],
            ['title' => 'Achat bitume 60/70 pour route dépôt Touba',      'amount_min' =>  490000,  'amount_max' =>  1960000],
            ['title' => 'Réapprovisionnement ciment CPJ 250 – agence',    'amount_min' =>  350000,  'amount_max' =>  1400000],
            ['title' => 'Achat pots à béton et panneaux coffreurs',       'amount_min' =>  120000,  'amount_max' =>   480000],
            ['title' => 'Commande treillis soudés extension entrepôt',    'amount_min' =>  240000,  'amount_max' =>   720000],
        ];

        $descriptions = [
            'Stock critique atteint — réapprovisionnement urgent validé par le responsable dépôt.',
            'Commande mensuelle selon le plan d\'approvisionnement annuel approuvé.',
            'Besoin identifié lors de l\'inventaire hebdomadaire du dépôt.',
            'Conformément au budget d\'exploitation de l\'exercice en cours.',
            'Demande émise suite aux recommandations de l\'audit stock de la semaine.',
            'Réapprovisionnement planifié — rupture prévisible sous 7 jours.',
            'Commande liée à l\'activité saisonnière et à l\'augmentation des ventes.',
            'Achat nécessaire pour assurer la continuité des livraisons clients.',
            'Conformément à la politique d\'achat validée par la direction générale.',
            'Commande de remplacement suite à la détérioration constatée du matériel.',
        ];

        // Période : juin 2025 → 7 avril 2026
        $start = Carbon::create(2025, 6, 1);
        $end   = Carbon::create(2026, 4, 7);

        $scenarii = [
            ['approved', true,  true,  3],
            ['approved', true,  true,  3],
            ['approved', true,  true,  3],
            ['rejected', true,  true,  1],
            ['rejected', true,  true,  2],
            ['pending',  true,  false, 1],
            ['pending',  true,  false, 0],
            ['draft',    false, false, 0],
        ];

        $approvedFournisseurs = $fournisseurs->where('is_approved', true)->values();
        $allFournisseurs      = $fournisseurs->values();
        $orderCount = 0;
        $lineCount  = 0;

        foreach ($demandeurUsers as $demandeur) {
            $nbOrders = rand(6, 10);

            for ($i = 0; $i < $nbOrders; $i++) {
                $template  = $orderTemplates[array_rand($orderTemplates)];
                $scenario  = $scenarii[array_rand($scenarii)];
                $createdAt = Carbon::createFromTimestamp(rand($start->timestamp, $end->timestamp));

                [$status, $submitted, $fullyValidated, $nbLevelsValidated] = $scenario;

                $submittedAt = $submitted
                    ? $createdAt->copy()->addDays(rand(1, 5))
                    : null;

                if ($submittedAt && $submittedAt->greaterThan($end)) {
                    $submittedAt = null;
                    $status      = 'draft';
                    $submitted   = false;
                }

                $currentLevelOrder = match ($status) {
                    'pending' => $nbLevelsValidated + 1,
                    default   => null,
                };

                // 2 commandes sur 3 ont des lignes d'articles
                $useLines        = ($i % 3 !== 0) && $articles->isNotEmpty() && $fournisseurs->isNotEmpty();
                $orderFournisseur = null;

                if (! $useLines && $allFournisseurs->isNotEmpty()) {
                    $orderFournisseur = $allFournisseurs->random();
                }

                $amount = rand($template['amount_min'], $template['amount_max']);
                $amount = round($amount / 1000) * 1000;

                $order = PurchaseOrder::create([
                    'user_id'             => $demandeur->id,
                    'boutique_id'         => $demandeur->boutique_id,
                    'fournisseur_id'      => $orderFournisseur?->id,
                    'title'               => $template['title'],
                    'description'         => $descriptions[array_rand($descriptions)],
                    'amount'              => $amount,
                    'status'              => $status,
                    'current_level_order' => $currentLevelOrder,
                    'submitted_at'        => $submittedAt,
                    'created_at'          => $createdAt,
                    'updated_at'          => $submittedAt ?? $createdAt,
                ]);

                // Lignes de commande
                if ($useLines) {
                    $nbLines     = rand(2, 5);
                    $sampledItems = $articles->random(min($nbLines, $articles->count()));
                    $totalAmount = 0;

                    foreach ($sampledItems as $article) {
                        $qty             = rand(10, 200);
                        $unitPrice       = $article->unit_price ?? rand(5000, 100000);
                        $unitPrice       = round($unitPrice * (0.85 + lcg_value() * 0.3) / 500) * 500;
                        $lineFournisseur = $approvedFournisseurs->isNotEmpty()
                            ? $approvedFournisseurs->random()
                            : null;

                        if (rand(1, 5) === 1 && $allFournisseurs->isNotEmpty()) {
                            $lineFournisseur = $allFournisseurs->random();
                        }

                        PurchaseOrderLine::create([
                            'purchase_order_id' => $order->id,
                            'article_id'        => $article->id,
                            'fournisseur_id'    => $lineFournisseur?->id,
                            'quantity'          => $qty,
                            'unit_price'        => $unitPrice,
                            'note'              => null,
                        ]);

                        $totalAmount += $qty * $unitPrice;
                        $lineCount++;
                    }

                    $order->updateQuietly(['amount' => $totalAmount]);
                }

                // Logs de validation
                if ($submitted && $nbLevelsValidated > 0) {
                    $validationDate = $submittedAt->copy();

                    for ($lvl = 1; $lvl <= $nbLevelsValidated; $lvl++) {
                        $validationDate = $validationDate->copy()->addDays(rand(1, 3));
                        $level          = $levels->firstWhere('order', $lvl);
                        $validateur     = $validateurUsers[$lvl - 1] ?? $validateurUsers[0];

                        $isLastLevel = ($lvl === $nbLevelsValidated);
                        $action      = match ($status) {
                            'rejected' => $isLastLevel ? 'rejected' : 'approved',
                            default    => 'approved',
                        };

                        ValidationLog::create([
                            'purchase_order_id'   => $order->id,
                            'validation_level_id' => $level?->id,
                            'user_id'             => $validateur->id,
                            'action'              => $action,
                            'comment'             => $action === 'rejected'
                                ? $this->rejectionComment()
                                : null,
                            'created_at'          => $validationDate,
                            'updated_at'          => $validationDate,
                        ]);
                    }
                }

                $orderCount++;
            }
        }

        $this->command->info("✓ {$orderCount} commandes créées pour " . count($demandeurUsers) . " demandeurs SCN.");
        $this->command->info("✓ {$lineCount} lignes de commande créées.");
        $this->command->info("✓ " . count($validateurUsers) . " validateurs créés.");
    }

    private function rejectionComment(): string
    {
        $comments = [
            'Montant dépassant le seuil budgétaire du trimestre. Revoir la quantité.',
            'Justification insuffisante. Merci de joindre un devis fournisseur comparatif.',
            'Fournisseur non homologué pour ce type de produit — sélectionner un partenaire agréé.',
            'Budget du département épuisé pour cet exercice. Reporter au prochain trimestre.',
            'Doublon de commande détecté. Vérifier le stock disponible avant de renouveler.',
        ];

        return $comments[array_rand($comments)];
    }
}
