<?php

namespace App\Notifications\Channels;

use App\Services\TwilioWhatsAppService;
use Illuminate\Notifications\Notification;

class TwilioWhatsAppChannel
{
    public function __construct(
        protected TwilioWhatsAppService $whatsAppService,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        $to = $notifiable->phone ?? $notifiable->mobile ?? null;

        if (! $to && method_exists($notifiable, 'routeNotificationForTwilioWhatsApp')) {
            $to = $notifiable->routeNotificationForTwilioWhatsApp($notification);
        }

        if (! $to) {
            return;
        }

        $message = null;
        if (method_exists($notification, 'toTwilioWhatsApp')) {
            $message = $notification->toTwilioWhatsApp($notifiable);
        } elseif (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);
        }

        if (! $message) {
            return;
        }

        $context = [
            'user_id' => $notifiable->id ?? null,
            'notification' => get_class($notification),
        ];

        $this->whatsAppService->send((string) $to, $message, $context);
    }
}
