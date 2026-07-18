<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use App\Services\CcaVenuePaymentService;
use Illuminate\Http\Request;

class CcaVenuePaymentController extends Controller
{
    public function checkout(string $transactionUuid)
    {
        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $transactionUuid)
            ->firstOrFail();

        $data = app(CcaVenuePaymentService::class)->checkoutViewData($transaction);

        if ($data['mock_mode']) {
            $amount = number_format((float) $transaction->amount, 2);
            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mock CCAvenue Payment</title>
  <style>
    body{font-family:Arial,sans-serif;background:#0f172a;color:#fff;padding:24px}
    .card{max-width:560px;margin:40px auto;background:#111827;border-radius:16px;padding:24px;border:1px solid #334155}
    .btn{display:inline-block;padding:12px 18px;margin:8px 8px 0 0;border-radius:10px;text-decoration:none;color:#fff}
    .success{background:#059669}.fail{background:#dc2626}.cancel{background:#64748b}
  </style>
</head>
<body>
  <div class="card">
    <h1>Mock CCAvenue Payment</h1>
    <p>User: {$data['user']->name}</p>
    <p>Plan: {$data['subscription']->plan_name_snapshot}</p>
    <p>Amount: {$amount} {$transaction->currency}</p>
    <p>This page is only for local/debug testing.</p>
    <a class="btn success" href="{$data['success_url']}">Simulate Success</a>
    <a class="btn fail" href="{$data['failure_url']}">Simulate Failure</a>
    <a class="btn cancel" href="{$data['cancel_url']}">Simulate Cancel</a>
  </div>
</body>
</html>
HTML;

            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $gatewayUrl = $data['gateway_url'];
        $encRequest = htmlspecialchars($data['enc_request'], ENT_QUOTES, 'UTF-8');
        $accessCode = htmlspecialchars($data['access_code'], ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Redirecting To CCAvenue</title>
</head>
<body onload="document.forms[0].submit()">
  <p>Redirecting to secure payment page...</p>
  <form method="post" action="{$gatewayUrl}">
    <input type="hidden" name="encRequest" value="{$encRequest}">
    <input type="hidden" name="access_code" value="{$accessCode}">
    <noscript><button type="submit">Continue</button></noscript>
  </form>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function response(Request $request, string $transactionUuid)
    {
        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $transactionUuid)
            ->firstOrFail();

        $service = app(CcaVenuePaymentService::class);

        try {
            $result = $service->processEncryptedResponse($transaction, (string) $request->input('encResp', ''));
            $status = $result['transaction']->status;
            $title = $status === 'success' ? 'Payment Successful' : 'Payment Not Completed';
            $message = $status === 'success'
                ? 'Your subscription has been activated. You can return to the app.'
                : 'Payment did not complete successfully. You can return to the app and retry.';
        } catch (\Throwable $e) {
            $title = 'Payment Processing Error';
            $message = config('app.debug') ? $e->getMessage() : 'Unable to process payment response.';
        }

        return response($this->resultHtml($title, $message), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function mock(string $transactionUuid, string $result)
    {
        $transaction = SubscriptionTransaction::query()
            ->with(['userSubscription.user'])
            ->where('transaction_uuid', $transactionUuid)
            ->firstOrFail();

        $processed = app(CcaVenuePaymentService::class)->processMockResult($transaction, $result);
        $status = $processed['transaction']->status;
        $title = $status === 'success' ? 'Mock Payment Successful' : 'Mock Payment Not Completed';
        $message = $status === 'success'
            ? 'Mock payment success simulated. Return to the app and check status.'
            : 'Mock payment did not complete successfully. Return to the app and retry if needed.';

        return response($this->resultHtml($title, $message), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
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
