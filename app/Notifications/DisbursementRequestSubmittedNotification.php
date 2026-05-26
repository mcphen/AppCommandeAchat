<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Concerns\CcAdmin;
use Illuminate\Notifications\Notification;

class DisbursementRequestSubmittedNotification extends Notification
{
    use CcAdmin;

    public function __construct(
        private readonly DisbursementRequest $disbursementRequest,
        private readonly ValidationLevel $level
    ) {}

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
            'type'  => 'disbursement_request_submitted',
            'title' => 'Nouvelle demande de décaissement à valider',
            'body'  => "La demande \"{$this->disbursementRequest->title}\" de {$this->disbursementRequest->user->name} nécessite votre validation au niveau {$this->level->name}.",
            'url'   => route('disbursement-validations.show', $this->disbursementRequest),
            'dr_id' => $this->disbursementRequest->id,
            'color' => 'blue',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->withAdminCc((new MailMessage)
            ->subject("Nouvelle demande de décaissement à valider — {$this->disbursementRequest->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle demande de décaissement requiert votre validation au niveau **{$this->level->name}**.")
            ->line("**Demande :** {$this->disbursementRequest->title}")
            ->line("**Montant :** " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->disbursementRequest->user->name}")
            ->action('Voir et valider', route('disbursement-validations.show', $this->disbursementRequest))
            ->line('Merci de traiter cette demande dans les meilleurs délais.'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.dr_submitted'),
            'variables'    => [
                '1' => $this->disbursementRequest->title . ' | ' . $this->disbursementRequest->natureOperation->name,
                '2' => $this->disbursementRequest->user->name,
                '3' => number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA',
                '4' => $this->level->name,
                '5' => route('disbursement-validations.show', $this->disbursementRequest),
            ],
            'fallback' => "📋 *Nouvelle demande de décaissement à valider*\n"
                . "Demande : {$this->disbursementRequest->title}\n"
                . "Montant : " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . " FCFA\n"
                . "Demandeur : {$this->disbursementRequest->user->name}\n"
                . "Niveau : {$this->level->name}\n"
                . route('disbursement-validations.show', $this->disbursementRequest),
        ];
    }
}
