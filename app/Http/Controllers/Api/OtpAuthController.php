<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\User;
use App\Services\MessageCentralSmsService;
use App\Settings\NotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class OtpAuthController extends Controller
{
    /** Wrong codes tolerated for one destination number before the OTP is burned. */
    private const OTP_MAX_ATTEMPTS = 5;
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $mobile = $this->canonicalize((string) $request->mobile);

        $settings = app(NotificationSettings::class);
        $smsService = app(MessageCentralSmsService::class);

        if ($smsService->isEnabled()) {
            $response = $smsService->sendOtp($this->localNumber($mobile));

            $verificationId = (string) data_get($response, 'data.verificationId', '');

            if ($verificationId === '') {
                $code = (int) (data_get($response, 'data.code') ?? data_get($response, 'code') ?? 0);

                return response()->json([
                    'message' => 'OTP send failed. Please try again.',
                    'code' => $code ?: null,
                ], 422);
            }

            cache()->put(
                $this->otpCacheKey($mobile),
                [
                    'mode' => 'messagecentral',
                    'verification_id' => $verificationId,
                    'name' => $request->name,
                ],
                now()->addMinutes((int) config('services.message_central.otp_expiry', 5))
            );

            return response()->json([
                'message' => 'OTP sent successfully',
                'verification_id' => $verificationId,
            ]);
        }

        // Fallback: local (dev) OTP — no SMS provider configured
        $otp = random_int(100000, 999999);

        cache()->put(
            $this->otpCacheKey($mobile),
            [
                'mode' => 'local',
                'otp' => hash('sha256', (string) $otp),
                'name' => $request->name,
            ],
            now()->addMinutes(10)
        );

        $payload = [
            'message' => 'OTP sent successfully',
        ];

        if ($this->canExposeOtpForDebug()) {
            $payload['otp_debug'] = $otp;
        }

        return response()->json($payload);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'otp' => ['required', 'digits_between:4,6'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        ]);

        $mobile = $this->canonicalize((string) $request->mobile);

        if ($this->tooManyOtpAttempts($mobile)) {
            return response()->json([
                'message' => 'Too many incorrect OTP attempts. Request a new OTP.',
            ], 429);
        }

        $cached = cache()->get($this->otpCacheKey($mobile));

        if (! is_array($cached)) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $smsService = app(MessageCentralSmsService::class);

        if (($cached['mode'] ?? 'local') === 'messagecentral') {
            $verificationId = (string) ($cached['verification_id'] ?? '');
            $response = $smsService->validateOtp($verificationId, (string) $request->otp);

            $status = (string) data_get($response, 'data.verificationStatus', '');

            if ($status !== 'VERIFICATION_COMPLETED') {
                $code = (int) (data_get($response, 'data.code') ?? data_get($response, 'code') ?? 0);

                $failure = match ($code) {
                    702 => 'Invalid OTP.',
                    703 => 'OTP has already been verified.',
                    705 => 'OTP has expired.',
                    800 => 'Maximum OTP attempts reached. Please request a new OTP.',
                    505, 506 => 'Invalid verification. Please request a new OTP.',
                    default => 'OTP verification failed. Please try again.',
                };

                return response()->json([
                    'message' => $failure,
                    'code' => $code ?: null,
                ], 422);
            }
        } else {
            $storedOtp = (string) ($cached['otp'] ?? '');
            $otpMatches = $storedOtp !== ''
                && hash_equals($storedOtp, hash('sha256', (string) $request->otp));

            if (! $otpMatches) {
                $this->registerFailedOtpAttempt($mobile);

                return response()->json([
                    'message' => 'Invalid or expired OTP.',
                ], 422);
            }
        }

        cache()->forget($this->otpCacheKey($mobile));
        cache()->forget($this->otpAttemptKey($mobile));

        $user = User::firstOrNew(['mobile' => $mobile]);
        $user->name = $cached['name'] ?? ($user->name ?? 'User');
        $user->password = Hash::make($request->password);
        $user->status = 'active';
        if (! $user->exists) {
            $user->role = $user->role ?? 'gp';
        }
        $user->save();

        if ($user->role === 'gp' && ! $user->gp()->exists()) {
            Gp::create(['user_id' => $user->id, 'status' => 'pending']);
        }
        if ($user->role === 'specialist' && ! $user->specialist()->exists()) {
            Specialist::create(['user_id' => $user->id]);
        }

        $user->tokens()->delete();

        $deviceName = $request->header('User-Agent') ?: 'mobile-register';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'gp_id' => $user->gp()->value('id'),
                'specialist_id' => $user->specialist()->value('id'),
            ],
        ]);
    }

    // POST /api/auth/send-otp — send OTP for login (mobile only, no name/password)
    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
        ]);

        $mobile = $this->canonicalize((string) $request->mobile);

        $smsService = app(MessageCentralSmsService::class);

        if ($smsService->isEnabled()) {
            $response = $smsService->sendOtp($this->localNumber($mobile));

            $verificationId = (string) data_get($response, 'data.verificationId', '');

            if ($verificationId === '') {
                $code = (int) (data_get($response, 'data.code') ?? data_get($response, 'code') ?? 0);

                return response()->json([
                    'message' => 'OTP send failed. Please try again.',
                    'code' => $code ?: null,
                ], 422);
            }

            cache()->put(
                $this->loginOtpCacheKey($mobile),
                [
                    'mode' => 'messagecentral',
                    'verification_id' => $verificationId,
                ],
                now()->addMinutes((int) config('services.message_central.otp_expiry', 5))
            );

            return response()->json([
                'message' => 'OTP sent successfully',
                'verification_id' => $verificationId,
            ]);
        }

        // Fallback: local (dev) OTP — no SMS provider configured
        $otp = random_int(100000, 999999);

        cache()->put(
            $this->loginOtpCacheKey($mobile),
            [
                'mode' => 'local',
                'otp' => hash('sha256', (string) $otp),
            ],
            now()->addMinutes(10)
        );

        $payload = [
            'message' => 'OTP sent successfully',
        ];

        if ($this->canExposeOtpForDebug()) {
            $payload['otp_debug'] = $otp;
        }

        return response()->json($payload);
    }

    public static function canonicalize(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '091')) {
            return '91'.substr($digits, 1);
        }

        return $digits;
    }

    private function localNumber(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';

        if (strlen($digits) > 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    private function otpCacheKey(string $mobile): string
    {
        return 'register_otp_'.$mobile;
    }

    private function loginOtpCacheKey(string $mobile): string
    {
        return 'login_otp_'.$mobile;
    }

    /**
     * The OTP is echoed back only for local debugging, and only while the app is
     * actually in debug mode: returning it purely on `APP_ENV` would hand full
     * account takeover to anyone if the environment was ever mis-set.
     */
    private function canExposeOtpForDebug(): bool
    {
        return app()->environment(['local', 'testing']) && (bool) config('app.debug');
    }

    private function otpAttemptKey(string $mobile): string
    {
        return 'verify_otp_attempts:'.$mobile;
    }

    private function tooManyOtpAttempts(string $mobile): bool
    {
        return (int) cache()->get($this->otpAttemptKey($mobile), 0) >= self::OTP_MAX_ATTEMPTS;
    }

    private function registerFailedOtpAttempt(string $mobile): void
    {
        $key = $this->otpAttemptKey($mobile);
        $attempts = (int) cache()->get($key, 0) + 1;

        cache()->put($key, $attempts, now()->addMinutes(15));

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            cache()->forget($this->otpCacheKey($mobile));
        }
    }
}
