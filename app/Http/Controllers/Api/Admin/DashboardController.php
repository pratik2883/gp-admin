<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $totalGps = User::where('role', 'gp')->count();
        $totalSpecialists = User::where('role', 'specialist')->count();
        $totalReferralsToday = Referral::whereDate('created_at', $today)->count();
        $totalReferralsMonth = Referral::where('created_at', '>=', $startOfMonth)->count();

        return response()->json([
            'total_gps' => $totalGps,
            'total_specialists' => $totalSpecialists,
            'total_referrals_today' => $totalReferralsToday,
            'total_referrals_month' => $totalReferralsMonth,
        ]);
    }
}
