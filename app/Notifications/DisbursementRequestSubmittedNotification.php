<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Notification;

class DisbursementRequestSubmittedNotification extends Notification
{
    public function __construct(
        private readonly DisbursementRequest $disbursementRequest,
        private readonly ValidationLevel $level
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'whatsapp'];
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
