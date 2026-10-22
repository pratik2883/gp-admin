<?php

namespace App\Http\Middleware;

use App\Models\Mr;
use App\Settings\MrSettings;
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
 *
 * It is also the enforcement point for admin switches: the MR app kill switch,
 * a blocked login and an inactive MR profile all stop here, because a setting
 * that the API ignores is not a control.
 */
class EnsureMr
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'mr') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'This account is blocked.'], 403);
        }

        $settings = app(MrSettings::class);

        if (! $settings->mr_app_enabled) {
            return response()->json([
                'message' => 'The MR app is currently disabled. Please contact the admin.',
            ], 403);
        }

        $mr = Mr::where('user_id', $user->id)->first();
        if ($mr && $mr->status === 'inactive') {
            return response()->json([
                'message' => 'This MR profile is inactive. Please contact the admin.',
            ], 403);
        }

        return $next($request);
    }
}
