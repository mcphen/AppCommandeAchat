<?php

namespace Database\Seeders;

use App\Models\BudgetAnnuel;
use App\Models\DemandeAutorisationPaiement;
use App\Models\Entreprise;
use App\Models\ExpressionBesoin;
use App\Models\NiveauValidation;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Validateur;
use App\Models\ValidationDap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    private int $ebSeq2025  = 0;
    private int $ebSeq2026  = 0;
    private int $dapSeq2025 = 0;
    private int $dapSeq2026 = 0;

    private function ebRef(int $year): string
    {
        $seq = $year === 2025 ? ++$this->ebSeq2025 : ++$this->ebSeq2026;
        return sprintf('EB-%d-%04d', $year, $seq);
    }

    private function dapRef(int $year): string
    {
        $seq = $year === 2025 ? ++$this->dapSeq2025 : ++$this->dapSeq2026;
        return sprintf('DAP-%d-%04d', $year, $seq);
    }

    public function run(): void
    {
        $compta = NiveauValidation::where('slug', 'compta')->first();
        $df     = NiveauValidation::where('slug', 'df')->first();
        $dg     = NiveauValidation::where('slug', 'dg')->first();
        $pdg    = NiveauValidation::where('slug', 'pdg')->first();

        $vCompta = Validateur::whereHas('niveauValidation', fn($q) => $q->where('slug', 'compta'))->first();
        $vDf     = Validateur::whereHas('niveauValidation', fn($q) => $q->where('slug', 'df'))->first();
        $vDg     = Validateur::whereHas('niveauValidation', fn($q) => $q->where('slug', 'dg'))->first();
        $vPdg    = Validateur::whereHas('niveauValidation', fn($q) => $q->where('slug', 'pdg'))->first();

        $uCompta = $vCompta->user;
        $uDf     = $vDf->user;
        $uDg     = $vDg->user;
        $uPdg    = $vPdg->user;

        $admin = User::whereHas('role', fn($q) => $q->where('slug', 'admin'))->first();

        // Transactions per company: [objet, montant, date_eb, statut_final, date_validation, motif_rejet?]
        // statut_final: 'payee', 'validee', 'rejetee_compta', 'rejetee_dap', 'en_cours'
        $transactions = [
            'FIM' => [
                // 2025 — bouclé
                ['Achat fournitures bureau',             180_000,   '2025-01-10', 'payee',         '2025-01-20'],
                ['Maintenance climatiseurs',              350_000,   '2025-01-28', 'payee',         '2025-02-07'],
                ['Abonnement logiciel comptabilité',     750_000,   '2025-02-12', 'payee',         '2025-02-24'],
                ['Formation Excel avancé personnel',     420_000,   '2025-03-05', 'payee',         '2025-03-18'],
                ['Renouvellement contrat gardiennage',   1_200_000, '2025-03-20', 'payee',         '2025-04-02'],
                ['Achat imprimante multifonction',       280_000,   '2025-04-08', 'rejetee_compta','2025-04-10', 'Justificatif insuffisant'],
                ['Mobilier bureau direction',            2_800_000, '2025-04-15', 'payee',         '2025-05-02'],
                ['Carburant véhicules de service',       195_000,   '2025-05-03', 'payee',         '2025-05-12'],
                ['Audit système informatique',           650_000,   '2025-06-02', 'payee',         '2025-06-18'],
                ['Achat matériel électrique',            310_000,   '2025-07-07', 'payee',         '2025-07-17'],
                ['Réparation groupe électrogène',        580_000,   '2025-08-12', 'payee',         '2025-08-25'],
                ['Communication campagne Q3',            980_000,   '2025-09-01', 'rejetee_dap',   '2025-09-15', 'Budget insuffisant ce trimestre'],
                ['Achat consommables imprimantes',       145_000,   '2025-09-20', 'payee',         '2025-09-30'],
                ['Entretien locaux siège',               430_000,   '2025-10-10', 'payee',         '2025-10-22'],
                ['Licence antivirus annuelle',           320_000,   '2025-11-03', 'payee',         '2025-11-14'],
                ['Frais déplacement équipe commerciale', 260_000,   '2025-12-01', 'payee',         '2025-12-12'],
                // 2026
                ['Achat chaises ergonomiques',           890_000,   '2026-01-08', 'payee',         '2026-01-20'],
                ['Abonnement cloud stockage',            240_000,   '2026-01-22', 'payee',         '2026-02-01'],
                ['Formation sécurité incendie',          370_000,   '2026-02-10', 'payee',         '2026-02-22'],
                ['Équipement salle de réunion',          1_650_000, '2026-03-03', 'en_cours',      null],
                ['Achat photocopieuse couleur',          480_000,   '2026-03-18', 'en_cours',      null],
            ],
            'ATR' => [
                // 2025
                ['Renouvellement flotte véhicules (acompte)', 3_500_000, '2025-01-15', 'payee',    '2025-02-05'],
                ['Achat GPS trackers véhicules',          620_000,   '2025-02-03', 'payee',         '2025-02-18'],
                ['Maintenance préventive camions',        890_000,   '2025-02-25', 'payee',         '2025-03-10'],
                ['Pneus de rechange stock',               750_000,   '2025-03-18', 'payee',         '2025-03-28'],
                ['Carburant Q2 2025',                     540_000,   '2025-04-02', 'payee',         '2025-04-14'],
                ['Formation conduite sécuritaire',        310_000,   '2025-04-28', 'rejetee_compta','2025-05-02', 'Doublon avec formation précédente'],
                ['Réparation camion ATR-12',              420_000,   '2025-05-15', 'payee',         '2025-05-26'],
                ['Achat outils atelier mécanique',        285_000,   '2025-06-10', 'payee',         '2025-06-20'],
                ['Assurance flotte annuelle',             1_800_000, '2025-07-01', 'payee',         '2025-07-18'],
                ['Équipement sécurité chauffeurs',        390_000,   '2025-08-05', 'payee',         '2025-08-16'],
                ['Carburant Q3 2025',                     510_000,   '2025-09-02', 'payee',         '2025-09-13'],
                ['Renouvellement vignettes véhicules',    175_000,   '2025-10-01', 'payee',         '2025-10-09'],
                ['Achat pièces détachées urgentes',       340_000,   '2025-11-12', 'payee',         '2025-11-22'],
                ['Carburant Q4 2025',                     490_000,   '2025-12-02', 'payee',         '2025-12-13'],
                // 2026
                ['Rénovation atelier maintenance',        2_200_000, '2026-01-10', 'payee',         '2026-01-28'],
                ['Achat outillage diagnostic électronique', 680_000, '2026-02-03', 'payee',         '2026-02-14'],
                ['Formation techniciens moteurs diesel',  450_000,   '2026-02-20', 'en_cours',      null],
                ['Carburant Q1 2026',                     520_000,   '2026-03-01', 'en_cours',      null],
            ],
            'ELY' => [
                // 2025
                ['Achat palettes stockage entrepôt',     480_000,   '2025-01-08', 'payee',         '2025-01-19'],
                ['Maintenance chariots élévateurs',      1_100_000, '2025-01-22', 'payee',         '2025-02-04'],
                ['Achat scanners code-barres',           950_000,   '2025-02-10', 'payee',         '2025-02-24'],
                ['Formation logistique entrepôt',         420_000,   '2025-03-03', 'payee',         '2025-03-14'],
                ['Réparation toiture entrepôt A',        2_500_000, '2025-03-20', 'payee',         '2025-04-08'],
                ['Achat étiqueteuses automatiques',      1_350_000, '2025-04-10', 'rejetee_dap',   '2025-04-22', 'Devis non conforme aux normes groupe'],
                ['Système de vidéosurveillance',         3_800_000, '2025-05-05', 'payee',         '2025-05-28'],
                ['Consommables emballage Q2',             380_000,   '2025-06-02', 'payee',         '2025-06-12'],
                ['Logiciel WMS (gestion entrepôt)',      5_200_000, '2025-06-20', 'payee',         '2025-07-15'],
                ['Entretien climatisation entrepôts',     620_000,   '2025-07-08', 'payee',         '2025-07-19'],
                ['Achat mobilier espace bureau entrepôt', 780_000,  '2025-08-04', 'payee',         '2025-08-16'],
                ['Consommables emballage Q3',             410_000,   '2025-09-01', 'payee',         '2025-09-11'],
                ['Formation pompiers volontaires',        290_000,   '2025-10-06', 'payee',         '2025-10-16'],
                ['Réparation chariot élévateur #3',       560_000,   '2025-11-03', 'payee',         '2025-11-14'],
                ['Consommables emballage Q4',             430_000,   '2025-12-01', 'payee',         '2025-12-11'],
                ['Extension entrepôt B (acompte)',       8_500_000, '2025-12-15', 'payee',         '2025-12-30'],
                // 2026
                ['Achat transpalettes électriques',      2_900_000, '2026-01-07', 'payee',         '2026-01-22'],
                ['Maintenance annuelle chariots',         740_000,   '2026-01-28', 'payee',         '2026-02-08'],
                ['Consommables emballage Q1 2026',        460_000,   '2026-02-10', 'payee',         '2026-02-20'],
                ['Système anti-incendie entrepôt C',     4_100_000, '2026-03-02', 'en_cours',      null],
                ['Formation nouveaux manutentionnaires',  350_000,   '2026-03-20', 'en_cours',      null],
            ],
            'SNG' => [
                // 2025
                ['Fournitures bureau & papeterie',        120_000,   '2025-01-12', 'payee',         '2025-01-22'],
                ['Abonnement téléphonie entreprise',      280_000,   '2025-01-30', 'payee',         '2025-02-10'],
                ['Réparation véhicule direction',         450_000,   '2025-02-18', 'payee',         '2025-03-01'],
                ['Achat ordinateurs portables (3)',       1_050_000, '2025-03-10', 'payee',         '2025-03-24'],
                ['Maintenance réseau informatique',       380_000,   '2025-04-07', 'payee',         '2025-04-18'],
                ['Frais notaire cession local',          2_400_000, '2025-04-22', 'rejetee_compta','2025-04-25', 'Manque approbation DG préalable'],
                ['Formation comptabilité SYSCOHADA',      560_000,   '2025-05-12', 'payee',         '2025-05-24'],
                ['Achat mobilier réception',              690_000,   '2025-06-03', 'payee',         '2025-06-16'],
                ['Carburant véhicules H1 2025',           310_000,   '2025-07-01', 'payee',         '2025-07-11'],
                ['Renouvellement bail local commercial',  1_500_000, '2025-08-10', 'payee',         '2025-08-26'],
                ['Achat tableau interactif salle conf.',  880_000,   '2025-09-08', 'payee',         '2025-09-20'],
                ['Entretien locaux & nettoyage Q3',       195_000,   '2025-10-02', 'payee',         '2025-10-12'],
                ['Abonnement logiciel RH',                420_000,   '2025-11-05', 'payee',         '2025-11-17'],
                ['Carburant véhicules H2 2025',           295_000,   '2025-12-03', 'payee',         '2025-12-13'],
                // 2026
                ['Rénovation bureau direction',           1_800_000, '2026-01-09', 'payee',         '2026-01-23'],
                ['Achat climatiseurs (2 unités)',          640_000,   '2026-02-04', 'payee',         '2026-02-15'],
                ['Formation management équipes',          510_000,   '2026-02-25', 'en_cours',      null],
                ['Achat serveur NAS sauvegarde',          960_000,   '2026-03-12', 'en_cours',      null],
            ],
        ];

        foreach ($transactions as $code => $items) {
            $entreprise = Entreprise::where('code', $code)->first();
            // Pick first employee of this company as the requester
            $employe = User::where('entreprise_id', $entreprise->id)->first();

            foreach ($items as $item) {
                [$objet, $montant, $dateEb, $statutFinal, $dateValidation, $motifRejet] = array_pad($item, 6, null);

                $anneeEb  = (int) substr($dateEb, 0, 4);
                $budget   = BudgetAnnuel::where('entreprise_id', $entreprise->id)
                                        ->where('annee', $anneeEb)
                                        ->first();

                // Create EB
                $ebStatut = match($statutFinal) {
                    'rejetee_compta' => 'rejetee',
                    default          => 'validee',
                };
                // EBs still en_cours => still en_attente at EB level
                if ($statutFinal === 'en_cours') $ebStatut = 'validee';

                $eb = ExpressionBesoin::create([
                    'reference'    => $this->ebRef($anneeEb),
                    'user_id'      => $employe->id,
                    'entreprise_id'=> $entreprise->id,
                    'objet'        => $objet,
                    'description'  => 'Demande relative à : ' . lcfirst($objet) . '.',
                    'montant'      => $montant,
                    'beneficiaire' => $entreprise->nom,
                    'justification'=> 'Nécessaire pour le bon fonctionnement des activités de ' . $entreprise->nom . '.',
                    'statut'       => $ebStatut,
                    'motif_rejet'  => $statutFinal === 'rejetee_compta' ? $motifRejet : null,
                    'rejected_at'  => $statutFinal === 'rejetee_compta' && $dateValidation ? $dateValidation : null,
                    'created_at'   => $dateEb,
                    'updated_at'   => $dateValidation ?? $dateEb,
                ]);

                if ($statutFinal === 'rejetee_compta') {
                    continue; // No DAP created
                }

                // Determine DAP statut
                $dapStatut = match($statutFinal) {
                    'payee'    => 'payee',
                    'validee'  => 'validee',
                    'rejetee_dap' => 'rejetee',
                    'en_cours' => 'en_cours',
                    default    => 'en_cours',
                };

                $anneeCreation = $anneeEb;
                $dap = DemandeAutorisationPaiement::create([
                    'reference'          => $this->dapRef($anneeCreation),
                    'expression_besoin_id' => $eb->id,
                    'budget_annuel_id'   => $budget?->id,
                    'created_by'         => $uCompta->id,
                    'statut'             => $dapStatut,
                    'notes'              => null,
                    'created_at'         => $dateValidation ?? $dateEb,
                    'updated_at'         => $dateValidation ?? $dateEb,
                ]);

                // ValidationDap for Compta (always)
                ValidationDap::create([
                    'dap_id'               => $dap->id,
                    'niveau_validation_id' => $compta->id,
                    'validateur_id'        => $vCompta->id,
                    'statut'               => 'approuve',
                    'commentaire'          => 'EB conforme, DAP créée.',
                    'validated_at'         => $dateValidation ?? $dateEb,
                    'created_at'           => $dateValidation ?? $dateEb,
                    'updated_at'           => $dateValidation ?? $dateEb,
                ]);

                if ($statutFinal === 'rejetee_dap') {
                    // DF rejects
                    ValidationDap::create([
                        'dap_id'               => $dap->id,
                        'niveau_validation_id' => $df->id,
                        'validateur_id'        => $vDf->id,
                        'statut'               => 'rejete',
                        'commentaire'          => $motifRejet,
                        'validated_at'         => $dateValidation,
                        'created_at'           => $dateValidation,
                        'updated_at'           => $dateValidation,
                    ]);

                    // Mark EB as rejected too
                    $eb->update(['statut' => 'rejetee', 'motif_rejet' => $motifRejet, 'rejected_at' => $dateValidation]);
                    continue;
                }

                if ($statutFinal === 'en_cours') {
                    // No further validations yet
                    continue;
                }

                // Full approval chain based on montant
                $needsDg  = $montant >= 500_000;
                $needsPdg = $montant >= 2_000_000;

                // DF validation (always required for DAP)
                $dfDate = $dateValidation;
                ValidationDap::create([
                    'dap_id'               => $dap->id,
                    'niveau_validation_id' => $df->id,
                    'validateur_id'        => $vDf->id,
                    'statut'               => 'approuve',
                    'commentaire'          => 'Approuvé par la Direction Financière.',
                    'validated_at'         => $dfDate,
                    'created_at'           => $dfDate,
                    'updated_at'           => $dfDate,
                ]);

                if ($needsDg) {
                    ValidationDap::create([
                        'dap_id'               => $dap->id,
                        'niveau_validation_id' => $dg->id,
                        'validateur_id'        => $vDg->id,
                        'statut'               => 'approuve',
                        'commentaire'          => 'Approuvé par la Direction Générale.',
                        'validated_at'         => $dfDate,
                        'created_at'           => $dfDate,
                        'updated_at'           => $dfDate,
                    ]);
                }

                if ($needsPdg) {
                    ValidationDap::create([
                        'dap_id'               => $dap->id,
                        'niveau_validation_id' => $pdg->id,
                        'validateur_id'        => $vPdg->id,
                        'statut'               => 'approuve',
                        'commentaire'          => 'Approuvé par la Présidence.',
                        'validated_at'         => $dfDate,
                        'created_at'           => $dfDate,
                        'updated_at'           => $dfDate,
                    ]);
                }

                // Paiement if statut = payee
                if ($statutFinal === 'payee' && $budget) {
                    Paiement::create([
                        'dap_id'          => $dap->id,
                        'created_by'      => $admin ? $admin->id : $uDf->id,
                        'montant'         => $montant,
                        'date_paiement'   => $dateValidation,
                        'reference'       => 'PAY-' . strtoupper(substr(md5($dap->reference), 0, 8)),
                        'mode_paiement'   => $montant >= 1_000_000 ? 'virement' : 'cheque',
                        'banque'          => 'SGBS',
                        'notes'           => null,
                        'created_at'      => $dateValidation,
                        'updated_at'      => $dateValidation,
                    ]);

                    // Update budget montant_consomme
                    $budget->increment('montant_consomme', $montant);
                }
            }
        }
    }
}
