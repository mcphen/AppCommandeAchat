<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApprovedAtLevelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $approvedLevel,
        private readonly ValidationLevel $nextLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande en attente de votre validation — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La commande suivante a été approuvée au niveau **{$this->approvedLevel->name}** et requiert maintenant votre validation au niveau **{$this->nextLevel->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 2, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }
}
