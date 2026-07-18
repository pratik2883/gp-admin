<?php

namespace App\Services;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageCentralSmsService
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly MessageCentralAuthService $auth,
    ) {}

    public function sendOtp(string $mobileNumber, int $countryCode = 91, int $otpLength = 4): ?array
    {
        if (! $this->settings->sms_enabled) {
            return null;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $url = $this->auth->baseUrl() . '/verification/v3/send?' . http_build_query([
            'countryCode' => (string) $countryCode,
            'flowType' => 'SMS',
            'mobileNumber' => $mobileNumber,
            'otpLength' => $otpLength,
        ]);

        $response = Http::withHeaders([
            'authToken' => $token,
        ])->post($url);

        if (! $response->successful()) {
            Log::error('MessageCentral SMS OTP send failed', [
                'mobile' => $mobileNumber,
                'response' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function validateOtp(string $verificationId, string $code): ?array
    {
        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $response = Http::withHeaders([
            'authToken' => $token,
        ])->get($this->auth->baseUrl() . '/verification/v3/validateOtp', [
            'verificationId' => $verificationId,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            Log::error('MessageCentral OTP validation failed', [
                'verificationId' => $verificationId,
                'response' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function sendSms(string $to, string $message, array $context = []): void
    {
        if (! $this->settings->sms_enabled) {
            return;
        }

        $senderId = $this->settings->message_central_sms_sender_id;
        if (! $senderId) {
            return;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            return;
        }

        $countryCode = '91';
        $mobileNumber = ltrim($to, '+');
        if (str_starts_with($mobileNumber, '91') && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, 2);
        }

        try {
            $url = $this->auth->baseUrl() . '/verification/v3/send?' . http_build_query([
                'countryCode' => $countryCode,
                'flowType' => 'SMS',
                'mobileNumber' => $mobileNumber,
                'senderId' => $senderId,
                'message' => $message,
            ]);

            $response = Http::withHeaders([
                'authToken' => $token,
            ])->post($url);

            if (! $response->successful()) {
                Log::error('MessageCentral SMS send failed', [
                    'to' => $to,
                    'response' => $response->body(),
                    'context' => $context,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('MessageCentral SMS send exception', [
                'error' => $e->getMessage(),
                'to' => $to,
                'context' => $context,
            ]);
        }
    }
}
