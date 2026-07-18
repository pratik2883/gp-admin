<?php

namespace App\Services;

use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Settings\GeneralSettings;
use Illuminate\Support\Str;

class CcaVenuePaymentService
{
    public function __construct(
        private readonly GeneralSettings $settings,
        private readonly CcaVenueCryptoService $crypto,
        private readonly SubscriptionService $subscriptions,
        private readonly SubscriptionNotificationService $notificationService,
    ) {}

    public function paymentsEnabled(): bool
    {
        return (bool) $this->settings->enable_ccavenue_payments
            && ($this->settings->ccavenue_mock_mode || $this->hasCredentials());
    }

    public function isMockMode(): bool
    {
        return (bool) $this->settings->ccavenue_mock_mode;
    }

    public function hasCredentials(): bool
    {
        return filled($this->settings->ccavenue_merchant_id)
            && filled($this->settings->ccavenue_access_code)
            && filled($this->settings->ccavenue_working_key);
    }

    public function defaultCurrency(): string
    {
        return strtoupper((string) ($this->settings->ccavenue_currency ?: 'INR'));
    }

    public function createTransaction(UserSubscription $subscription, User $user): SubscriptionTransaction
    {
        $uuid = (string) Str::uuid();
        $orderId = 'SUB'.$subscription->id.'-'.Str::upper(Str::random(10));

        $transaction = SubscriptionTransaction::create([
            'user_subscription_id' => $subscription->id,
            'transaction_uuid' => $uuid,
            'gateway' => 'ccavenue',
            'order_id' => $orderId,
            'status' => 'initiated',
            'amount' => $subscription->price_snapshot,
            'currency' => $subscription->currency_snapshot ?: $this->defaultCurrency(),
            'initiated_at' => now(),
            'request_payload' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_mobile' => $user->mobile,
                'subscription_id' => $subscription->id,
            ],
        ]);

        $subscription->loadMissing('user');
        $this->notificationService->notifyPendingPayment($subscription, $transaction);

