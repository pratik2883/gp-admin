<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Models\User;
use App\Services\FirebaseIdTokenVerifier;
use App\Services\CcaVenuePaymentService;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use App\Settings\GeneralSettings;
use App\Settings\OtpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AuthController extends Controller
{
    // POST /api/auth/login-with-otp
    public function loginWithOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'firebase_id_token' => ['required', 'string'],
            'role_hint' => ['nullable', Rule::in(['gp', 'specialist'])],
            'terms_accepted' => 'accepted',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }

        $otpSettings = app(OtpSettings::class);
        if (! $otpSettings->enable_otp_login) {
            return response()->json(['message' => 'OTP login is disabled.'], 403);
        }
        if (! is_string($otpSettings->firebase_project_id) || $otpSettings->firebase_project_id === '') {
            return response()->json(['message' => 'Firebase Project ID is not configured.'], 422);
        }

        $idToken = (string) $request->input('firebase_id_token');
        $roleHint = $request->input('role_hint');

        try {
            $claims = app(FirebaseIdTokenVerifier::class)->verify($idToken, (string) $otpSettings->firebase_project_id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $phone = $this->normalizePhone((string) ($claims['phone_number'] ?? ''));
        $name = is_string($claims['name'] ?? null) ? (string) $claims['name'] : null;
        $email = is_string($claims['email'] ?? null) ? (string) $claims['email'] : null;

        if ($phone === '') {
            return response()->json(['message' => 'Firebase token does not contain phone number.'], 422);
        }

        $user = $this->findUserByPhone($phone);
        if (! $user) {
            $user = new User();
            $user->mobile = $phone;
            $user->name = $name ?: 'User';
            $user->email = $email ?: ($phone.'@example.test');
            $user->role = $roleHint ?: 'gp';
            $user->role_subtype = $roleHint === 'specialist' ? 'specialist' : null;
            $user->status = 'active';
            $user->password = Hash::make(str()->random(24));
            $user->terms_accepted_at = $request->boolean('terms_accepted') ? now() : now();
            $user->notification_consent_granted_at = now();
            $user->save();
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Account is not active'], 403);
        }

        if ($user->role === 'gp' && ! $user->gp()->exists()) {
            Gp::create(['user_id' => $user->id, 'status' => 'pending']);
        } elseif ($user->role === 'specialist' && ! $user->specialist()->exists()) {
            Specialist::create(['user_id' => $user->id]);
        }

        $deviceName = $request->header('User-Agent') ?: 'mobile-otp-login';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^\d+]/', '', $phone) ?? '';
        return trim($p);
    }

    private function findUserByPhone(string $phone): ?User
    {
        $user = User::query()->where('mobile', $phone)->first();
        if ($user) {
            return $user;
        }

        if (str_starts_with($phone, '+91') && strlen($phone) >= 13) {
            $last10 = substr($phone, -10);
            $user = User::query()->where('mobile', $last10)->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    // POST /api/auth/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'email' => 'nullable|email|max:190|unique:users,email',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['gp', 'specialist', 'hospital_admin'])],
            'clinic_name' => 'nullable|string|max:190',
            'address_line' => 'nullable|string|max:255',
            'terms_accepted' => 'accepted',
        ]);

        // Check for existing partial registration (user without GP/specialist record)
        $existingUser = User::where('email', $data['email'])
            ->orWhere('mobile', $data['mobile'])
            ->first();

        if ($existingUser) {
            // If user exists but has no GP/specialist record, check if it's a partial registration
            if ($existingUser->role === 'gp' && ! $existingUser->gp()->exists()) {
                // Clean up orphaned user from partial registration
                Log::info('Cleaning up partial GP registration for user ID: ' . $existingUser->id);
                $existingUser->delete();
            } elseif ($existingUser->role === 'specialist' && ! $existingUser->specialist()->exists()) {
                // Clean up orphaned user from partial registration
                Log::info('Cleaning up partial specialist registration for user ID: ' . $existingUser->id);
                $existingUser->delete();
            } else {
                // Genuine duplicate - return error
                return response()->json([
                    'message' => 'The email has already been taken.',
                    'errors' => [
                        'email' => ['The email has already been taken.']
                    ]
                ], 422);
            }
        }

        try {
            $user = DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'mobile' => $data['mobile'],
                    'role' => $data['role'],
                    'status' => 'active',
                    'password' => Hash::make($data['password']),
                    'terms_accepted_at' => $data['terms_accepted'] ? now() : null,
                    'notification_consent_granted_at' => $data['terms_accepted'] ? now() : null,
                ]);

                if ($user->role === 'gp' && ! $user->gp()->exists()) {
                    Gp::create([
                        'user_id' => $user->id,
                        'status' => 'pending',
                        'clinic_name' => $data['clinic_name'] ?? null,
                        'address_line' => $data['address_line'] ?? null,
                    ]);
                }

                if ($user->role === 'specialist' && ! $user->specialist()->exists()) {
                    Specialist::create(['user_id' => $user->id]);
                }

                return $user;
            });
        } catch (\Throwable $e) {
            Log::error('Registration failed: ' . $e->getMessage(), [
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'role' => $data['role'],
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $this->userPayload($user->loadMissing(['gp', 'specialist'])),
            'token' => $token,
        ], 201);
    }

    // POST /api/specialist/register
    public function specialistRegister(Request $request)
    {
        $settings = app(GeneralSettings::class);
        $subscriptions = app(SubscriptionService::class);
        $razorpay = app(RazorpayService::class);
        $cca = app(CcaVenuePaymentService::class);
        $paymentsEnabled = $razorpay->paymentsEnabled() || $cca->paymentsEnabled();
        $gateway = $razorpay->paymentsEnabled() ? 'razorpay' : ($cca->paymentsEnabled() ? 'ccavenue' : null);
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'email' => 'nullable|email|max:190|unique:users,email',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'password' => 'required|string|min:6',
            'role_subtype' => ['required', Rule::in(['specialist', 'hospital'])],
            'subscription_plan_id' => [$paymentsEnabled ? 'required' : 'nullable', 'integer'],
            'specialty_code' => 'nullable|string|max:120',
            'additional_specialty_ids' => 'nullable|array',
            'additional_specialty_ids.*' => 'integer|exists:specialties,id',
            'hospital_name' => 'nullable|string|max:190',
            'clinic_street' => 'nullable|string|max:255',
            'clinic_area' => 'nullable|string|max:190',
            'clinic_city' => 'nullable|string|max:100',
            'clinic_pincode' => 'nullable|string|max:12',
            'registration_no' => 'nullable|string|max:100',
            'council_name' => 'nullable|string|max:150',
            'bio' => 'nullable|string',
            'videos' => $settings->allow_profile_videos_for_specialists ? 'nullable|array' : 'prohibited',
            'videos.*' => $settings->allow_profile_videos_for_specialists ? 'nullable|url|max:500' : 'prohibited',
            'certificates' => $settings->allow_profile_certificates_for_specialists ? 'nullable|array' : 'prohibited',
            'certificates.*' => $settings->allow_profile_certificates_for_specialists ? 'file|max:10240' : 'prohibited',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'terms_accepted' => 'accepted',
        ]);

        $plan = null;
        if ($paymentsEnabled && ! empty($data['subscription_plan_id'])) {
            $planCategory = $subscriptions->categoryForRoleSubtype($data['role_subtype']);
            $plan = $subscriptions->requireSelectablePlan($data['subscription_plan_id'], (string) $planCategory);
        }

        [$user, $paymentPayload] = DB::transaction(function () use ($data, $settings, $request, $plan, $subscriptions, $paymentsEnabled, $gateway, $razorpay, $cca) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'mobile' => $data['mobile'],
                'role' => 'specialist',
                'role_subtype' => $data['role_subtype'],
                'status' => 'active',
                'password' => Hash::make($data['password']),
                'terms_accepted_at' => now(),
                'notification_consent_granted_at' => now(),
            ]);

            $specialist = Specialist::firstOrCreate(['user_id' => $user->id], []);
            if (! empty($data['specialty_code'])) {
                $specialist->specialty_id = Specialty::query()
                    ->where('code', $data['specialty_code'])
                    ->value('id');
            }
            if (array_key_exists('hospital_name', $data)) {
                $specialist->hospital_name = $data['hospital_name'];
            }
            if (array_key_exists('clinic_street', $data)) {
                $specialist->clinic_street = $data['clinic_street'];
            }
            if (array_key_exists('clinic_area', $data)) {
                $specialist->clinic_area = $data['clinic_area'];
            }
            if (array_key_exists('clinic_city', $data)) {
                $specialist->clinic_city = $data['clinic_city'];
            }
            if (array_key_exists('clinic_pincode', $data)) {
                $specialist->clinic_pincode = $data['clinic_pincode'];
            }
            if (
                array_key_exists('clinic_street', $data)
                || array_key_exists('clinic_area', $data)
                || array_key_exists('clinic_city', $data)
                || array_key_exists('clinic_pincode', $data)
            ) {
                $specialist->clinic_address = trim(implode(', ', array_filter([
                    $specialist->clinic_street,
                    $specialist->clinic_area,
                    $specialist->clinic_city,
                    $specialist->clinic_pincode,
                ])));
            }
            if (array_key_exists('registration_no', $data)) {
                $specialist->medical_council_registration_no = $data['registration_no'];
            }
            if (array_key_exists('council_name', $data)) {
                $specialist->medical_council_name = $data['council_name'];
            }
            if (array_key_exists('bio', $data)) {
                $specialist->bio = $data['bio'];
            }
            if ($settings->allow_profile_videos_for_specialists && array_key_exists('videos', $data)) {
                $specialist->videos = $data['videos'];
            }
            if ($request->hasFile('profile_photo')) {
                if ($specialist->profile_photo_path) {
                    Storage::disk('public')->delete($specialist->profile_photo_path);
                }
                $specialist->profile_photo_path = $request->file('profile_photo')
                    ->store('specialists/'.$user->id, 'public');
            }
            if ($settings->allow_profile_certificates_for_specialists && $request->hasFile('certificates')) {
                $existing = is_array($specialist->certificates) ? $specialist->certificates : [];
                $uploaded = [];
                foreach ((array) $request->file('certificates') as $file) {
                    if (! $file) {
                        continue;
                    }
                    $uploaded[] = $file->store('specialists/'.$user->id.'/certificates', 'public');
                }
                $specialist->certificates = array_values(array_merge($existing, $uploaded));
            }
            $specialist->save();

            if (array_key_exists('additional_specialty_ids', $data)) {
                $ids = collect($data['additional_specialty_ids'] ?? [])
                    ->map(fn ($v) => (int) $v)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $primaryId = (int) ($specialist->specialty_id ?? 0);
                $ids = array_values(array_filter($ids, fn ($id) => $primaryId <= 0 || $id !== $primaryId));
                $specialist->additionalSpecialties()->sync($ids);
            }

            $paymentPayload = null;
            if ($paymentsEnabled && $plan !== null) {
                $subscription = $subscriptions->createPendingSubscription($user, $plan, [
                    'source' => 'specialist_register',
                    'role_subtype' => $data['role_subtype'],
                ], true);

                if ($gateway === 'razorpay') {
                    $paymentPayload = $razorpay->createOrder($subscription, $user);
                } else {
                    $transaction = $cca->createTransaction($subscription, $user);
                    $paymentPayload = $cca->buildCheckoutPayload($transaction, $user);
                }
            }

            return [$user, $paymentPayload];
        });

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'user' => $this->userPayload($user->loadMissing(['gp', 'specialist'])),
            'token' => $token,
            'token_type' => 'Bearer',
            'payment' => $paymentPayload,
        ], 201);
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('mobile', $request->mobile)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json([
                'message' => 'Account is not active',
            ], 403);
        }

        $deviceName = $request->header('User-Agent') ?: 'mobile-login';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()->loadMissing(['gp', 'specialist'])),
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role,
            'role_subtype' => $user->role_subtype,
            'gp_id' => $user->gp()->value('id'),
            'specialist_id' => $user->specialist()->value('id'),
            'diagnostic_center_id' => $user->diagnosticCenter()->value('id'),
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            'terms_accepted' => $user->terms_accepted_at !== null,
            'notification_consent' => $user->notification_consent_granted_at !== null,
        ];
    }
}
