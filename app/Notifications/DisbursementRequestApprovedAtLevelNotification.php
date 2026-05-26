<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Concerns\CcAdmin;
use Illuminate\Notifications\Notification;

class DisbursementRequestApprovedAtLevelNotification extends Notification
{
    use CcAdmin;

    public function __construct(
        private readonly DisbursementRequest $disbursementRequest,
        private readonly ValidationLevel $currentLevel,
        private readonly ValidationLevel $nextLevel
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
            'type'  => 'disbursement_request_approved_at_level',
            'title' => 'Demande de décaissement approuvée — niveau suivant',
            'body'  => "La demande \"{$this->disbursementRequest->title}\" a été approuvée au niveau {$this->currentLevel->name}. Elle est maintenant à votre niveau : {$this->nextLevel->name}.",
            'url'   => route('disbursement-validations.show', $this->disbursementRequest),
            'dr_id' => $this->disbursementRequest->id,
            'color' => 'blue',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->withAdminCc((new MailMessage)
            ->subject("Demande de décaissement en attente de votre validation — {$this->disbursementRequest->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La demande suivante a été approuvée au niveau **{$this->currentLevel->name}** et requiert maintenant votre validation au niveau **{$this->nextLevel->name}**.")
            ->line("**Demande :** {$this->disbursementRequest->title}")
            ->line("**Montant :** " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->disbursementRequest->user->name}")
            ->action('Voir et valider', route('disbursement-validations.show', $this->disbursementRequest))
            ->line('Merci de traiter cette demande dans les meilleurs délais.'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.dr_approved_at_level'),
            'variables'    => [
                '1' => $this->disbursementRequest->title,
                '2' => $this->disbursementRequest->user->name,
                '3' => number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA',
                '4' => $this->currentLevel->name,
                '5' => $this->nextLevel->name,
                '6' => route('disbursement-validations.show', $this->disbursementRequest),
            ],
            'fallback' => "✅ *Demande de décaissement — niveau suivant*\n"
                . "Demande : {$this->disbursementRequest->title}\n"
                . "Montant : " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . " FCFA\n"
                . "Demandeur : {$this->disbursementRequest->user->name}\n"
                . "Approuvé au niveau : {$this->currentLevel->name}\n"
                . "Niveau requis : {$this->nextLevel->name}\n"
                . route('disbursement-validations.show', $this->disbursementRequest),
        ];
    }
}
