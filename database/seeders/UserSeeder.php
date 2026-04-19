<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use App\Models\NiveauValidation;
use App\Models\Role;
use App\Models\User;
use App\Models\Validateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $employe    = Role::where('slug', 'employe')->first();
        $validateur = Role::where('slug', 'validateur')->first();

        $fimoluse = Entreprise::where('code', 'FIM')->first();
        $atra     = Entreprise::where('code', 'ATR')->first();
        $elycargo = Entreprise::where('code', 'ELY')->first();
        $senegui  = Entreprise::where('code', 'SNG')->first();

        // ── Employés par société ──────────────────────────────────────────────
        $employes = [
            // Fimoluse
            ['name' => 'Mamadou Diallo',   'email' => 'mdiallo@fimoluse.sn',   'entreprise_id' => $fimoluse->id, 'fonction' => 'Responsable administratif'],
            ['name' => 'Aïssatou Ba',       'email' => 'aba@fimoluse.sn',       'entreprise_id' => $fimoluse->id, 'fonction' => 'Assistante de direction'],
            // ATRA
            ['name' => 'Ibrahim Sow',       'email' => 'isow@atra.sn',          'entreprise_id' => $atra->id,     'fonction' => 'Chef de projet'],
            ['name' => 'Fatou Ndiaye',      'email' => 'fndiaye@atra.sn',       'entreprise_id' => $atra->id,     'fonction' => 'Responsable logistique'],
            // Elycargo
            ['name' => 'Ousmane Diop',      'email' => 'odiop@elycargo.sn',     'entreprise_id' => $elycargo->id, 'fonction' => 'Directeur exploitation'],
            ['name' => 'Mariama Kouyaté',   'email' => 'mkouyate@elycargo.sn',  'entreprise_id' => $elycargo->id, 'fonction' => 'Responsable achats'],
            // Senegui
            ['name' => 'Abdoulaye Fall',    'email' => 'afall@senegui.sn',      'entreprise_id' => $senegui->id,  'fonction' => 'Directeur général adjoint'],
            ['name' => 'Rokhaya Mbaye',     'email' => 'rmbaye@senegui.sn',     'entreprise_id' => $senegui->id,  'fonction' => 'Responsable financier'],
        ];

        foreach ($employes as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => Hash::make('password'), 'role_id' => $employe->id])
            );
        }

        // ── Validateurs groupe (sans entreprise) ─────────────────────────────
        $niveauCompta = NiveauValidation::where('slug', 'compta')->first();
        $niveauDf     = NiveauValidation::where('slug', 'df')->first();
        $niveauDg     = NiveauValidation::where('slug', 'dg')->first();
        $niveauPdg    = NiveauValidation::where('slug', 'pdg')->first();

        $validateurs = [
            ['name' => 'Amadou Touré',   'email' => 'compta@fortunecapital.sn',  'fonction' => 'Responsable comptable',     'niveau' => $niveauCompta],
            ['name' => 'Moussa Camara',  'email' => 'df@fortunecapital.sn',      'fonction' => 'Directeur Financier',       'niveau' => $niveauDf],
            ['name' => 'Cheikh Diagne',  'email' => 'dg@fortunecapital.sn',      'fonction' => 'Directeur Général',         'niveau' => $niveauDg],
            ['name' => 'El Hadj Mbaye',  'email' => 'pdg@fortunecapital.sn',     'fonction' => 'Président Directeur Général', 'niveau' => $niveauPdg],
        ];

        foreach ($validateurs as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'fonction' => $data['fonction'],
                    'password' => Hash::make('password'),
                    'role_id'  => $validateur->id,
                ]
            );

            Validateur::firstOrCreate(
                ['user_id' => $user->id],
                ['niveau_validation_id' => $data['niveau']->id, 'is_active' => true]
            );
        }
    }
}
