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

    public function isEnabled(): bool
    {
        if ($this->settings->message_central_sms_enabled || $this->settings->sms_enabled) {
            return true;
        }

        return (bool) (config('services.message_central.auth_token')
            || config('services.message_central.customer_id'));
    }

    public function sendOtp(string $mobileNumber, ?int $countryCode = null, ?int $otpLength = null): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $countryCode ??= (int) config('services.message_central.country', 91);
        $otpLength ??= (int) config('services.message_central.otp_length', 4);
        $flowType = (string) config('services.message_central.otp_flow_type', 'SMS');

        $url = $this->auth->baseUrl().'/verification/v3/send?'.http_build_query([
            'countryCode' => (string) $countryCode,
            'flowType' => $flowType,
            'mobileNumber' => $mobileNumber,
            'otpLength' => $otpLength,
        ]);

        $response = Http::withHeaders([
            'authToken' => $token,
        ])->post($url);

        $body = $response->json();

        if (! is_array($body)) {
            Log::error('MessageCentral SMS OTP send failed', [
                'mobile' => $mobileNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::error('MessageCentral SMS OTP send failed', [
                'mobile' => $mobileNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        return $body;
    }

    public function validateOtp(string $verificationId, string $code): ?array
    {
        $token = $this->auth->getToken();
        if (! $token) {
            return null;
        }

        $response = Http::withHeaders([
            'authToken' => $token,
        ])->get($this->auth->baseUrl().'/verification/v3/validateOtp', [
            'verificationId' => $verificationId,
            'code' => $code,
        ]);

        $body = $response->json();

        if (! is_array($body)) {
            Log::error('MessageCentral OTP validation failed', [
                'verificationId' => $verificationId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::error('MessageCentral OTP validation failed', [
                'verificationId' => $verificationId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        return $body;
    }

    public function sendSms(string $to, string $message, array $context = []): ?array
    {
        if (! $this->isEnabled()) {
            Log::warning('MessageCentral SMS send skipped: service disabled');
            return null;
        }

        $senderId = $this->settings->message_central_sms_sender_id
            ?: config('services.message_central.otp_sender_id');
        if (! $senderId) {
            Log::warning('MessageCentral SMS send skipped: sender ID not set');
            return null;
        }

        $token = $this->auth->getToken();
        if (! $token) {
            Log::warning('MessageCentral SMS send skipped: failed to get auth token');
            return null;
        }

        $countryCode = (string) config('services.message_central.country', '91');
        $mobileNumber = ltrim($to, '+');
        if (str_starts_with($mobileNumber, '91') && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, 2);
        }

        $params = [
            'countryCode' => $countryCode,
            'flowType' => (string) config('services.message_central.otp_flow_type', 'SMS'),
            'mobileNumber' => $mobileNumber,
            'message' => $message,
        ];

        if (! empty($senderId)) {
            $params['senderId'] = $senderId;
        }

        // Per MessageCentral documentation: TemplateID and EntityId must be present or both must be missing
        $templateId = trim((string) $this->settings->message_central_sms_template_id);
        $entityId = trim((string) $this->settings->message_central_sms_entity_id);
        if ($templateId !== '' && $entityId !== '') {
            $params['templateId'] = $templateId;
            $params['entityId'] = $entityId;
        }

        try {
            $url = $this->auth->baseUrl().'/verification/v3/send?'.http_build_query($params);

            $response = Http::withHeaders([
                'authToken' => $token,
            ])->post($url);

            $body = $response->json();

            if (! $response->successful()) {
                Log::error('MessageCentral SMS send failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'context' => $context,
                ]);
            }

            return is_array($body) ? $body : ['raw' => $response->body()];
        } catch (Throwable $e) {
            Log::error('MessageCentral SMS send exception', [
                'error' => $e->getMessage(),
                'to' => $to,
                'context' => $context,
            ]);

            return null;
        }
    }
}
