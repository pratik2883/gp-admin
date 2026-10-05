<?php

namespace App\Services;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MessageCentralAuthService
{
    private const BASE_URL = 'https://cpaas.messagecentral.com';

    private const TOKEN_CACHE_KEY = 'message_central_auth_token';

    public function __construct(
        private readonly NotificationSettings $settings,
    ) {}

    public function getToken(): ?string
    {
        $configuredToken = config('services.message_central.auth_token');
        if (is_string($configuredToken) && $configuredToken !== '') {
            return $configuredToken;
        }

        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $customerId = $this->settings->message_central_customer_id
            ?: config('services.message_central.customer_id');
        $password = $this->settings->message_central_password
            ?: config('services.message_central.password');

        if (! $customerId || ! $password) {
            return null;
        }

        $key = base64_encode($password);

        $response = Http::get($this->baseUrl().'/auth/v1/authentication/token', [
            'customerId' => $customerId,
            'key' => $key,
            'scope' => 'NEW',
            'country' => config('services.message_central.country', '91'),
            'email' => $this->settings->message_central_email
                ?: config('services.message_central.email', ''),
        ]);

        if (! $response->successful()) {
            return null;
        }

        $token = (string) ($response->json('token') ?? '');

        if ($token === '') {
            return null;
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, 840);

        return $token;
    }

    public function forgetToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    public function baseUrl(): string
    {
        return (string) config('services.message_central.base_url', self::BASE_URL);
    }
}
