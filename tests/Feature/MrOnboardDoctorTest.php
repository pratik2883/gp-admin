<?php

namespace Tests\Feature;

use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The MR "Instant Doctor Onboarding" form collects a different set of details
 * per role. These tests pin the contract between the MR app payload and the
 * doctor tables, because the previous implementation dropped every GP field
 * (mass-assignment), stored an empty name for diagnostic centers, and 500'd
 * when onboarding one at all (`users.role` enum has no diagnostic_center).
 */
class MrOnboardDoctorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsMr(): User
    {
        // Must be `role=mr`: the /api/mr/* group is behind the `ensure.mr`
        // guard (see MrAccessControlTest), so a non-MR account now gets a 403.
        $mr = User::create([
            'name' => 'MR Field Executive',
            'email' => 'mr.executive@example.test',
            'mobile' => '9000000000',
            'role' => 'mr',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        Sanctum::actingAs($mr);

        return $mr;
    }

    /** GD is not installed in every dev environment, so build a fake upload. */
    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 16, 'image/jpeg');
    }

    public function test_gp_onboarding_persists_every_role_specific_field(): void
    {
        Storage::fake('public');
        $mr = $this->actingAsMr();

        $response = $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'gp',
            'name' => 'Dr. Rajesh Sharma',
            'mobile' => '9000000001',
            'email' => 'rajesh.sharma@example.test',
            'degree' => 'MBBS, MD (General Medicine)',
            'clinic_name' => 'Sharma Health Care Clinic',
            'address' => 'Shop 12, Station Road, Thane West',
            'registration_number' => 'MMC-12345',
            'council_name' => 'Maharashtra Medical Council',
            'latitude' => 19.2183,
            'longitude' => 72.9781,
            'profile_photo' => $this->fakeImage('profile.jpg'),
            'clinic_photo' => $this->fakeImage('clinic.jpg'),
            'visiting_card' => $this->fakeImage('card.jpg'),
        ]);

        $response->assertOk();

        $user = User::where('mobile', '9000000001')->firstOrFail();
        $this->assertSame('gp', $user->role);

        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Sharma Health Care Clinic', $gp->clinic_name);
        $this->assertSame('Shop 12, Station Road, Thane West', $gp->address_line);
        $this->assertSame('MBBS, MD (General Medicine)', $gp->degree);
        $this->assertSame('MMC-12345', $gp->registration_number);
        $this->assertSame('Maharashtra Medical Council', $gp->registration_council);
        $this->assertNotNull($gp->profile_photo_path);
        $this->assertNotNull($gp->clinic_photo_path);
        $this->assertNotNull($gp->visiting_card_path);
        $this->assertEqualsWithDelta(19.2183, (float) $gp->latitude, 0.0000001);
        $this->assertSame($mr->id, $gp->onboarded_by_mr_id);
    }

    public function test_specialist_onboarding_persists_specialty_and_council_details(): void
    {
        Storage::fake('public');
        $this->actingAsMr();

        $specialty = Specialty::create([
            'name' => 'Cardiology',
            'code' => 'cardiology',
            'plain_label' => 'Cardiologist',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'specialist',
            'name' => 'Dr. Meera Iyer',
            'mobile' => '9000000002',
            'degree' => 'MBBS, DNB (Cardiology)',
            'specialty_id' => $specialty->id,
            'hospital_name' => 'Iyer Heart Institute',
            'clinic_name' => 'Iyer Heart Institute',
            'address' => '4th Floor, Palm Court, Thane',
            'whatsapp_number' => '9000000002',
            'registration_number' => 'MMC-98765',
            'council_name' => 'Maharashtra Medical Council',
            'years_of_experience' => 12,
            'bio' => 'Interventional cardiologist with 12 years of practice.',
            'latitude' => 19.2001,
            'longitude' => 72.9700,
        ]);

        $response->assertOk();

        $user = User::where('mobile', '9000000002')->firstOrFail();
        $this->assertSame('specialist', $user->role);

        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($specialty->id, (int) $specialist->specialty_id);
        $this->assertSame('Iyer Heart Institute', $specialist->hospital_name);
        $this->assertSame('4th Floor, Palm Court, Thane', $specialist->clinic_address);
        $this->assertSame('MMC-98765', $specialist->medical_council_registration_no);
        $this->assertSame('Maharashtra Medical Council', $specialist->medical_council_name);
        $this->assertSame(12, (int) $specialist->years_of_experience);
        $this->assertSame('9000000002', $specialist->whatsapp_number);
        $this->assertSame(['degree' => 'MBBS, DNB (Cardiology)'], $specialist->education_primary);
        $this->assertNotNull($specialist->bio);
    }

    public function test_diagnostic_center_onboarding_succeeds_without_a_location(): void
    {
        Storage::fake('public');
        $this->actingAsMr();

        $response = $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'diagnostic_center',
            'name' => 'Shree Diagnostics',
            'center_name' => 'Shree Diagnostics',
            'center_type' => 'Pathology Lab',
            'mobile' => '9000000003',
            'email' => 'front.desk@shreediagnostics.test',
            'address' => 'Ground Floor, Shree Complex, Thane',
            'micro_area' => 'Naupada',
            'alternate_number' => '9000000004',
            'authorized_person_name' => 'Sunil Patil',
            'authorized_person_role' => 'Owner',
        ]);

        // Before the fix this returned HTTP 500: `users.role` has no
        // diagnostic_center enum value and `location_id` was NOT NULL.
        $response->assertOk();

        $user = User::where('mobile', '9000000003')->firstOrFail();
        $this->assertSame('specialist', $user->role);
        $this->assertSame('diagnostic_center', $user->role_subtype);
        $this->assertTrue($user->diagnosticCenter()->exists());

        $center = DiagnosticCenter::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Shree Diagnostics', $center->name);
        $this->assertSame('Pathology Lab', $center->center_type);
        $this->assertSame('Naupada', $center->micro_area);
        $this->assertSame('9000000003', $center->mobile_number);
        $this->assertSame('Sunil Patil', $center->authorized_person_name);
        $this->assertNull($center->location_id);
    }

    public function test_onboarding_rejects_duplicate_mobile_numbers(): void
    {
        $this->actingAsMr();

        User::create([
            'name' => 'Existing Doctor',
            'email' => 'existing@example.test',
            'mobile' => '9000000005',
            'role' => 'gp',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/api/mr/onboard-doctor', [
            'role' => 'gp',
            'name' => 'Duplicate Doctor',
            'mobile' => '9000000005',
        ])->assertStatus(422)->assertJsonValidationErrors('mobile');
    }
}
