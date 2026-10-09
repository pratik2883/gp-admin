<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Models\User;
use App\Services\CcaVenuePaymentService;
use App\Services\FirebaseIdTokenVerifier;
use App\Services\MessageCentralSmsService;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use App\Settings\GeneralSettings;
use App\Settings\OtpSettings;
use App\Support\AuthPayloadCache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
            'firebase_id_token' => ['nullable', 'string'],
            'verification_id' => ['nullable', 'string'],
            'otp_code' => ['nullable', 'string', 'digits_between:4,6'],
            'mobile' => ['nullable', 'string', 'max:20'],
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

        $roleHint = $request->input('role_hint');
        $firebaseToken = (string) $request->input('firebase_id_token', '');
        $name = null;
        $email = null;

        if ($firebaseToken !== '') {
            if (! is_string($otpSettings->firebase_project_id) || $otpSettings->firebase_project_id === '') {
                return response()->json(['message' => 'Firebase Project ID is not configured.'], 422);
            }

            try {
                $claims = app(FirebaseIdTokenVerifier::class)->verify($firebaseToken, (string) $otpSettings->firebase_project_id);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $phone = $this->normalizePhone((string) ($claims['phone_number'] ?? ''));
            $name = is_string($claims['name'] ?? null) ? (string) $claims['name'] : null;
            $email = is_string($claims['email'] ?? null) ? (string) $claims['email'] : null;

            if ($phone === '') {
                return response()->json(['message' => 'Firebase token does not contain phone number.'], 422);
            }
        } else {
            $phone = OtpAuthController::canonicalize((string) $request->input('mobile', ''));
            if ($phone === '') {
                return response()->json(['message' => 'Mobile number is required for OTP login.'], 422);
            }

            $verificationId = (string) $request->input('verification_id', '');
            $otpCode = (string) $request->input('otp_code', '');

            $cached = cache()->get('login_otp_'.$phone);
            if (! is_array($cached)) {
                return response()->json(['message' => 'Invalid or expired OTP.'], 422);
            }

            if (($cached['mode'] ?? '') === 'local') {
                if ((string) ($cached['otp'] ?? '') !== $otpCode) {
                    return response()->json(['message' => 'Invalid or expired OTP.'], 422);
                }
            } else {
                if ((string) ($cached['verification_id'] ?? '') !== $verificationId) {
                    return response()->json(['message' => 'Invalid or expired OTP.'], 422);
                }

                $otpResponse = app(MessageCentralSmsService::class)->validateOtp($verificationId, $otpCode);
                $status = (string) data_get($otpResponse, 'data.verificationStatus', '');

                if ($status !== 'VERIFICATION_COMPLETED') {
                    $code = (int) (data_get($otpResponse, 'data.code') ?? data_get($otpResponse, 'code') ?? 0);

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
            }

            cache()->forget('login_otp_'.$phone);
        }

        $user = $this->findUserByPhone($phone);
        if (! $user) {
            $user = new User;
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
                Log::info('Cleaning up partial GP registration for user ID: '.$existingUser->id);
                $existingUser->delete();
            } elseif ($existingUser->role === 'specialist' && ! $existingUser->specialist()->exists()) {
                // Clean up orphaned user from partial registration
                Log::info('Cleaning up partial specialist registration for user ID: '.$existingUser->id);
                $existingUser->delete();
            } else {
                // Genuine duplicate - return error
                return response()->json([
                    'message' => 'The email has already been taken.',
                    'errors' => [
                        'email' => ['The email has already been taken.'],
                    ],
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
            Log::error('Registration failed: '.$e->getMessage(), [
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
            'user' => $this->userPayload($user),
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
            'whatsapp_number' => 'nullable|string|max:20',
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
            'profile_photo_base64' => 'nullable|string',
            'profile_photo_mime' => 'nullable|string|max:30',
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
            $specialist->whatsapp_number = !empty($data['whatsapp_number']) ? $data['whatsapp_number'] : $data['mobile'];
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
                $file = $request->file('profile_photo');
                $specialist->deleteProfilePhotoFiles();
                $specialist->profile_photo_path = $specialist->storeProfilePhoto(
                    (string) file_get_contents($file->getRealPath()),
                    $file->getClientOriginalExtension() ?: 'jpg'
                );
            } elseif (! empty($data['profile_photo_base64'])) {
                $raw = $data['profile_photo_base64'];
                $mime = $data['profile_photo_mime'] ?? 'image/jpeg';
                if (str_starts_with($raw, 'data:')) {
                    $parts = explode(';', $raw, 2);
                    if (count($parts) === 2 && str_starts_with($parts[0], 'data:')) {
                        $mime = substr($parts[0], 5);
                        $raw = $parts[1];
                    }
                    if (str_starts_with($raw, 'base64,')) {
                        $raw = substr($raw, 7);
                    }
                }
                $decoded = base64_decode($raw, true);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $ext = match($mime) {
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };
                    $specialist->deleteProfilePhotoFiles();
                    $specialist->profile_photo_path = $specialist->storeProfilePhoto($decoded, $ext);
                }
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
            } elseif ($settings->allow_profile_certificates_for_specialists && ! empty($data['certificates_base64']) && is_array($data['certificates_base64'])) {
                $existing = is_array($specialist->certificates) ? $specialist->certificates : [];
                $uploaded = [];
                foreach ($data['certificates_base64'] as $cert) {
                    if (empty($cert['data']) || empty($cert['mime'])) {
                        continue;
                    }
                    $decoded = base64_decode($cert['data'], true);
                    if ($decoded === false || strlen($decoded) === 0) {
                        continue;
                    }
                    $ext = match($cert['mime']) {
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };
                    $filename = 'cert_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                    $publicDir = public_path('specialists/'.$user->id.'/certificates');
                    if (! is_dir($publicDir)) {
                        mkdir($publicDir, 0755, true);
                    }
                    $storedPath = 'specialists/'.$user->id.'/certificates/'.$filename;
                    file_put_contents($publicDir.'/'.$filename, $decoded);
                    $uploaded[] = $storedPath;
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
            'user' => $this->userPayload($user),
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
            'user' => $this->cachedUserPayload((int) $request->user()->getKey()),
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
        return $this->buildUserPayload((int) $user->getKey());
    }

    /**
     * Memoised payload for the hot `/api/auth/me` read.
     *
     * Cache *tags* are intentionally not used: the configured store (`file`) does
     * not support taggable keys, so invalidation is driven by model events on the
     * rows this payload aggregates (see AppServiceProvider).
     */
    private function cachedUserPayload(int $userId): array
    {
        $ttl = (int) config('auth.payload_cache_ttl', 60);

        if ($ttl <= 0) {
            return $this->buildUserPayload($userId);
        }

        return Cache::remember(
            AuthPayloadCache::key($userId),
            now()->addSeconds($ttl),
            fn (): array => $this->buildUserPayload($userId)
        );
    }

    /**
     * Build the user payload from a single joined SELECT.
     *
     * Replaces the previous implementation, which issued a separate query for the
     * user row, the GP id, the GP status, the specialist id, the diagnostic centre
     * id, the latest subscription and its latest transaction.
     */
    private function buildUserPayload(int $userId): array
    {
        $row = DB::selectOne(<<<'SQL'
            select
                u.id as id,
                u.name as name,
                u.email as email,
                u.mobile as mobile,
                u.role as role,
                u.role_subtype as role_subtype,
                u.terms_accepted_at as terms_accepted_at,
                u.notification_consent_granted_at as notification_consent_granted_at,
                -- gps.user_id / specialists.user_id are not unique at the DB level,
                -- so these are scalar subqueries: they cannot multiply rows and pick
                -- the same "first row" the previous `hasOne()->value()` call did.
                (select g.id from gps g where g.user_id = u.id order by g.id limit 1) as gp_id,
                (select g.status from gps g where g.user_id = u.id order by g.id limit 1) as gp_status,
                (select s.id from specialists s where s.user_id = u.id order by s.id limit 1) as specialist_id,
                dc.id as diagnostic_center_id,
                us.id as sub_id,
                us.subscription_plan_id as sub_plan_id,
                us.plan_name_snapshot as sub_plan_name,
                us.category_snapshot as sub_category,
                us.plan_family_snapshot as sub_plan_family,
                us.bed_slab_snapshot as sub_bed_slab,
                us.duration_months_snapshot as sub_duration_months,
                us.price_snapshot as sub_price,
                us.currency_snapshot as sub_currency,
                us.status as sub_status,
                us.payment_status as sub_payment_status,
                us.starts_at as sub_starts_at,
                us.ends_at as sub_ends_at,
                us.trial_ends_at as sub_trial_ends_at,
                (
                    select st.transaction_uuid
                    from subscription_transactions st
                    where st.user_subscription_id = us.id
                    order by st.id desc
                    limit 1
                ) as sub_transaction_uuid
            from users u
            left join diagnostic_centers dc on dc.user_id = u.id
            left join user_subscriptions us on us.id = (
                select max(us2.id) from user_subscriptions us2 where us2.user_id = u.id
            )
            where u.id = ?
            SQL, [$userId]);

        if ($row === null) {
            return [];
        }

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'mobile' => $row->mobile,
            'role' => $row->role,
            'role_subtype' => $row->role_subtype,
            'gp_id' => $row->gp_id === null ? null : (int) $row->gp_id,
            'gp_status' => $row->gp_status,
            'specialist_id' => $row->specialist_id === null ? null : (int) $row->specialist_id,
            'diagnostic_center_id' => $row->diagnostic_center_id === null ? null : (int) $row->diagnostic_center_id,
            'subscription' => $this->subscriptionPayload($row),
            'terms_accepted' => $row->terms_accepted_at !== null,
            'notification_consent' => $row->notification_consent_granted_at !== null,
        ];
    }

    /**
     * Mirrors SubscriptionService::latestSummaryFor() key-for-key and value-for-value.
     */
    private function subscriptionPayload(object $row): ?array
    {
        if ($row->sub_id === null) {
            return null;
        }

        $timezone = (string) config('app.timezone');

        $startsAt = $row->sub_starts_at !== null ? Carbon::parse($row->sub_starts_at, $timezone) : null;
        $endsAt = $row->sub_ends_at !== null ? Carbon::parse($row->sub_ends_at, $timezone) : null;
        $trialEndsAt = $row->sub_trial_ends_at !== null ? Carbon::parse($row->sub_trial_ends_at, $timezone) : null;

        return [
            'id' => (int) $row->sub_id,
            'plan_id' => $row->sub_plan_id === null ? null : (int) $row->sub_plan_id,
            'plan_name' => $row->sub_plan_name,
            'category' => $row->sub_category,
            'plan_family' => $row->sub_plan_family,
            'bed_slab' => $row->sub_bed_slab,
            'duration_months' => (int) $row->sub_duration_months,
            'price' => (float) $row->sub_price,
            'currency' => $row->sub_currency,
            'status' => $row->sub_status,
            'payment_status' => $row->sub_payment_status,
            'starts_at' => $startsAt?->toIso8601String(),
            'ends_at' => $endsAt?->toIso8601String(),
            'trial_ends_at' => $trialEndsAt?->toIso8601String(),
            'is_active' => $row->sub_status === 'active' && ($endsAt === null || $endsAt->isFuture()),
            'latest_transaction_uuid' => $row->sub_transaction_uuid,
        ];
    }
}
