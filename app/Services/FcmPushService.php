<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::warning('FCM push skipped: could not obtain access token.', [
                'user_id' => $userId,
                'project_id' => $projectId,
            ]);

            return;
        }

        $endpoint = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId);

        $sent = 0;
        foreach ($tokens as $token) {
            // FCM v1 has no `icon` field on `notification`. Sending one made every
            // request fail with 400 INVALID_ARGUMENT ("Unknown name icon"). The
            // Android icon belongs under android.notification instead.
            $message = [
                'message' => array_filter([
                    'token' => $token,
                    'notification' => array_filter([
                        'title' => $title,
                        'body' => $body,
                    ], fn ($v) => $v !== ''),
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'icon' => 'ic_stat_notification',
                            'channel_id' => 'gp_high_importance_channel',
                        ],
                    ],
                ]),
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($endpoint, $message);

            if ($response->status() === 401) {
                $this->forgetAccessToken();
                $accessToken = $this->getAccessToken();
                if ($accessToken === null) {
                    Log::warning('FCM push aborted: access token refresh failed.', ['user_id' => $userId]);

                    return;
                }

                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, $message);
            }

            if (! $response->successful()) {
                $this->handleDeliveryFailure($response, $userId, $token);

                continue;
            }

            $sent++;
        }

        Log::info('FCM push sent', [
            'user_id' => $userId,
            'tokens' => count($tokens),
            'delivered' => $sent,
        ]);
    }

    private function handleDeliveryFailure($response, int $userId, string $token): void
    {
        $error = $response->json('error') ?? [];
        $code = (string) ($error['status'] ?? '');
        $message = (string) ($error['message'] ?? '');

        Log::warning('FCM push delivery failed', [
            'user_id' => $userId,
            'status_code' => $response->status(),
            'fcm_status' => $code,
            'message' => $message,
            'token_prefix' => substr($token, 0, 24),
        ]);

        // Only prune when FCM says the token itself is gone. INVALID_ARGUMENT is
        // deliberately excluded: FCM also returns it for malformed payloads, and a
        // payload bug must never wipe out every user's valid device tokens.
        $staleCodes = ['UNREGISTERED', 'NOT_FOUND'];
        if (in_array($code, $staleCodes, true)) {
            DeviceToken::query()
                ->where('user_id', $userId)
                ->where('token', $token)
                ->delete();
            Log::info('FCM stale device token removed', [
                'user_id' => $userId,
                'fcm_status' => $code,
            ]);
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
