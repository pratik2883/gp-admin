<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FcmPushService
{
    public function sendToUser(int $userId, array $payload): void
    {
        $enabled = (bool) config('services.fcm.enabled', false);
        $projectId = (string) config('services.fcm.project_id', '');

        if (! $enabled || $projectId === '') {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $title = (string) ($payload['title'] ?? '');
        $body = (string) ($payload['body'] ?? '');
        $data = $this->stringifyDataPayload((array) ($payload['data'] ?? []));

        if ($title === '' && $body === '') {
            return;
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return;
        }

        $endpoint = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId);

        foreach ($tokens as $token) {
            $message = [
                'message' => array_filter([
                    'token' => $token,
                    'notification' => array_filter([
                        'title' => $title,
                        'body' => $body,
                    ], fn ($v) => $v !== ''),
                    'data' => $data,
                ]),
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($endpoint, $message);

            if ($response->status() === 401) {
                $this->forgetAccessToken();
                $accessToken = $this->getAccessToken();
                if ($accessToken === null) {
                    return;
                }

                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, $message);
            }

            if (! $response->successful()) {
                continue;
            }
        }
    }

    private function stringifyDataPayload(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v === null) {
                continue;
            }
            if (is_bool($v)) {
                $out[(string) $k] = $v ? '1' : '0';

                continue;
            }
            if (is_scalar($v)) {
                $out[(string) $k] = (string) $v;

                continue;
            }
        }

        return $out;
    }

    private function getAccessToken(): ?string
    {
        $cacheKey = 'fcm_access_token';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $creds = $this->loadServiceAccount();
        if ($creds === null) {
            return null;
        }

        $now = time();
        $jwt = $this->createServiceAccountJwt(
            $creds['client_email'],
            $creds['private_key'],
            $now
        );

        if ($jwt === null) {
            return null;
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $tokenResponse->successful()) {
            return null;
        }

        $accessToken = (string) ($tokenResponse->json('access_token') ?? '');
        $expiresIn = (int) ($tokenResponse->json('expires_in') ?? 0);

        if ($accessToken === '' || $expiresIn <= 0) {
            return null;
        }

        Cache::put($cacheKey, $accessToken, max(1, $expiresIn - 60));

        return $accessToken;
    }

    private function forgetAccessToken(): void
    {
        Cache::forget('fcm_access_token');
    }

    private function loadServiceAccount(): ?array
    {
        $json = (string) config('services.fcm.service_account_json', '');
        $path = (string) config('services.fcm.service_account_path', '');

        $raw = null;
        if ($json !== '') {
            $raw = $json;
        } elseif ($path !== '' && is_file($path)) {
            $raw = file_get_contents($path) ?: null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        $email = (string) ($decoded['client_email'] ?? '');
        $privateKey = (string) ($decoded['private_key'] ?? '');

        if ($email === '' || $privateKey === '') {
            return null;
        }

        return [
            'client_email' => $email,
            'private_key' => $privateKey,
        ];
    }

    private function createServiceAccountJwt(string $clientEmail, string $privateKeyPem, int $now): ?string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        if (! is_string($header) || ! is_string($claims)) {
            return null;
        }

        $toSign = $header.'.'.$claims;

        $signature = '';
        $ok = openssl_sign($toSign, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            return null;
        }

        return $toSign.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
