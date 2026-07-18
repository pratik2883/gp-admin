<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Referral;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalSpecialists = Specialist::count();
        $totalLocations = Hospital::distinct('city')->count('city');
        $totalGps = Gp::count();
        $totalReferrals = Referral::count();

        $acceptedReferrals = Referral::where('status', 'accepted')->count();
        $consultedReferrals = Referral::where('status', 'consulted')->count();
        $closedReferrals = Referral::where('status', 'closed')->count();

        $from = Carbon::today()->subDays(29);
        $to = Carbon::today();

        $raw = Referral::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $trend = [];
        for ($d = 0; $d < 30; $d++) {
            $day = $from->copy()->addDays($d)->toDateString();
            $trend[$day] = 0;
        }
        foreach ($raw as $row) {
            $trend[$row->date] = (int) $row->count;
        }

        $trendData = [];
        foreach ($trend as $date => $count) {
            $trendData[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return response()->json([
            'summary' => [
                'total_specialists' => $totalSpecialists,
                'total_locations' => $totalLocations,
                'total_gps' => $totalGps,
                'total_referrals' => $totalReferrals,
            ],
            'referral_stats' => [
                'accepted' => $acceptedReferrals,
                'consulted' => $consultedReferrals,
                'closed' => $closedReferrals,
            ],
            'trend_30_days' => $trendData,
        ]);
    }
}
