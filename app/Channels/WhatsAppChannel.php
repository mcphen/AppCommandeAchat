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

        $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $phone) {
            return;
        }

        $payload = $notification->toWhatsApp($notifiable);

        $params = ['from' => 'whatsapp:' . config('services.twilio.whatsapp_from')];

        if (is_array($payload) && ! empty($payload['template_sid'])) {
            $params['contentSid']       = $payload['template_sid'];
            $params['contentVariables'] = json_encode($payload['variables'] ?? []);
        } else {
            $params['body'] = is_array($payload) ? ($payload['fallback'] ?? '') : $payload;
        }

        $this->twilio->messages->create('whatsapp:' . $phone, $params);
    }
}
