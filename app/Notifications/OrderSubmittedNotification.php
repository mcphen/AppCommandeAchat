<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

class OrderSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;


    public function __construct(
        private readonly PurchaseOrder $order,
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
            'type'        => 'order_submitted',
            'title'       => 'Nouvelle commande à valider',
            'body'        => "La commande \"{$this->order->title}\" de {$this->order->user->name} nécessite votre validation au niveau {$this->level->name}.",
            'url'         => route('validations.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'blue',
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.order_submitted'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => $this->order->user->name,
                '3' => number_format($this->order->amount, 0, ',', ' ') . ' FCFA',
                '4' => $this->level->name,
                '5' => route('validations.show', $this->order),
            ],
            'fallback' => "📋 *Nouvelle commande à valider*\n"
                . "Commande : {$this->order->title}\n"
                . "Montant : " . number_format($this->order->amount, 0, ',', ' ') . " FCFA\n"
                . "Demandeur : {$this->order->user->name}\n"
                . "Niveau : {$this->level->name}\n"
                . route('validations.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle commande à valider — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle commande d'achat requiert votre validation au niveau **{$this->level->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }
}
