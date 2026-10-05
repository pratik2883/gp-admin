<?php

namespace App\Notifications;

use App\Models\DiagnosticReferral;
use App\Notifications\Concerns\RendersFromTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiagnosticReferralStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RendersFromTemplates;

    public function __construct(
        public DiagnosticReferral $referral,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels('diagnostic_referral_status', $notifiable);
    }

    public function viaQueues(): array
    {
        return $this->defaultNotificationQueues();
    }

    protected function templateEventKey(): string
    {
        return 'diagnostic_referral_status';
    }

    protected function templateContext(object $notifiable): array
    {
        $ref = $this->referral;

        return [
            'user_name' => $notifiable->name ?? '',
            'lead_code' => $ref->lead_code ?? '',
            'patient_name' => $ref->patient_name ?? '',
            'gp_name' => optional($ref->gp?->user)->name ?? '',
            'specialist_name' => optional($ref->specialist?->user)->name ?? '',
            'center_name' => $ref->center?->name ?? '',
            'status' => $this->status,
            'action_url' => url('/admin/diagnostic-referrals'),
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