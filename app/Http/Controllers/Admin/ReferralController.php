<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;

class ReferralController extends Controller
{
    public function index()
    {
        $referrals = Referral::with(['gp.user', 'specialist.user', 'hospital'])->orderBy('id', 'desc')->paginate(20);

        return view('admin.referrals', compact('referrals'));
    }
}
