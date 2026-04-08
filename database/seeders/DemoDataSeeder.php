<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
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
        $roles  = Role::pluck('id', 'slug');
        $levels = ValidationLevel::orderBy('order')->get();

        // ── Validateurs (un par niveau) ──────────────────────────────────────
        $validateurs = [
            [
                'name'                => 'Mamadou Koné',
                'email'               => 'm.kone@achatpro.ci',
                'validation_level_id' => $levels->firstWhere('order', 1)?->id,
            ],
            [
                'name'                => 'Fatou Diallo',
                'email'               => 'f.diallo@achatpro.ci',
                'validation_level_id' => $levels->firstWhere('order', 2)?->id,
            ],
            [
                'name'                => 'Kouassi Bamba',
                'email'               => 'k.bamba@achatpro.ci',
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

        // ── Demandeurs ────────────────────────────────────────────────────────
        $demandeurs = [
            ['name' => 'Awa Touré',       'email' => 'a.toure@achatpro.ci'],
            ['name' => 'Ibrahima Seck',   'email' => 'i.seck@achatpro.ci'],
            ['name' => 'Mariam Coulibaly','email' => 'm.coulibaly@achatpro.ci'],
            ['name' => 'Seydou Traoré',   'email' => 's.traore@achatpro.ci'],
            ['name' => 'Aminata Bah',     'email' => 'a.bah@achatpro.ci'],
        ];

        $demandeurUsers = [];
        foreach ($demandeurs as $data) {
            $demandeurUsers[] = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'role_id'  => $roles['demandeur'],
                ])
            );
        }

        // ── Données des commandes ─────────────────────────────────────────────
        $orderTemplates = [
            ['title' => 'Achat de matériel informatique',          'amount_min' => 500000,   'amount_max' => 3000000],
            ['title' => 'Fournitures de bureau',                    'amount_min' => 50000,    'amount_max' => 300000],
            ['title' => 'Abonnement logiciel de comptabilité',      'amount_min' => 150000,   'amount_max' => 600000],
            ['title' => 'Mobilier de bureau',                       'amount_min' => 400000,   'amount_max' => 2500000],
            ['title' => 'Maintenance photocopieur',                  'amount_min' => 80000,    'amount_max' => 250000],
            ['title' => 'Consommables imprimantes',                  'amount_min' => 45000,    'amount_max' => 180000],
            ['title' => 'Véhicule de service',                      'amount_min' => 5000000,  'amount_max' => 15000000],
            ['title' => 'Formation Excel et outils bureautiques',    'amount_min' => 120000,   'amount_max' => 500000],
            ['title' => 'Achat de climatiseurs',                    'amount_min' => 600000,   'amount_max' => 2000000],
            ['title' => 'Papeterie et consommables',                'amount_min' => 30000,    'amount_max' => 150000],
            ['title' => 'Équipements de sécurité incendie',         'amount_min' => 200000,   'amount_max' => 800000],
            ['title' => 'Serveur informatique et NAS',              'amount_min' => 1500000,  'amount_max' => 5000000],
            ['title' => 'Entretien et nettoyage des locaux',        'amount_min' => 100000,   'amount_max' => 400000],
            ['title' => 'Matériel audiovisuel (vidéoprojecteur)',   'amount_min' => 350000,   'amount_max' => 1200000],
            ['title' => 'Équipement cuisine salle de pause',        'amount_min' => 180000,   'amount_max' => 700000],
            ['title' => 'Achat de licences antivirus',              'amount_min' => 90000,    'amount_max' => 400000],
            ['title' => 'Réparation groupe électrogène',             'amount_min' => 250000,   'amount_max' => 900000],
            ['title' => 'Achat de téléphones IP',                   'amount_min' => 300000,   'amount_max' => 1000000],
            ['title' => 'Fournitures d\'hygiène et sanitaires',     'amount_min' => 60000,    'amount_max' => 200000],
            ['title' => 'Renouvellement abonnement internet',       'amount_min' => 120000,   'amount_max' => 500000],
            ['title' => 'Achat de disques durs externes',           'amount_min' => 80000,    'amount_max' => 350000],
            ['title' => 'Uniformes et équipements du personnel',    'amount_min' => 400000,   'amount_max' => 1500000],
            ['title' => 'Rénovation salle de réunion',              'amount_min' => 800000,   'amount_max' => 3000000],
            ['title' => 'Achat d\'onduleurs (UPS)',                 'amount_min' => 200000,   'amount_max' => 800000],
        ];

        $descriptions = [
            'Demande validée par le département technique suite à l\'audit interne.',
            'Besoin urgent identifié lors de la réunion du comité de direction.',
            'Remplacement du matériel obsolète conformément au plan d\'équipement annuel.',
            'Acquisition nécessaire pour assurer la continuité des opérations.',
            'Commande liée au projet d\'extension du service informatique.',
            'Demande émise suite aux recommandations du rapport trimestriel.',
            'Renouvellement planifié dans le budget de fonctionnement de l\'exercice.',
            'Achat nécessaire pour améliorer les conditions de travail du personnel.',
            'Conformément à la politique d\'achat approuvée par la direction générale.',
            'Nécessaire pour respecter les normes de sécurité en vigueur.',
        ];

        // Période : juin 2025 → 7 avril 2026
        $start = Carbon::create(2025, 6, 1);
        $end   = Carbon::create(2026, 4, 7);

        // Distribution des statuts sur la période
        $scenarii = [
            // [status, submitted, fully_validated, nb_levels_validated]
            ['approved', true,  true,  3],  // approuvée complètement
            ['approved', true,  true,  3],
            ['approved', true,  true,  3],
            ['rejected', true,  true,  1],  // rejetée au niveau 1
            ['rejected', true,  true,  2],  // rejetée au niveau 2
            ['pending',  true,  false, 1],  // en attente au niveau 2
            ['pending',  true,  false, 0],  // en attente au niveau 1
            ['draft',    false, false, 0],  // jamais soumise
        ];

        $orderCount = 0;

        foreach ($demandeurUsers as $demandeur) {
            // Chaque demandeur génère entre 6 et 10 commandes
            $nbOrders = rand(6, 10);

            for ($i = 0; $i < $nbOrders; $i++) {
                $template  = $orderTemplates[array_rand($orderTemplates)];
                $scenario  = $scenarii[array_rand($scenarii)];
                $createdAt = Carbon::createFromTimestamp(rand($start->timestamp, $end->timestamp));

                [$status, $submitted, $fullyValidated, $nbLevelsValidated] = $scenario;

                // Soumission 1 à 5 jours après création
                $submittedAt = $submitted
                    ? $createdAt->copy()->addDays(rand(1, 5))
                    : null;

                // Ne pas soumettre si la date de soumission dépasse le 7 avril 2026
                if ($submittedAt && $submittedAt->greaterThan($end)) {
                    $submittedAt = null;
                    $status      = 'draft';
                    $submitted   = false;
                }

                $currentLevelOrder = match ($status) {
                    'pending'  => $nbLevelsValidated + 1,
                    'approved' => null,
                    'rejected' => null,
                    default    => null,
                };

                $amount = rand($template['amount_min'], $template['amount_max']);
                // Arrondi à 1000 FCFA
                $amount = round($amount / 1000) * 1000;

                $order = PurchaseOrder::create([
                    'user_id'             => $demandeur->id,
                    'title'               => $template['title'],
                    'description'         => $descriptions[array_rand($descriptions)],
                    'amount'              => $amount,
                    'status'              => $status,
                    'current_level_order' => $currentLevelOrder,
                    'submitted_at'        => $submittedAt,
                    'created_at'          => $createdAt,
                    'updated_at'          => $submittedAt ?? $createdAt,
                ]);

                // Logs de validation
                if ($submitted && $nbLevelsValidated > 0) {
                    $validationDate = $submittedAt->copy();

                    for ($lvl = 1; $lvl <= $nbLevelsValidated; $lvl++) {
                        $validationDate = $validationDate->copy()->addDays(rand(1, 3));
                        $level          = $levels->firstWhere('order', $lvl);
                        $validateur     = $validateurUsers[$lvl - 1] ?? $validateurUsers[0];

                        $isLastLevel  = ($lvl === $nbLevelsValidated);
                        $action       = match ($status) {
                            'rejected' => $isLastLevel ? 'rejected' : 'approved',
                            default    => 'approved',
                        };

                        ValidationLog::create([
                            'purchase_order_id'  => $order->id,
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

        $this->command->info("✓ {$orderCount} commandes créées pour " . count($demandeurUsers) . " demandeurs.");
        $this->command->info("✓ " . count($validateurUsers) . " validateurs créés.");
    }

    private function rejectionComment(): string
    {
        $comments = [
            'Montant trop élevé par rapport au budget disponible.',
            'Justification insuffisante. Veuillez fournir des devis comparatifs.',
            'Demande non conforme à la politique d\'achat en vigueur.',
            'Budget du département insuffisant pour cet exercice.',
            'Priorité non justifiée. À reconsidérer au prochain trimestre.',
        ];

        return $comments[array_rand($comments)];
    }
}
