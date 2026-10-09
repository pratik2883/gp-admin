<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Only trust proxies we explicitly opt into via TRUSTED_PROXIES
        // (comma-separated IPs/CIDRs, or `*`). Behind Nginx/Cloudflare an
        // untrusted-proxy setup collapses every caller onto the proxy IP, which
        // breaks per-client rate limiting and audit logging. Leaving this unset
        // keeps the framework default: no proxy headers are trusted.
        $rawTrustedProxies = trim((string) env('TRUSTED_PROXIES', ''));

        if ($rawTrustedProxies !== '') {
            $trustedProxies = array_values(array_filter(array_map(
                'trim',
                explode(',', $rawTrustedProxies)
            )));

            // `*` / `**` must be passed as a *string*: Laravel resolves those to
            // "trust the calling IP". As an array entry they match no IP and would
            // silently trust nothing. The literal `REMOTE_ADDR` entry is handled by
            // the framework and works in the array form.
            if ($trustedProxies === ['*'] || $trustedProxies === ['**']) {
                $middleware->trustProxies(at: '*');
            } elseif ($trustedProxies !== []) {
                $middleware->trustProxies(at: $trustedProxies);
            }
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'ensure.gp' => \App\Http\Middleware\EnsureGp::class,
            'ensure.specialist' => \App\Http\Middleware\EnsureSpecialist::class,
            'ensure.diagnostic' => \App\Http\Middleware\EnsureDiagnosticCenter::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('subscriptions:check-expiry')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
