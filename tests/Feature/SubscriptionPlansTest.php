<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\GeneralSettings;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Tests\TestCase;

class SubscriptionPlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);
        $this->bootstrapPaymentSettings();
    }

    public function test_public_subscription_plans_endpoint_returns_grouped_specialist_plans(): void
    {
        $response = $this->getJson('/api/public/subscription-plans?category=specialist');

        $response
            ->assertOk()
            ->assertJsonPath('category', 'specialist');

        $this->assertNotEmpty($response->json('plans'));
        $this->assertNotEmpty($response->json('groups'));
    }

    public function test_specialist_registration_creates_pending_subscription(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'normal')
            ->value('id');

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'Test Specialist',
            'email' => 'test-specialist@example.com',
            'mobile' => '9876543210',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ]);

        $response->assertCreated();

        $user = User::query()->where('mobile', '9876543210')->firstOrFail();

        $this->assertSame('specialist', $user->role);
        $this->assertSame('specialist', $user->role_subtype);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $planId,
            'category_snapshot' => 'specialist',
        ]);
    }

    private function bootstrapPaymentSettings(): void
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
        $store->enable_ccavenue_payments = true;
        $store->ccavenue_mock_mode = true;
        $store->save();
    }
}
