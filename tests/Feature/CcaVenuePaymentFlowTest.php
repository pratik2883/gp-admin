<?php

namespace Tests\Feature;

use App\Settings\NotificationSettings;
use App\Settings\GeneralSettings;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Tests\TestCase;

class CcaVenuePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);
        $this->bootstrapGeneralSettings();
        $this->bootstrapNotificationSettings();
    }

    public function test_specialist_registration_returns_mock_payment_payload_when_ccavenue_is_enabled(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'Paid Specialist',
            'email' => 'paid-specialist@example.com',
            'mobile' => '9555511111',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payment.mock_mode', true)
            ->assertJsonPath('user.subscription.status', 'pending_payment');

        $this->assertNotEmpty($response->json('payment.checkout_url'));
        $this->assertNotEmpty($response->json('payment.status_url'));
    }

    public function test_mock_success_callback_activates_subscription(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $registration = $this->postJson('/api/specialist/register', [
            'name' => 'Mock Success Specialist',
            'email' => 'mock-success@example.com',
            'mobile' => '9555522222',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ])->assertCreated();

        $uuid = $registration->json('payment.transaction_uuid');

        $this->get('/payments/ccavenue/mock/'.$uuid.'/success')
            ->assertOk();

        $this->getJson('/api/public/subscription-payments/'.$uuid)
            ->assertOk()
            ->assertJsonPath('transaction_status', 'success')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.payment_status', 'paid')
            ->assertJsonPath('subscription.is_active', true);

        $this->assertDatabaseHas('notifications', [
            'type' => \App\Notifications\SubscriptionLifecycleNotification::class,
        ]);

        $notifications = DB::table('notifications')
            ->where('type', \App\Notifications\SubscriptionLifecycleNotification::class)
            ->pluck('data');

        $this->assertTrue($notifications->contains(
            fn ($data) => str_contains((string) $data, 'subscription_payment_pending')
        ));
        $this->assertTrue($notifications->contains(
            fn ($data) => str_contains((string) $data, 'subscription_activated')
        ));
    }

    public function test_registration_skips_subscription_flow_when_ccavenue_is_disabled(): void
    {
        $this->disablePayments();

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'No Payment Specialist',
            'email' => 'no-payment@example.com',
            'mobile' => '9555533333',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payment', null)
            ->assertJsonPath('user.subscription', null);

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_hospital_registration_skips_subscription_flow_when_ccavenue_is_disabled(): void
    {
        $this->disablePayments();

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'No Payment Hospital',
            'email' => 'no-payment-hospital@example.com',
            'mobile' => '9555544444',
            'password' => '123456',
            'role_subtype' => 'hospital',
            'terms_accepted' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payment', null)
            ->assertJsonPath('user.subscription', null);

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_diagnostic_registration_skips_subscription_flow_when_ccavenue_is_disabled(): void
    {
        $this->disablePayments();

        $locationId = DB::table('locations')->insertGetId([
            'name' => 'Thane',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/diagnostic/register', [
            'center_name' => 'No Payment Diagnostic',
            'email' => 'no-payment-diagnostic@example.com',
            'mobile_number' => '9555555555',
            'password' => '123456',
            'location_id' => $locationId,
            'center_type' => 'lab',
            'address' => 'Main Road',
            'micro_area' => 'Naupada',
            'opening_time' => '09:00',
            'closing_time' => '18:00',
            'authorized_person_name' => 'Admin User',
            'authorized_person_role' => 'manager',
            'authorized_person_mobile' => '9555566666',
            'authorized_person_email' => 'diagnostic-admin@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payment', null)
            ->assertJsonPath('user.subscription', null);

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_public_subscription_plans_endpoint_returns_empty_when_ccavenue_is_disabled(): void
    {
        $this->disablePayments();

        $this->getJson('/api/public/subscription-plans?category=specialist')
            ->assertOk()
            ->assertJsonPath('category', 'specialist')
            ->assertJsonPath('enabled', false)
            ->assertJsonPath('plans', [])
            ->assertJsonPath('groups', []);
    }

    private function bootstrapGeneralSettings(): void
    {
        $store = app(GeneralSettings::class);
        $this->bootstrapSettingsProperties($store);

        $store->refresh();
        $store->enable_ccavenue_payments = true;
        $store->ccavenue_mock_mode = true;
        $store->ccavenue_test_mode = true;
        $store->ccavenue_currency = 'INR';
        $store->save();
    }

    private function bootstrapNotificationSettings(): void
    {
        $store = app(NotificationSettings::class);
        $this->bootstrapSettingsProperties($store);
        $store->refresh();
        $store->in_app_enabled = true;
        $store->email_enabled = true;
        $store->sms_enabled = false;
        $store->whatsapp_enabled = false;
        $store->push_enabled = false;
        $store->save();
    }

    private function disablePayments(): void
    {
        $store = app(GeneralSettings::class);
        $store->enable_ccavenue_payments = false;
        $store->save();
    }

    private function bootstrapSettingsProperties(object $store): void
    {
        $group = $store::group();
        $ref = new ReflectionClass($store);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            SettingsProperty::query()->updateOrCreate(
                [
                    'group' => $group,
                    'name' => $property->getName(),
                ],
                [
                    'payload' => json_encode($store->{$property->getName()}),
                    'locked' => false,
                ],
            );
        }
    }
}
