<?php

namespace App\Services;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageCentralWhatsAppService
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly MessageCentralAuthService $auth,
    ) {}

    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $language = 'en_US',
        ?array $variables = null,
        ?array $ctaVariables = null,
    ): ?array {
        if (! $this->settings->message_central_whatsapp_enabled) {
            return null;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $senderId = $this->settings->message_central_whatsapp_sender_id;
        if (! $senderId) {
            return null;
        }

        $countryCode = '91';
        $mobileNumber = ltrim($to, '+');
        if (str_starts_with($mobileNumber, '91') && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, 2);
        }

        $params = [
            'flowType' => 'WHATSAPP',
            'type' => 'BROADCAST',
            'templateName' => $templateName,
            'senderId' => $senderId,
            'countryCode' => $countryCode,
            'mobileNumber' => $mobileNumber,
            'langId' => $language,
        ];

        if ($variables !== null && count($variables) > 0) {
            $params['variables'] = implode(',', $variables);
        }

        if ($ctaVariables !== null && count($ctaVariables) > 0) {
            $params['ctaVariables'] = implode(',', $ctaVariables);
        }

        try {
            $url = $this->auth->baseUrl() . '/verification/v3/send?' . http_build_query($params);

            $response = Http::withHeaders([
                'authToken' => $token,
            ])->post($url);

            if (! $response->successful()) {
                Log::error('MessageCentral WhatsApp template send failed', [
                    'to' => $to,
                    'template' => $templateName,
                    'response' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('MessageCentral WhatsApp template send exception', [
                'error' => $e->getMessage(),
                'to' => $to,
                'template' => $templateName,
            ]);

            return null;
        }
    }

    public function sendChatMessage(
        string $to,
        string $message,
    ): ?array {
        if (! $this->settings->message_central_whatsapp_enabled) {
            return null;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $senderId = $this->settings->message_central_whatsapp_sender_id;
        if (! $senderId) {
            return null;
        }

        $countryCode = '91';
        $mobileNumber = ltrim($to, '+');
        if (str_starts_with($mobileNumber, '91') && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, 2);
        }

        try {
            $url = $this->auth->baseUrl() . '/verification/v3/send?' . http_build_query([
                'flowType' => 'WHATSAPP',
                'type' => 'CHAT',
                'senderId' => $senderId,
                'countryCode' => $countryCode,
                'mobileNumber' => $mobileNumber,
                'message' => $message,
            ]);

            $response = Http::withHeaders([
                'authToken' => $token,
            ])->post($url);

            if (! $response->successful()) {
                Log::error('MessageCentral WhatsApp chat send failed', [
                    'to' => $to,
                    'response' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('MessageCentral WhatsApp chat send exception', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return null;
        }
    }

    public function sendText(string $to, string $message, array $context = []): void
    {
        try {
            $this->sendChatMessage($to, $message);
        } catch (Throwable $e) {
            Log::error('MessageCentral WhatsApp text send exception', [
                'error' => $e->getMessage(),
                'to' => $to,
                'context' => $context,
            ]);
        }
    }
}
