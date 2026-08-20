<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class OrderSubmittedNotification extends Notification
{

    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $level
    ) {}

    /**
     * Notifie tous les validateurs du niveau : une notification "cloche" (database)
     * pour chacun, et un seul e-mail groupe adresse a tout le service (tous les
     * validateurs en "A"), plutot qu'un e-mail individuel par validateur.
     */
    public static function sendToLevel(PurchaseOrder $order, ValidationLevel $level): void
    {
        $validators = $level->validators;

        foreach ($validators as $validator) {
            $validator->notify(new self($order, $level));
        }

        $emails = $validators->pluck('email')->filter()->unique()->values();

        if ($emails->isNotEmpty()) {
            NotificationFacade::route('mail', $emails->all())->notify(new self($order, $level));
        }
    }

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database'];
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
            ->greeting('Bonjour,')
            ->line("Une nouvelle commande d'achat requiert la validation de votre service : {$this->level->name}.")
            ->line("Commande : {$this->order->title}")
            ->line('Montant : ' . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("Fournisseur : {$this->order->fournisseur?->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.')
            ->bcc('enockmambou@gmail.com');
    }
}
