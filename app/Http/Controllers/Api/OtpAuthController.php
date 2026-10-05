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

class OtpAuthController extends Controller
{
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
                'otp' => $otp,
                'name' => $request->name,
            ],
            now()->addMinutes(10)
        );

        $payload = [
            'message' => 'OTP sent successfully',
        ];

        if (app()->environment(['local', 'testing'])) {
            $payload['otp_debug'] = $otp;
        }

        return response()->json($payload);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'otp' => ['required', 'digits_between:4,6'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $mobile = $this->canonicalize((string) $request->mobile);
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
            if ((string) ($cached['otp'] ?? '') !== (string) $request->otp) {
                return response()->json([
                    'message' => 'Invalid or expired OTP.',
                ], 422);
            }
        }

        cache()->forget($this->otpCacheKey($mobile));

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
                'otp' => (string) $otp,
            ],
            now()->addMinutes(10)
        );

        $payload = [
            'message' => 'OTP sent successfully',
        ];

        if (app()->environment(['local', 'testing'])) {
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
}
