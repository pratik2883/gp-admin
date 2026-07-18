<?php

namespace App\Services;

use App\Models\Specialist;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function categoryForRoleSubtype(?string $roleSubtype): ?string
    {
        return match ($roleSubtype) {
            'specialist' => 'specialist',
            'hospital' => 'hospital',
            'diagnostic_center' => 'diagnostic_center',
            default => null,
        };
    }

    public function requireSelectablePlan(int|string|null $planId, string $category): SubscriptionPlan
    {
        $id = is_int($planId) ? $planId : (int) $planId;

        $plan = SubscriptionPlan::query()
            ->whereKey($id)
            ->where('category', $category)
            ->where('is_active', true)
            ->first();

        if ($plan) {
            return $plan;
        }

        throw ValidationException::withMessages([
            'subscription_plan_id' => ['Please select a valid active plan.'],
        ]);
    }

    public function createPendingSubscription(
        User $user,
        SubscriptionPlan $plan,
        array $metadata = [],
        bool $requirePayment = false,
    ): UserSubscription
    {
        return UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'category_snapshot' => $plan->category,
            'plan_name_snapshot' => $plan->name,
            'plan_family_snapshot' => $plan->plan_family,
            'bed_slab_snapshot' => $plan->bed_slab,
            'duration_months_snapshot' => (int) $plan->duration_months,
            'price_snapshot' => $plan->price,
            'currency_snapshot' => $plan->currency ?: 'INR',
            'status' => $requirePayment ? 'pending_payment' : 'pending_activation',
            'payment_status' => $requirePayment ? 'pending' : 'unpaid',
            'metadata' => $metadata,
        ]);
    }

    public function latestSummaryFor(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        /** @var UserSubscription|null $subscription */
        $subscription = $user->subscriptions()->with('subscriptionPlan')->first();
        if (! $subscription) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'plan_id' => $subscription->subscription_plan_id,
            'plan_name' => $subscription->plan_name_snapshot,
            'category' => $subscription->category_snapshot,
            'plan_family' => $subscription->plan_family_snapshot,
            'bed_slab' => $subscription->bed_slab_snapshot,
            'duration_months' => (int) $subscription->duration_months_snapshot,
            'price' => (float) $subscription->price_snapshot,
            'currency' => $subscription->currency_snapshot,
            'status' => $subscription->status,
            'payment_status' => $subscription->payment_status,
            'starts_at' => optional($subscription->starts_at)?->toIso8601String(),
            'ends_at' => optional($subscription->ends_at)?->toIso8601String(),
            'trial_ends_at' => optional($subscription->trial_ends_at)?->toIso8601String(),
            'is_active' => $subscription->isActive(),
            'latest_transaction_uuid' => $subscription->transactions()->value('transaction_uuid'),
        ];
    }

    public function syncPremiumFlag(User $user): void
    {
        if ($user->role !== 'specialist' || $user->role_subtype !== 'specialist') {
            return;
        }

        /** @var Specialist|null $specialist */
        $specialist = $user->specialist()->first();
        if (! $specialist) {
            return;
        }

        $activePremium = $user->subscriptions()
            ->where('status', 'active')
            ->where('category_snapshot', 'specialist')
            ->where('plan_family_snapshot', 'premium')
            ->exists();

        if ((bool) $specialist->is_premium === $activePremium) {
            return;
        }

        $specialist->forceFill(['is_premium' => $activePremium])->save();
    }

    public function expireOverdue(): int
    {
        $expired = UserSubscription::query()
            ->where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            $subscription->markExpired();
            $this->syncPremiumFlag($subscription->user);
            app(SubscriptionNotificationService::class)->notifyExpired($subscription);
            $count++;
        }

        return $count;
    }

    public function withTrialAndBonus(UserSubscription $subscription, SubscriptionPlan $plan): void
    {
        $startsAt = now();
        $trialDays = (int) $plan->trial_days;
        $bonusMonths = (int) $plan->bonus_months;
        $duration = (int) $plan->duration_months + $bonusMonths;

        $endsAt = (clone $startsAt)->addMonths($duration);

        $trialEndsAt = $trialDays > 0 ? (clone $startsAt)->addDays($trialDays) : null;

        $subscription->forceFill([
            'status' => 'active',
            'payment_status' => 'paid',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'activated_at' => now(),
            'trial_ends_at' => $trialEndsAt,
            'bonus_months_credited' => $bonusMonths > 0,
            'expired_at' => null,
            'cancelled_at' => null,
        ])->save();
    }
}
