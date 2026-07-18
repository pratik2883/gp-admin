<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\MessageCentralSmsChannel;
use App\Notifications\Channels\MessageCentralWhatsAppChannel;
use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Channels\TwilioWhatsAppChannel;
use App\Settings\NotificationSettings;

trait ResolvesNotificationChannels
{
    protected function resolveNotificationChannels(string $event, object $notifiable): array
    {
        $settings = app(NotificationSettings::class);
        $logicalChannels = $settings->channelsFor($event);

        return collect($logicalChannels)
            ->filter(fn (string $channel) => $this->notifiableAllowsChannel($notifiable, $channel))
            ->map(function (string $channel) {
                return match ($channel) {
                    'email' => 'mail',
                    'in_app' => 'database',
                    'sms' => TwilioSmsChannel::class,
                    'push' => FcmChannel::class,
                    'whatsapp' => TwilioWhatsAppChannel::class,
                    'mc_sms' => MessageCentralSmsChannel::class,
                    'mc_whatsapp' => MessageCentralWhatsAppChannel::class,
                    default => null,
                };
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function defaultNotificationQueues(): array
    {
        return [
            'mail' => 'notifications',
            'database' => 'notifications',
            TwilioSmsChannel::class => 'notifications',
            FcmChannel::class => 'notifications',
            TwilioWhatsAppChannel::class => 'notifications',
            MessageCentralSmsChannel::class => 'notifications',
            MessageCentralWhatsAppChannel::class => 'notifications',
        ];
    }

    private function notifiableAllowsChannel(object $notifiable, string $channel): bool
    {
        if (method_exists($notifiable, 'allowsNotificationChannel')) {
            return (bool) $notifiable->allowsNotificationChannel($channel);
        }

        return true;
    }
}
