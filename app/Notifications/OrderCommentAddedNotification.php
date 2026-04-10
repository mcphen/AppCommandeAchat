<?php

namespace App\Notifications;

use App\Models\OrderComment;
use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCommentAddedNotification extends Notification
{
    public function __construct(
        private readonly PurchaseOrder $order,
        private readonly OrderComment  $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'comment_added',
            'title'       => 'Nouveau message sur une commande',
            'body'        => "{$this->comment->user->name} a commenté « {$this->order->title} » : {$this->comment->content}",
            'url'         => route('purchase-orders.show', $this->order),
            'order_id'    => $this->order->id,
            'order_title' => $this->order->title,
            'color'       => 'blue',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouveau message — {$this->order->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->comment->user->name} a laissé un message sur la commande **{$this->order->title}**.")
            ->line("**Message :** {$this->comment->content}")
            ->action('Voir la discussion', route('purchase-orders.show', $this->order));
    }
}
