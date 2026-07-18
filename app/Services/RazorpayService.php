<?php

namespace App\Services;

use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Settings\GeneralSettings;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class RazorpayService
{
    private ?Api $api = null;

    public function __construct(
        private readonly GeneralSettings $settings,
        private readonly SubscriptionService $subscriptions,
        private readonly SubscriptionNotificationService $notificationService,
    ) {}

    public function paymentsEnabled(): bool
    {
        return (bool) $this->settings->enable_razorpay_payments
            && ($this->settings->razorpay_mock_mode || $this->hasCredentials());
    }

    public function isMockMode(): bool
    {
        return (bool) $this->settings->razorpay_mock_mode;
    }

    public function hasCredentials(): bool
    {
        return filled($this->settings->razorpay_key_id)
            && filled($this->settings->razorpay_key_secret);
    }

    public function keyId(): string
    {
        return (string) $this->settings->razorpay_key_id;
    }

    private function api(): Api
    {
        if ($this->api === null) {
            $this->api = new Api(
                (string) $this->settings->razorpay_key_id,
                (string) $this->settings->razorpay_key_secret,
            );
        }
        return $this->api;
    }

    public function createOrder(UserSubscription $subscription, User $user): array
    {
        $uuid = (string) Str::uuid();
        $amountPaise = (int) round((float) $subscription->price_snapshot * 100);
        $receipt = 'SUB' . $subscription->id . '-' . Str::upper(Str::random(8));

        $orderData = [
            'receipt' => $receipt,
            'amount' => $amountPaise,
            'currency' => $subscription->currency_snapshot ?: 'INR',
            'notes' => [
                'user_id' => (string) $user->id,
                'user_name' => $user->name ?? '',
                'user_email' => $user->email ?? '',
                'user_mobile' => $user->mobile ?? '',
                'subscription_id' => (string) $subscription->id,
                'transaction_uuid' => $uuid,
            ],
        ];

        if ($this->isMockMode()) {
            $razorpayOrderId = 'order_MOCK_' . Str::upper(Str::random(14));
            $amount = (int) $orderData['amount'];
            $currency = $orderData['currency'];
        } else {
            $razorpayOrder = $this->api()->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'];
            $amount = (int) $razorpayOrder['amount'];
            $currency = $razorpayOrder['currency'];
        }

        $transaction = SubscriptionTransaction::create([
            'user_subscription_id' => $subscription->id,
            'transaction_uuid' => $uuid,
            'gateway' => 'razorpay',
            'order_id' => $razorpayOrderId,
            'status' => 'initiated',
            'amount' => $amount / 100,
            'currency' => $currency,
            'initiated_at' => now(),
            'request_payload' => array_merge($orderData, [
                'razorpay_order_id' => $razorpayOrderId,
                'mock_mode' => $this->isMockMode(),
            ]),
        ]);

        $subscription->loadMissing('user');
        $this->notificationService->notifyPendingPayment($subscription, $transaction);

        return $this->buildCheckoutPayload($transaction, $user);
    }

    public function buildCheckoutPayload(SubscriptionTransaction $transaction, User $user): array
    {
        $amountPaise = (int) round((float) $transaction->amount * 100);

        return [
            'transaction_uuid' => $transaction->transaction_uuid,
            'gateway' => 'razorpay',
            'razorpay_order_id' => $transaction->order_id,
            'razorpay_key_id' => $this->keyId(),
            'amount' => $amountPaise,
            'amount_decimal' => (float) $transaction->amount,
            'currency' => $transaction->currency ?: 'INR',
            'user_name' => $user->name,
            'user_email' => $user->email ?? '',
            'user_contact' => $user->mobile ?? '',
            'mock_mode' => $this->isMockMode(),
            'status_url' => url('/api/public/subscription-payments/' . $transaction->transaction_uuid),
            'checkout_url' => $this->isMockMode()
                ? route('payments.razorpay.mock', [$transaction->transaction_uuid, 'success'])
                : null,
        ];
    }

    public function verifyPayment(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
    {
        if ($this->isMockMode()) {
            return true;
        }

        try {
            $attributes = [
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ];
            $this->api()->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function processSuccessfulPayment(SubscriptionTransaction $transaction, string $razorpayPaymentId): array
    {
        $subscription = $transaction->userSubscription()->with('user')->firstOrFail();
        $user = $subscription->user;

        $transaction->forceFill([
            'tracking_id' => $razorpayPaymentId,
            'status' => 'success',
            'payment_reference' => $razorpayPaymentId,
            'order_status' => 'Success',
            'response_payload' => [
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $transaction->order_id,
            ],
            'completed_at' => now(),
        ])->save();

        $subscription->forceFill([
            'payment_status' => 'paid',
            'payment_reference' => $razorpayPaymentId,
        ])->save();

        $subscription->markActive();
        $this->subscriptions->syncPremiumFlag($user);
        $this->notificationService->notifyActivated($subscription->fresh(['user']), $transaction->fresh());

        return [
            'transaction' => $transaction->fresh(),
            'subscription' => $subscription->fresh(),
            'user' => $user->fresh(),
        ];
    }

    public function processFailedPayment(SubscriptionTransaction $transaction, string $errorMessage = null): array
    {
        $subscription = $transaction->userSubscription()->with('user')->firstOrFail();
        $user = $subscription->user;

        $transaction->forceFill([
            'status' => 'failed',
            'order_status' => 'Failure',
            'failure_message' => $errorMessage ?: 'Payment failed.',
            'response_payload' => array_merge(
                $transaction->response_payload ?? [],
                ['error_message' => $errorMessage],
            ),
            'completed_at' => now(),
        ])->save();

        $subscription->forceFill([
            'status' => 'pending_payment',
            'payment_status' => 'failed',
            'notes' => trim(($subscription->notes ?? '') . "\nPayment failed: " . ($errorMessage ?: 'Unknown error')),
        ])->save();

        $this->notificationService->notifyPaymentFailed($subscription->fresh(['user']), $transaction->fresh());

        return [
            'transaction' => $transaction->fresh(),
            'subscription' => $subscription->fresh(),
            'user' => $user->fresh(),
        ];
    }

    public function processWebhook(array $payload, string $signature, string $webhookSecret): ?array
    {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);
        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $event = $payload['event'] ?? '';
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];

        if (empty($paymentEntity['order_id'])) {
            return null;
        }

        $razorpayOrderId = $paymentEntity['order_id'];
        $transaction = SubscriptionTransaction::query()
            ->where('order_id', $razorpayOrderId)
            ->where('gateway', 'razorpay')
            ->first();

        if (! $transaction || $transaction->status === 'success') {
            return null;
        }

        return match ($event) {
            'payment.captured' => $this->processSuccessfulPayment(
                $transaction,
                $paymentEntity['id'] ?? '',
            ),
            'payment.failed' => $this->processFailedPayment(
                $transaction,
                $paymentEntity['error_description'] ?? 'Payment failed via webhook.',
            ),
            default => null,
        };
    }
}
