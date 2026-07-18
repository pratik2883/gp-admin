<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\User;
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

        $mobile = (string) $request->mobile;
        $otp = random_int(100000, 999999);

        cache()->put(
            $this->otpCacheKey($mobile),
            [
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

        $mobile = (string) $request->mobile;
        $cached = cache()->get($this->otpCacheKey($mobile));

        if (! $cached || (string) ($cached['otp'] ?? '') !== (string) $request->otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
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

    private function otpCacheKey(string $mobile): string
    {
        return 'register_otp_'.$mobile;
    }
}
