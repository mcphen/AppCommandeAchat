<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderSubmittedNotification extends Notification
{

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $level
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'order_submitted',
            'title'       => 'Nouvelle commande à valider',
            'body'        => "La commande \"{$this->order->title}\" (fournisseur : {$this->order->fournisseur?->name}) nécessite votre validation au niveau {$this->level->name}.",
            'url'         => route('validations.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'blue',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle commande à valider — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle commande d'achat requiert votre validation au niveau **{$this->level->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Fournisseur :** {$this->order->fournisseur?->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.')
            ->bcc('enockmambou@gmail.com');
    }
}
