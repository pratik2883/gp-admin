<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Services\CcaVenuePaymentService;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    public function initiate(Request $request, int $subscriptionId)
    {
        $user = $request->user();
        $subscription = UserSubscription::query()
            ->where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $gateway = $this->resolveGateway();

        if ($gateway === null) {
            return response()->json([
                'message' => 'No payment gateway is enabled.',
            ], 422);
        }

        if ($subscription->status === 'active') {
            return response()->json([
                'message' => 'Subscription is already active.',
                'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            ]);
        }

        $latest = $subscription->transactions()->first();
        if ($latest && in_array($latest->status, ['initiated', 'pending'], true)) {
            $payload = $latest->gateway === 'razorpay'
                ? app(RazorpayService::class)->buildCheckoutPayload($latest, $user)
                : app(CcaVenuePaymentService::class)->buildCheckoutPayload($latest, $user);

            return response()->json([
                'message' => 'Payment already initiated.',
                'payment' => $payload,
                'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            ]);
        }

        $subscription->forceFill([
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ])->save();

        if ($gateway === 'razorpay') {
            $razorpay = app(RazorpayService::class);
            $payload = $razorpay->createOrder($subscription, $user);
        } else {
            $cca = app(CcaVenuePaymentService::class);
            $transaction = $cca->createTransaction($subscription, $user);
            $payload = $cca->buildCheckoutPayload($transaction, $user);
        }

        return response()->json([
            'message' => 'Payment initiated successfully.',
            'payment' => $payload,
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
        ]);
    }

    public function publicStatus(string $transactionUuid)
    {
        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $transactionUuid)
            ->firstOrFail();

        return response()->json([
            'transaction_uuid' => $transaction->transaction_uuid,
            'transaction_status' => $transaction->status,
            'order_status' => $transaction->order_status,
            'failure_message' => $transaction->failure_message,
            'gateway' => $transaction->gateway,
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($transaction->userSubscription->user),
        ]);
    }

    private function resolveGateway(): ?string
    {
        $razorpay = app(RazorpayService::class);
        if ($razorpay->paymentsEnabled()) {
            return 'razorpay';
        }

        $cca = app(CcaVenuePaymentService::class);
        if ($cca->paymentsEnabled()) {
            return 'ccavenue';
        }

        return null;
    }
}
