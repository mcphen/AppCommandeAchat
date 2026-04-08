<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $level
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle commande à valider — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle commande d'achat requiert votre validation au niveau **{$this->level->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 2, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }
}
