<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry
        {--dry-run : Check without actually expiring or sending notifications}';

    protected $description = 'Mark overdue subscriptions as expired and send expiry notifications';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $overdue = \App\Models\UserSubscription::query()
            ->where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue subscriptions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$overdue->count()} overdue subscription(s).");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'User', 'Plan', 'Ends At'], $overdue->map(fn ($s) => [
                $s->id,
                $s->user?->name ?? "(user {$s->user_id})",
                $s->plan_name_snapshot,
                $s->ends_at?->toIso8601String(),
            ]));
            $this->warn('Dry run — no changes made.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($overdue->count());
        $bar->start();

        $expired = 0;
        foreach ($overdue as $subscription) {
            $subscription->markExpired();
            $subscriptionService->syncPremiumFlag($subscription->user);
            app(\App\Services\SubscriptionNotificationService::class)->notifyExpired($subscription);
            $expired++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Expired {$expired} subscription(s) and sent notifications.");

        return self::SUCCESS;
    }
}
