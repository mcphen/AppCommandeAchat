<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApprovedAtLevelNotification extends Notification
{

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $approvedLevel,
        private readonly ValidationLevel $nextLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'order_approved_at_level',
            'title'       => 'Commande en attente de votre validation',
            'body'        => "La commande \"{$this->order->title}\" a été {$this->approvedLevel->actionPastParticiple()} au niveau {$this->approvedLevel->name} et requiert votre {$this->nextLevel->actionNoun()} au niveau {$this->nextLevel->name}.",
            'url'         => route('validations.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'amber',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande en attente de votre validation — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La commande suivante a été {$this->approvedLevel->actionPastParticiple()} au niveau **{$this->approvedLevel->name}** et requiert maintenant votre {$this->nextLevel->actionNoun()} au niveau **{$this->nextLevel->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.')
            ->bcc('enockmambou@gmail.com');
    }
}
