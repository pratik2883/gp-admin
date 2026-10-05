<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MessageCentralSmsService;
use App\Settings\OtpSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageCentralOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'enable_otp_login' => true,
            'firebase_project_id' => null,
            'firebase_api_key' => null,
            'firebase_app_id' => null,
            'firebase_sender_id' => null,
        ] as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['group' => 'otp', 'name' => $name],
                ['payload' => json_encode($value)],
            );
        }

        $settings = app(OtpSettings::class);
        $settings->enable_otp_login = true;
        $settings->save();
    }

    private function fakeMessageCentral(string $verificationId = 'vc-test-123', string $status = 'VERIFICATION_COMPLETED'): void
    {
        $this->app->instance(MessageCentralSmsService::class, new class($verificationId, $status) extends MessageCentralSmsService
        {
            public function __construct(
                private string $verificationId,
                private string $status,
            ) {}

            public function isEnabled(): bool
            {
                return true;
            }

            public function sendOtp(string $mobileNumber, ?int $countryCode = null, ?int $otpLength = null): ?array
            {
                return ['data' => ['verificationId' => $this->verificationId]];
            }

            public function validateOtp(string $verificationId, string $code): ?array
            {
                return ['data' => ['verificationStatus' => $this->status]];
            }
        });
    }

    public function test_send_login_otp_returns_verification_id(): void
    {
        $this->fakeMessageCentral();

        $this->postJson('/api/auth/send-otp', ['mobile' => '9892711228'])
            ->assertOk()
            ->assertJson([
                'message' => 'OTP sent successfully',
                'verification_id' => 'vc-test-123',
            ]);
    }

    public function test_login_with_otp_message_central_creates_gp_and_returns_token(): void
    {
        $this->fakeMessageCentral();

        $this->postJson('/api/auth/send-otp', ['mobile' => '9892711228'])->assertOk();

        $res = $this->postJson('/api/auth/login-with-otp', [
            'mobile' => '9892711228',
            'verification_id' => 'vc-test-123',
            'otp_code' => '1234',
            'role_hint' => 'gp',
            'terms_accepted' => true,
        ])->assertOk();

        $res->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'role']]);
        $this->assertSame('gp', $res->json('user.role'));

        $user = User::where('mobile', '919892711228')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->gp()->value('status'));
    }

    public function test_login_with_otp_keeps_existing_role_subtype(): void
    {
        $this->fakeMessageCentral();

        $existing = User::create([
            'name' => 'Existing Hospital',
            'email' => 'hospital@example.test',
            'mobile' => '919892711228',
            'role' => 'specialist',
            'role_subtype' => 'hospital',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/api/auth/send-otp', ['mobile' => '9892711228'])->assertOk();

        $res = $this->postJson('/api/auth/login-with-otp', [
            'mobile' => '9892711228',
            'verification_id' => 'vc-test-123',
            'otp_code' => '1234',
            'role_hint' => 'specialist',
            'terms_accepted' => true,
        ])->assertOk();

        $this->assertSame('specialist', $res->json('user.role'));
        $this->assertSame('hospital', $existing->fresh()->role_subtype);
    }

    public function test_login_with_otp_rejects_wrong_code(): void
    {
        $this->fakeMessageCentral('vc-test-123', 'FAILED');

        $this->postJson('/api/auth/send-otp', ['mobile' => '9892711228'])->assertOk();

        $this->postJson('/api/auth/login-with-otp', [
            'mobile' => '9892711228',
            'verification_id' => 'vc-test-123',
            'otp_code' => '9999',
            'role_hint' => 'gp',
            'terms_accepted' => true,
        ])->assertStatus(422);
    }
}
