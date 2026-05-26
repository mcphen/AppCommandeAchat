<?php

namespace App\Notifications;

use App\Models\DisbursementRequest;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Concerns\CcAdmin;
use Illuminate\Notifications\Notification;

class DisbursementRequestRejectedNotification extends Notification
{
    use CcAdmin;

    public function __construct(
        private readonly DisbursementRequest $disbursementRequest,
        private readonly ValidationLevel $level,
        private readonly ?string $comment
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
        $body = "Votre demande de decaissement \"{$this->disbursementRequest->title}\" a ete refusee au niveau {$this->level->name}.";
        if ($this->comment) {
            $body .= " Motif : {$this->comment}";
        }

        return [
            'type'  => 'disbursement_request_rejected',
            'title' => 'Demande de decaissement refusee',
            'body'  => $body,
            'url'   => route('disbursement-requests.show', $this->disbursementRequest),
            'dr_id' => $this->disbursementRequest->id,
            'color' => 'red',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->withAdminCc((new MailMessage)
            ->subject("Votre demande de décaissement a été refusée — {$this->disbursementRequest->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre demande de décaissement a été **refusée** au niveau **{$this->level->name}**.")
            ->line("**Demande :** {$this->disbursementRequest->title}")
            ->line("**Montant :** " . number_format($this->disbursementRequest->amount, 0, ',', ' ') . ' FCFA')
            ->when($this->comment, fn ($m) => $m->line("**Motif :** {$this->comment}"))
            ->action('Voir la demande', route('disbursement-requests.show', $this->disbursementRequest))
            ->line('Vous pouvez modifier votre demande et la re-soumettre.'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.dr_rejected'),
            'variables'    => [
                '1' => $this->disbursementRequest->title,
                '2' => $this->level->name,
                '3' => $this->comment ?: 'Non precise',
                '4' => route('disbursement-requests.show', $this->disbursementRequest),
            ],
            'fallback' => "Demande de decaissement refusee\n"
                . "Demande : {$this->disbursementRequest->title}\n"
                . ($this->comment ? "Motif : {$this->comment}\n" : '')
                . route('disbursement-requests.show', $this->disbursementRequest),
        ];
    }
}
