<?php

namespace App\Notifications;

use App\Models\Decaissement;
use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DecaissementEnregistreNotification extends Notification
{
    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly Decaissement  $decaissement
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'whatsapp'];
    }

    public function toDatabase(object $notifiable): array
    {
        $reste = $this->order->resteADecaisser();

        return [
            'type'        => 'decaissement_enregistre',
            'title'       => 'Décaissement enregistré',
            'body'        => "Un décaissement de " . number_format($this->decaissement->montant, 0, ',', ' ') . " FCFA a été effectué sur la commande \"{$this->order->title}\"."
                . ($reste > 0 ? " Reste à décaisser : " . number_format($reste, 0, ',', ' ') . " FCFA." : " Commande entièrement décaissée."),
            'url'         => route('decaissements.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'emerald',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reste  = $this->order->resteADecaisser();
        $statut = $reste > 0
            ? "Reste à décaisser : **" . number_format($reste, 0, ',', ' ') . " FCFA**"
            : "La commande est **entièrement décaissée**.";

        return (new MailMessage)
            ->subject("Décaissement enregistré — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un décaissement a été enregistré sur la commande **{$this->order->title}**.")
            ->line("**Montant décaissé :** " . number_format($this->decaissement->montant, 0, ',', ' ') . ' FCFA')
            ->line("**Par :** {$this->decaissement->recorder->name}")
            ->line("**Date :** {$this->decaissement->decaissement_date}")
            ->line($statut)
            ->action('Voir la commande', route('decaissements.show', $this->order));
    }

    public function toWhatsApp(object $notifiable): array
    {
        $reste  = $this->order->resteADecaisser();
        $statut = $reste > 0
            ? 'Reste : ' . number_format($reste, 0, ',', ' ') . ' FCFA'
            : 'Commande entièrement décaissée.';

        return [
            'template_sid' => config('services.twilio.templates.decaissement_enregistre'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => number_format($this->decaissement->montant, 0, ',', ' ') . ' FCFA',
                '3' => $this->decaissement->recorder->name,
                '4' => $statut,
                '5' => route('decaissements.show', $this->order),
            ],
            'fallback' => "✅ *Décaissement enregistré*\n"
                . "Commande : {$this->order->title}\n"
                . "Montant : " . number_format($this->decaissement->montant, 0, ',', ' ') . " FCFA\n"
                . "Par : {$this->decaissement->recorder->name}\n"
                . "{$statut}\n"
                . route('decaissements.show', $this->order),
        ];
    }
}