        return $transaction;
    }

    public function buildCheckoutPayload(SubscriptionTransaction $transaction, User $user): array
    {
        return [
            'transaction_uuid' => $transaction->transaction_uuid,
            'checkout_url' => route('payments.ccavenue.checkout', $transaction->transaction_uuid),
            'status_url' => url('/api/public/subscription-payments/'.$transaction->transaction_uuid),
            'mock_mode' => $this->isMockMode(),
            'test_mode' => (bool) $this->settings->ccavenue_test_mode,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'user_name' => $user->name,
        ];
    }

    public function requestFields(SubscriptionTransaction $transaction, User $user): array
    {
        $payload = [
            'merchant_id' => (string) $this->settings->ccavenue_merchant_id,
            'order_id' => $transaction->order_id,
            'redirect_url' => route('payments.ccavenue.response', $transaction->transaction_uuid),
            'cancel_url' => route('payments.ccavenue.response', $transaction->transaction_uuid),
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'currency' => $transaction->currency ?: $this->defaultCurrency(),
            'language' => 'EN',
            'billing_name' => (string) $user->name,
            'billing_email' => (string) ($user->email ?: ''),
            'billing_tel' => (string) $user->mobile,
            'merchant_param1' => (string) $transaction->user_subscription_id,
            'merchant_param2' => $transaction->transaction_uuid,
        ];

        $plain = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

        return [
            'merchant_id' => (string) $this->settings->ccavenue_merchant_id,
            'access_code' => (string) $this->settings->ccavenue_access_code,
            'enc_request' => $this->crypto->encrypt($plain, (string) $this->settings->ccavenue_working_key),
            'gateway_url' => $this->gatewayUrl(),
            'plain_payload' => $payload,
        ];
    }

    public function gatewayUrl(): string
    {
        return $this->settings->ccavenue_test_mode
            ? 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction'
            : 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    }

    public function processMockResult(SubscriptionTransaction $transaction, string $result): array
    {
        $result = strtolower($result);
        $response = [
            'order_id' => $transaction->order_id,
            'tracking_id' => 'MOCK-'.Str::upper(Str::random(12)),
            'order_status' => match ($result) {
                'success' => 'Success',
                'cancel' => 'Aborted',
                default => 'Failure',
            },
            'status_message' => match ($result) {
                'success' => 'Mock payment completed successfully.',
                'cancel' => 'Mock payment cancelled by user.',
                default => 'Mock payment failed.',
            },
        ];

        return $this->finalizeTransaction($transaction, $response);
    }

    public function processEncryptedResponse(SubscriptionTransaction $transaction, string $encResp): array
    {
        $decoded = $this->crypto->decrypt($encResp, (string) $this->settings->ccavenue_working_key);
        $payload = $this->crypto->parseResponseString($decoded);

        return $this->finalizeTransaction($transaction, $payload);
    }

    public function finalizeTransaction(SubscriptionTransaction $transaction, array $payload): array
    {
        $orderStatus = strtolower((string) ($payload['order_status'] ?? ''));
        $subscription = $transaction->userSubscription()->with('user')->firstOrFail();
        $user = $subscription->user;

        $txStatus = match ($orderStatus) {
            'success' => 'success',
            'aborted' => 'cancelled',
            default => 'failed',
        };

        $transaction->forceFill([
            'tracking_id' => $payload['tracking_id'] ?? $transaction->tracking_id,
            'status' => $txStatus,
            'payment_reference' => $payload['tracking_id'] ?? $payload['order_id'] ?? $transaction->payment_reference,
            'order_status' => $payload['order_status'] ?? $transaction->order_status,
            'failure_message' => $payload['status_message'] ?? $payload['failure_message'] ?? null,
            'response_payload' => $payload,
            'completed_at' => now(),
        ])->save();

        if ($txStatus === 'success') {
            $subscription->forceFill([
                'payment_status' => 'paid',
                'payment_reference' => $transaction->payment_reference,
            ])->save();
            $subscription->markActive();
            $this->subscriptions->syncPremiumFlag($user);
            $this->notificationService->notifyActivated($subscription->fresh(['user']), $transaction->fresh());
        } elseif ($txStatus === 'cancelled') {
            $subscription->forceFill([
                'status' => 'pending_payment',
                'payment_status' => 'failed',
                'notes' => trim((string) ($subscription->notes."\nPayment aborted by user.")),
            ])->save();
            $this->notificationService->notifyPaymentFailed($subscription->fresh(['user']), $transaction->fresh());
        } else {
            $subscription->forceFill([
                'status' => 'pending_payment',
                'payment_status' => 'failed',
                'notes' => trim((string) ($subscription->notes."\n".($payload['status_message'] ?? 'Payment failed.'))),
            ])->save();
            $this->notificationService->notifyPaymentFailed($subscription->fresh(['user']), $transaction->fresh());
        }

        return [
            'transaction' => $transaction->fresh(),
            'subscription' => $subscription->fresh(),
            'user' => $user->fresh(),
        ];
    }

    public function checkoutViewData(SubscriptionTransaction $transaction): array
    {
        $subscription = $transaction->userSubscription()->with('user')->firstOrFail();
        $user = $subscription->user;

        if ($this->isMockMode()) {
            return [
                'mock_mode' => true,
                'transaction' => $transaction,
                'subscription' => $subscription,
                'user' => $user,
                'success_url' => route('payments.ccavenue.mock', [$transaction->transaction_uuid, 'success']),
                'failure_url' => route('payments.ccavenue.mock', [$transaction->transaction_uuid, 'failure']),
                'cancel_url' => route('payments.ccavenue.mock', [$transaction->transaction_uuid, 'cancel']),
            ];
        }

        $request = $this->requestFields($transaction, $user);

        return [
            'mock_mode' => false,
            'transaction' => $transaction,
            'subscription' => $subscription,
            'user' => $user,
            'gateway_url' => $request['gateway_url'],
            'enc_request' => $request['enc_request'],
            'access_code' => $request['access_code'],
        ];
    }
}
