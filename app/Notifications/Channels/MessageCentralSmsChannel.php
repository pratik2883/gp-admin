<?php

namespace App\Notifications\Channels;

use App\Services\MessageCentralSmsService;
use Illuminate\Notifications\Notification;

class MessageCentralSmsChannel
{
    public function __construct(
        protected MessageCentralSmsService $smsService,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        $to = $notifiable->phone ?? $notifiable->mobile ?? null;

        if (! $to && method_exists($notifiable, 'routeNotificationForMessageCentralSms')) {
            $to = $notifiable->routeNotificationForMessageCentralSms($notification);
        }

        if (! $to) {
            return;
        }

        $message = null;
        if (method_exists($notification, 'toMessageCentralSms')) {
            $message = $notification->toMessageCentralSms($notifiable);
        } elseif (method_exists($notification, 'toSms')) {
            $message = $notification->toSms($notifiable);
        }

        if (! $message) {
            return;
        }

        $context = [
            'user_id' => $notifiable->id ?? null,
            'notification' => get_class($notification),
        ];

        $this->smsService->sendSms($to, $message, $context);
    }
}
