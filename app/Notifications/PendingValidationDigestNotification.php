<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingValidationDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $levelName,
        private readonly array $orders,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->orders);
        $mail = (new MailMessage)
            ->subject("Rappel : {$count} commande(s) en attente — {$this->levelName}")
            ->greeting('Bonjour,')
            ->line("{$count} commande(s) sont en attente de votre direction depuis au moins 3 jours ouvrés.")
            ->line('Voici le récapitulatif à traiter :');

        foreach ($this->orders as $order) {
            $amount = number_format($order['amount'], 0, ',', ' ');
            $mail->line("• {$order['title']} — {$amount} FCFA — {$order['supplier']} — {$order['days']} jour(s) ouvré(s)");
        }

        return $mail
            ->action('Voir les validations en attente', route('validations.index'))
            ->line('Ce message est un digest unique envoyé à l’ensemble de votre direction.');
    }
}
