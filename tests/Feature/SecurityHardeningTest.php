<?php

namespace Tests\Feature;

use App\Models\Gp;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\CcaVenuePaymentService;
use App\Services\RazorpayService;
use App\Settings\GeneralSettings;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Tests\TestCase;

/**
 * Pins the fixes from SECURITY-REVIEW-2026-10-24.md so they cannot silently
 * regress: the anonymous mock-payment callbacks, weak password/lockout rules,
 * unrevoked tokens, the debug route, unhashed OTPs and the upload file route.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);
        $this->materialiseGeneralSettings();
    }

    // ---------------------------------------------------------------- payments

    public function test_mock_payment_callbacks_are_refused_outside_development(): void
    {
        [$subscription, $transaction] = $this->pendingCcaVenueTransaction();

        // A public host: the callback must not finalise anything.
        $this->app['env'] = 'production';

        $this->get('/payments/ccavenue/mock/'.$transaction->transaction_uuid.'/success')
            ->assertNotFound();

        $this->get('/payments/razorpay/mock/'.$transaction->transaction_uuid.'/success')
            ->assertNotFound();

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('pending', $subscription->fresh()->status);
        $this->assertSame('pending', $subscription->fresh()->payment_status);
    }

    public function test_mock_mode_setting_is_ignored_outside_development(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->razorpay_mock_mode = true;
        $settings->ccavenue_mock_mode = true;
        $settings->save();

        // Local/testing keeps the developer flow working.
        $this->assertTrue(app(RazorpayService::class)->isMockMode());

        $this->app['env'] = 'production';

        $this->assertFalse(app(RazorpayService::class)->isMockMode());
        $this->assertFalse(app(CcaVenuePaymentService::class)->isMockMode());
    }

    public function test_mock_mode_does_not_enable_payments_without_credentials_in_production(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->razorpay_mock_mode = true;
        $settings->enable_razorpay_payments = true;
        $settings->razorpay_key_id = '';
        $settings->razorpay_key_secret = '';
        $settings->save();

        $this->app['env'] = 'production';

        $this->assertFalse(app(RazorpayService::class)->paymentsEnabled());
        $this->assertFalse(app(CcaVenuePaymentService::class)->paymentsEnabled());
    }

    // ------------------------------------------------------------------- login

    public function test_login_locks_the_account_after_ten_wrong_passwords(): void
    {
        $user = $this->makeUser(['mobile' => '9888888888']);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/login', [
                'mobile' => $user->mobile,
                'password' => 'WrongGuess'.$attempt,
            ])->assertStatus(422);
        }

        // Even the correct password is refused while the lockout is active.
        $this->postJson('/api/login', [
            'mobile' => $user->mobile,
            'password' => 'Passw0rd123',
        ])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_successful_login_clears_the_failure_counter(): void
    {
        $user = $this->makeUser(['mobile' => '9888888877']);

        $this->postJson('/api/login', [
            'mobile' => $user->mobile,
            'password' => 'WrongGuess',
        ])->assertStatus(422);

        $this->postJson('/api/login', [
            'mobile' => $user->mobile,
            'password' => 'Passw0rd123',
        ])->assertOk()->assertJsonStructure(['token']);

        for ($attempt = 1; $attempt <= 9; $attempt++) {
            $this->postJson('/api/login', [
                'mobile' => $user->mobile,
                'password' => 'WrongGuess'.$attempt,
            ])->assertStatus(422);
        }

        // Counter restarted at zero, so nine more failures are still allowed.
        $this->postJson('/api/login', [
            'mobile' => $user->mobile,
            'password' => 'Passw0rd123',
        ])->assertOk();
    }

    // --------------------------------------------------------------- passwords

    public function test_registration_rejects_a_six_character_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Weak Password GP',
            'email' => 'weak-password@example.test',
            'mobile' => '9777777777',
            'password' => '123456',
            'role' => 'gp',
            'terms_accepted' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_changing_the_password_revokes_other_sessions(): void
    {
        $user = $this->makeUser(['mobile' => '9666666666']);
        Gp::create(['user_id' => $user->id, 'status' => 'approved']);

        $currentToken = $user->createToken('phone-current')->plainTextToken;
        $user->createToken('phone-stolen');

        $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->postJson('/api/gp/profile/change-password', [
                'current_password' => 'Passw0rd123',
                'new_password' => 'NewPassw0rd1',
                'new_password_confirmation' => 'NewPassw0rd1',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'phone-stolen']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'phone-current']);
    }

    // --------------------------------------------------------------------- OTP

    public function test_otp_verification_locks_after_five_wrong_codes(): void
    {
        $mobile = '9876543210';

        Cache::put(
            'register_otp_919876543210',
            ['mode' => 'local', 'otp' => hash('sha256', '123456'), 'name' => 'OTP User'],
            now()->addMinutes(10),
        );

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/register/verify-otp', [
                'mobile' => $mobile,
                'otp' => '000000',
                'password' => 'Passw0rd123',
            ])->assertStatus(422);
        }

        // The challenge is burned: even the correct code is refused now.
        $this->postJson('/api/register/verify-otp', [
            'mobile' => $mobile,
            'otp' => '123456',
            'password' => 'Passw0rd123',
        ])->assertStatus(429);
    }

    public function test_debug_otp_is_not_returned_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $response = $this->postJson('/api/auth/send-otp', ['mobile' => '9876500001'])
            ->assertOk();

        $this->assertArrayNotHasKey('otp_debug', $response->json());
    }

    // ------------------------------------------------------------------ routes

    public function test_debug_token_route_no_longer_exists(): void
    {
        $this->getJson('/api/debug-token')->assertNotFound();
    }

    public function test_security_headers_are_attached_to_api_responses(): void
    {
        $response = $this->getJson('/api/ping')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeaderMissing('X-Powered-By');
    }

    // ------------------------------------------------------------ device tokens

    public function test_a_push_token_cannot_be_claimed_by_another_account(): void
    {
        $owner = $this->makeUser(['mobile' => '9555555501']);
        $attacker = $this->makeUser(['mobile' => '9555555502']);

        $owner->deviceTokens()->create([
            'token' => 'fcm-token-owned-by-someone-else',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $attackerToken = $attacker->createToken('phone')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-owned-by-someone-else',
                'platform' => 'android',
            ])
            ->assertStatus(409);

        $this->assertDatabaseHas('device_tokens', [
            'token' => 'fcm-token-owned-by-someone-else',
            'user_id' => $owner->id,
        ]);
    }

    // ------------------------------------------------------------ upload types

    public function test_upload_allow_list_cannot_be_widened_to_active_content(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->allowed_file_types = ['pdf', 'svg', 'html', 'php', 'png'];
        $settings->save();

        $allowed = app(GeneralSettings::class)->allowedMimeTypes();

        $this->assertContains('pdf', $allowed);
        $this->assertContains('png', $allowed);
        $this->assertNotContains('svg', $allowed);
        $this->assertNotContains('html', $allowed);
        $this->assertNotContains('php', $allowed);
    }

    // ----------------------------------------------------------------- storage

    public function test_storage_route_only_serves_passive_files_inside_the_upload_root(): void
    {
        Storage::disk('public')->put('profiles/security-test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8AAAwAB/AF+9WQAAAAASUVORK5CYII='
        ));
        Storage::disk('public')->put(
            'profiles/security-test.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        );

        try {
            $this->get('/storage/profiles/security-test.png')
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png')
                ->assertHeader('X-Content-Type-Options', 'nosniff');

            // Active content must never be served inline from our own origin.
            $this->get('/storage/profiles/security-test.svg')->assertNotFound();
            $this->get('/storage/.env')->assertNotFound();
            $this->get('/storage/profiles/../../.env')->assertNotFound();
        } finally {
            Storage::disk('public')->delete(['profiles/security-test.png', 'profiles/security-test.svg']);
        }
    }

    // ---------------------------------------------------------------- fixtures

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Security Test GP',
            'email' => 'security-'.Str::random(8).'@example.test',
            'mobile' => '900000'.random_int(1000, 9999),
            'role' => 'gp',
            'status' => 'active',
            'password' => 'Passw0rd123',
        ], $overrides));
    }

    /** @return array{0: UserSubscription, 1: SubscriptionTransaction} */
    private function pendingCcaVenueTransaction(): array
    {
        $user = $this->makeUser();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::query()->value('id'),
            'category_snapshot' => 'gp',
            'plan_name_snapshot' => 'Security Test Plan',
            'plan_family_snapshot' => 'premium',
            'duration_months_snapshot' => 1,
            'price_snapshot' => 999,
            'currency_snapshot' => 'INR',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $transaction = SubscriptionTransaction::create([
            'user_subscription_id' => $subscription->id,
            'gateway' => 'ccavenue',
            'transaction_uuid' => (string) Str::uuid(),
            'order_id' => 'SEC-TEST-'.Str::upper(Str::random(8)),
            'amount' => 999,
            'currency' => 'INR',
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        return [$subscription, $transaction];
    }

    private function materialiseGeneralSettings(): void
    {
        $store = app(GeneralSettings::class);
        $group = $store::group();
        $ref = new ReflectionClass($store);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            SettingsProperty::query()->updateOrCreate(
                ['group' => $group, 'name' => $property->getName()],
                ['payload' => json_encode($store->{$property->getName()}), 'locked' => false],
            );
        }

        $store->refresh();
    }
}
