<?php

namespace App\Notifications;

use App\Models\Specialist;
use App\Notifications\Concerns\RendersFromTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewSpecialistRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersFromTemplates;

    public function __construct(
        public Specialist $specialist,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels('new_specialist_registered', $notifiable);
    }

    public function viaQueues(): array
    {
        return $this->defaultNotificationQueues();
    }

    protected function templateEventKey(): string
    {
        return 'new_specialist_registered';
    }

    protected function templateContext(object $notifiable): array
    {
        $s = $this->specialist;

        return [
            'user_name' => optional($s->user)->name ?? 'Specialist',
            'specialty' => $s->primary_specialization ?? '',
            'city' => $s->city ?? '',
            'action_url' => url('/admin/specialists'),
        ];
    }

    protected function extraArrayData(object $notifiable): array
    {
        return [
            'specialist_id' => $this->specialist->id,
        ];
    }
}
