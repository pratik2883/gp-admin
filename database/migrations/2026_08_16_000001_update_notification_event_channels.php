<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventsChannels = [
            ['event' => 'referral_created', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_accepted', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_consulted', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_closed', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'referral_rejected', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'diagnostic_referral_created', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'diagnostic_referral_status', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'new_gp_registered', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'new_specialist_registered', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_payment_pending', 'channels' => ['email', 'in_app']],
            ['event' => 'subscription_activated', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_payment_failed', 'channels' => ['email', 'in_app']],
            ['event' => 'subscription_expired', 'channels' => ['email', 'in_app', 'push']],
            ['event' => 'subscription_cancelled', 'channels' => ['email', 'in_app']],
        ];

        $now = now();

        $existing = DB::table('settings')
            ->where('group', 'notifications')
            ->where('name', 'events_channels')
            ->first();

        if ($existing) {
            DB::table('settings')
                ->where('group', 'notifications')
                ->where('name', 'events_channels')
                ->update([
                    'payload' => json_encode($eventsChannels),
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('settings')->insert([
                'group' => 'notifications',
                'name' => 'events_channels',
                'locked' => false,
                'payload' => json_encode($eventsChannels),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // No reverse needed; settings are configurable from the admin panel.
    }
};
