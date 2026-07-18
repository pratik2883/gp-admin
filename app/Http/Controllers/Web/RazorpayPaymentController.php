<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use App\Services\RazorpayService;
use Illuminate\Http\Request;

class RazorpayPaymentController extends Controller
{
    public function mock(string $transactionUuid, string $result)
    {
        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $transactionUuid)
            ->where('gateway', 'razorpay')
            ->firstOrFail();

        $service = app(RazorpayService::class);

        if ($result === 'success') {
            $processed = $service->processSuccessfulPayment(
                $transaction,
                'pay_MOCK_' . strtoupper(\Illuminate\Support\Str::random(16)),
            );
        } else {
            $processed = $service->processFailedPayment(
                $transaction,
                $result === 'cancel' ? 'Payment cancelled by user.' : 'Mock payment failure.',
            );
        }

        $status = $processed['transaction']->status;
        $title = $status === 'success' ? 'Mock Payment Successful' : 'Mock Payment Not Completed';
        $message = $status === 'success'
            ? 'Mock payment success simulated. Return to the app and check status.'
            : 'Mock payment did not complete successfully. Return to the app and retry if needed.';

        return response($this->resultHtml($title, $message), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $webhookSecret = (string) app(\App\Settings\GeneralSettings::class)->razorpay_webhook_secret;

        if (empty($webhookSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Webhook secret not configured.'], 422);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload.'], 400);
        }

        $result = app(RazorpayService::class)->processWebhook($data, $signature, $webhookSecret);

        if ($result === null) {
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['status' => 'ok']);
    }

    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'transaction_uuid' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $validated['transaction_uuid'])
            ->where('gateway', 'razorpay')
            ->firstOrFail();

        if ($transaction->status === 'success') {
            return response()->json([
                'status' => 'already_processed',
                'subscription' => app(\App\Services\SubscriptionService::class)->latestSummaryFor(
                    $transaction->userSubscription->user,
                ),
            ]);
        }

        $service = app(RazorpayService::class);
        $verified = $service->verifyPayment(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
        );

        if (! $verified) {
            $service->processFailedPayment($transaction, 'Signature verification failed.');

            return response()->json([
                'status' => 'verification_failed',
                'message' => 'Payment signature verification failed.',
            ], 422);
        }

        $service->processSuccessfulPayment($transaction, $validated['razorpay_payment_id']);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment verified and subscription activated.',
            'subscription' => app(\App\Services\SubscriptionService::class)->latestSummaryFor(
                $transaction->userSubscription->user,
            ),
        ]);
    }

    private function resultHtml(string $title, string $message): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    body{font-family:Arial,sans-serif;background:#0f172a;color:#fff;padding:24px}
    .card{max-width:560px;margin:40px auto;background:#111827;border-radius:16px;padding:24px;border:1px solid #334155}
  </style>
</head>
<body>
  <div class="card">
    <h1>{$safeTitle}</h1>
    <p>{$safeMessage}</p>
  </div>
</body>
</html>
HTML;
    }
}
