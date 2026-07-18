<?php

namespace App\Notifications;

use App\Models\Gp;
use App\Notifications\Concerns\RendersFromTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewGpRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersFromTemplates;

    public function __construct(
        public Gp $gp,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels('new_gp_registered', $notifiable);
    }

    public function viaQueues(): array
    {
        return $this->defaultNotificationQueues();
    }

    protected function templateEventKey(): string
    {
        return 'new_gp_registered';
    }

    protected function templateContext(object $notifiable): array
    {
        $gp = $this->gp;

        return [
            'user_name' => optional($gp->user)->name ?? 'GP',
            'mobile' => optional($gp->user)->mobile ?? '',
            'email' => optional($gp->user)->email ?? '',
            'city' => $gp->city ?? '',
            'action_url' => url('/admin/gps'),
        ];
    }

    protected function extraArrayData(object $notifiable): array
    {
        return [
            'gp_id' => $this->gp->id,
        ];
    }
}
