<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Map: category name → articles
        $articlesByCategory = [
            'Ordinateurs & Accessoires' => [
                ['name' => 'PC Portable Dell Latitude 5540',   'reference' => 'DELL-LAT5540',  'unit' => 'pièce',  'unit_price' => 950000],
                ['name' => 'PC Portable HP ProBook 450',       'reference' => 'HP-PB450',       'unit' => 'pièce',  'unit_price' => 780000],
                ['name' => 'Écran LCD 24" Dell',               'reference' => 'DELL-MON24',     'unit' => 'pièce',  'unit_price' => 185000],
                ['name' => 'Clavier + Souris sans fil',        'reference' => 'KBM-WL-SET',     'unit' => 'lot',    'unit_price' => 35000],
                ['name' => 'Webcam HD 1080p',                  'reference' => 'CAM-HD1080',     'unit' => 'pièce',  'unit_price' => 45000],
            ],
            'Imprimantes & Consommables' => [
                ['name' => 'Imprimante Laser HP LaserJet M404', 'reference' => 'HP-LJ-M404',   'unit' => 'pièce',  'unit_price' => 295000],
                ['name' => 'Toner HP LaserJet CF259A',          'reference' => 'HP-TONER-59A', 'unit' => 'pièce',  'unit_price' => 38000],
                ['name' => 'Ramette papier A4 80g (500 feuilles)', 'reference' => 'PPR-A4-80G','unit' => 'carton', 'unit_price' => 8500],
                ['name' => 'Cartouche encre Canon PG-540',      'reference' => 'CAN-PG540',    'unit' => 'pièce',  'unit_price' => 12000],
                ['name' => 'Photocopieur Ricoh MP 2014',        'reference' => 'RICOH-MP2014', 'unit' => 'pièce',  'unit_price' => 1250000],
            ],
            'Réseaux & Téléphonie' => [
                ['name' => 'Switch HP Aruba 24 ports',         'reference' => 'HP-SW-24P',     'unit' => 'pièce',  'unit_price' => 380000],
                ['name' => 'Routeur Cisco RV340',               'reference' => 'CISCO-RV340',  'unit' => 'pièce',  'unit_price' => 290000],
                ['name' => 'Téléphone IP Cisco CP-7821',        'reference' => 'CISCO-7821',   'unit' => 'pièce',  'unit_price' => 145000],
                ['name' => 'Point d\'accès WiFi Ubiquiti UAP', 'reference' => 'UBNT-UAP-AC',  'unit' => 'pièce',  'unit_price' => 95000],
            ],
            'Stockage & Serveurs' => [
                ['name' => 'Disque dur externe 2 To USB 3.0',  'reference' => 'HDD-EXT-2TB',  'unit' => 'pièce',  'unit_price' => 78000],
                ['name' => 'Clé USB 64 Go',                    'reference' => 'USB-64G',       'unit' => 'pièce',  'unit_price' => 8000],
                ['name' => 'Serveur Dell PowerEdge T150',      'reference' => 'DELL-PE-T150',  'unit' => 'pièce',  'unit_price' => 2800000],
                ['name' => 'NAS Synology DS923+',               'reference' => 'SYN-DS923',    'unit' => 'pièce',  'unit_price' => 1450000],
            ],
            'Logiciels & Licences' => [
                ['name' => 'Microsoft 365 Business Basic (1 an)', 'reference' => 'MS365-BB-1Y', 'unit' => 'pièce',  'unit_price' => 55000],
                ['name' => 'Antivirus Kaspersky Endpoint 1 an',   'reference' => 'KAS-END-1Y',  'unit' => 'pièce',  'unit_price' => 42000],
                ['name' => 'Abonnement Sage Comptabilité 1 an',   'reference' => 'SAGE-COMPTA', 'unit' => 'forfait','unit_price' => 480000],
                ['name' => 'Adobe Acrobat Pro 1 an',              'reference' => 'ADOBE-AC-1Y', 'unit' => 'pièce',  'unit_price' => 95000],
            ],
            'Bureaux & Tables' => [
                ['name' => 'Bureau direction 160x80 cm',        'reference' => 'BUR-DIR-160',  'unit' => 'pièce',  'unit_price' => 280000],
                ['name' => 'Table de réunion 8 personnes',      'reference' => 'TBL-REU-8P',   'unit' => 'pièce',  'unit_price' => 550000],
                ['name' => 'Bureau open space 120x60 cm',       'reference' => 'BUR-OPS-120',  'unit' => 'pièce',  'unit_price' => 145000],
            ],
            'Chaises & Sièges' => [
                ['name' => 'Fauteuil direction ergonomique',    'reference' => 'FAU-DIR-ERG',  'unit' => 'pièce',  'unit_price' => 185000],
                ['name' => 'Chaise de bureau opérateur',        'reference' => 'CHS-OPE-STD',  'unit' => 'pièce',  'unit_price' => 75000],
                ['name' => 'Chaise visiteur empilable',         'reference' => 'CHS-VIS-EMP',  'unit' => 'pièce',  'unit_price' => 35000],
            ],
            'Rangement & Classement' => [
                ['name' => 'Armoire métallique 2 portes',       'reference' => 'ARM-MET-2P',   'unit' => 'pièce',  'unit_price' => 195000],
                ['name' => 'Caisson à roulettes 3 tiroirs',     'reference' => 'CAI-ROU-3T',   'unit' => 'pièce',  'unit_price' => 95000],
                ['name' => 'Étagère bibliothèque bois',         'reference' => 'ETG-BIB-BOI',  'unit' => 'pièce',  'unit_price' => 85000],
            ],
            'Papeterie & Reliure' => [
                ['name' => 'Classeur à levier A4 (boîte 10)',   'reference' => 'CLS-A4-LV10',  'unit' => 'boîte',  'unit_price' => 18500],
                ['name' => 'Stylos bille Bic (lot 50)',         'reference' => 'STYLO-BIC-50', 'unit' => 'lot',    'unit_price' => 7500],
                ['name' => 'Cahier grand format 200 pages',     'reference' => 'CAH-GF-200',   'unit' => 'pièce',  'unit_price' => 2500],
                ['name' => 'Post-it repositionnables (lot 6)',  'reference' => 'PSTIT-LOT6',   'unit' => 'lot',    'unit_price' => 6500],
            ],
            'Produits d\'hygiène' => [
                ['name' => 'Savon liquide mains 5L',            'reference' => 'SAV-LIQ-5L',   'unit' => 'litre',  'unit_price' => 4500],
                ['name' => 'Papier hygiénique (carton 96 rouleaux)', 'reference' => 'PPH-CRT96', 'unit' => 'carton', 'unit_price' => 32000],
                ['name' => 'Gel hydroalcoolique 1L',            'reference' => 'GEL-HYD-1L',   'unit' => 'litre',  'unit_price' => 5500],
                ['name' => 'Sacs poubelle 100L (carton 100)',   'reference' => 'SAC-PBL-100',  'unit' => 'carton', 'unit_price' => 18000],
            ],
            'Climatiseurs & Ventilateurs' => [
                ['name' => 'Climatiseur split 12000 BTU LG',    'reference' => 'LG-SPLIT-12K', 'unit' => 'pièce',  'unit_price' => 380000],
                ['name' => 'Climatiseur split 18000 BTU LG',    'reference' => 'LG-SPLIT-18K', 'unit' => 'pièce',  'unit_price' => 520000],
                ['name' => 'Ventilateur industriel sur pied',   'reference' => 'VENT-IND-STD', 'unit' => 'pièce',  'unit_price' => 45000],
            ],
            'Groupe électrogène & UPS' => [
                ['name' => 'Onduleur APC 1500VA',               'reference' => 'APC-UPS-1500', 'unit' => 'pièce',  'unit_price' => 195000],
                ['name' => 'Onduleur APC 3000VA',               'reference' => 'APC-UPS-3000', 'unit' => 'pièce',  'unit_price' => 380000],
                ['name' => 'Groupe électrogène 10 kVA diesel',  'reference' => 'GRP-ELEC-10K', 'unit' => 'pièce',  'unit_price' => 2500000],
            ],
            'Incendie & Évacuation' => [
                ['name' => 'Extincteur CO2 5 kg',               'reference' => 'EXT-CO2-5K',   'unit' => 'pièce',  'unit_price' => 45000],
                ['name' => 'Détecteur de fumée optique',        'reference' => 'DET-FUM-OPT',  'unit' => 'pièce',  'unit_price' => 18000],
                ['name' => 'Panneau sortie de secours LED',     'reference' => 'PAN-SEC-LED',  'unit' => 'pièce',  'unit_price' => 25000],
            ],
            'Vidéoprojecteurs & Écrans' => [
                ['name' => 'Vidéoprojecteur Epson EB-E20',      'reference' => 'EPS-EB-E20',   'unit' => 'pièce',  'unit_price' => 450000],
                ['name' => 'Téléviseur 55" Samsung Smart TV',   'reference' => 'SAM-TV-55',    'unit' => 'pièce',  'unit_price' => 680000],
                ['name' => 'Écran de projection 200x150 cm',    'reference' => 'ECR-PROJ-200', 'unit' => 'pièce',  'unit_price' => 85000],
            ],
            'Uniformes & EPI' => [
                ['name' => 'Polo personnalisé entreprise',      'reference' => 'POLO-ENT-STD', 'unit' => 'pièce',  'unit_price' => 12000],
                ['name' => 'Chaussures de sécurité S3',         'reference' => 'CHS-SEC-S3',   'unit' => 'pièce',  'unit_price' => 38000],
                ['name' => 'Gilet de protection haute visibilité', 'reference' => 'GIL-HV-STD','unit' => 'pièce',  'unit_price' => 8500],
            ],
        ];

        $created = 0;
        foreach ($articlesByCategory as $categoryName => $articles) {
            $category = Category::where('name', $categoryName)->first();
            if (!$category) continue;

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
