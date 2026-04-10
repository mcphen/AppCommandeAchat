<?php

namespace App\Notifications;

use App\Models\ValidationDelegation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DelegationReceivedNotification extends Notification
{
    public function __construct(private readonly ValidationDelegation $delegation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'delegation_received',
            'title'       => 'Délégation de validation reçue',
            'body'        => "{$this->delegation->delegator->name} vous délègue ses droits de validation \"{$this->delegation->validationLevel->name}\" du {$this->delegation->starts_at->format('d/m/Y')} au {$this->delegation->ends_at->format('d/m/Y')}.",
            'url'         => route('delegations.index'),
            'color'       => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Délégation de validation — {$this->delegation->validationLevel->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->delegation->delegator->name} vous a délégué ses droits de validation pour le niveau **{$this->delegation->validationLevel->name}**.")
            ->line("**Période :** du {$this->delegation->starts_at->format('d/m/Y')} au {$this->delegation->ends_at->format('d/m/Y')}")
            ->when($this->delegation->reason, fn ($m) => $m->line("**Motif :** {$this->delegation->reason}"))
            ->action('Voir mes délégations', route('delegations.index'))
            ->line('Durant cette période, vous pourrez valider les commandes à ce niveau en son nom.');
    }
}
