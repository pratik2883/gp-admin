<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser hardening for every response (API, admin panel, landing pages).
 *
 * The panel previously answered with no `X-Frame-Options` (so it could be framed
 * for clickjacking), no `nosniff`, and a `X-Powered-By: PHP/8.x` banner.
 *
 * CSP is deliberately not set here: the Filament panel relies on inline styles
 * and scripts, so a policy would have to be built and tested with the panel, not
 * bolted on globally.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self)');

        // Removes the PHP version banner the SAPI injects.
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        $headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
