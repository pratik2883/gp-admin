<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use App\Settings\NotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        $settings = app(NotificationSettings::class);
        $channels = [];

        if ($settings->in_app_enabled) {
            $channels[] = 'database';
        }

        if ($settings->push_enabled && $notifiable->allowsNotificationChannel('push')) {
            $channels[] = FcmChannel::class;
        }

        return array_values(array_unique($channels));
    }

    public function viaQueues(): array
    {
        return [
            'database' => 'notifications',
            FcmChannel::class => 'notifications',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'body' => $this->body,
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'admin_broadcast',
        ], $this->context);
    }
}
