<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\FournisseurArticle;
use Illuminate\Database\Seeder;

class FournisseurArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Chaque entrée = un tarif proposé par un fournisseur pour un article donné.
        // Plusieurs fournisseurs proposent les mêmes articles → le comparateur peut fonctionner.
        // Format : [code_fournisseur, reference_article, prix, ref_fns, delai_jours, valide_jusqu_au, notes]

        $tarifs = [

            // ════════════════════════════════════════════════════════════════
            // HUILES ALIMENTAIRES
            // ════════════════════════════════════════════════════════════════
            // Bidon 50L
            ['SOD-002', 'HUI-ALI-50L',    44000,  'SOD-HUI50',   5,  '2026-12-31', 'Prix départ dépôt Mbao'],
            ['DHM-011', 'HUI-ALI-50L',    46500,  null,          7,  null,         'Prix négocié sans engagement'],
            ['ACT-010', 'HUI-ALI-50L',    47000,  'ACT-H50-L',  10,  '2026-06-30', 'Disponible sur Touba uniquement'],

            // Bouteille 10L
            ['SOD-002', 'HUI-ALI-10L',    9000,   'SOD-HUI10',   5,  '2026-12-31', null],
            ['DHM-011', 'HUI-ALI-10L',    9800,   null,          7,  null,         null],

            // Bouteille 5L
            ['SOD-002', 'HUI-ALI-05L',    5200,   'SOD-HUI05',   5,  '2026-12-31', null],
            ['DHM-011', 'HUI-ALI-05L',    5800,   null,          8,  null,         null],
            ['ACT-010', 'HUI-ALI-05L',    5500,   'ACT-H05',     7,  '2026-09-30', null],

            // Sachets 260
            ['SOD-002', 'HUI-SAC-260',   26500,   'SOD-SAC260',  5,  '2026-12-31', 'Conditionnement carton'],
            ['DHM-011', 'HUI-SAC-260',   28500,   null,          6,  null,         null],
            ['ACT-010', 'HUI-SAC-260',   27000,   'ACT-S260',    9,  '2026-06-30', null],

            // Carton 12 × 1L
            ['SOD-002', 'HUI-ALI-1L-12', 17500,   'SOD-12X1L',   5,  '2026-12-31', null],
            ['DHM-011', 'HUI-ALI-1L-12', 19000,   null,          7,  null,         null],

            // ════════════════════════════════════════════════════════════════
            // FARINES & CÉRÉALES
            // ════════════════════════════════════════════════════════════════
            // Farine 50 kg
            ['GMD-001', 'FAR-BLE-50KG',  18500,   'GMD-F50',     3,  '2026-12-31', 'Tarif grossiste Grands Moulins'],
            ['ACT-010', 'FAR-BLE-50KG',  20000,   null,         10,  null,         'Disponible à Touba'],
            ['DHM-011', 'FAR-BLE-50KG',  19500,   'DHM-F50',     6,  null,         null],

            // Farine 25 kg
            ['GMD-001', 'FAR-BLE-25KG',  10000,   'GMD-F25',     3,  '2026-12-31', null],
            ['ACT-010', 'FAR-BLE-25KG',  11000,   null,         10,  null,         null],

            // Maïs 50 kg
            ['ACT-010', 'MAIS-LOC-50KG', 13500,   null,          7,  null,         null],
            ['DHM-011', 'MAIS-LOC-50KG', 14200,   null,          5,  null,         null],

            // Mil 50 kg
            ['ACT-010', 'MIL-50KG',      12500,   null,          7,  null,         null],
            ['DHM-011', 'MIL-50KG',      13500,   null,          5,  null,         null],

            // Riz brisé 50 kg
            ['GMD-001', 'RIZ-BRI-50KG',  23000,   'GMD-RIZ50',   4,  '2026-12-31', null],
            ['ACT-010', 'RIZ-BRI-50KG',  25000,   null,          8,  null,         null],
            ['DHM-011', 'RIZ-BRI-50KG',  24500,   null,          5,  null,         null],

            // ════════════════════════════════════════════════════════════════
            // CONDITIONNEMENTS & SACHETS
            // ════════════════════════════════════════════════════════════════
            ['SOD-002', 'PACK-SAC-100ML', 11500,  'SOD-SAC100',  5,  '2026-12-31', null],
            ['ACT-010', 'PACK-SAC-100ML', 12500,  null,         10,  null,         null],

            ['SOD-002', 'PACK-SAC-200ML', 11000,  'SOD-SAC200',  5,  '2026-12-31', null],
            ['ACT-010', 'PACK-SAC-200ML', 12000,  null,         10,  null,         null],

            // ════════════════════════════════════════════════════════════════
            // POMPES & VANNES
            // ════════════════════════════════════════════════════════════════
            // Pompe hydraulique
            ['PFI-007', 'POMPE-HYD-380V', 270000, 'PFI-PH380',  14,  '2026-12-31', 'Garantie 1 an pièces'],
            ['QTS-006', 'POMPE-HYD-380V', 290000, 'QTS-PH380',  21,  null,         null],
            ['MED-012', 'POMPE-HYD-380V', 265000, null,         30,  null,         'Prix sans garantie'],

            // Pompe centrifuge 1,5 kW
            ['PFI-007', 'POMPE-CEN-15KW', 118000, 'PFI-PC15',   10,  '2026-12-31', null],
            ['QTS-006', 'POMPE-CEN-15KW', 128000, 'QTS-PC15',   14,  null,         null],

            // Pompe carburant manuelle
            ['QTS-006', 'POMPE-CARB-MAN',  43000, 'QTS-PCM',     7,  null,         null],
            ['PFI-007', 'POMPE-CARB-MAN',  46000, 'PFI-PCM',     5,  '2026-12-31', null],

            // Vanne boisseau DN50
            ['PFI-007', 'VAN-BSP-DN50',   33000,  'PFI-VBD50',   7,  '2026-12-31', 'Corps laiton'],
            ['QTS-006', 'VAN-BSP-DN50',   36000,  'QTS-VBD50',   5,  null,         'Corps inox'],

            // Vanne régulation DN100
            ['PFI-007', 'VAN-REG-DN100',  80000,  'PFI-VRD100', 14,  '2026-12-31', null],
            ['QTS-006', 'VAN-REG-DN100',  88000,  'QTS-VRD100', 21,  null,         null],

            // Clapet anti-retour DN40
            ['PFI-007', 'CLAP-AR-DN40',   26000,  'PFI-CAR40',   7,  '2026-12-31', null],
            ['QTS-006', 'CLAP-AR-DN40',   29500,  'QTS-CAR40',   5,  null,         null],

            // ════════════════════════════════════════════════════════════════
            // PIÈCES DÉTACHÉES MÉCANIQUES
            // ════════════════════════════════════════════════════════════════
            ['QTS-006', 'COUR-TRP-A60',    8000,  'QTS-CA60',    5,  null,         null],
            ['PFI-007', 'COUR-TRP-A60',    8800,  'PFI-CA60',    7,  '2026-12-31', null],

            ['QTS-006', 'COUR-TRP-B75',   10500,  'QTS-CB75',    5,  null,         null],
            ['PFI-007', 'COUR-TRP-B75',   11500,  'PFI-CB75',    7,  '2026-12-31', null],

            ['QTS-006', 'ROUL-BB-6205',    6000,  'QTS-RB6205',  5,  null,         null],
            ['PFI-007', 'ROUL-BB-6205',    6800,  'PFI-RB6205',  7,  '2026-12-31', null],

            ['QTS-006', 'ROUL-BB-6305',    7500,  'QTS-RB6305',  5,  null,         null],
            ['PFI-007', 'ROUL-BB-6305',    8200,  'PFI-RB6305',  7,  '2026-12-31', null],

            ['QTS-006', 'JOINT-TOR-50',    9000,  'QTS-JT50',    3,  null,         null],
            ['PFI-007', 'JOINT-TOR-50',    9800,  'PFI-JT50',    5,  '2026-12-31', null],

            // ════════════════════════════════════════════════════════════════
            // PEINTURES & REVÊTEMENTS
            // ════════════════════════════════════════════════════════════════
            ['CRS-008', 'PEIN-ACR-INT-20', 36000, 'CRS-PAI20',   7,  '2026-12-31', 'Teinte blanc cassé standard'],
            ['QTS-006', 'PEIN-ACR-INT-20', 39000, 'QTS-PAI20',   5,  null,         null],
            ['MED-012', 'PEIN-ACR-INT-20', 34500, null,          14,  null,         'Sans garantie qualité'],

            ['CRS-008', 'PEIN-GLY-EXT-20', 43000, 'CRS-PGE20',   7,  '2026-12-31', null],
            ['QTS-006', 'PEIN-GLY-EXT-20', 46500, 'QTS-PGE20',   5,  null,         null],

            ['CRS-008', 'LAQU-BLN-5L',    13500,  'CRS-LB5L',    5,  '2026-12-31', null],
            ['QTS-006', 'LAQU-BLN-5L',    15000,  'QTS-LB5L',    3,  null,         null],

            ['CRS-008', 'SOUC-UNI-5L',    11500,  'CRS-SU5L',    5,  '2026-12-31', null],

            // ════════════════════════════════════════════════════════════════
            // HUILES MOTEUR
            // ════════════════════════════════════════════════════════════════
            // 15W40 bidon 20L
            ['LPD-005', 'HUI-MOT-15W40-20L', 28500, 'LPD-15W40-20', 5,  '2026-12-31', 'Total Quartz ou équivalent'],
            ['QTS-006', 'HUI-MOT-15W40-20L', 31000, 'QTS-15W40',    7,  null,          null],
            ['DHM-011', 'HUI-MOT-15W40-20L', 30000, null,           8,  null,          null],

            // 5W30 bidon 5L
            ['LPD-005', 'HUI-MOT-5W30-5L',   8000, 'LPD-5W30-5',   5,  '2026-12-31', null],
            ['QTS-006', 'HUI-MOT-5W30-5L',   9000, 'QTS-5W30',     7,  null,          null],

            // 20W50 bidon 20L
            ['LPD-005', 'HUI-MOT-20W50-20L', 26500, 'LPD-20W50-20', 5,  '2026-12-31', null],
            ['DHM-011', 'HUI-MOT-20W50-20L', 28500, null,           8,  null,          null],

            // Graisse multi-usage
            ['LPD-005', 'GRAI-MUL-24',       45000, 'LPD-GMU24',   5,  '2026-12-31', 'Marque SKF ou Total'],
            ['QTS-006', 'GRAI-MUL-24',       49000, 'QTS-GMU24',   7,  null,          null],

            // ════════════════════════════════════════════════════════════════
            // HUILES HYDRAULIQUES & INDUSTRIELLES
            // ════════════════════════════════════════════════════════════════
            ['LPD-005', 'HUI-HYD-ISO46-20L', 33000, 'LPD-HYD46-20', 5,  '2026-12-31', null],
            ['PFI-007', 'HUI-HYD-ISO46-20L', 36500, 'PFI-HYD46',    7,  null,          null],

            ['LPD-005', 'HUI-ATF-5L',        12000, 'LPD-ATF5',     5,  '2026-12-31', null],
            ['QTS-006', 'HUI-ATF-5L',        13500, 'QTS-ATF5',     7,  null,          null],

            ['LPD-005', 'HUI-COMP-ISO100-20L',36000, 'LPD-COMP100', 7,  '2026-12-31', null],

            // ════════════════════════════════════════════════════════════════
            // CIMENTS & LIANTS
            // ════════════════════════════════════════════════════════════════
            // CPA 200
            ['SOCO-003', 'CIM-CPA-200-50KG',  6200, 'SCM-CPA200',   3,  '2026-12-31', 'Prix départ usine Rufisque'],
            ['CDS-004',  'CIM-CPA-200-50KG',  6400, 'CDS-CPA200',   5,  '2026-12-31', 'Prix départ Diamniadio'],
            ['MED-012',  'CIM-CPA-200-50KG',  6800, null,          10,  null,          'Revendeur non agréé'],

            // CPJ 250
            ['SOCO-003', 'CIM-CPJ-250-50KG',  6700, 'SCM-CPJ250',   3,  '2026-12-31', null],
            ['CDS-004',  'CIM-CPJ-250-50KG',  6900, 'CDS-CPJ250',   5,  '2026-12-31', null],
            ['MED-012',  'CIM-CPJ-250-50KG',  7300, null,          10,  null,          null],

            // CPA 285
            ['SOCO-003', 'CIM-CPA-285-50KG',  7200, 'SCM-CPA285',   3,  '2026-12-31', 'Ciment haute résistance'],
            ['CDS-004',  'CIM-CPA-285-50KG',  7400, 'CDS-CPA285',   5,  '2026-12-31', null],

            // Chaux hydraulique
            ['CDS-004',  'CHAUX-HYD-25KG',    4500, 'CDS-CHX25',    5,  '2026-12-31', null],
            ['MED-012',  'CHAUX-HYD-25KG',    5200, null,          12,  null,          null],

            // ════════════════════════════════════════════════════════════════
            // BITUME & ÉTANCHÉITÉ
            // ════════════════════════════════════════════════════════════════
            // Bitume 60/70 fût 200L
            ['BRA-009', 'BIT-6070-200L',    94000, 'BRA-B6070-200', 7,  '2026-12-31', 'Prix franco dépôt client'],
            ['MED-012', 'BIT-6070-200L',   102000, null,           15,  null,          null],

            // Émulsion bidon 30L
            ['BRA-009', 'EMUL-BIT-30L',    20500, 'BRA-EMUL30',    5,  '2026-12-31', null],
            ['MED-012', 'EMUL-BIT-30L',    23000, null,           10,  null,          null],

            // Membrane étanchéité 4mm
            ['BRA-009', 'MEMB-ETN-4MM',    33000, 'BRA-MEMB4',     7,  '2026-12-31', null],
            ['CRS-008', 'MEMB-ETN-4MM',    36500, 'CRS-MEMB4',     5,  null,          null],

            // ════════════════════════════════════════════════════════════════
            // BÉTON & COFFRAGES
            // ════════════════════════════════════════════════════════════════
            ['CDS-004',  'POT-BET-50L',    11500, 'CDS-PB50',     5,  '2026-12-31', null],
            ['MED-012',  'POT-BET-50L',    13000, null,          10,  null,          null],

            ['CDS-004',  'PAN-COF-250X50',  9000, 'CDS-PC250',    5,  '2026-12-31', null],
            ['MED-012',  'PAN-COF-250X50', 10500, null,          12,  null,          null],

            ['CDS-004',  'TRE-SOU-TS25',   22500, 'CDS-TS25',     5,  '2026-12-31', null],
            ['SOCO-003', 'TRE-SOU-TS25',   23500, 'SCM-TS25',     7,  null,          null],

            ['CDS-004',  'BET-PAE-30KG',    5200, 'CDS-BPE30',    3,  '2026-12-31', null],
            ['SOCO-003', 'BET-PAE-30KG',    5500, 'SCM-BPE30',    5,  null,          null],
        ];

        // Pré-charger les maps pour éviter N+1
        $fournisseurMap = Fournisseur::all()->keyBy('code');
        $articleMap     = Article::all()->keyBy('reference');

        $created = 0;
        $skipped = 0;

        foreach ($tarifs as [$codeFns, $refArticle, $prix, $refFns, $delai, $validite, $notes]) {
            $fournisseur = $fournisseurMap->get($codeFns);
            $article     = $articleMap->get($refArticle);

            if (! $fournisseur || ! $article) {
                $this->command->warn("⚠ Ignoré : fournisseur={$codeFns} article={$refArticle}");
                $skipped++;
                continue;
            }

            FournisseurArticle::updateOrCreate(
                [
                    'fournisseur_id' => $fournisseur->id,
                    'article_id'     => $article->id,
                ],
                [
                    'unit_price'            => $prix,
                    'reference_fournisseur' => $refFns,
                    'delai_livraison_jours' => $delai,
                    'valide_jusqu_au'       => $validite,
                    'notes'                 => $notes,
                    'is_active'             => true,
                ]
            );
            $created++;
        }

        $this->command->info("✓ {$created} tarifs fournisseurs créés" . ($skipped ? " ({$skipped} ignorés)" : '') . '.');
    }
}
