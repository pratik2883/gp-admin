<?php

namespace App\Notifications\Channels;

use App\Services\FcmPushService;
use App\Settings\NotificationSettings;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(
        protected FcmPushService $pushService,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $settings = app(NotificationSettings::class);
        if (! ($settings->push_enabled ?? false)) {
            return;
        }

        $data = method_exists($notification, 'toArray')
            ? (array) $notification->toArray($notifiable)
            : [];

        $title = (string) ($data['title'] ?? '');
        $body = (string) ($data['body'] ?? '');

        if ($title === '' && $body === '') {
            return;
        }

        $pushData = [
            'type' => $data['type'] ?? null,
            'target_id' => $data['target_id'] ?? ($data['referral_id'] ?? ($data['gp_id'] ?? ($data['specialist_id'] ?? null))),
            'role' => $data['role'] ?? null,
        ];

        foreach (['lead_code', 'referral_id'] as $k) {
            if (array_key_exists($k, $data)) {
                $pushData[$k] = $data[$k];
            }
        }

        $this->pushService->sendToUser((int) $notifiable->id, [
            'title' => $title,
            'body' => $body,
            'data' => $pushData,
        ]);
    }
}
