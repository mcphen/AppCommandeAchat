<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use Illuminate\Notifications\Notification;

class DisbursementRequestFinallyApprovedNotification extends Notification
{
    public function __construct(private readonly DisbursementRequest $disbursementRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'whatsapp'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'  => 'disbursement_request_finally_approved',
            'title' => 'Demande de decaissement entierement approuvee',
            'body'  => "Votre demande \"{\$this->disbursementRequest->title}\" a ete approuvee par tous les niveaux de validation. Le decaissement a ete enregistre automatiquement.",
            'url'   => route('disbursement-requests.show', $this->disbursementRequest),
            'dr_id' => $this->disbursementRequest->id,
            'color' => 'emerald',
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.dr_finally_approved'),
            'variables'    => [
                '1' => $this->disbursementRequest->title,
                '2' => number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA',
                '3' => route('disbursement-requests.show', $this->disbursementRequest),
            ],
            'fallback' => "Demande de decaissement approuvee\n"
                . "Demande : {\$this->disbursementRequest->title}\n"
                . "Montant : " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . " FCFA\n"
                . "Votre demande a ete approuvee par tous les niveaux.\n"
                . route('disbursement-requests.show', $this->disbursementRequest),
        ];
    }
}
