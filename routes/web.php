<?php

use App\Http\Controllers\Web\CcaVenuePaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/landing-assets/app_logo.png', function () {
    $path = public_path('landing-assets/app_logo.png');
    if (! is_file($path)) {
        $path = base_path('gp-app/assets/images/app_logo.png');
    }
    if (! is_file($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

$stripLovable = function (string $html): string {
    $html = preg_replace('/<aside\\b[^>]*\\bid=["\\\']lovable-badge["\\\'][\\s\\S]*?<\\/aside>/i', '', $html) ?? $html;
    $html = preg_replace('/<script\\b[^>]*>(?:(?!<\\/script>)[\\s\\S])*lovable-badge(?:(?!<\\/script>)[\\s\\S])*<\\/script>/i', '', $html) ?? $html;
    return $html;
};

$getLandingLayoutParts = function () use ($stripLovable): array {
    static $parts = null;
    if (is_array($parts)) {
        return $parts;
    }

    $landingPath = base_path('pages/landing-page.md');
    if (! is_file($landingPath)) {
        $parts = ['head' => '', 'mainOpen' => '<main>', 'header' => '', 'footer' => ''];
        return $parts;
    }

    $layoutHtml = $stripLovable(file_get_contents($landingPath) ?: '');

    preg_match('/<head\\b[^>]*>([\\s\\S]*?)<\\/head>/i', $layoutHtml, $headMatch);
    preg_match('/<main\\b[^>]*>/i', $layoutHtml, $mainOpenMatch);
    preg_match('/<header\\b[\\s\\S]*?<\\/header>/i', $layoutHtml, $headerMatch);
    preg_match('/<footer\\b[\\s\\S]*?<\\/footer>/i', $layoutHtml, $footerMatch);

    $parts = [
        'head' => $headMatch[1] ?? '',
        'mainOpen' => $mainOpenMatch[0] ?? '<main>',
        'header' => $headerMatch[0] ?? '',
        'footer' => $footerMatch[0] ?? '',
    ];

    return $parts;
};

$renderMdPage = function (string $pageTitle, string $mdFilename) use ($getLandingLayoutParts) {
    $parts = $getLandingLayoutParts();
    if ($parts['head'] === '' && $parts['header'] === '' && $parts['footer'] === '') {
        abort(404);
    }

    $setTitle = function (string $head, string $title): string {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $titleTag = "<title>{$safeTitle} — Specialist Connect PRO</title>";
        if (preg_match('/<title\\b[^>]*>[\\s\\S]*?<\\/title>/i', $head)) {
            return preg_replace('/<title\\b[^>]*>[\\s\\S]*?<\\/title>/i', $titleTag, $head, 1) ?? $head;
        }
        return $titleTag . "\n" . $head;
    };

    $linkify = function (string $escaped): string {
        $escaped = preg_replace(
            '/\\b([a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,})\\b/i',
            '<a class="text-brand-cyan hover:text-white transition" href="mailto:$1">$1</a>',
            $escaped
        ) ?? $escaped;

        $escaped = preg_replace(
            '/\\bspecialistconnectpro\\.com\\b/i',
            '<a class="text-brand-cyan hover:text-white transition" href="https://specialistconnectpro.com" target="_blank" rel="noopener noreferrer">specialistconnectpro.com</a>',
            $escaped
        ) ?? $escaped;

        return $escaped;
    };

    $markdownToHtml = function (string $markdown, string $title) use ($linkify): string {
        $markdown = str_replace("\r\n", "\n", $markdown);
        $markdown = str_replace("\r", "\n", $markdown);
        $lines = preg_split("/\n/", $markdown);
        $lines = is_array($lines) ? $lines : [];

        $html = [];
        $inUl = false;

        $closeUl = function () use (&$html, &$inUl) {
            if ($inUl) {
                $html[] = '</ul>';
                $inUl = false;
            }
        };

        foreach ($lines as $idx => $line) {
            $raw = trim((string) $line);
            if ($idx === 0 && $raw !== '' && strtolower($raw) === strtolower($title)) {
                continue;
            }

            if ($raw === '') {
                $closeUl();
                continue;
            }

            if (preg_match('/^#{1,6}\\s+/', $raw)) {
                $closeUl();
                $level = max(1, min(6, strspn($raw, '#')));
                $text = trim(preg_replace('/^#{1,6}\\s+/', '', $raw) ?? $raw);
                $text = $linkify(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
                $size = match ($level) {
                    1 => 'text-3xl',
                    2 => 'text-2xl',
                    default => 'text-xl',
                };
                $html[] = "<h{$level} class=\"mt-8 font-display {$size} font-semibold text-white\">{$text}</h{$level}>";
                continue;
            }

            $escaped = $linkify(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));

            if (preg_match('/^\\d+\\.\\s+/', $raw)) {
                $closeUl();
                $html[] = "<h2 class=\"mt-8 font-display text-2xl font-semibold text-white\">{$escaped}</h2>";
                continue;
            }

            if (preg_match('/^[-•]\\s+/', $raw)) {
                $item = trim(preg_replace('/^[-•]\\s+/', '', $raw) ?? $raw);
                $item = $linkify(htmlspecialchars($item, ENT_QUOTES, 'UTF-8'));
                if (! $inUl) {
                    $html[] = '<ul class="mt-4 list-disc space-y-2 pl-6 text-white/70">';
                    $inUl = true;
                }
                $html[] = "<li>{$item}</li>";
                continue;
            }

            $closeUl();
            if (preg_match('/^Last Updated:/i', $raw)) {
                $html[] = "<p class=\"mt-4 text-sm text-white/55\">{$escaped}</p>";
                continue;
            }

            if (preg_match('/^([A-Za-z][A-Za-z \\-]+):\\s*(.+)$/', $raw, $m)) {
                $label = htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8');
                $value = $linkify(htmlspecialchars(trim($m[2]), ENT_QUOTES, 'UTF-8'));
                $html[] = "<p class=\"mt-4 text-white/70\"><span class=\"font-medium text-white/85\">{$label}:</span> {$value}</p>";
                continue;
            }

            $html[] = "<p class=\"mt-4 text-white/70 leading-relaxed\">{$escaped}</p>";
        }

        $closeUl();

        $body = trim(implode("\n", $html));
        if ($body === '') {
            $body = '<p class="mt-4 text-white/70 leading-relaxed">Content will be updated soon.</p>';
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<section class="pt-28 pb-20">
  <div class="mx-auto max-w-3xl px-5 sm:px-8">
    <h1 class="font-display text-3xl font-bold text-white">{$safeTitle}</h1>
    {$body}
  </div>
</section>
HTML;
    };

    $contentPath = base_path('pages/' . ltrim($mdFilename, '/\\'));
    $md = is_file($contentPath) ? (file_get_contents($contentPath) ?: '') : '';

    $headInner = $setTitle($parts['head'], $pageTitle);
    $policyCss = <<<'CSS'
<style>
.policy-content h1,.policy-content h2,.policy-content h3{color:#fff;font-weight:600;margin-top:2rem}
.policy-content h1{font-size:1.875rem;line-height:2.25rem;margin-top:0}
.policy-content h2{font-size:1.5rem;line-height:2rem}
.policy-content h3{font-size:1.25rem;line-height:1.75rem}
.policy-content p{margin-top:1rem;color:rgba(255,255,255,.7);line-height:1.7}
.policy-content ul{margin-top:1rem;padding-left:1.5rem;list-style:disc;color:rgba(255,255,255,.7)}
.policy-content li{margin-top:.5rem;line-height:1.7}
.policy-content a{color:#22d3ee;text-decoration:none}
.policy-content a:hover{color:#fff}
</style>
CSS;
    if (stripos($headInner, '.policy-content') === false) {
        $headInner .= "\n" . $policyCss;
    }

    $isHtmlFragment = preg_match('/^\s*</', $md) === 1;
    if ($isHtmlFragment) {
        $fragment = $md;
        $fragment = preg_replace('/^\s*<h1\b[^>]*>[\s\S]*?<\/h1>\s*/i', '', $fragment, 1) ?? $fragment;

        $safeTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        $contentHtml = <<<HTML
<section class="pt-28 pb-20">
  <div class="mx-auto max-w-3xl px-5 sm:px-8">
    <h1 class="font-display text-3xl font-bold text-white">{$safeTitle}</h1>
    <div class="policy-content">
{$fragment}
    </div>
  </div>
</section>
HTML;
    } else {
        $contentHtml = $markdownToHtml($md, $pageTitle);
    }

    $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n{$headInner}\n</head>\n<body>\n{$parts['mainOpen']}\n{$parts['header']}\n{$contentHtml}\n{$parts['footer']}\n</main>\n</body>\n</html>";

    return response()->make($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
};

Route::get('/contact', fn () => $renderMdPage('Contact', 'Contact_Us.md'));
Route::get('/support', fn () => $renderMdPage('Support', 'Contact_Us.md'));
Route::get('/privacy-policy', fn () => $renderMdPage('Privacy Policy', 'PrivacyPolicy.md'));
Route::get('/terms-conditions', fn () => $renderMdPage('Terms & Conditions', 'Terms_Conditions.md'));
Route::get('/refund-cancellation-policy', fn () => $renderMdPage('Refund & Cancellation Policy', 'Refund_Cancellation_Policy.md'));
Route::get('/disclaimer', fn () => $renderMdPage('Disclaimer', 'Disclaimer.md'));
Route::get('/delete-account', fn () => $renderMdPage('Delete Account', 'delete-account.md'));
Route::get('/notification-consent', fn () => $renderMdPage('Notification & Communication Consent', 'Notification_Consent_Policy.md'));

Route::post('/payments/razorpay/webhook', [\App\Http\Controllers\Web\RazorpayPaymentController::class, 'webhook'])
    ->name('payments.razorpay.webhook');
Route::post('/payments/razorpay/verify', [\App\Http\Controllers\Web\RazorpayPaymentController::class, 'verifyPayment'])
    ->name('payments.razorpay.verify');
Route::get('/payments/razorpay/mock/{transactionUuid}/{result}', [\App\Http\Controllers\Web\RazorpayPaymentController::class, 'mock'])
    ->name('payments.razorpay.mock');

Route::get('/payments/ccavenue/checkout/{transactionUuid}', [CcaVenuePaymentController::class, 'checkout'])
    ->name('payments.ccavenue.checkout');
Route::post('/payments/ccavenue/response/{transactionUuid}', [CcaVenuePaymentController::class, 'response'])
    ->name('payments.ccavenue.response');
Route::get('/payments/ccavenue/mock/{transactionUuid}/{result}', [CcaVenuePaymentController::class, 'mock'])
    ->name('payments.ccavenue.mock');
Route::get('/payments/ccavenue/status/{transactionUuid}', function (string $transactionUuid) {
    return redirect('/api/public/subscription-payments/'.$transactionUuid);
})->name('payments.ccavenue.status');

Route::get('/', function () use ($stripLovable) {
    $user = auth()->user();
    if ($user && ($user->role ?? null) === 'admin') {
        return redirect()->route('filament.admin.pages.dashboard');
    }

    $landingPath = base_path('pages/landing-page.md');
    if (is_file($landingPath)) {
        $html = $stripLovable(file_get_contents($landingPath) ?: '');

        return response()->make(
            $html,
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/admin/system-backups/{backup}/download', function (\App\Models\SystemBackup $backup) {
    $user = auth()->user();
    if (! $user || ($user->role ?? null) !== 'admin') {
        abort(403);
    }

    if ($backup->status !== 'completed' || ! $backup->disk || ! $backup->path) {
        abort(404);
    }

    if (! \Illuminate\Support\Facades\Storage::disk($backup->disk)->exists($backup->path)) {
        abort(404);
    }

    app(\App\Services\SystemBackupService::class)->log($backup, $user, 'backup_downloaded');

    return \Illuminate\Support\Facades\Storage::disk($backup->disk)->download($backup->path);
})
    ->middleware(['auth', 'signed'])
    ->name('system-backups.download');
