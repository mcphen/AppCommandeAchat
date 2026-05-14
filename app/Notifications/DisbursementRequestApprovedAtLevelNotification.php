<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Notification;

class DisbursementRequestApprovedAtLevelNotification extends Notification
{
    public function __construct(
        private readonly DisbursementRequest $disbursementRequest,
        private readonly ValidationLevel $currentLevel,
        private readonly ValidationLevel $nextLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'whatsapp'];
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
