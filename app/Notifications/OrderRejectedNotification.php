<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRejectedNotification extends Notification
{

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $level,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'order_rejected',
            'title'       => 'Commande refusée',
            'body'        => "La commande \"{$this->order->title}\" a été refusée au niveau {$this->level->name}." . ($this->reason ? " Motif : {$this->reason}" : ''),
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'red',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande refusée — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La commande d'achat a été **refusée** au niveau **{$this->level->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Motif du refus :**")
            ->line($this->reason)
            ->action('Voir la commande', route('purchase-orders.show', $this->order))
            ->line('La correction doit être apportée dans Sage100 ; la commande corrigée sera resynchronisée automatiquement.');
    }
}
