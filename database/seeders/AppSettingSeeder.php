<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Identité de l'entreprise ──────────────────────────────────
            'company_name'    => 'Société Commerciale Ndienne (SCN-SUARL)',
            'company_address' => 'Parc Mbaye Touré, Dalifort – BP 18070, Dakar, Sénégal',
            'company_phone'   => '+221 77 450 89 50',
            'company_email'   => 'admin@scndienne.sn',
            'company_website' => 'https://scndienne.sn',
            'company_nif'     => 'SN-DKR-2002-B-12345',
            'company_rccm'    => 'RCCM SN-DKR-2002-B-12345',

            // ── Configuration e-mail (mode log par défaut – à ajuster en prod) ─
            'mail_mailer'       => 'log',
            'mail_host'         => 'smtp.scndienne.sn',
            'mail_port'         => 587,
            'mail_encryption'   => 'tls',
            'mail_username'     => 'no-reply@scndienne.sn',
            'mail_from_address' => 'no-reply@scndienne.sn',
            'mail_from_name'    => 'SCN – Gestion des Achats',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('✓ ' . count($settings) . ' paramètres d\'application configurés (SCN-SUARL).');
    }
}
