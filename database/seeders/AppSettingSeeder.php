<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Identité de l'entreprise (adresse/téléphone/NIF/RCCM à compléter via l'admin) ──
            'company_name'    => 'CONSTRUCSEN',
            'company_address' => '',
            'company_phone'   => '',
            'company_email'   => 'admin@construcsen.com',
            'company_website' => 'https://www.construcsen.com',
            'company_nif'     => '',
            'company_rccm'    => '',

            // ── Configuration e-mail (mode log par défaut – à ajuster en prod) ─
            'mail_mailer'       => 'log',
            'mail_host'         => 'smtp.construcsen.com',
            'mail_port'         => 587,
            'mail_encryption'   => 'tls',
            'mail_username'     => 'no-reply@construcsen.com',
            'mail_from_address' => 'no-reply@construcsen.com',
            'mail_from_name'    => 'Construcsen – Gestion des Achats',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('✓ ' . count($settings) . ' paramètres d\'application configurés (Construcsen).');
    }
}
