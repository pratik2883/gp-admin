<?php

namespace Tests\Unit;

use App\Http\Resources\ReferralResource;
use App\Models\Gp;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralResourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeReferral(): Referral
    {
        $gpUser = User::create([
            'name' => 'GP Test',
            'email' => 'gp-resource@example.com',
            'mobile' => '9000100001',
            'role' => 'gp',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);
        $gp = Gp::create(['user_id' => $gpUser->id, 'status' => 'approved']);

        $spUser = User::create([
            'name' => 'Specialist Test',
            'email' => 'sp-resource@example.com',
            'mobile' => '9000100002',
            'role' => 'specialist',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);
        $specialist = Specialist::create(['user_id' => $spUser->id]);

        return Referral::create([
            'lead_code' => 'SSC-TEST-1',
            'gp_id' => $gp->id,
            'specialist_id' => $specialist->id,
            'patient_name' => 'Test Patient',
            'patient_mobile' => '9876501234',
            'patient_age' => 45,
            'patient_gender' => 'male',
            'case_summary' => 'Test case summary',
            'appointment_type' => 'opd',
            'status' => 'sent',
        ]);
    }

    public function test_resource_does_not_crash_when_specialist_relation_is_not_loaded(): void
    {
        $referral = $this->makeReferral();

        $data = (new ReferralResource($referral))->resolve();

        $this->assertSame(45, $data['patient_age']);
        $this->assertNull($data['specialist']);
        $this->assertNull($data['hospital']);
        $this->assertSame('specialist', $data['referral_type']);
    }

    public function test_resource_includes_specialist_details_when_relation_is_loaded(): void
    {
        $referral = $this->makeReferral();
        $referral->load(['gp.user', 'specialist.user', 'specialist.hospitals']);

        $data = (new ReferralResource($referral))->resolve();

        $this->assertIsArray($data['specialist']);
        $this->assertSame($referral->specialist_id, $data['specialist']['id']);
        $this->assertSame('Specialist Test', $data['specialist']['name']);
        $this->assertSame(45, $data['patient_age']);
    }
}
