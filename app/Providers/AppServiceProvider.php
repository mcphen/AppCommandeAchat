<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Appliquer la configuration SMTP stockée en base de données
        $this->applyMailSettings();
    }

    private function applyMailSettings(): void
    {
        // Sécurité : la table doit exister (migrations pas encore jouées en fresh install)
        try {
            if (! Schema::hasTable('app_settings')) {
                return;
            }

            $settings = \App\Models\AppSetting::allAsArray();

            if (empty($settings)) {
                return;
            }

            $mailer = $settings['mail_mailer'] ?? null;

            if ($mailer) {
                config(['mail.default' => $mailer]);
            }

            if (isset($settings['mail_host'])) {
                config(['mail.mailers.smtp.host' => $settings['mail_host']]);
            }
            if (isset($settings['mail_port'])) {
                config(['mail.mailers.smtp.port' => (int) $settings['mail_port']]);
            }
            if (isset($settings['mail_encryption'])) {
                config(['mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?: null]);
            }
            if (isset($settings['mail_username'])) {
                config(['mail.mailers.smtp.username' => $settings['mail_username']]);
            }
            if (isset($settings['mail_password'])) {
                config(['mail.mailers.smtp.password' => $settings['mail_password']]);
            }
            if (isset($settings['mail_from_address'])) {
                config(['mail.from.address' => $settings['mail_from_address']]);
            }
            if (isset($settings['mail_from_name'])) {
                config(['mail.from.name' => $settings['mail_from_name']]);
            }
        } catch (\Exception) {
            // Ne pas bloquer l'app si la DB est inaccessible
        }
    }
}
