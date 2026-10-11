<?php

namespace Tests\Feature;

use App\Models\Mr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The `/api/mr/*` group was reachable by *any* authenticated account: a GP
 * token returned 200 from `/api/mr/dashboard`, could write DCR visits, and the
 * dashboard's `Mr::firstOrCreate` created an `mrs` row for that GP. These tests
 * pin the `ensure.mr` guard so MR data stays inside the MR field force.
 */
class MrAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => 'Test '.strtoupper($role),
            'email' => $role.'.user@example.test',
            'mobile' => '98111'.random_int(10000, 99999),
            'role' => $role,
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_mr_dashboard_is_forbidden_for_a_gp_account(): void
    {
        Sanctum::actingAs($this->makeUser('gp'));

        $this->getJson('/api/mr/dashboard')->assertStatus(403);
    }

    public function test_mr_endpoints_are_forbidden_for_non_mr_roles(): void
    {
        foreach (['gp', 'specialist', 'admin'] as $role) {
            Sanctum::actingAs($this->makeUser($role));

            $this->getJson('/api/mr/dashboard')->assertStatus(403);
            $this->getJson('/api/mr/profile')->assertStatus(403);
            $this->getJson('/api/mr/calendar')->assertStatus(403);
            $this->getJson('/api/mr/visits')->assertStatus(403);
            $this->getJson('/api/mr/non-active-gps')->assertStatus(403);
        }
    }

    public function test_non_mr_account_cannot_log_a_visit_or_get_an_mr_row_created(): void
    {
        $gp = $this->makeUser('gp');
        Sanctum::actingAs($gp);

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'gp',
            'doctor_name' => 'Probe Doctor',
            'visit_purpose' => 'pitching',
        ])->assertStatus(403);

        $this->assertSame(0, Mr::where('user_id', $gp->id)->count());
        $this->assertSame(0, Mr::count());
    }

    public function test_mr_account_reaches_the_dashboard_and_gets_an_mr_profile(): void
    {
        Sanctum::actingAs($this->makeUser('mr'));

        $this->getJson('/api/mr/dashboard')->assertOk()->assertJsonStructure([
            'mr_profile',
            'stats' => [
                'today_visits',
                'daily_target',
                'attendance_status',
            ],
        ]);

        $this->assertSame(1, Mr::count());
    }
}
