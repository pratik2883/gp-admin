<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMrPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_visit_mrs_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'mobile' => '9876543210',
        ]);

        $response = $this->actingAs($admin)->get('/admin/mrs');

        if ($response->status() !== 200) {
            dump($response->exception?->getMessage());
            dump($response->exception?->getTraceAsString());
        }

        $response->assertStatus(200);
    }
}
