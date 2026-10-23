<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
        ]);

        $user = $request->user();
        $now = now();

        $token = DeviceToken::query()->where('token', $data['token'])->first();
        if ($token) {
            // A push token identifies one installed app, not a user. Letting a
            // caller re-own an existing token would redirect another account's
            // notifications to their device; the old account must log out
            // (which deletes the token) before the device can be reused.
            if ((int) $token->user_id !== (int) $user->id) {
                return response()->json([
                    'message' => 'This device token belongs to another account.',
                ], 409);
            }

            $token->platform = $data['platform'] ?? $token->platform;
            $token->last_seen_at = $now;
            $token->save();
        } else {
            DeviceToken::query()->create([
                'user_id' => $user->id,
                'token' => $data['token'],
                'platform' => $data['platform'] ?? null,
                'last_seen_at' => $now,
            ]);
        }

        return response()->json([
            'message' => 'Device token saved.',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:500'],
        ]);

        $query = DeviceToken::query()
            ->where('user_id', $user->id);

        $token = $data['token'] ?? null;
        if (is_string($token) && $token !== '') {
            $query->where('token', $token);
        }

        $query->delete();

        return response()->json([
            'message' => 'Device token removed.',
        ]);
    }
}
