<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderFinallyApprovedNotification extends Notification
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
            'type'        => 'order_finally_approved',
            'title'       => 'Commande entièrement approuvée',
            'body'        => "Votre commande \"{$this->order->title}\" a été approuvée par tous les niveaux de validation.",
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'emerald',
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.order_finally_approved'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => number_format($this->order->amount, 0, ',', ' ') . ' FCFA',
                '3' => route('purchase-orders.show', $this->order),
            ],
            'fallback' => "🎉 *Commande entièrement approuvée*\n"
                . "Commande : {$this->order->title}\n"
                . "Montant : " . number_format($this->order->amount, 0, ',', ' ') . " FCFA\n"
                . "Votre commande a été approuvée par tous les niveaux.\n"
                . route('purchase-orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre commande a été approuvée — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! Votre commande d'achat a été **entièrement approuvée** par tous les niveaux de validation.")
            ->line("**Commande :** {$this->order->title}")
            ->line("**Montant :** " . number_format($this->order->amount, 0, ',', ' ') . ' FCFA')
            ->action('Voir la commande', route('purchase-orders.show', $this->order))
            ->line('Vous pouvez maintenant procéder à l\'achat.');
    }
}
