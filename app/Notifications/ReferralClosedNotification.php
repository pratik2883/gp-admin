<?php

namespace App\Notifications;

use App\Models\Referral;
use App\Notifications\Concerns\RendersFromTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReferralClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersFromTemplates;

    public function __construct(
        public Referral $referral,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels('referral_closed', $notifiable);
    }

    public function viaQueues(): array
    {
        return $this->defaultNotificationQueues();
    }

    protected function templateEventKey(): string
    {
        return 'referral_closed';
    }

    protected function templateContext(object $notifiable): array
    {
        $ref = $this->referral;

        return [
            'user_name' => $notifiable->name ?? '',
            'lead_code' => $ref->lead_code ?? '',
            'patient_name' => $ref->patient_name ?? '',
            'specialist_name' => optional($ref->specialist?->user)->name ?? '',
            'gp_name' => optional($ref->gp?->user)->name ?? '',
            'status' => $ref->status ?? 'closed',
            'action_url' => url('/admin/referrals'),
        ];
    }

    protected function extraArrayData(object $notifiable): array
    {
        $ref = $this->referral;

        return [
            'referral_id' => $ref->id,
            'target_id' => $ref->id,
            'role' => 'gp',
            'lead_code' => $ref->lead_code,
        ];
    }
}
