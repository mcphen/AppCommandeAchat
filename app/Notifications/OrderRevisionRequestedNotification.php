<?php

namespace App\Notifications;

use App\Models\OrderComment;
use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRevisionRequestedNotification extends Notification
{
    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly OrderComment  $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'whatsapp'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'revision_requested',
            'title'       => 'Révision demandée sur votre commande',
            'body'        => "{$this->comment->user->name} demande une révision sur « {$this->order->title} » : {$this->comment->content}",
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'indigo',
        ];
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template_sid' => config('services.twilio.templates.revision_requested'),
            'variables'    => [
                '1' => $this->order->title,
                '2' => $this->comment->user->name,
                '3' => $this->comment->content,
                '4' => route('purchase-orders.show', $this->order),
            ],
            'fallback' => "🔄 *Révision demandée*\n"
                . "Commande : {$this->order->title}\n"
                . "De : {$this->comment->user->name}\n"
                . "Message : {$this->comment->content}\n"
                . route('purchase-orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Révision demandée — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->comment->user->name} vous demande de réviser la commande **{$this->order->title}** avant de prendre une décision de validation.")
            ->line("**Message :** {$this->comment->content}")
            ->action('Voir la commande', route('purchase-orders.show', $this->order))
            ->line('Vous pouvez modifier votre commande et la re-soumettre depuis cette page.');
    }
}
