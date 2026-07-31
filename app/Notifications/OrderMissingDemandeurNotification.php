<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderMissingDemandeurNotification extends Notification
{
    public function __construct(private readonly PurchaseOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'order_missing_demandeur',
            'title'       => 'Commande Sage sans demandeur identifié',
            'body'        => "La commande \"{$this->order->title}\" (fournisseur : {$this->order->fournisseur?->name}) a été importée depuis Sage100 sans collaborateur reconnu : personne ne peut la compléter et la soumettre.",
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'red',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande Sage sans demandeur — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une commande d'achat a été importée depuis Sage100 sans collaborateur reconnu (code Sage absent ou non mappé à un utilisateur de l'application).")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Fournisseur :** {$this->order->fournisseur?->name}")
            ->line('**Montant :** ' . number_format((float) $this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("Aucun demandeur ne recevra de rappel pour cette commande tant qu'elle reste dans cet état : merci de lui rattacher le bon demandeur, ou de joindre les pièces et la soumettre vous-même.")
            ->action('Voir la commande', route('purchase-orders.show', $this->order))
            ->line("Elle ne pourra ni être validée ni être commandée tant qu'elle n'est pas soumise.");
    }
}
