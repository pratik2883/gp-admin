<?php

namespace Tests\Feature;

use App\Filament\Resources\MrAssignments\MrAssignmentResource;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Mr;
use App\Models\MrAssignment;
use App\Models\MrAttendance;
use App\Models\MrLeave;
use App\Models\MrVisit;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The admin panel had no view of MR work at all, and the MR module could not
 * record a hospital visit. These tests pin both: the new Hospital doctor type
 * end-to-end, the MR assignments API, and every new admin page returning 200.
 */
class MrAdminActivityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'mobile' => '9876500001',
        ]);
    }

    private function mr(string $mobile = '9000000010', string $name = 'MR Field Executive'): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $mobile.'@example.test',
            'mobile' => $mobile,
            'role' => 'mr',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        Mr::create([
            'user_id' => $user->id,
            'employee_code' => 'MR-TEST-'.substr($mobile, -4),
            'territory_zone' => 'Mumbai & Thane',
            'daily_visit_target' => 10,
            'monthly_subscription_target' => 5,
            'status' => 'active',
        ]);

        return $user;
    }

    // ---------------------------------------------------------------- pages

    public function test_every_mr_activity_admin_page_renders(): void
    {
        $admin = $this->admin();
        $mr = $this->mr();

        $visit = MrVisit::create([
            'mr_user_id' => $mr->id,
            'doctor_type' => 'gp',
            'doctor_name' => 'Dr. Test',
            'visit_date' => now()->toDateString(),
            'visit_purpose' => 'pitching',
            'latitude' => 19.07,
            'longitude' => 72.87,
        ]);

        MrAttendance::create([
            'mr_user_id' => $mr->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => 'present',
        ]);

        MrLeave::create([
            'mr_user_id' => $mr->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason' => 'Family function',
            'status' => 'pending',
        ]);

        MrAssignment::create([
            'mr_user_id' => $mr->id,
            'title' => 'Visit Dr. Patil',
            'purpose' => 'follow_up',
            'priority' => 'normal',
            'status' => 'pending',
            'lead_name' => 'Dr. Patil',
        ]);

        foreach ([
            '/admin/mr-activity',
            '/admin/mr-activity?mr='.$mr->id,
            '/admin/mr-visits',
            '/admin/mr-attendances',
            '/admin/mr-leaves',
            '/admin/mr-collaterals/collaterals',
            '/admin/mr-assignments',
            '/admin/onboarded-doctors',
        ] as $url) {
            $response = $this->actingAs($admin)->get($url);

            if ($response->status() !== 200) {
                dump($url, $response->exception?->getMessage());
            }

            $response->assertStatus(200);
        }

        // The hub lists the officer, and the drill-down shows their visits.
        $this->actingAs($admin)
            ->get('/admin/mr-activity')
            ->assertSee('MR Field Executive');

        $this->actingAs($admin)
            ->get('/admin/mr-activity?mr='.$mr->id)
            ->assertSee('Recent visits')
            ->assertSee('Dr. Test');
    }

    // ------------------------------------------------------- hospital type

    public function test_mr_can_onboard_a_hospital_and_it_lands_in_the_directory(): void
    {
        $mr = $this->mr();
        Sanctum::actingAs($mr);

        $response = $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'hospital',
            'name' => 'Dr. Sunita Rao',
            'mobile' => '9000000020',
            'email' => 'admin@raohospital.test',
            'hospital_name' => 'Rao Multispeciality Hospital',
            'hospital_type' => 'Multi-Speciality',
            'address' => 'Plot 4, Andheri West',
            'micro_area' => 'Andheri West',
            'clinic_city' => 'Mumbai',
            'clinic_pincode' => '400053',
            'clinic_state' => 'Maharashtra',
            'alternate_number' => '022-40001234',
            'authorized_person_name' => 'Sunita Rao',
            'authorized_person_role' => 'Medical Director',
        ]);

        $response->assertOk();

        $user = User::where('mobile', '9000000020')->firstOrFail();
        $this->assertSame('specialist', $user->role);
        $this->assertSame('hospital', $user->role_subtype);

        $hospital = Hospital::where('name', 'Rao Multispeciality Hospital')->firstOrFail();
        $this->assertSame('Multi-Speciality', $hospital->hospital_type);
        $this->assertSame('Andheri West', $hospital->micro_area);
        $this->assertSame('400053', $hospital->pincode);
        $this->assertSame('pending', $hospital->status);
        $this->assertSame($mr->id, (int) $hospital->onboarded_by_mr_id);

        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Rao Multispeciality Hospital', $specialist->hospital_name);
        $this->assertSame($mr->id, (int) $specialist->onboarded_by_mr_id);
        $this->assertTrue($hospital->specialists()->whereKey($specialist->id)->exists());
    }

    public function test_mr_can_log_a_hospital_visit_against_the_directory_record(): void
    {
        $mr = $this->mr();
        Sanctum::actingAs($mr);

        $hospital = Hospital::create([
            'name' => 'Rao Multispeciality Hospital',
            'city' => 'Mumbai',
            'country' => 'India',
            'status' => 'active',
        ]);

        $this->postJson('/api/mr/visits', [
            'doctor_type' => 'hospital',
            'hospital_id' => $hospital->id,
            'visit_purpose' => 'pitching',
        ])->assertOk();

        $visit = MrVisit::latest('id')->firstOrFail();
        $this->assertSame('hospital', $visit->doctor_type);
        $this->assertSame($hospital->id, (int) $visit->hospital_id);
        $this->assertSame('Rao Multispeciality Hospital', $visit->doctor_name);
    }

    // ---------------------------------------------------------- assignments

    public function test_assignments_are_scoped_to_the_signed_in_mr(): void
    {
        $mine = $this->mr('9000000030', 'MR One');
        $other = $this->mr('9000000031', 'MR Two');

        $assignment = MrAssignment::create([
            'mr_user_id' => $mine->id,
            'title' => 'Visit Dr. Patil',
            'purpose' => 'follow_up',
            'priority' => 'high',
            'status' => 'pending',
            'lead_name' => 'Dr. Patil',
            'lead_mobile' => '9000000099',
        ]);

        MrAssignment::create([
            'mr_user_id' => $other->id,
            'title' => 'Someone else work',
            'purpose' => 'follow_up',
            'priority' => 'normal',
            'status' => 'pending',
            'lead_name' => 'Dr. Other',
        ]);

        Sanctum::actingAs($mine);

        $response = $this->getJson('/api/mr/assignments');
        $response->assertOk();
        $this->assertCount(1, $response->json('assignments'));
        $this->assertSame('Dr. Patil', $response->json('assignments.0.target_name'));

        // Completing it stamps the timestamp.
        $this->patchJson('/api/mr/assignments/'.$assignment->id, ['status' => 'done'])
            ->assertOk();

        $assignment->refresh();
        $this->assertSame('done', $assignment->status);
        $this->assertNotNull($assignment->completed_at);
    }

    public function test_an_mr_cannot_update_another_mrs_assignment(): void
    {
        $mine = $this->mr('9000000040', 'MR One');
        $other = $this->mr('9000000041', 'MR Two');

        $assignment = MrAssignment::create([
            'mr_user_id' => $other->id,
            'title' => 'Not yours',
            'purpose' => 'follow_up',
            'priority' => 'normal',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($mine);

        $this->patchJson('/api/mr/assignments/'.$assignment->id, ['status' => 'done'])
            ->assertNotFound();

        $this->assertSame('pending', $assignment->fresh()->status);
    }

    public function test_assignment_payload_maps_existing_doctors_and_leads(): void
    {
        $mr = $this->mr();
        $doctor = User::create([
            'name' => 'Dr. Existing',
            'email' => 'existing.gp@example.test',
            'mobile' => '9000000050',
            'role' => 'gp',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);
        Gp::create(['user_id' => $doctor->id, 'clinic_name' => 'Existing Clinic']);

        $payload = MrAssignmentResource::payload([
            'mr_user_id' => $mr->id,
            'title' => 'Follow up on referrals',
            'purpose' => 'follow_up',
            'priority' => 'normal',
            'status' => 'pending',
            'target_mode' => 'doctor',
            'doctor_user_id' => $doctor->id,
        ], $this->admin()->id);

        $this->assertSame(User::class, $payload['assignable_type']);
        $this->assertSame($doctor->id, $payload['assignable_id']);
        $this->assertNull($payload['lead_name']);

        $leadPayload = MrAssignmentResource::payload([
            'mr_user_id' => $mr->id,
            'title' => 'Visit a lead',
            'purpose' => 'onboard',
            'priority' => 'high',
            'status' => 'done',
            'target_mode' => 'lead',
            'lead_name' => 'Dr. Prospect',
            'lead_mobile' => '9000000051',
        ]);

        $this->assertNull($leadPayload['assignable_type']);
        $this->assertSame('Dr. Prospect', $leadPayload['lead_name']);
        $this->assertNotNull($leadPayload['completed_at']);
    }
}
