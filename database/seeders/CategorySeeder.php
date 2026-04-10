<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'name'        => 'Informatique & Télécom',
                'description' => 'Matériel informatique, réseaux et télécommunications',
                'children'    => [
                    ['name' => 'Ordinateurs & Accessoires', 'description' => 'PC portables, fixes et périphériques'],
                    ['name' => 'Imprimantes & Consommables', 'description' => 'Imprimantes, toners, cartouches, papier'],
                    ['name' => 'Réseaux & Téléphonie', 'description' => 'Switches, routeurs, téléphones IP'],
                    ['name' => 'Stockage & Serveurs', 'description' => 'Disques durs, NAS, serveurs'],
                    ['name' => 'Logiciels & Licences', 'description' => 'Licences logiciels, abonnements SaaS'],
                ],
            ],
            [
                'name'        => 'Mobilier & Aménagement',
                'description' => 'Mobilier de bureau et aménagement des espaces',
                'children'    => [
                    ['name' => 'Bureaux & Tables', 'description' => 'Bureaux, tables de réunion, comptoirs'],
                    ['name' => 'Chaises & Sièges', 'description' => 'Chaises de bureau, fauteuils, sièges visiteurs'],
                    ['name' => 'Rangement & Classement', 'description' => 'Armoires, étagères, caissons'],
                    ['name' => 'Décoration & Signalétique', 'description' => 'Tableaux, plantes, signalétique intérieure'],
                ],
            ],
            [
                'name'        => 'Fournitures de Bureau',
                'description' => 'Papeterie, consommables et petits équipements de bureau',
                'children'    => [
                    ['name' => 'Papeterie & Reliure', 'description' => 'Stylos, classeurs, cahiers, chemises'],
                    ['name' => 'Tampons & Cachets', 'description' => 'Tampons encreurs, dateurs, cachets officiels'],
                    ['name' => 'Produits d\'hygiène', 'description' => 'Savon, désinfectant, papier hygiénique, sacs poubelle'],
                ],
            ],
            [
                'name'        => 'Électroménager & Climatisation',
                'description' => 'Équipements électriques et climatisation',
                'children'    => [
                    ['name' => 'Climatiseurs & Ventilateurs', 'description' => 'Climatiseurs split, ventilateurs industriels'],
                    ['name' => 'Groupe électrogène & UPS', 'description' => 'Groupes électrogènes, onduleurs, stabilisateurs'],
                    ['name' => 'Cuisine & Pause', 'description' => 'Réfrigérateurs, micro-ondes, machines à café'],
                ],
            ],
            [
                'name'        => 'Sécurité & Surveillance',
                'description' => 'Équipements de sécurité physique et incendie',
                'children'    => [
                    ['name' => 'Incendie & Évacuation', 'description' => 'Extincteurs, détecteurs de fumée, plans d\'évacuation'],
                    ['name' => 'Vidéosurveillance', 'description' => 'Caméras IP, enregistreurs DVR/NVR, écrans de contrôle'],
                    ['name' => 'Contrôle d\'accès', 'description' => 'Badges, lecteurs biométriques, serrures électroniques'],
                ],
            ],
            [
                'name'        => 'Audiovisuel & Communication',
                'description' => 'Équipements de présentation et de communication',
                'children'    => [
                    ['name' => 'Vidéoprojecteurs & Écrans', 'description' => 'Vidéoprojecteurs, écrans de projection, TV'],
                    ['name' => 'Sonorisation', 'description' => 'Micros, amplificateurs, haut-parleurs, enceintes'],
                ],
            ],
            [
                'name'        => 'Transport & Véhicules',
                'description' => 'Véhicules de service et frais de déplacement',
                'children'    => [
                    ['name' => 'Véhicules de service', 'description' => 'Achat et location de véhicules utilitaires'],
                    ['name' => 'Carburant & Entretien', 'description' => 'Carburant, révision, pneumatiques'],
                ],
            ],
            [
                'name'        => 'Formation & Ressources Humaines',
                'description' => 'Formations du personnel et ressources humaines',
                'children'    => [
                    ['name' => 'Formations techniques', 'description' => 'Formations IT, logiciels, certifications'],
                    ['name' => 'Formations managériales', 'description' => 'Leadership, gestion de projet, communication'],
                    ['name' => 'Uniformes & EPI', 'description' => 'Vêtements de travail, équipements de protection individuelle'],
                ],
            ],
        ];

        foreach ($tree as $rootData) {
            $children = $rootData['children'] ?? [];
            unset($rootData['children']);

            $root = Category::create(array_merge($rootData, ['is_active' => true]));

            foreach ($children as $childData) {
                Category::create(array_merge($childData, [
                    'parent_id' => $root->id,
                    'is_active' => true,
                ]));
            }
        }

        $this->command->info('✓ Catégories créées (' . Category::count() . ' au total).');
    }
}
