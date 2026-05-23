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
        $channels = ['database', 'mail'];
        if ($notifiable->whatsapp_notifications) {
            $channels[] = 'whatsapp';
        }
        return $channels;
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

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.delegation_received'),
            'variables'    => [
                '1' => $this->delegation->delegator->name,
                '2' => $this->delegation->validationLevel->name,
                '3' => $this->delegation->starts_at->format('d/m/Y'),
                '4' => $this->delegation->ends_at->format('d/m/Y'),
                '5' => route('delegations.index'),
            ],
            'fallback' => "🤝 *Délégation de validation reçue*\n"
                . "De : {$this->delegation->delegator->name}\n"
                . "Niveau : {$this->delegation->validationLevel->name}\n"
                . "Du {$this->delegation->starts_at->format('d/m/Y')} au {$this->delegation->ends_at->format('d/m/Y')}\n"
                . route('delegations.index'),
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
