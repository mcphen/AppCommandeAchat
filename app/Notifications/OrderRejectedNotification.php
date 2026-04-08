<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $level,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre commande a été refusée — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre commande d'achat a été **refusée** au niveau **{$this->level->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 2, ',', ' ') . ' FCFA')
            ->line("**Motif du refus :**")
            ->line($this->reason)
            ->action('Modifier et re-soumettre', route('purchase-orders.edit', $this->order))
            ->line('Vous pouvez modifier votre commande en tenant compte du motif et la re-soumettre.');
    }
}
