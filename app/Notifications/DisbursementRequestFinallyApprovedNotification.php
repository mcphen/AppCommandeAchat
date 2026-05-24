<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

class DisbursementRequestFinallyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public function __construct(private readonly DisbursementRequest $disbursementRequest) {}

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
            'type'  => 'disbursement_request_finally_approved',
            'title' => 'Demande de decaissement entierement approuvee',
            'body'  => "Votre demande \"{\$this->disbursementRequest->title}\" a ete approuvee par tous les niveaux de validation. Le decaissement a ete enregistre automatiquement.",
            'url'   => route('disbursement-requests.show', $this->disbursementRequest),
            'dr_id' => $this->disbursementRequest->id,
            'color' => 'emerald',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre demande de décaissement a été approuvée — {$this->disbursementRequest->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! Votre demande de décaissement a été **entièrement approuvée** par tous les niveaux de validation.")
            ->line("**Demande :** {$this->disbursementRequest->title}")
            ->line("**Montant :** " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA')
            ->action('Voir la demande', route('disbursement-requests.show', $this->disbursementRequest))
            ->line('Le décaissement a été enregistré automatiquement.');
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
