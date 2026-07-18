<?php

namespace App\Services;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

class TwilioSmsService
{
    public function __construct(
        protected NotificationSettings $settings,
    ) {}

    public function send(string $to, string $message, array $context = []): void
    {
        if (! $this->settings->sms_enabled) {
            return;
        }

        $sid = $this->settings->twilio_account_sid;
        $token = $this->settings->twilio_auth_token;
        $from = $this->settings->twilio_from_number;

        if (! $sid || ! $token || ! $from) {
            return;
        }

        $client = new Client($sid, $token);

        try {
            $client->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);
        } catch (Throwable $e) {
            $meta = array_merge([
                'channel' => 'twilio_sms',
                'to' => $to,
                'message_length' => strlen($message),
            ], $context);
            Log::error('Twilio SMS send failed', [
                'error' => $e->getMessage(),
                'meta' => $meta,
            ]);
            throw $e;
        }
    }
}
