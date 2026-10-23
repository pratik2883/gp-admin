<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /** Password guesses allowed for one admin e-mail + IP before a lockout. */
    private const LOGIN_MAX_ATTEMPTS = 10;

    /** How long a locked-out caller has to wait (seconds). */
    private const LOGIN_LOCKOUT_SECONDS = 900;

    /** Compared when the account does not exist, to keep response times equal. */
    private const DUMMY_PASSWORD_HASH = '$2y$12$y0pRq3AWEpb1RAj1M5i0m.7FnxNl9zwM9wXm3ppRSc7p20nt.CH9K';

    public function login(AdminLoginRequest $request)
    {
        $data = $request->validated();

        $throttleKey = 'admin-login:'.mb_strtolower((string) $data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many login attempts. Try again in '.max(1, (int) ceil($seconds / 60)).' minute(s).',
            ], 429)->header('Retry-After', (string) $seconds);
        }

        $user = User::where('email', $data['email'])->first();
        $passwordIsValid = Hash::check(
            (string) $data['password'],
            $user?->password ?: self::DUMMY_PASSWORD_HASH,
        );

        if (! $user || ! $passwordIsValid) {
            RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);

            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        RateLimiter::clear($throttleKey);

        if ($user->role !== 'admin' || ($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $token = $user->createToken('admin-panel')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out from all devices']);
    }
}
