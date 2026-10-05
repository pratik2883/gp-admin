<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use App\Notifications\Channels\FcmChannel;
use App\Settings\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    private function settingsWith(bool $push): NotificationSettings
    {
        $settings = new NotificationSettings;
        $settings->email_enabled = true;
        $settings->in_app_enabled = true;
        $settings->push_enabled = $push;
        $settings->events_channels = [];

        return $settings;
    }

    public function test_defaults_include_push_for_referral_events(): void
    {
        $settings = $this->settingsWith(true);

        foreach (['referral_created', 'referral_accepted', 'referral_consulted', 'referral_closed', 'referral_rejected'] as $event) {
            $channels = $settings->channelsFor($event);
            $this->assertContains('push', $channels, "{$event} should include push");
            $this->assertContains('email', $channels);
            $this->assertContains('in_app', $channels);
        }
    }

    public function test_defaults_include_push_for_diagnostic_events(): void
    {
        $settings = $this->settingsWith(true);

        $this->assertContains('push', $settings->channelsFor('diagnostic_referral_created'));
        $this->assertContains('push', $settings->channelsFor('diagnostic_referral_status'));
    }

    public function test_push_is_filtered_out_when_disabled(): void
    {
        $settings = $this->settingsWith(false);

        $this->assertNotContains('push', $settings->channelsFor('referral_created'));
        $this->assertSame(['email', 'in_app'], $settings->channelsFor('referral_created'));
    }

    public function test_configured_events_channels_take_precedence(): void
    {
        $settings = $this->settingsWith(true);
        $settings->events_channels = [
            ['event' => 'referral_rejected', 'channels' => ['in_app']],
        ];

        $this->assertSame(['in_app'], $settings->channelsFor('referral_rejected'));
    }

    public function test_admin_broadcast_notification_uses_database_and_fcm(): void
    {
        DB::table('settings')->insert([
            ['group' => 'notifications', 'name' => 'in_app_enabled', 'payload' => json_encode(true), 'locked' => false, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'notifications', 'name' => 'push_enabled', 'payload' => json_encode(true), 'locked' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile' => '9892711228',
            'role' => 'gp',
            'status' => 'active',
            'notification_preferences' => ['push' => true, 'email' => true, 'sms' => true, 'whatsapp' => true],
            'password' => bcrypt('secret123'),
        ]);

        $notification = new AdminBroadcastNotification('Hello', 'Test broadcast');
        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);

        $payload = $notification->toArray($user);
        $this->assertSame('Hello', $payload['title']);
        $this->assertSame('Test broadcast', $payload['body']);
        $this->assertSame('admin_broadcast', $payload['type']);
    }
}
