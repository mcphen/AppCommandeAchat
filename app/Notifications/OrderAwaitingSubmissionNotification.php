<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAwaitingSubmissionNotification extends Notification
{
    public function __construct(private readonly PurchaseOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'order_awaiting_submission',
            'title'       => 'Commande à compléter et soumettre',
            'body'        => "La commande \"{$this->order->title}\" (fournisseur : {$this->order->fournisseur?->name}) importée depuis Sage100 attend vos pièces jointes et votre soumission.",
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'amber',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande à compléter — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une commande d'achat a été importée depuis Sage100 et vous est rattachée en tant que demandeur.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Fournisseur :** {$this->order->fournisseur?->name}")
            ->line('**Montant :** ' . number_format((float) $this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line('Merci de vous connecter, de joindre la ou les pièce(s) justificative(s) obligatoire(s), puis de soumettre la commande pour validation.')
            ->action('Compléter et soumettre', route('purchase-orders.show', $this->order))
            ->line("Tant que la commande n'est pas soumise, elle ne peut ni être validée ni être commandée.");
    }
}
