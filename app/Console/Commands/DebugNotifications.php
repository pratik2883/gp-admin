<?php

namespace App\Console\Commands;

use App\Models\Gp;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\User;
use App\Notifications\NewGpRegisteredNotification;
use App\Notifications\NewSpecialistRegisteredNotification;
use App\Notifications\ReferralAcceptedNotification;
use App\Notifications\ReferralClosedNotification;
use App\Notifications\ReferralConsultedNotification;
use App\Notifications\ReferralCreatedNotification;
use App\Notifications\SubscriptionLifecycleNotification;
use App\Models\UserSubscription;
use App\Settings\NotificationSettings;
use App\Settings\OtpSettings;
use Illuminate\Console\Command;

class DebugNotifications extends Command
{
    protected $signature = 'notifications:debug
        {action? : status|test-sms|test-whatsapp|test-email|test-inapp|test-push|test-all-events|test-referral-flow|firebase-otp}
        {--user= : User ID to send test to}
        {--mobile= : Mobile number for SMS/WhatsApp test}
        {--event= : Specific event name for channel resolution}';

    protected $description = 'Debug notification system - check config, send test notifications';

    public function handle(): int
    {
        $action = $this->argument('action') ?? 'status';

        return match ($action) {
            'status' => $this->showStatus(),
            'test-sms' => $this->testSms(),
            'test-whatsapp' => $this->testWhatsApp(),
            'test-email' => $this->testEmail(),
            'test-inapp' => $this->testInApp(),
            'test-push' => $this->testPush(),
            'test-all-events' => $this->testAllEvents(),
            'test-referral-flow' => $this->testReferralFlow(),
            'firebase-otp' => $this->checkFirebaseOtp(),
            default => $this->error("Unknown action: {$action}"),
        };

        return self::SUCCESS;
    }

    private function showStatus(): int
    {
        $ns = app(NotificationSettings::class);

        $this->newLine();
        $this->info('=== Notification Settings ===');
        $this->table(['Setting', 'Value'], [
            ['sms_enabled', $ns->sms_enabled ? '✅ true' : '❌ false'],
            ['whatsapp_enabled', $ns->whatsapp_enabled ? '✅ true' : '❌ false'],
            ['email_enabled', $ns->email_enabled ? '✅ true' : '❌ false'],
            ['in_app_enabled', $ns->in_app_enabled ? '✅ true' : '❌ false'],
            ['push_enabled', $ns->push_enabled ? '✅ true' : '❌ false'],
            ['twilio_account_sid', $ns->twilio_account_sid ? substr($ns->twilio_account_sid, 0, 8).'...' : '❌ (not set)'],
            ['twilio_auth_token', $ns->twilio_auth_token ? substr($ns->twilio_auth_token, 0, 6).'...' : '❌ (not set)'],
            ['twilio_from_number', $ns->twilio_from_number ?? '❌ (not set)'],
            ['twilio_whatsapp_from', $ns->twilio_whatsapp_from ?? '❌ (not set)'],
            ['twilio_verify_service_sid', $ns->twilio_verify_service_sid ? substr($ns->twilio_verify_service_sid, 0, 8).'...' : '(not set)'],
            ['mail_from_name', $ns->mail_from_name ?? '(not set)'],
            ['mail_from_address', $ns->mail_from_address ?? '(not set)'],
        ]);

        $this->newLine();
        $this->info('=== Events & Channels ('.count($ns->events_channels).' configured) ===');
        if (empty($ns->events_channels)) {
            $this->warn('  No events configured — using defaults from channelsFor()');
            $defaults = [
                'referral_created', 'referral_accepted', 'referral_consulted', 'referral_closed',
                'new_gp_registered', 'new_specialist_registered',
                'subscription_payment_pending', 'subscription_activated',
                'subscription_payment_failed', 'subscription_expired', 'subscription_cancelled',
            ];
            foreach ($defaults as $event) {
                $channels = $ns->channelsFor($event);
                $this->line("  {$event}: ".(empty($channels) ? '❌ no active channels' : implode(', ', $channels)));
            }
        } else {
            foreach ($ns->events_channels as $row) {
                $event = $row['event'] ?? '?';
                $channels = $row['channels'] ?? [];
                $effective = $ns->channelsFor($event);
                $this->line("  {$event}: configured=".implode(',', $channels).' effective='.(empty($effective) ? '❌ none' : implode(',', $effective)));
            }
        }

        $this->newLine();
        $this->info('=== Event Templates ('.count($ns->event_templates).' configured) ===');
        if (! empty($ns->event_templates)) {
            foreach ($ns->event_templates as $row) {
                $event = $row['event'] ?? '?';
                $subject = $row['mail_subject'] ?? '(no subject)';
                $this->line("  {$event}: {$subject}");
            }
        } else {
            $this->warn('  No custom templates configured — using defaults');
        }

        $this->newLine();
        $this->info('=== FCM Push Config ===');
        $fcmEnabled = config('services.fcm.enabled', false);
        $fcmProject = config('services.fcm.project_id', '');
        $this->table(['Setting', 'Value'], [
            ['fcm.enabled', $fcmEnabled ? '✅ true' : '❌ false'],
            ['fcm.project_id', $fcmProject ?: '❌ (not set)'],
            ['fcm.service_account_json', config('services.fcm.service_account_json') ? '✅ set ('.strlen(config('services.fcm.service_account_json')).' chars)' : '(not set)'],
            ['fcm.service_account_path', config('services.fcm.service_account_path') ?: '(not set)'],
        ]);

        return self::SUCCESS;
    }

