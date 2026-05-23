<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DecaissementPretNotification extends Notification
{
    public function __construct(private readonly PurchaseOrder $order) {}

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
            'type'        => 'decaissement_pret',
            'title'       => 'Décaissement à effectuer',
            'body'        => "La commande \"{$this->order->title}\" est approuvée. Vous pouvez procéder au décaissement de " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA.',
            'url'         => route('decaissements.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'green',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Décaissement à effectuer — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La commande **{$this->order->title}** a été approuvée par tous les niveaux de validation.")
            ->line("**Montant à décaisser :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Procéder au décaissement', route('decaissements.show', $this->order))
            ->line('Merci de traiter ce décaissement dans les meilleurs délais.');
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.decaissement_pret'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => number_format($this->order->amount, 0, ',', ' ') . ' FCFA',
                '3' => $this->order->user->name,
                '4' => route('decaissements.show', $this->order),
            ],
            'fallback' => "💰 *Décaissement à effectuer*\n"
                . "Commande : {$this->order->title}\n"
                . "Montant : " . number_format($this->order->amount, 0, ',', ' ') . " FCFA\n"
                . "Demandeur : {$this->order->user->name}\n"
                . route('decaissements.show', $this->order),
        ];
    }
}
