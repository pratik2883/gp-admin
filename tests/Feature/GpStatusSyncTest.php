<?php

namespace Tests\Feature;

use App\Models\Gp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingGp(): array
    {
        $user = User::create([
            'name' => 'Pratik Somwanshi',
            'email' => 'ps00055@gmail.com',
            'mobile' => '9892711228',
            'role' => 'gp',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);
        $gp = Gp::create(['user_id' => $user->id, 'status' => 'pending']);

        return [$user, $gp];
    }

    public function test_approving_via_gp_signups_keeps_user_status_active(): void
    {
        [$user, $gp] = $this->makePendingGp();

        $gp->update(['status' => 'approved']);
        $user->update(['status' => 'active']);

        $this->assertSame('approved', $gp->fresh()->status);
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_blocking_user_from_users_tab_syncs_gp_approval_status(): void
    {
        [$user, $gp] = $this->makePendingGp();

        $user->update(['status' => 'blocked']);
        $this->assertSame('blocked', $gp->fresh()->status);

        $user->update(['status' => 'active']);
        $this->assertSame('approved', $gp->fresh()->status);
    }

    public function test_approving_gp_then_blocking_syncs_both_ways(): void
    {
        [$user, $gp] = $this->makePendingGp();

        $gp->update(['status' => 'approved']);
        $user->update(['status' => 'active']);

        $gp->update(['status' => 'blocked']);
        $user->update(['status' => 'blocked']);

        $this->assertSame('blocked', $gp->fresh()->status);
        $this->assertSame('blocked', $user->fresh()->status);
    }

    public function test_non_status_update_does_not_touch_gp_status(): void
    {
        [$user, $gp] = $this->makePendingGp();

        $user->update(['name' => 'Dr. Test Updated']);

        $this->assertSame('pending', $gp->fresh()->status);
    }
}