    private function testSms(): int
    {
        $mobile = $this->option('mobile');
        if (! $mobile) {
            $user = $this->resolveUser();
            if (! $user) return self::FAILURE;
            $mobile = $user->mobile;
            if (! $mobile) {
                $this->error("User {$user->id} has no mobile number");
                return self::FAILURE;
            }
        }

        $ns = app(NotificationSettings::class);
        if (! $ns->sms_enabled) {
            $this->warn('SMS is disabled in settings');
            if (! $this->confirm('Send anyway? (will fail if Twilio creds are missing)')) {
                return self::FAILURE;
            }
        }

        $this->info("Sending test SMS to {$mobile}...");
        try {
            app(\App\Services\TwilioSmsService::class)->send($mobile, 'Test SMS from GP-Admin debug command at '.now()->format('d M Y H:i:s'));
            $this->info('✅ SMS sent successfully!');
        } catch (\Throwable $e) {
            $this->error("❌ SMS failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testWhatsApp(): int
    {
        $mobile = $this->option('mobile');
        if (! $mobile) {
            $user = $this->resolveUser();
            if (! $user) return self::FAILURE;
            $mobile = $user->mobile;
            if (! $mobile) {
                $this->error("User {$user->id} has no mobile number");
                return self::FAILURE;
            }
        }

        $ns = app(NotificationSettings::class);
        if (! $ns->whatsapp_enabled) {
            $this->warn('WhatsApp is disabled in settings');
            if (! $this->confirm('Send anyway?')) {
                return self::FAILURE;
            }
        }

        $this->info("Sending test WhatsApp to {$mobile}...");
        try {
            app(\App\Services\TwilioWhatsAppService::class)->send($mobile, 'Test WhatsApp from GP-Admin debug at '.now()->format('d M Y H:i:s'));
            $this->info('✅ WhatsApp sent successfully!');
        } catch (\Throwable $e) {
            $this->error("❌ WhatsApp failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testEmail(): int
    {
        $user = $this->resolveUser();
        if (! $user) return self::FAILURE;
        if (! $user->email) {
            $this->error("User {$user->id} has no email address");
            return self::FAILURE;
        }

        $this->info("Sending test email to {$user->email}...");
        try {
            $gp = Gp::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'pending', 'city' => 'Test City']
            );
            $user->notify(new NewGpRegisteredNotification($gp));
            $this->info('✅ Email notification sent!');
        } catch (\Throwable $e) {
            $this->error("❌ Email failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testInApp(): int
    {
        $user = $this->resolveUser();
        if (! $user) return self::FAILURE;

        $this->info("Sending test in-app notification to user {$user->id}...");
        try {
            $referral = Referral::first();
            if (! $referral) {
                $this->warn('No referral found, sending without real referral context');
                $referral = new Referral();
                $referral->lead_code = 'TEST-'.now()->format('YmdHis');
                $referral->patient_name = 'Test Patient';
                $referral->priority = 'routine';
                $referral->status = 'sent';
            }
            $user->notify(new ReferralCreatedNotification($referral));
            $this->info('✅ In-app notification sent!');
        } catch (\Throwable $e) {
            $this->error("❌ In-app notification failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testPush(): int
    {
        $user = $this->resolveUser();
        if (! $user) return self::FAILURE;

        $fcmEnabled = config('services.fcm.enabled', false);
        if (! $fcmEnabled) {
            $this->warn('FCM push is disabled in config/services.php (FCM_ENABLED env var)');
        }

        $tokens = $user->deviceTokens()->count();
        if ($tokens === 0) {
            $this->warn("User {$user->id} has 0 device tokens — push won\'t be delivered");
        }
        $this->info("User has {$tokens} device token(s)");

        $this->info("Sending test push to user {$user->id}...");
        try {
            app(\App\Services\FcmPushService::class)->sendToUser($user->id, [
                'title' => 'Test Push Notification',
                'body' => 'This is a test push from GP-Admin debug at '.now()->format('d M Y H:i:s'),
                'data' => ['type' => 'test', 'source' => 'debug'],
            ]);
            $this->info('✅ Push notification sent!');
        } catch (\Throwable $e) {
            $this->error("❌ Push failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function testAllEvents(): int
    {
        $user = $this->resolveUser();
        if (! $user) return self::FAILURE;

        $ns = app(NotificationSettings::class);
        $events = [
            'referral_created',
            'referral_accepted',
            'referral_consulted',
            'referral_closed',
            'new_gp_registered',
            'new_specialist_registered',
            'subscription_payment_pending',
            'subscription_activated',
            'subscription_payment_failed',
            'subscription_expired',
            'subscription_cancelled',
        ];

        $passed = 0;
        $failed = 0;

        foreach ($events as $event) {
            $channels = $ns->channelsFor($event);
            $verb = empty($channels) ? '❌ SKIP (no active channels)' : '✅ channels: '.implode(', ', $channels);
            $this->line("  {$event}: {$verb}");

            try {
                $ref = new Referral();
                $ref->lead_code = 'TEST-'.now()->format('YmdHis');
                $ref->patient_name = 'Test Patient';
                $ref->priority = 'routine';
                $ref->status = 'sent';

                match ($event) {
                    'referral_created' => $user->notify(new ReferralCreatedNotification(clone $ref)),
                    'referral_accepted' => $user->notify(new ReferralAcceptedNotification(clone $ref)),
                    'referral_consulted' => $user->notify(new ReferralConsultedNotification(clone $ref)),
                    'referral_closed' => $user->notify(new ReferralClosedNotification(clone $ref)),
                    'new_gp_registered' => $user->notify(new NewGpRegisteredNotification(Gp::firstOrCreate(['user_id' => $user->id]))),
                    'new_specialist_registered' => $user->notify(new NewSpecialistRegisteredNotification(Specialist::firstOrCreate(['user_id' => $user->id]))),
                    'subscription_payment_pending' => $this->sendSubNotification($user, 'subscription_payment_pending'),
                    'subscription_activated' => $this->sendSubNotification($user, 'subscription_activated'),
                    'subscription_payment_failed' => $this->sendSubNotification($user, 'subscription_payment_failed'),
                    'subscription_expired' => $this->sendSubNotification($user, 'subscription_expired'),
                    'subscription_cancelled' => $this->sendSubNotification($user, 'subscription_cancelled'),
                };
                $passed++;
            } catch (\Throwable $e) {
                $this->warn("    ↪ Exception: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Results: {$passed} sent, {$failed} failed");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function sendSubNotification(User $user, string $event): void
    {
        $sub = UserSubscription::first();
        if (! $sub) {
            throw new \RuntimeException('No user subscription found in database');
        }
        $user->notify(new SubscriptionLifecycleNotification($event, $sub, [
            'user_name' => $user->name,
            'plan_name' => $sub->plan_name_snapshot,
            'amount' => number_format((float) $sub->price_snapshot, 2),
            'currency' => $sub->currency_snapshot ?: 'INR',
            'status' => $sub->status,
            'payment_status' => $sub->payment_status,
            'category' => $sub->category_snapshot,
            'ends_at' => optional($sub->ends_at)->format('d M Y') ?: '-',
        ]));
    }

    private function testReferralFlow(): int
    {
        $gpUser = User::whereHas('gp')->first();
        $specUser = User::whereHas('specialist')->first();

        if (! $gpUser || ! $specUser) {
            $this->error('Need at least one GP user and one Specialist user in database');
            $this->line('GP users: '.User::whereHas('gp')->count());
            $this->line('Specialist users: '.User::whereHas('specialist')->count());
            return self::FAILURE;
        }

        $gp = $gpUser->gp;
        $specialist = $specUser->specialist;

        $this->info("Testing full referral flow for GP:{$gp->id} → Specialist:{$specialist->id}");
        $this->newLine();

        // Step 1: Create referral
        $this->info('1. Creating test referral...');
        $referral = Referral::create([
            'lead_code' => 'TST-'.now()->format('YmdHis'),
            'gp_id' => $gp->id,
            'specialist_id' => $specialist->id,
            'patient_name' => 'Test Patient',
            'patient_age' => 30,
            'patient_mobile' => '9999999999',
            'case_summary' => 'Test case for notification debugging',
            'appointment_type' => 'opd',
            'referral_type' => 'specialist',
            'status' => 'sent',
            'priority' => 'routine',
        ]);
        $this->line("   Referral #{$referral->id} created");

        // Step 2: Notify (sent -> specialist gets notification)
        $this->info('2. Sending referral_created notifications...');
        $gpUser->notify(new ReferralCreatedNotification($referral));
        $this->line('   ✅ GP notified');

        // Step 3: Simulate acceptance
        $this->info('3. Sending referral_accepted notifications...');
        $referral->update(['status' => 'accepted']);
        $gpUser->notify(new ReferralAcceptedNotification($referral));
        $this->line('   ✅ GP notified');

        // Step 4: Simulate consulted
        $this->info('4. Sending referral_consulted notifications...');
        $referral->update(['status' => 'consulted']);
        $gpUser->notify(new ReferralConsultedNotification($referral));
        $this->line('   ✅ GP notified');

        // Step 5: Simulate closed
        $this->info('5. Sending referral_closed notifications...');
        $referral->update(['status' => 'closed']);
        $gpUser->notify(new ReferralClosedNotification($referral));
        $this->line('   ✅ GP notified');

        $this->newLine();
        $this->info('✅ Full referral flow tested!');
        $this->warn("Referral #{$referral->id} was left in 'closed' state. You may want to delete it.");

        return self::SUCCESS;
    }

    private function checkFirebaseOtp(): int
    {
        $otp = app(OtpSettings::class);

        $this->newLine();
        $this->info('=== Firebase OTP / Auth Settings ===');
        $this->table(['Setting', 'Value'], [
            ['enable_otp_login', $otp->enable_otp_login ? '✅ true' : '❌ false'],
            ['firebase_project_id', $otp->firebase_project_id ?? '❌ (not set)'],
            ['firebase_api_key', $otp->firebase_api_key ? substr($otp->firebase_api_key, 0, 10).'...' : '❌ (not set)'],
            ['firebase_app_id', $otp->firebase_app_id ?? '❌ (not set)'],
            ['firebase_sender_id', $otp->firebase_sender_id ?? '❌ (not set)'],
        ]);

        $this->newLine();
        $this->info('=== FCM Service Config ===');
        $this->table(['Setting', 'Value'], [
            ['FCM_ENABLED (env)', env('FCM_ENABLED', '(not set)')],
            ['FCM_PROJECT_ID (env)', env('FCM_PROJECT_ID', '(not set)')],
            ['FCM_SERVICE_ACCOUNT_PATH (env)', env('FCM_SERVICE_ACCOUNT_PATH', '(not set)') ?: '(not set)'],
            ['FCM_SERVICE_ACCOUNT_JSON (env)', env('FCM_SERVICE_ACCOUNT_JSON') ? '✅ set ('.strlen(env('FCM_SERVICE_ACCOUNT_JSON')).' chars)' : '(not set)'],
        ]);

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user');
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found");
                return null;
            }
            return $user;
        }

        $user = User::first();
        if (! $user) {
            $this->error('No users in database');
            return null;
        }
        $this->line("Using user ID {$user->id} ({$user->name}, {$user->email})");
        return $user;
    }
}
