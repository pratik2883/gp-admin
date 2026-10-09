<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache-key home for the `/api/auth/me` user payload.
 *
 * Kept in one place so the controller that writes the entry and the model
 * events that invalidate it can never drift apart.
 */
class AuthPayloadCache
{
    public const PREFIX = 'auth_user_payload:';

    public static function key(int $userId): string
    {
        return self::PREFIX.$userId;
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::key($userId));
    }
}
