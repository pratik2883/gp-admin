<?php

namespace App\Notifications;

use App\Models\UserSubscription;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Services\SubscriptionNotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    private ?array $resolvedPayload = null;

    public function __construct(
        public string $eventKey,
        public UserSubscription $subscription,
        public array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveNotificationChannels($this->eventKey, $notifiable);
    }

    public function viaQueues(): array
    {
        return $this->defaultNotificationQueues();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->templatePayload();
        $lines = preg_split("/\r\n|\n|\r/", (string) $payload['mail_body']) ?: [];

        $mail = (new MailMessage)->subject((string) $payload['mail_subject']);
        $greetingName = trim((string) ($notifiable->name ?? ''));
        if ($greetingName !== '') {
            $mail->greeting('Hello '.$greetingName.',');
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $mail->line($line);
        }

        if (($this->context['action_url'] ?? '') !== '') {
            $mail->action('Open App', (string) $this->context['action_url']);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $payload = $this->templatePayload();

        return [
            'title' => $payload['in_app_title'],
            'body' => $payload['in_app_body'],
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => $this->eventKey,
            'target_id' => $this->subscription->id,
            'subscription_id' => $this->subscription->id,
            'status' => $this->subscription->status,
            'payment_status' => $this->subscription->payment_status,
            'plan_name' => $this->subscription->plan_name_snapshot,
            'category' => $this->subscription->category_snapshot,
        ];
    }

    public function toTwilioSms(object $notifiable): string
    {
        return (string) $this->templatePayload()['sms_text'];
    }

    public function toTwilioWhatsApp(object $notifiable): string
    {
        return (string) $this->templatePayload()['whatsapp_text'];
    }

    public function toMessageCentralSms(object $notifiable): string
    {
        return (string) $this->templatePayload()['sms_text'];
    }

    public function toMessageCentralWhatsApp(object $notifiable): string
    {
        return (string) $this->templatePayload()['whatsapp_text'];
    }

    private function templatePayload(): array
    {
        if ($this->resolvedPayload !== null) {
            return $this->resolvedPayload;
        }

        return $this->resolvedPayload = app(SubscriptionNotificationTemplateService::class)
            ->build($this->eventKey, $this->context);
    }
}
