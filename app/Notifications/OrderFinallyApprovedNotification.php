<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderFinallyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PurchaseOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre commande a été approuvée — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! Votre commande d'achat a été **entièrement approuvée** par tous les niveaux de validation.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 2, ',', ' ') . ' FCFA')
            ->action('Voir la commande', route('purchase-orders.show', $this->order))
            ->line('Vous pouvez maintenant procéder à l\'achat.');
    }
}
