<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use App\Settings\NotificationSettings;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Tests\TestCase;

class RazorpayPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);
        $this->bootstrapGeneralSettings();
        $this->bootstrapNotificationSettings();
    }

    public function test_specialist_registration_returns_mock_order_when_razorpay_is_enabled(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'Razorpay Specialist',
            'email' => 'razorpay-specialist@example.com',
            'mobile' => '9666611111',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('payment.mock_mode', true)
            ->assertJsonPath('payment.gateway', 'razorpay');

        $this->assertNotEmpty($response->json('payment.razorpay_order_id'));
        $this->assertNotEmpty($response->json('payment.razorpay_key_id'));
        $this->assertNotEmpty($response->json('payment.transaction_uuid'));
        $this->assertNotEmpty($response->json('payment.status_url'));
        $this->assertStringContainsString('order_MOCK_', $response->json('payment.razorpay_order_id'));
        $this->assertEquals('rzp_test_key', $response->json('payment.razorpay_key_id'));
        $this->assertEquals(59900, $response->json('payment.amount'));
        $this->assertEquals('INR', $response->json('payment.currency'));
    }

    public function test_mock_success_callback_activates_subscription(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $registration = $this->postJson('/api/specialist/register', [
            'name' => 'Rz Mock Success',
            'email' => 'rz-mock-success@example.com',
            'mobile' => '9666622222',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ])->assertCreated();

        $uuid = $registration->json('payment.transaction_uuid');

        $this->get('/payments/razorpay/mock/' . $uuid . '/success')
            ->assertOk();

        $this->getJson('/api/public/subscription-payments/' . $uuid)
            ->assertOk()
            ->assertJsonPath('transaction_status', 'success')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.payment_status', 'paid')
            ->assertJsonPath('subscription.is_active', true);

        $this->assertDatabaseHas('notifications', [
            'type' => \App\Notifications\SubscriptionLifecycleNotification::class,
        ]);
    }

    public function test_mock_failure_callback_marks_subscription_failed(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $registration = $this->postJson('/api/specialist/register', [
            'name' => 'Rz Mock Fail',
            'email' => 'rz-mock-fail@example.com',
            'mobile' => '9666633333',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ])->assertCreated();

        $uuid = $registration->json('payment.transaction_uuid');

        $this->get('/payments/razorpay/mock/' . $uuid . '/failure')
            ->assertOk();

        $this->getJson('/api/public/subscription-payments/' . $uuid)
            ->assertOk()
            ->assertJsonPath('transaction_status', 'failed')
            ->assertJsonPath('subscription.payment_status', 'failed')
            ->assertJsonPath('subscription.is_active', false);
    }

    public function test_registration_skips_subscription_when_razorpay_is_disabled(): void
    {
        $this->disablePayments();

        $response = $this->postJson('/api/specialist/register', [
            'name' => 'Rz No Payment',
            'email' => 'rz-no-payment@example.com',
            'mobile' => '9666644444',
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

    public function test_payment_verification_with_valid_signature(): void
    {
        $planId = \App\Models\SubscriptionPlan::query()
            ->where('category', 'specialist')
            ->where('plan_family', 'premium')
            ->value('id');

        $registration = $this->postJson('/api/specialist/register', [
            'name' => 'Rz Verify',
            'email' => 'rz-verify@example.com',
            'mobile' => '9666655555',
            'password' => '123456',
            'role_subtype' => 'specialist',
            'terms_accepted' => true,
            'subscription_plan_id' => $planId,
        ])->assertCreated();

        $uuid = $registration->json('payment.transaction_uuid');
        $orderId = $registration->json('payment.razorpay_order_id');

        $this->postJson('/payments/razorpay/verify', [
            'transaction_uuid' => $uuid,
            'razorpay_payment_id' => 'pay_MOCK_TEST123456',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'mock_signature',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.payment_status', 'paid');
    }

    public function test_public_subscription_plans_endpoint_works_with_razorpay(): void
    {
        $response = $this->getJson('/api/public/subscription-plans?category=specialist');

        $response
            ->assertOk()
            ->assertJsonPath('category', 'specialist')
            ->assertJsonPath('enabled', true);

        $this->assertNotEmpty($response->json('plans'));
        $this->assertNotEmpty($response->json('groups'));
    }

    public function test_feature_flags_indicate_razorpay_when_enabled(): void
    {
        $response = $this->getJson('/api/public/feature-flags');

        $response
            ->assertOk()
            ->assertJsonPath('enable_subscription_payments', true)
            ->assertJsonPath('payment_gateway', 'razorpay');
    }

    private function bootstrapGeneralSettings(): void
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
        $store->enable_razorpay_payments = true;
        $store->razorpay_mock_mode = true;
        $store->razorpay_key_id = 'rzp_test_key';
        $store->razorpay_key_secret = 'test_secret';
        $store->razorpay_webhook_secret = 'test_webhook_secret';
        $store->save();
    }

    private function bootstrapNotificationSettings(): void
    {
        $store = app(NotificationSettings::class);
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
        $store->enable_razorpay_payments = false;
        $store->save();
    }
}
