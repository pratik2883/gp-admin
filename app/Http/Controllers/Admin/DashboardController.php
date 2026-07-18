<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Referral;
use App\Models\Specialist;

class DashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'total_specialists' => Specialist::count(),
            'total_locations' => Hospital::distinct('city')->count('city'),
            'total_gps' => Gp::count(),
            'total_referrals' => Referral::count(),
        ];

        $accepted = Referral::where('status', 'accepted')->count();
        $consulted = Referral::where('status', 'consulted')->count();
        $closed = Referral::where('status', 'closed')->count();

        return view('admin.dashboard', compact('summary', 'accepted', 'consulted', 'closed'));
    }
}
