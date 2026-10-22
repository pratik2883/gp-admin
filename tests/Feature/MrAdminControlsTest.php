<?php

namespace Tests\Feature;

use App\Models\Mr;
use App\Models\MrVisit;
use App\Models\User;
use App\Settings\MrSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The admin panel is the control surface for the whole MR app: kill switch,
 * account blocking, shift timings, allowed doctor categories, daily visit cap,
 * GPS/selfie demands and payment links. A setting the API ignores is not a
 * control, so each switch is verified through a real request.
 */
class MrAdminControlsTest extends TestCase
{
    use RefreshDatabase;

    private function mr(string $mobile = '9000000100', string $status = 'active'): User
    {
        $user = User::create([
            'name' => 'MR Controlled',
            'email' => $mobile.'@example.test',
            'mobile' => $mobile,
            'role' => 'mr',
            'status' => $status,
            'password' => bcrypt('secret123'),
        ]);

        Mr::create([
            'user_id' => $user->id,
            'employee_code' => 'MR-C-'.substr($mobile, -4),
            'territory_zone' => 'Mumbai & Thane',
            'daily_visit_target' => 10,
            'monthly_subscription_target' => 5,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    /** Writes the admin-controlled values the way the settings page does. */
    private function setMrSettings(array $values): void
    {
        foreach ($values as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['group' => 'mr', 'name' => $name],
                [
                    'payload' => json_encode($value),
                    'locked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        app(MrSettings::class)->refresh();
    }

    public function test_mr_app_kill_switch_blocks_every_mr_account(): void
    {
        $this->mr();
        $this->setMrSettings(['mr_app_enabled' => false]);

        $response = $this->getJson('/api/mr/dashboard');

        $response->assertStatus(403);
        $this->assertStringContainsString('disabled', $response->json('message'));
    }

    public function test_blocked_login_is_refused(): void
    {
        $this->mr(status: 'blocked');

        $this->getJson('/api/mr/dashboard')
            ->assertStatus(403)
            ->assertJson(['message' => 'This account is blocked.']);
    }

    public function test_inactive_mr_profile_is_refused(): void
    {
        $user = $this->mr();
        Mr::where('user_id', $user->id)->update(['status' => 'inactive']);

        $this->getJson('/api/mr/dashboard')
            ->assertStatus(403)
            ->assertJson(['message' => 'This MR profile is inactive. Please contact the admin.']);
    }

    public function test_shift_timings_come_from_the_admin_settings(): void
    {
        $this->mr();
        $this->setMrSettings([
            'shift_start' => '10:15',
            'shift_end' => '19:45',
            'attendance_popup_enabled' => false,
        ]);

        $response = $this->getJson('/api/mr/profile')->assertOk();

        $this->assertSame('10:15 AM', $response->json('shift_settings.shift_start'));
        $this->assertSame('07:45 PM', $response->json('shift_settings.shift_end'));
        $this->assertFalse($response->json('shift_settings.attendance_popup_enabled'));
    }

    public function test_disabled_doctor_category_cannot_be_onboarded(): void
    {
        $this->mr();
        $this->setMrSettings(['allow_onboard_hospital' => false]);

        $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'hospital',
            'name' => 'Blocked Hospital',
            'mobile' => '9000000200',
        ])->assertStatus(422)->assertJsonValidationErrors('role');

        // The categories that are still on keep working.
        $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'gp',
            'name' => 'Dr. Allowed',
            'mobile' => '9000000201',
            'clinic_name' => 'Allowed Clinic',
        ])->assertOk();
    }

    public function test_payment_link_switch_is_enforced(): void
    {
        $this->mr();
        $this->setMrSettings(['allow_payment_link' => false]);

        $this->postJson('/api/mr/payment-link', [
            'doctor_user_id' => 1,
            'plan_id' => 1,
        ])->assertStatus(403);
    }

    public function test_geotag_requirement_on_visit_is_enforced(): void
    {
        $this->mr();
        $this->setMrSettings(['geotag_required_on_visit' => true]);

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'gp',
            'doctor_name' => 'Dr. No GPS',
            'visit_purpose' => 'pitching',
        ])->assertStatus(422);

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'gp',
            'doctor_name' => 'Dr. With GPS',
            'visit_purpose' => 'pitching',
            'latitude' => 19.07,
            'longitude' => 72.87,
        ])->assertOk();
    }

    public function test_daily_visit_cap_is_enforced(): void
    {
        $this->mr();
        $this->setMrSettings(['max_visits_per_day' => 1]);

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'gp',
            'doctor_name' => 'First',
            'visit_purpose' => 'pitching',
        ])->assertOk();

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'gp',
            'doctor_name' => 'Second',
            'visit_purpose' => 'pitching',
        ])->assertStatus(422);

        $this->assertSame(1, MrVisit::count());
    }

    public function test_selfie_requirement_on_check_in_is_enforced(): void
    {
        $this->mr();
        $this->setMrSettings(['selfie_required_on_check_in' => true]);

        $this->postJson('/api/mr/attendance/check-in', [])->assertStatus(422);
    }

    public function test_mr_settings_page_renders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'mobile' => '9876500010',
        ]);

        $this->actingAs($admin)
            ->get('/admin/mr-settings')
            ->assertStatus(200)
            ->assertSee('MR App Settings');
    }

    public function test_admin_forms_open_and_admin_can_log_a_visit_for_an_mr(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'mobile' => '9876500012',
        ]);
        $mr = $this->mr();

        $this->actingAs($admin);

        foreach ([
            \App\Filament\Resources\MrVisits\Pages\ManageMrVisits::class,
            \App\Filament\Resources\MrLeaves\Pages\ManageMrLeaves::class,
            \App\Filament\Resources\MrAttendances\Pages\ManageMrAttendances::class,
            \App\Filament\Resources\MrAssignments\Pages\ManageMrAssignments::class,
            \App\Filament\Resources\MrCollaterals\Pages\ManageCollaterals::class,
        ] as $page) {
            \Livewire\Livewire::test($page)->mountAction('create')->assertSuccessful();
        }

        \Livewire\Livewire::test(\App\Filament\Resources\MrVisits\Pages\ManageMrVisits::class)
            ->callAction('create', [
                'mr_user_id' => $mr->id,
                'visit_date' => now()->toDateString(),
                'doctor_type' => 'gp',
                'doctor_name' => 'Dr. Admin Entry',
                'visit_purpose' => 'feedback',
            ])
            ->assertHasNoErrors();

        $visit = MrVisit::where('doctor_name', 'Dr. Admin Entry')->firstOrFail();
        $this->assertSame($mr->id, (int) $visit->mr_user_id);
        $this->assertSame('feedback', $visit->visit_purpose);
    }

    public function test_saving_the_settings_page_persists_and_takes_effect(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'mobile' => '9876500011',
        ]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Pages\Settings\MrSettingsPage::class)
            ->set('data.mr_app_enabled', false)
            ->set('data.shift_start', '10:00')
            ->set('data.allow_onboard_hospital', false)
            ->call('save')
            ->assertHasNoErrors();

        $store = app(MrSettings::class)->refresh();
        $this->assertFalse($store->mr_app_enabled);
        $this->assertStringStartsWith('10:00', (string) $store->shift_start);
        $this->assertFalse($store->allow_onboard_hospital);

        // And the saved switch is what the API obeys.
        $this->mr();
        $this->getJson('/api/mr/dashboard')->assertStatus(403);
    }
}
