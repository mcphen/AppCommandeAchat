<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountCreatedNotification extends Notification
{
    public function __construct(private readonly string $plainPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre compte a été créé')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Un compte vient de vous être créé sur ' . config('app.name') . '.')
            ->line("**Email :** {$notifiable->email}")
            ->line("**Mot de passe :** {$this->plainPassword}")
            ->action('Se connecter', route('login'))
            ->line('Nous vous recommandons de changer ce mot de passe dès votre première connexion.');
    }
}
