<?php

namespace App\Notifications\Concerns;

use App\Services\SubscriptionNotificationTemplateService;
use Illuminate\Notifications\Messages\MailMessage;

trait RendersFromTemplates
{
    use ResolvesNotificationChannels;

    abstract protected function templateEventKey(): string;

    abstract protected function templateContext(object $notifiable): array;

    protected function extraArrayData(object $notifiable): array
    {
        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->buildTemplatePayload($notifiable);
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

        $context = $this->templateContext($notifiable);
        if (($context['action_url'] ?? '') !== '') {
            $mail->action('Open App', (string) $context['action_url']);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $payload = $this->buildTemplatePayload($notifiable);

        return array_merge([
            'title' => $payload['in_app_title'],
            'body' => $payload['in_app_body'],
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => $this->templateEventKey(),
        ], $this->extraArrayData($notifiable));
    }

    public function toTwilioSms(object $notifiable): string
    {
        return (string) $this->buildTemplatePayload($notifiable)['sms_text'];
    }

    public function toTwilioWhatsApp(object $notifiable): string
    {
        return (string) $this->buildTemplatePayload($notifiable)['whatsapp_text'];
    }

    public function toMessageCentralSms(object $notifiable): string
    {
        return (string) $this->buildTemplatePayload($notifiable)['sms_text'];
    }

    public function toMessageCentralWhatsApp(object $notifiable): string
    {
        return (string) $this->buildTemplatePayload($notifiable)['whatsapp_text'];
    }

    private function buildTemplatePayload(object $notifiable): array
    {
        return app(SubscriptionNotificationTemplateService::class)
            ->build($this->templateEventKey(), $this->templateContext($notifiable));
    }
}
