<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $fournisseurs = [
            // ── Homologués ────────────────────────────────────────────────────
            [
                'name'        => 'Grands Moulins de Dakar',
                'code'        => 'GMD-001',
                'email'       => 'commercial@gmd.sn',
                'phone'       => '+221 33 839 70 00',
                'address'     => 'Boulevard du Centenaire de la Commune de Dakar',
                'city'        => 'Dakar - Port',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'SeneOil Distribution',
                'code'        => 'SOD-002',
                'email'       => 'ventes@seneoil.sn',
                'phone'       => '+221 33 822 14 50',
                'address'     => 'Zone Industrielle de Mbao',
                'city'        => 'Dakar - Mbao',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'SOCOCIM Industries',
                'code'        => 'SOCO-003',
                'email'       => 'commandes@sococim.sn',
                'phone'       => '+221 33 834 00 00',
                'address'     => 'Route de Rufisque, Km 25',
                'city'        => 'Rufisque',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Ciments du Sahel',
                'code'        => 'CDS-004',
                'email'       => 'direction@cimentsahel.sn',
                'phone'       => '+221 33 867 32 10',
                'address'     => 'Zone Franche Industrielle, Diamniadio',
                'city'        => 'Diamniadio',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Lubrifiant Pro Dakar',
                'code'        => 'LPD-005',
                'email'       => 'commercial@lubripro.sn',
                'phone'       => '+221 77 620 44 30',
                'address'     => 'Rue de Thiès, Zone Industrielle Bel-Air',
                'city'        => 'Dakar - Bel-Air',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'QuincaTech Sénégal',
                'code'        => 'QTS-006',
                'email'       => 'devis@quincatech.sn',
                'phone'       => '+221 33 825 98 70',
                'address'     => 'Allées du Boulevard de la République',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Pompes & Fluides Industrie',
                'code'        => 'PFI-007',
                'email'       => 'pfi@pompesfluides.sn',
                'phone'       => '+221 33 832 61 45',
                'address'     => 'Avenue du Président Lamine Guèye',
                'city'        => 'Dakar - Plateau',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Couleurs & Revêtements Sénégal',
                'code'        => 'CRS-008',
                'email'       => 'info@couleurs-sn.com',
                'phone'       => '+221 76 850 22 11',
                'address'     => 'Route de l\'Aéroport, Yoff',
                'city'        => 'Dakar - Yoff',
                'is_active'   => true,
                'is_approved' => true,
            ],
            [
                'name'        => 'Bitume & Routes Afrique',
                'code'        => 'BRA-009',
                'email'       => 'contact@bitumeroutes.sn',
                'phone'       => '+221 33 869 11 20',
                'address'     => 'Port Autonome de Dakar, Quai 7',
                'city'        => 'Dakar - Port',
                'is_active'   => true,
                'is_approved' => true,
            ],

            // ── Non homologués ────────────────────────────────────────────────
            [
                'name'        => 'Aliou Commerce Touba',
                'code'        => 'ACT-010',
                'email'       => null,
                'phone'       => '+221 77 345 67 89',
                'address'     => 'Marché Ocas, Touba',
                'city'        => 'Touba',
                'is_active'   => true,
                'is_approved' => false,
            ],
            [
                'name'        => 'Dépôt El Hadji Mbaye',
                'code'        => 'DHM-011',
                'email'       => 'elhajmbaye@gmail.com',
                'phone'       => '+221 78 456 78 90',
                'address'     => 'Cité Fadia, Médina',
                'city'        => 'Dakar - Médina',
                'is_active'   => true,
                'is_approved' => false,
            ],
            [
                'name'        => 'Materio Express Dakar',
                'code'        => 'MED-012',
                'email'       => 'materio.express@outlook.com',
                'phone'       => '+221 70 123 45 67',
                'address'     => 'Parcelles Assainies, U22',
                'city'        => 'Dakar - Parcelles Assainies',
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
