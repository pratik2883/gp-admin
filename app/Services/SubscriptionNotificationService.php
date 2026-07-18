<?php

namespace App\Services;

use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\SubscriptionLifecycleNotification;

class SubscriptionNotificationService
{
    public function notifyPendingPayment(UserSubscription $subscription, ?SubscriptionTransaction $transaction = null): void
    {
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $this->send($user, 'subscription_payment_pending', $subscription, $transaction);
    }

    public function notifyActivated(UserSubscription $subscription, ?SubscriptionTransaction $transaction = null): void
    {
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $this->send($user, 'subscription_activated', $subscription, $transaction);
    }

    public function notifyPaymentFailed(UserSubscription $subscription, ?SubscriptionTransaction $transaction = null): void
    {
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $this->send($user, 'subscription_payment_failed', $subscription, $transaction);
    }

    public function notifyExpired(UserSubscription $subscription): void
    {
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $this->send($user, 'subscription_expired', $subscription, null);
    }

    public function notifyCancelled(UserSubscription $subscription): void
    {
        $user = $subscription->user;
        if (! $user) {
            return;
        }

        $this->send($user, 'subscription_cancelled', $subscription, null);
    }

    private function send(
        User $user,
        string $eventKey,
        UserSubscription $subscription,
        ?SubscriptionTransaction $transaction,
    ): void {
        $context = [
            'user_name' => $user->name,
            'plan_name' => $subscription->plan_name_snapshot,
            'amount' => number_format((float) $subscription->price_snapshot, 2, '.', ''),
            'currency' => $subscription->currency_snapshot ?: 'INR',
            'status' => $transaction?->order_status ?: $subscription->status,
            'payment_status' => $subscription->payment_status,
            'category' => $subscription->category_snapshot,
            'action_url' => $transaction
                ? route('payments.ccavenue.checkout', $transaction->transaction_uuid)
                : '',
            'ends_at' => optional($subscription->ends_at)?->format('d M Y') ?: '-',
        ];

        $user->notify(new SubscriptionLifecycleNotification($eventKey, $subscription, $context));
    }
}
