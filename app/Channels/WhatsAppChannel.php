<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class WhatsAppChannel
{
    public function __construct(private Client $twilio) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $devRedirect = config('services.twilio.dev_redirect');
        if ($devRedirect && app()->environment('local')) {
            // En local, envoyer à tous les numéros de dev (liste séparée par virgules)
            $phones = array_map('trim', explode(',', $devRedirect));
        } else {
            $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

            if (! $phone) {
                return;
            }

            $phones = [$phone];
        }

        $payload = $notification->toWhatsApp($notifiable);

        $params = ['from' => 'whatsapp:' . config('services.twilio.whatsapp_from')];

        if (is_array($payload) && ! empty($payload['template_sid'])) {
            $params['contentSid']       = $payload['template_sid'];
            $params['contentVariables'] = json_encode($payload['variables'] ?? []);
        } else {
            $params['body'] = is_array($payload) ? ($payload['fallback'] ?? '') : $payload;
        }

        foreach ($phones as $phone) {
            $this->twilio->messages->create('whatsapp:' . $phone, $params);
        }
    }
}
