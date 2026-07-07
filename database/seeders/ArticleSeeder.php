<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Catalogue de démo générique (quincaillerie/BTP) — à adapter au catalogue réel Construcsen
        $articlesByCategory = [

            // ── Huiles Alimentaires ───────────────────────────────────────────
            'Huiles Alimentaires' => [
                ['name' => 'Bidon d\'huile végétale 50 L',           'reference' => 'HUI-ALI-50L',   'unit' => 'bidon',    'unit_price' => 45000],
                ['name' => 'Bouteille d\'huile végétale 10 L',       'reference' => 'HUI-ALI-10L',   'unit' => 'bouteille','unit_price' => 9500],
                ['name' => 'Bouteille d\'huile végétale 5 L',        'reference' => 'HUI-ALI-05L',   'unit' => 'bouteille','unit_price' => 5500],
                ['name' => 'Huile végétale – lot 260 sachets',       'reference' => 'HUI-SAC-260',   'unit' => 'lot',      'unit_price' => 28000],
                ['name' => 'Carton d\'huile 1 L (12 bouteilles)',    'reference' => 'HUI-ALI-1L-12', 'unit' => 'carton',   'unit_price' => 18000],
            ],

            // ── Farines & Céréales ────────────────────────────────────────────
            'Farines & Céréales' => [
                ['name' => 'Sac de farine de blé 50 kg',             'reference' => 'FAR-BLE-50KG',  'unit' => 'sac',      'unit_price' => 19500],
                ['name' => 'Sac de farine de blé 25 kg',             'reference' => 'FAR-BLE-25KG',  'unit' => 'sac',      'unit_price' => 10500],
                ['name' => 'Sac de maïs local 50 kg',                'reference' => 'MAIS-LOC-50KG', 'unit' => 'sac',      'unit_price' => 14000],
                ['name' => 'Sac de mil 50 kg',                       'reference' => 'MIL-50KG',      'unit' => 'sac',      'unit_price' => 13000],
                ['name' => 'Sac de riz brisé importé 50 kg',         'reference' => 'RIZ-BRI-50KG',  'unit' => 'sac',      'unit_price' => 24000],
            ],

            // ── Conditionnements & Sachets ────────────────────────────────────
            'Conditionnements & Sachets' => [
                ['name' => 'Pack sachets alimentaires 100 ml (carton 10 000)', 'reference' => 'PACK-SAC-100ML', 'unit' => 'carton', 'unit_price' => 12000],
                ['name' => 'Pack sachets alimentaires 200 ml (carton 5 000)',  'reference' => 'PACK-SAC-200ML', 'unit' => 'carton', 'unit_price' => 11500],
                ['name' => 'Rouleaux film étirable alimentaire 500 m',         'reference' => 'FILM-ETI-500',   'unit' => 'rouleau','unit_price' => 8500],
                ['name' => 'Sacs polypropylène tissé 50 kg (lot 500)',          'reference' => 'SAC-PP-50KG500', 'unit' => 'lot',    'unit_price' => 32000],
            ],

            // ── Pompes & Vannes ───────────────────────────────────────────────
            'Pompes & Vannes' => [
                ['name' => 'Pompe hydraulique industrielle 380V',     'reference' => 'POMPE-HYD-380V','unit' => 'pièce',    'unit_price' => 285000],
                ['name' => 'Pompe centrifuge eau 1,5 kW',             'reference' => 'POMPE-CEN-15KW','unit' => 'pièce',    'unit_price' => 125000],
                ['name' => 'Pompe à carburant manuelle',              'reference' => 'POMPE-CARB-MAN','unit' => 'pièce',    'unit_price' => 45000],
                ['name' => 'Vanne à boisseau sphérique DN50',         'reference' => 'VAN-BSP-DN50',  'unit' => 'pièce',    'unit_price' => 35000],
                ['name' => 'Vanne de régulation industrielle DN100',  'reference' => 'VAN-REG-DN100', 'unit' => 'pièce',    'unit_price' => 85000],
                ['name' => 'Clapet anti-retour inox DN40',            'reference' => 'CLAP-AR-DN40',  'unit' => 'pièce',    'unit_price' => 28000],
                ['name' => 'Tuyau flexible hydraulique HT 2 m',      'reference' => 'TUY-HYD-HT2M',  'unit' => 'pièce',    'unit_price' => 18500],
            ],

            // ── Pièces Détachées Mécaniques ───────────────────────────────────
            'Pièces Détachées Mécaniques' => [
                ['name' => 'Courroie trapézoïdale A-60',              'reference' => 'COUR-TRP-A60',  'unit' => 'pièce',    'unit_price' => 8500],
                ['name' => 'Courroie trapézoïdale B-75',              'reference' => 'COUR-TRP-B75',  'unit' => 'pièce',    'unit_price' => 11000],
                ['name' => 'Roulement à billes 6205',                 'reference' => 'ROUL-BB-6205',  'unit' => 'pièce',    'unit_price' => 6500],
                ['name' => 'Roulement à billes 6305',                 'reference' => 'ROUL-BB-6305',  'unit' => 'pièce',    'unit_price' => 8000],
                ['name' => 'Kit joints toriques assortis (lot 50)',   'reference' => 'JOINT-TOR-50',  'unit' => 'lot',      'unit_price' => 9500],
                ['name' => 'Vis & boulons inox M12 (boîte 50)',       'reference' => 'VIS-INX-M12-50','unit' => 'boîte',    'unit_price' => 7000],
            ],

            // ── Peintures & Revêtements ───────────────────────────────────────
            'Peintures & Revêtements' => [
                ['name' => 'Peinture acrylique intérieure 20 L',      'reference' => 'PEIN-ACR-INT-20','unit' => 'bidon',   'unit_price' => 38000],
                ['name' => 'Peinture glycérophtalique extérieure 20 L','reference' => 'PEIN-GLY-EXT-20','unit' => 'bidon',  'unit_price' => 45000],
                ['name' => 'Laque brillante blanche 5 L',             'reference' => 'LAQU-BLN-5L',   'unit' => 'bidon',   'unit_price' => 14500],
                ['name' => 'Sous-couche universelle 5 L',             'reference' => 'SOUC-UNI-5L',   'unit' => 'bidon',   'unit_price' => 12000],
                ['name' => 'Enduit de rebouchage 25 kg',              'reference' => 'ENDU-REB-25KG', 'unit' => 'sac',     'unit_price' => 9500],
            ],

            // ── Huiles Moteur ─────────────────────────────────────────────────
            'Huiles Moteur' => [
                ['name' => 'Huile moteur 15W40 – bidon 20 L',         'reference' => 'HUI-MOT-15W40-20L', 'unit' => 'bidon', 'unit_price' => 30000],
                ['name' => 'Huile moteur 5W30 – bidon 5 L',           'reference' => 'HUI-MOT-5W30-5L',   'unit' => 'bidon', 'unit_price' => 8500],
                ['name' => 'Huile moteur 20W50 – bidon 20 L',         'reference' => 'HUI-MOT-20W50-20L', 'unit' => 'bidon', 'unit_price' => 28000],
                ['name' => 'Graisse multi-usage – carton 24 x 400 g', 'reference' => 'GRAI-MUL-24',       'unit' => 'carton','unit_price' => 48000],
                ['name' => 'Huile de transmission SAE 80W90 – 5 L',   'reference' => 'HUI-TRM-80W90-5L',  'unit' => 'bidon', 'unit_price' => 9500],
            ],

            // ── Huiles Hydrauliques & Industrielles ───────────────────────────
            'Huiles Hydrauliques & Industrielles' => [
                ['name' => 'Huile hydraulique ISO 46 – bidon 20 L',   'reference' => 'HUI-HYD-ISO46-20L', 'unit' => 'bidon', 'unit_price' => 35000],
                ['name' => 'Huile boîte de vitesses ATF – 5 L',       'reference' => 'HUI-ATF-5L',         'unit' => 'bidon', 'unit_price' => 12500],
                ['name' => 'Huile compresseur ISO 100 – 20 L',        'reference' => 'HUI-COMP-ISO100-20L','unit' => 'bidon', 'unit_price' => 38000],
                ['name' => 'Vaseline technique – pot 1 kg',           'reference' => 'VASEL-TECH-1KG',     'unit' => 'pot',   'unit_price' => 5500],
            ],

            // ── Ciments & Liants ──────────────────────────────────────────────
            'Ciments & Liants' => [
                ['name' => 'Ciment Portland CPA 200 – sac 50 kg',     'reference' => 'CIM-CPA-200-50KG',  'unit' => 'sac',   'unit_price' => 6500],
                ['name' => 'Ciment Portland CPJ 250 – sac 50 kg',     'reference' => 'CIM-CPJ-250-50KG',  'unit' => 'sac',   'unit_price' => 7000],
                ['name' => 'Ciment Portland CPA 285 – sac 50 kg',     'reference' => 'CIM-CPA-285-50KG',  'unit' => 'sac',   'unit_price' => 7500],
                ['name' => 'Chaux hydraulique – sac 25 kg',           'reference' => 'CHAUX-HYD-25KG',    'unit' => 'sac',   'unit_price' => 4800],
                ['name' => 'Mortier colle C2 – sac 25 kg',            'reference' => 'MORT-C2-25KG',      'unit' => 'sac',   'unit_price' => 8000],
            ],

            // ── Bitume & Étanchéité ───────────────────────────────────────────
            'Bitume & Étanchéité' => [
                ['name' => 'Bitume routier 60/70 – fût 200 L',        'reference' => 'BIT-6070-200L',     'unit' => 'fût',   'unit_price' => 98000],
                ['name' => 'Émulsion bitumineuse – bidon 30 L',       'reference' => 'EMUL-BIT-30L',      'unit' => 'bidon', 'unit_price' => 22000],
                ['name' => 'Membrane d\'étanchéité bitumée 4 mm',     'reference' => 'MEMB-ETN-4MM',      'unit' => 'rouleau','unit_price' => 35000],
                ['name' => 'Bitume émulsifié cut-back – bidon 20 L',  'reference' => 'BIT-CUT-20L',       'unit' => 'bidon', 'unit_price' => 18500],
            ],

            // ── Béton & Coffrages ─────────────────────────────────────────────
            'Béton & Coffrages' => [
                ['name' => 'Pot à béton 50 L',                        'reference' => 'POT-BET-50L',       'unit' => 'pièce', 'unit_price' => 12000],
                ['name' => 'Panneau coffreur bois 250 x 50 cm',       'reference' => 'PAN-COF-250X50',    'unit' => 'pièce', 'unit_price' => 9500],
                ['name' => 'Treillis soudé 200 x 300 cm (ST 25)',     'reference' => 'TRE-SOU-TS25',      'unit' => 'panneau','unit_price' => 24000],
                ['name' => 'Béton prêt à l\'emploi – sac 30 kg',      'reference' => 'BET-PAE-30KG',      'unit' => 'sac',   'unit_price' => 5500],
                ['name' => 'Étais métalliques réglables 1,5–3 m',     'reference' => 'ETAI-MET-15-3',     'unit' => 'pièce', 'unit_price' => 18000],
            ],
        ];

        $created = 0;
        foreach ($articlesByCategory as $categoryName => $articles) {
            $category = Category::where('name', $categoryName)->first();
            if (! $category) {
                $this->command->warn("⚠ Catégorie introuvable : {$categoryName}");
                continue;
            }

            foreach ($articles as $data) {
                Article::firstOrCreate(
                    ['reference' => $data['reference']],
                    array_merge($data, [
                        'category_id' => $category->id,
                        'is_active'   => true,
                    ])
                );
                $created++;
            }
        }

        $this->command->info("✓ {$created} articles créés.");
    }
}
