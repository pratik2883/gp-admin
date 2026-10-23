<?php

namespace Tests\Feature;

use App\Models\DiagnosticCenter;
use App\Models\DiagnosticReferral;
use App\Models\Gp;
use App\Models\Location;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Simulates several accounts using the platform at the same time — two GPs, two
 * specialists, two diagnostic centres, an MR and an admin — and pins that no
 * request ever answers with another account's data.
 *
 * This is the regression net for the multi-tenant surface: list scoping, detail
 * reads, status changes, notifications, support tickets, the cached /auth/me
 * payload and the login lockout all have to stay per-account while the other
 * users are active.
 */
class MultiUserIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $gpA;
    private User $gpB;
    private User $specialistA;
    private User $specialistB;
    private User $centerA;
    private User $centerB;
    private User $mrUser;
    private User $admin;

    private Gp $gpProfileA;
    private Gp $gpProfileB;
    private Specialist $specialistProfileA;
    private Specialist $specialistProfileB;
    private DiagnosticCenter $centerProfileA;
    private DiagnosticCenter $centerProfileB;

    private Referral $referralA;
    private Referral $referralB;
    private DiagnosticReferral $diagnosticReferralA;
    private DiagnosticReferral $diagnosticReferralB;

    protected function setUp(): void
    {
        parent::setUp();

        $location = Location::create(['name' => 'Isolation Test Area']);

        $this->gpA = $this->makeUser('gp', '9000000001');
        $this->gpB = $this->makeUser('gp', '9000000002');
        $this->specialistA = $this->makeUser('specialist', '9000000003');
        $this->specialistB = $this->makeUser('specialist', '9000000004');
        // Diagnostic centres are specialist accounts with a centre profile
        // (that is what the registration endpoint creates).
        $this->centerA = $this->makeUser('specialist', '9000000005', 'diagnostic_center');
        $this->centerB = $this->makeUser('specialist', '9000000006', 'diagnostic_center');
        $this->mrUser = $this->makeUser('mr', '9000000007');
        $this->admin = $this->makeUser('admin', '9000000008');

        $this->gpProfileA = Gp::create(['user_id' => $this->gpA->id, 'status' => 'approved']);
        $this->gpProfileB = Gp::create(['user_id' => $this->gpB->id, 'status' => 'approved']);
        $this->specialistProfileA = Specialist::create(['user_id' => $this->specialistA->id]);
        $this->specialistProfileB = Specialist::create(['user_id' => $this->specialistB->id]);

        $this->centerProfileA = DiagnosticCenter::create([
            'user_id' => $this->centerA->id,
            'location_id' => $location->id,
            'name' => 'Isolation Diagnostics A',
            'status' => 'active',
        ]);
        $this->centerProfileB = DiagnosticCenter::create([
            'user_id' => $this->centerB->id,
            'location_id' => $location->id,
            'name' => 'Isolation Diagnostics B',
            'status' => 'active',
        ]);

        $this->referralA = Referral::create([
            'lead_code' => 'ISO-A-001',
            'gp_id' => $this->gpProfileA->id,
            'specialist_id' => $this->specialistProfileA->id,
            'referral_type' => 'specialist',
            'patient_name' => 'Patient Of GP A',
            'patient_mobile' => '9111100001',
            'patient_age' => 41,
            'case_summary' => 'Belongs to GP A',
            'appointment_type' => 'opd',
            'status' => 'sent',
        ]);

        $this->referralB = Referral::create([
            'lead_code' => 'ISO-B-001',
            'gp_id' => $this->gpProfileB->id,
            'specialist_id' => $this->specialistProfileB->id,
            'referral_type' => 'specialist',
            'patient_name' => 'Patient Of GP B',
            'patient_mobile' => '9111100002',
            'patient_age' => 52,
            'case_summary' => 'Belongs to GP B',
            'appointment_type' => 'opd',
            'status' => 'sent',
        ]);

        $this->diagnosticReferralA = DiagnosticReferral::create([
            'lead_code' => 'ISO-DX-A-001',
            'gp_id' => $this->gpProfileA->id,
            'diagnostic_center_id' => $this->centerProfileA->id,
            'patient_name' => 'DX Patient A',
            'case_summary' => 'Diagnostic for centre A',
            'appointment_type' => 'opd',
            'status' => 'sent',
        ]);

        $this->diagnosticReferralB = DiagnosticReferral::create([
            'lead_code' => 'ISO-DX-B-001',
            'gp_id' => $this->gpProfileB->id,
            'diagnostic_center_id' => $this->centerProfileB->id,
            'patient_name' => 'DX Patient B',
            'case_summary' => 'Diagnostic for centre B',
            'appointment_type' => 'opd',
            'status' => 'sent',
        ]);
    }

    // --------------------------------------------------------------- identity

    public function test_the_cached_auth_payload_never_leaks_between_users(): void
    {
        $actors = [$this->gpA, $this->gpB, $this->specialistA];

        // Interleave requests the way three devices would arrive concurrently.
        for ($round = 0; $round < 5; $round++) {
            foreach ($actors as $expected) {
                $this->actingAsToken($expected)
                    ->getJson('/api/auth/me')
                    ->assertOk()
                    ->assertJsonPath('user.id', $expected->id)
                    ->assertJsonPath('user.mobile', $expected->mobile)
                    ->assertJsonPath('user.role', $expected->role);
            }
        }
    }

    // ---------------------------------------------------------------- referrals

    public function test_each_gp_only_lists_and_reads_their_own_referrals(): void
    {
        $this->actingAsToken($this->gpA)
            ->getJson('/api/gp/referrals')
            ->assertOk()
            ->assertJsonPath('data.0.lead_code', 'ISO-A-001')
            ->assertJsonMissing(['lead_code' => 'ISO-B-001']);

        $this->actingAsToken($this->gpB)
            ->getJson('/api/gp/referrals')
            ->assertOk()
            ->assertJsonPath('data.0.lead_code', 'ISO-B-001')
            ->assertJsonMissing(['lead_code' => 'ISO-A-001']);

        // Cross-tenant reads are indistinguishable from "does not exist".
        $this->actingAsToken($this->gpB)
            ->getJson('/api/gp/referrals/'.$this->referralA->id)
            ->assertNotFound();

        $this->actingAsToken($this->gpA)
            ->getJson('/api/gp/referrals/'.$this->referralB->id)
            ->assertNotFound();
    }

    public function test_a_specialist_cannot_open_or_change_another_specialists_referral(): void
    {
        $this->actingAsToken($this->specialistA)
            ->getJson('/api/specialist/referrals')
            ->assertOk()
            ->assertJsonPath('data.0.lead_code', 'ISO-A-001')
            ->assertJsonMissing(['lead_code' => 'ISO-B-001']);

        $this->actingAsToken($this->specialistB)
            ->getJson('/api/specialist/referrals')
            ->assertOk()
            ->assertJsonMissing(['lead_code' => 'ISO-A-001']);

        // Reading, accepting and closing someone else's referral all 404.
        $this->actingAsToken($this->specialistA)
            ->getJson('/api/specialist/referrals/'.$this->referralB->id)
            ->assertNotFound();

        $this->actingAsToken($this->specialistA)
            ->postJson('/api/specialist/referrals/'.$this->referralB->id.'/accept')
            ->assertNotFound();

        $this->actingAsToken($this->specialistB)
            ->postJson('/api/specialist/referrals/'.$this->referralA->id.'/close')
            ->assertNotFound();

        // …while the owner's own action still works.
        $this->actingAsToken($this->specialistA)
            ->postJson('/api/specialist/referrals/'.$this->referralA->id.'/accept')
            ->assertOk();

        $this->assertSame('accepted', $this->referralA->fresh()->status);
        $this->assertSame('sent', $this->referralB->fresh()->status);
    }

    public function test_diagnostic_centres_only_see_their_own_referrals(): void
    {
        $this->actingAsToken($this->centerA)
            ->getJson('/api/diagnostic/referrals')
            ->assertOk()
            ->assertJsonMissing(['lead_code' => 'ISO-DX-B-001']);

        $this->actingAsToken($this->centerA)
            ->getJson('/api/diagnostic/referrals/'.$this->diagnosticReferralB->id)
            ->assertNotFound();

        $this->actingAsToken($this->centerB)
            ->patchJson('/api/diagnostic/referrals/'.$this->diagnosticReferralA->id.'/status', [
                'status' => 'closed',
            ])
            ->assertNotFound();

        $this->assertSame('sent', $this->diagnosticReferralA->fresh()->status);
    }

    // ----------------------------------------------------- notifications/tickets

    public function test_notifications_are_private_to_the_recipient(): void
    {
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\ReferralReceivedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->gpA->id,
            'data' => json_encode(['title' => 'For GP A only']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsToken($this->gpA)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAsToken($this->gpB)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAsToken($this->gpB)
            ->postJson('/api/notifications/'.$notificationId.'/mark-read')
            ->assertNotFound();

        $this->actingAsToken($this->gpA)
            ->postJson('/api/notifications/'.$notificationId.'/mark-read')
            ->assertOk();
    }

    public function test_support_tickets_are_private_to_the_reporter(): void
    {
        $this->actingAsToken($this->gpA)
            ->postJson('/api/support-tickets', [
                'subject' => 'GP A private ticket',
                'priority' => 'medium',
                'message' => 'Only GP A should read this.',
            ])
            ->assertCreated();

        $ticketId = \App\Models\SupportTicket::query()->latest('id')->value('id');

        $this->actingAsToken($this->gpB)
            ->getJson('/api/support-tickets/'.$ticketId)
            ->assertNotFound();

        $this->actingAsToken($this->gpB)
            ->postJson('/api/support-tickets/'.$ticketId.'/reply', ['message' => 'Intruder'])
            ->assertNotFound();

        $this->actingAsToken($this->gpA)
            ->getJson('/api/support-tickets/'.$ticketId)
            ->assertOk()
            ->assertJsonPath('data.subject', 'GP A private ticket');
    }

    // ------------------------------------------------------------- roles/logins

    public function test_role_boundaries_hold_while_every_role_is_active(): void
    {
        $matrix = [
            [$this->gpA, '/api/gp/dashboard', 200],
            [$this->gpA, '/api/specialist/referrals', 403],
            [$this->gpA, '/api/mr/dashboard', 403],
            [$this->gpA, '/api/admin/gps', 403],
            [$this->specialistA, '/api/specialist/referrals', 200],
            [$this->specialistA, '/api/gp/dashboard', 403],
            [$this->centerA, '/api/diagnostic/referrals', 200],
            [$this->centerA, '/api/gp/dashboard', 403],
            [$this->mrUser, '/api/mr/dashboard', 200],
            [$this->mrUser, '/api/gp/dashboard', 403],
            [$this->admin, '/api/admin/gps', 200],
            [$this->admin, '/api/mr/dashboard', 403],
        ];

        foreach ($matrix as [$user, $uri, $expected]) {
            $this->actingAsToken($user)
                ->getJson($uri)
                ->assertStatus($expected);
        }
    }

    public function test_login_lockout_only_locks_the_account_that_failed(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/login', [
                'mobile' => $this->gpA->mobile,
                'password' => 'Wrong'.$attempt,
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'mobile' => $this->gpA->mobile,
            'password' => 'Passw0rd123',
        ])->assertStatus(429);

        // A different user is completely unaffected.
        $this->postJson('/api/login', [
            'mobile' => $this->gpB->mobile,
            'password' => 'Passw0rd123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_changing_one_password_does_not_sign_other_users_out(): void
    {
        $gpAToken = $this->gpA->createToken('a-phone')->plainTextToken;
        $gpAStolen = $this->gpA->createToken('a-tablet');
        $gpBToken = $this->gpB->createToken('b-phone')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$gpAToken)
            ->postJson('/api/gp/profile/change-password', [
                'current_password' => 'Passw0rd123',
                'new_password' => 'NewPassw0rd1',
                'new_password_confirmation' => 'NewPassw0rd1',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $gpAStolen->accessToken->id]);

        // GP B keeps working with their own token.
        $this->withHeader('Authorization', 'Bearer '.$gpBToken)
            ->getJson('/api/gp/referrals')
            ->assertOk();
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(string $role, string $mobile, ?string $roleSubtype = null): User
    {
        return User::create([
            'name' => ucfirst($role).' '.$mobile,
            'email' => $role.'.'.$mobile.'@example.test',
            'mobile' => $mobile,
            'role' => $role,
            'role_subtype' => $roleSubtype,
            'status' => 'active',
            'password' => 'Passw0rd123',
        ]);
    }

    /** Signs the next request in as this user with a real Sanctum token. */
    private function actingAsToken(User $user): static
    {
        // The test container keeps guards alive between calls, so the previous
        // request's user would otherwise be reused. Production resolves one
        // user per process, so this only resets the in-process simulation.
        $this->app['auth']->forgetGuards();

        $token = $user->createToken('sim-'.Str::random(6))->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
