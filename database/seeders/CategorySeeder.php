<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Arborescence calée sur l'activité réelle de SCN-SUARL
        // (alimentaire · quincaillerie/pompes · lubrifiants · matériaux de construction)
        $tree = [
            [
                'name'         => 'Produits Alimentaires',
                'description'  => 'Huiles, farines, céréales et conditionnements alimentaires',
                'account_code' => '6011',
                'account_label'=> 'Achats de marchandises alimentaires',
                'children'     => [
                    [
                        'name'          => 'Huiles Alimentaires',
                        'description'   => 'Bidons, bouteilles et sachets d\'huile végétale',
                        'account_code'  => '60111',
                        'account_label' => 'Huiles alimentaires',
                    ],
                    [
                        'name'          => 'Farines & Céréales',
                        'description'   => 'Sacs de farine de blé, mil, maïs et autres céréales',
                        'account_code'  => '60112',
                        'account_label' => 'Farines et céréales',
                    ],
                    [
                        'name'          => 'Conditionnements & Sachets',
                        'description'   => 'Sachets alimentaires, packs et emballages en vrac',
                        'account_code'  => '60113',
                        'account_label' => 'Conditionnements alimentaires',
                    ],
                ],
            ],
            [
                'name'         => 'Quincaillerie & Équipements Industriels',
                'description'  => 'Pompes, vannes, pièces mécaniques et peintures',
                'account_code' => '6012',
                'account_label'=> 'Achats quincaillerie et équipements',
                'children'     => [
                    [
                        'name'          => 'Pompes & Vannes',
                        'description'   => 'Pompes hydrauliques, centrifuges, vannes industrielles',
                        'account_code'  => '60121',
                        'account_label' => 'Pompes et vannes',
                    ],
                    [
                        'name'          => 'Pièces Détachées Mécaniques',
                        'description'   => 'Courroies, roulements, joints et consommables mécaniques',
                        'account_code'  => '60122',
                        'account_label' => 'Pièces détachées mécaniques',
                    ],
                    [
                        'name'          => 'Peintures & Revêtements',
                        'description'   => 'Peintures acryliques, glycérophtaliques, enduits et lasures',
                        'account_code'  => '60123',
                        'account_label' => 'Peintures et revêtements',
                    ],
                ],
            ],
            [
                'name'         => 'Lubrifiants & Huiles Techniques',
                'description'  => 'Huiles moteur, huiles hydrauliques et graisses industrielles',
                'account_code' => '6013',
                'account_label'=> 'Achats de lubrifiants',
                'children'     => [
                    [
                        'name'          => 'Huiles Moteur',
                        'description'   => 'Huiles moteur toutes viscosités pour véhicules et groupes',
                        'account_code'  => '60131',
                        'account_label' => 'Huiles moteur',
                    ],
                    [
                        'name'          => 'Huiles Hydrauliques & Industrielles',
                        'description'   => 'Huiles hydrauliques, boîtes de vitesses, graisses multi-usages',
                        'account_code'  => '60132',
                        'account_label' => 'Huiles hydrauliques et industrielles',
                    ],
                ],
            ],
            [
                'name'         => 'Matériaux de Construction',
                'description'  => 'Ciments, bitumes, béton et coffrages',
                'account_code' => '6014',
                'account_label'=> 'Achats matériaux de construction',
                'children'     => [
                    [
                        'name'          => 'Ciments & Liants',
                        'description'   => 'Ciment Portland CPA/CPJ, chaux hydraulique, mortiers',
                        'account_code'  => '60141',
                        'account_label' => 'Ciments et liants',
                    ],
                    [
                        'name'          => 'Bitume & Étanchéité',
                        'description'   => 'Bitume routier, émulsions bitumineuses, produits d\'étanchéité',
                        'account_code'  => '60142',
                        'account_label' => 'Bitume et étanchéité',
                    ],
                    [
                        'name'          => 'Béton & Coffrages',
                        'description'   => 'Pots à béton, panneaux coffreurs, treillis soudés, coffrages métalliques',
                        'account_code'  => '60143',
                        'account_label' => 'Béton et coffrages',
                    ],
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
