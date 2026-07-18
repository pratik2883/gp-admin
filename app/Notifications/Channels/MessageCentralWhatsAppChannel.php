<?php

namespace App\Notifications\Channels;

use App\Services\MessageCentralWhatsAppService;
use Illuminate\Notifications\Notification;

class MessageCentralWhatsAppChannel
{
    public function __construct(
        protected MessageCentralWhatsAppService $whatsAppService,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        $to = $notifiable->phone ?? $notifiable->mobile ?? null;

        if (! $to && method_exists($notifiable, 'routeNotificationForMessageCentralWhatsApp')) {
            $to = $notifiable->routeNotificationForMessageCentralWhatsApp($notification);
        }

        if (! $to) {
            return;
        }

        $message = null;
        if (method_exists($notification, 'toMessageCentralWhatsApp')) {
            $message = $notification->toMessageCentralWhatsApp($notifiable);
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

        $this->whatsAppService->sendText($to, $message, $context);
    }
}
