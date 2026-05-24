<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

class OrderApprovedAtLevelNotification extends Notification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;


    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly ValidationLevel $approvedLevel,
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
            'type'        => 'order_approved_at_level',
            'title'       => 'Commande en attente de votre validation',
            'body'        => "La commande \"{$this->order->title}\" a été validée au niveau {$this->approvedLevel->name} et requiert votre approbation au niveau {$this->nextLevel->name}.",
            'url'         => route('validations.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'amber',
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.order_approved_at_level'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => $this->order->user->name,
                '3' => number_format($this->order->amount, 0, ',', ' ') . ' FCFA',
                '4' => $this->approvedLevel->name,
                '5' => $this->nextLevel->name,
                '6' => route('validations.show', $this->order),
            ],
            'fallback' => "✅ *Commande en attente de votre validation*\n"
                . "Commande : {$this->order->title}\n"
                . "Montant : " . number_format($this->order->amount, 0, ',', ' ') . " FCFA\n"
                . "Demandeur : {$this->order->user->name}\n"
                . "Niveau requis : {$this->nextLevel->name}\n"
                . route('validations.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commande en attente de votre validation — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La commande suivante a été approuvée au niveau **{$this->approvedLevel->name}** et requiert maintenant votre validation au niveau **{$this->nextLevel->name}**.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->line("**Demandeur :** {$this->order->user->name}")
            ->action('Voir et valider', route('validations.show', $this->order))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }
}
