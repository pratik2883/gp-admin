<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the `/api/mr/*` surface.
 *
 * Those routes previously sat under `auth:sanctum` only, so *any* logged-in
 * account could call them. Verified against the live API: a `role=gp` token got
 * `200` from `/api/mr/dashboard`, wrote DCR visits through `/api/mr/visits`, and
 * `MrController::dashboard`'s `Mr::firstOrCreate` silently minted an `mrs` row
 * (with an auto `employee_code`) for that GP. MR data is internal field-force
 * data, so the whole group is restricted to accounts created as MR
 * representatives.
 */
class EnsureMr
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'mr') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
