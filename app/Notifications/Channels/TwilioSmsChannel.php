<?php

namespace App\Notifications\Channels;

use App\Services\TwilioSmsService;
use Illuminate\Notifications\Notification;

class TwilioSmsChannel
{
    public function __construct(
        protected TwilioSmsService $smsService,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        $to = $notifiable->phone ?? $notifiable->mobile ?? null;

        if (! $to && method_exists($notifiable, 'routeNotificationForTwilioSms')) {
            $to = $notifiable->routeNotificationForTwilioSms($notification);
        }

        if (! $to) {
            return;
        }

        $message = null;
        if (method_exists($notification, 'toTwilioSms')) {
            $message = $notification->toTwilioSms($notifiable);
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
        foreach (['referral', 'gp', 'specialist'] as $prop) {
            if (property_exists($notification, $prop) && isset($notification->{$prop})) {
                $context[$prop.'_id'] = $notification->{$prop}->id ?? null;
            }
        }
        $this->smsService->send($to, $message, $context);
    }
}
