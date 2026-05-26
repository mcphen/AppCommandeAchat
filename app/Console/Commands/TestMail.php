<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Models\User;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {email? : Adresse de destination (défaut : MAIL_ADMIN_CC)}';
    protected $description = 'Vérifie la configuration SMTP et envoie un mail de test';

    public function handle(): int
    {
        $to = $this->argument('email') ?? config('mail.admin_cc');

        if (! $to) {
            $this->error('Aucune adresse fournie et MAIL_ADMIN_CC non défini.');
            return self::FAILURE;
        }

        $this->info('Configuration mail active :');
        $this->table(['Clé', 'Valeur'], [
            ['driver',    config('mail.default')],
            ['host',      config('mail.mailers.smtp.host')],
            ['port',      config('mail.mailers.smtp.port')],
            ['username',  config('mail.mailers.smtp.username')],
            ['from',      config('mail.from.address')],
            ['admin_cc',  config('mail.admin_cc') ?? '—'],
        ]);

        // ── 1. Mail brut ────────────────────────────────────────────────────
        $this->newLine();
        $this->line("Envoi d'un mail brut à <comment>{$to}</comment>...");

        try {
            Mail::raw('Ceci est un test SMTP depuis la commande artisan mail:test. Si vous recevez ce message, la configuration fonctionne.', function ($msg) use ($to) {
                $msg->to($to)->subject('[AchatPro] Test SMTP — ' . now()->format('d/m/Y H:i'));
            });
            $this->info('✓ Mail brut envoyé.');
        } catch (\Exception $e) {
            $this->error('✗ Échec mail brut : ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── 2. Notification réelle (simulée sur un vrai utilisateur) ────────
        $this->newLine();
        $this->line('Simulation d\'une notification de commande...');

        $user  = User::where('email', $to)->first() ?? User::first();
        $level = ValidationLevel::first();
        $order = PurchaseOrder::first();

        if (! $user || ! $level || ! $order) {
            $this->warn('⚠ Données insuffisantes pour simuler la notification (user/level/order manquant).');
            $this->info('Le mail brut a bien été envoyé — SMTP OK.');
            return self::SUCCESS;
        }

        // On force temporairement l'email du notifiable vers l'adresse de test
        $originalEmail   = $user->email;
        $user->email     = $to;

        try {
            $user->notify(new OrderSubmittedNotification($order, $level));
            $this->info("✓ Notification envoyée (simulée sur \"{$user->name}\").");
        } catch (\Exception $e) {
            $this->error('✗ Échec notification : ' . $e->getMessage());
        } finally {
            $user->email = $originalEmail;
        }

        $this->newLine();
        $this->info("Vérifiez la boîte <comment>{$to}</comment>. Vous devriez avoir reçu 2 e-mails.");

        return self::SUCCESS;
    }
}
