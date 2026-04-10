<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $fournisseurs = [
            // Homologués
            [
                'name'        => 'Informatique Services Dakar',
                'code'        => 'ISD-001',
                'email'       => 'contact@isd.sn',
                'phone'       => '+221 33 821 00 10',
                'address'     => '12, Avenue Léopold Sédar Senghor',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Bureau Plus Sénégal',
                'code'        => 'BPS-002',
                'email'       => 'ventes@bureauplus.sn',
                'phone'       => '+221 33 864 25 30',
                'address'     => 'Rue 10, Zone Industrielle',
                'city'        => 'Dakar - Médina',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Tech Solutions West Africa',
                'code'        => 'TSWA-003',
                'email'       => 'sales@techsolutionswa.com',
                'phone'       => '+221 77 430 55 20',
                'address'     => 'VDN, Sacré-Cœur 3',
                'city'        => 'Dakar - Almadies',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Mobilier & Design Sénégal',
                'code'        => 'MDS-004',
                'email'       => 'info@mobilierdesign.sn',
                'phone'       => '+221 33 832 47 00',
                'address'     => 'Avenue Bourguiba, Imm. Ndiambour',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'SénéFroid Climatisation',
                'code'        => 'SFC-005',
                'email'       => 'contact@senefroid.sn',
                'phone'       => '+221 76 543 21 09',
                'address'     => 'Route de Rufisque, Km 12',
                'city'        => 'Dakar - Pikine',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Sécurité Pro Dakar',
                'code'        => 'SPD-006',
                'email'       => 'devis@securitepro.sn',
                'phone'       => '+221 33 825 88 15',
                'address'     => 'Liberté 6, Rue 15',
                'city'        => 'Dakar - Grand Dakar',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Papeterie Centrale Dakar',
                'code'        => 'PCD-007',
                'email'       => 'commandes@papeteriecentrale.sn',
                'phone'       => '+221 33 822 10 40',
                'address'     => 'Marché Sandaga, Bloc B',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Auto Dakar Service',
                'code'        => 'ADS-008',
                'email'       => 'contact@autodakar.sn',
                'phone'       => '+221 33 836 70 22',
                'address'     => 'Zone de Recasement, Rue des Mécaniciens',
                'city'        => 'Dakar - Guédiawaye',
                'is_active'   => true,
                'is_approved' => true,
            ],

            // Non homologués
            [
                'name'        => 'InfoTech Express',
                'code'        => 'ITE-009',
                'email'       => 'infotech.express@gmail.com',
                'phone'       => '+221 78 900 12 34',
                'address'     => 'Marché HLM, Allée 4',
                'city'        => 'Dakar - Médina',
                'is_active'   => true,
                'is_approved' => false,
            ],
            [
                'name'        => 'Fournitures Rapides SN',
                'code'        => 'FRS-010',
                'email'       => null,
                'phone'       => '+221 70 234 56 78',
                'address'     => 'Cité Fadia',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => false,
            ],
            [
                'name'        => 'Dakar Digital Hub',
                'code'        => 'DDH-011',
                'email'       => 'hello@dakardh.com',
                'phone'       => '+221 77 111 22 33',
                'address'     => 'Point E, Villa 47',
                'city'        => 'Dakar - Almadies',
                'is_active'   => true,
                'is_approved' => false,
            ],
            [
                'name'        => 'Éclat Mobilier',
                'code'        => 'ECM-012',
                'email'       => 'eclatmobilier@outlook.com',
                'phone'       => '+221 76 678 90 11',
                'address'     => 'Parcelles Assainies, U17',
                'city'        => 'Dakar - Grand Dakar',
                'is_active'   => false,
                'is_approved' => false,
            ],
        ];

        foreach ($fournisseurs as $data) {
            Fournisseur::firstOrCreate(['code' => $data['code']], $data);
        }

        $this->command->info('✓ ' . Fournisseur::count() . ' fournisseurs créés.');
    }
}
