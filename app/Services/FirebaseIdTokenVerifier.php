<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseIdTokenVerifier
{
    private const CERTS_CACHE_KEY = 'firebase_securetoken_certs_v1';

    public function verify(string $idToken, string $projectId): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid Firebase ID token.');
        }

        $header = $this->decodePart($parts[0]);
        $payload = $this->decodePart($parts[1]);

        $alg = (string) ($header['alg'] ?? '');
        if ($alg !== 'RS256') {
            throw new RuntimeException('Unsupported token algorithm.');
        }

        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new RuntimeException('Invalid token header.');
        }

        $certs = $this->getCerts();
        $cert = $certs[$kid] ?? null;
        if (! is_string($cert) || $cert === '') {
            Cache::forget(self::CERTS_CACHE_KEY);
            $certs = $this->getCerts();
            $cert = $certs[$kid] ?? null;
        }
        if (! is_string($cert) || $cert === '') {
            throw new RuntimeException('Unknown signing key.');
        }

        $data = $parts[0].'.'.$parts[1];
        $signature = $this->base64UrlDecode($parts[2], true);
        if ($signature === null) {
            throw new RuntimeException('Invalid token signature.');
        }

        $verified = openssl_verify($data, $signature, $cert, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('Invalid token signature.');
        }

        $now = time();
        $clockSkew = 60;

        $aud = (string) ($payload['aud'] ?? '');
        $iss = (string) ($payload['iss'] ?? '');
        $exp = (int) ($payload['exp'] ?? 0);
        $iat = (int) ($payload['iat'] ?? 0);

        if ($aud !== $projectId) {
            throw new RuntimeException('Invalid token audience.');
        }

        if ($iss !== 'https://securetoken.google.com/'.$projectId) {
            throw new RuntimeException('Invalid token issuer.');
        }

        if ($exp !== 0 && $exp < ($now - $clockSkew)) {
            throw new RuntimeException('Token expired.');
        }

        if ($iat !== 0 && $iat > ($now + $clockSkew)) {
            throw new RuntimeException('Token issued in the future.');
        }

        return $payload;
    }

    private function decodePart(string $part): array
    {
        $decoded = $this->base64UrlDecode($part, false);
        if ($decoded === null) {
            throw new RuntimeException('Invalid token encoding.');
        }

        $json = json_decode($decoded, true);
        if (! is_array($json)) {
            throw new RuntimeException('Invalid token JSON.');
        }

        return $json;
    }

    private function base64UrlDecode(string $input, bool $binary): ?string
    {
        $replaced = strtr($input, '-_', '+/');
        $padLen = (4 - (strlen($replaced) % 4)) % 4;
        if ($padLen > 0) {
            $replaced .= str_repeat('=', $padLen);
        }

        $decoded = base64_decode($replaced, true);
        if ($decoded === false) {
            return null;
        }

        return $binary ? $decoded : $decoded;
    }

    private function getCerts(): array
    {
        return Cache::remember(self::CERTS_CACHE_KEY, now()->addHours(6), function () {
            $res = Http::timeout(10)->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
            if (! $res->ok()) {
                throw new RuntimeException('Failed to fetch Firebase certificates.');
            }

            $data = $res->json();
            if (! is_array($data)) {
                throw new RuntimeException('Invalid certificates response.');
            }

            $out = [];
            foreach ($data as $kid => $cert) {
                if (is_string($kid) && is_string($cert) && $kid !== '' && $cert !== '') {
                    $out[$kid] = $cert;
                }
            }

            if ($out === []) {
                throw new RuntimeException('No Firebase certificates found.');
            }

            return $out;
        });
    }
}

