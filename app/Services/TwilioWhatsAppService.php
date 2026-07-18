<?php

namespace App\Services;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

class TwilioWhatsAppService
{
    public function __construct(
        protected NotificationSettings $settings,
    ) {}

    public function send(string $to, string $message, array $context = []): void
    {
        if (! $this->settings->whatsapp_enabled) {
            return;
        }

        $sid = $this->settings->twilio_account_sid;
        $token = $this->settings->twilio_auth_token;
        $from = $this->normalizeWhatsappNumber($this->settings->twilio_whatsapp_from);
        $recipient = $this->normalizeWhatsappNumber($to);

        if (! $sid || ! $token || ! $from || ! $recipient) {
            return;
        }

        $client = new Client($sid, $token);

        try {
            $client->messages->create($recipient, [
                'from' => $from,
                'body' => $message,
            ]);
        } catch (Throwable $e) {
            $meta = array_merge([
                'channel' => 'twilio_whatsapp',
                'to' => $recipient,
                'message_length' => strlen($message),
            ], $context);
            Log::error('Twilio WhatsApp send failed', [
                'error' => $e->getMessage(),
                'meta' => $meta,
            ]);
            throw $e;
        }
    }

    private function normalizeWhatsappNumber(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return str_starts_with(strtolower($value), 'whatsapp:')
            ? $value
            : 'whatsapp:'.$value;
    }
}
