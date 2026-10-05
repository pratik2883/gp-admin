<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collateral;
use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Mr;
use App\Models\MrAttendance;
use App\Models\MrLeave;
use App\Models\MrVisit;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MrController extends Controller
{
    // GET /api/mr/dashboard
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $mr = Mr::firstOrCreate(['user_id' => $user->id], [
            'employee_code' => 'MR-'.str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'territory_zone' => 'Mumbai & Thane',
            'daily_visit_target' => 10,
            'monthly_subscription_target' => 5,
        ]);

        $today = Carbon::today()->format('Y-m-d');
        $todayVisitsCount = MrVisit::where('mr_user_id', $user->id)
            ->whereDate('visit_date', $today)
            ->count();

        $todayAttendance = MrAttendance::where('mr_user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Non-active GPs (registered > 15 days ago, 0 referrals)
        $fifteenDaysAgo = Carbon::now()->subDays(15);
        $nonActiveGpsCount = Gp::where('created_at', '<=', $fifteenDaysAgo)
            ->whereDoesntHave('referrals')
            ->count();

        // Expiring subscriptions in next 7 days
        $sevenDaysLater = Carbon::now()->addDays(7);
        $expiringSubscriptionsCount = UserSubscription::where('status', 'active')
            ->whereBetween('expires_at', [Carbon::now(), $sevenDaysLater])
            ->count();

        return response()->json([
            'mr_profile' => $mr,
            'user' => $user,
            'stats' => [
                'today_visits' => $todayVisitsCount,
                'daily_target' => $mr->daily_visit_target,
                'monthly_subscription_target' => $mr->monthly_subscription_target,
                'non_active_gps_count' => $nonActiveGpsCount,
                'expiring_subscriptions_count' => $expiringSubscriptionsCount,
                'attendance_status' => $todayAttendance ? ($todayAttendance->check_out_at ? 'checked_out' : 'checked_in') : 'not_checked_in',
            ],
            'today_attendance' => $todayAttendance,
        ]);
    }

    // POST /api/mr/attendance/check-in
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');

        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'selfie' => 'nullable|image|max:5048',
        ]);

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('mr/attendances/'.$user->id, 'public');
        }

        $attendance = MrAttendance::updateOrCreate(
            ['mr_user_id' => $user->id, 'date' => $today],
            [
                'check_in_at' => Carbon::now(),
                'check_in_lat' => $request->latitude,
                'check_in_lng' => $request->longitude,
                'selfie_path' => $selfiePath,
                'status' => 'present',
            ]
        );

        return response()->json([
            'message' => 'Attendance checked in successfully',
            'attendance' => $attendance,
        ]);
    }

    // POST /api/mr/attendance/check-out
    public function checkOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = MrAttendance::where('mr_user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance) {
            return response()->json(['message' => 'No check-in record found for today'], 400);
        }

        $attendance->update([
            'check_out_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Attendance checked out successfully',
            'attendance' => $attendance,
        ]);
    }

    // POST /api/mr/visits
    public function logVisit(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'doctor_user_id' => 'nullable|integer|exists:users,id',
            'doctor_type' => 'required|in:gp,specialist,diagnostic_center',
            'doctor_name' => 'nullable|string|max:190',
            'clinic_name' => 'nullable|string|max:190',
            'visit_purpose' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'selfie' => 'nullable|image|max:5048',
        ]);

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('mr/visits/'.$user->id, 'public');
        }

        $visit = MrVisit::create([
            'mr_user_id' => $user->id,
            'doctor_user_id' => $data['doctor_user_id'] ?? null,
            'doctor_type' => $data['doctor_type'],
            'doctor_name' => $data['doctor_name'] ?? null,
            'clinic_name' => $data['clinic_name'] ?? null,
            'visit_date' => Carbon::today(),
            'visit_purpose' => $data['visit_purpose'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'selfie_path' => $selfiePath,
        ]);

        return response()->json([
            'message' => 'Visit logged successfully',
            'visit' => $visit,
        ]);
    }

    // GET /api/mr/visits
    public function getVisits(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));

        $visits = MrVisit::where('mr_user_id', $user->id)
            ->whereDate('visit_date', $date)
            ->with(['doctorUser'])
            ->latest()
            ->get();

        return response()->json([
            'visits' => $visits,
        ]);
    }

    // POST /api/mr/onboard-doctor
    public function onboardDoctor(Request $request)
    {
        $request->validate([
            'role' => 'required|in:gp,specialist,diagnostic_center',
            'name' => 'required|string|max:190',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'email' => 'nullable|email|max:190|unique:users,email',
            'degree' => 'nullable|string|max:120',
            'specialty_id' => 'nullable|integer',
            'hospital_name' => 'nullable|string|max:190',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'profile_photo' => 'nullable|image|max:5048',
            'clinic_photo' => 'nullable|image|max:5048',
        ]);

        $role = $request->role;
        $user = User::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email ?: $request->mobile.'@specialistconnectpro.com',
            'password' => Hash::make(Str::random(12)),
            'role' => $role,
            'status' => 'active',
        ]);

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profiles/'.$user->id, 'public');
        }

        $clinicPhotoPath = null;
        if ($request->hasFile('clinic_photo')) {
            $clinicPhotoPath = $request->file('clinic_photo')->store('clinics/'.$user->id, 'public');
        }

        if ($role === 'gp') {
            Gp::create([
                'user_id' => $user->id,
                'hospital_name' => $request->hospital_name ?? $request->name.' Clinic',
                'clinic_address' => $request->address,
                'degree' => $request->degree,
                'profile_photo_path' => $profilePhotoPath,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        } elseif ($role === 'specialist') {
            Specialist::create([
                'user_id' => $user->id,
                'specialty_id' => $request->specialty_id,
                'hospital_name' => $request->hospital_name ?? $request->name.' Hospital',
                'clinic_address' => $request->address,
                'profile_photo_path' => $profilePhotoPath,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        } elseif ($role === 'diagnostic_center') {
            DiagnosticCenter::create([
                'user_id' => $user->id,
                'center_name' => $request->hospital_name ?? $request->name.' Diagnostic Center',
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        }

        return response()->json([
            'message' => 'Doctor onboarded successfully!',
            'doctor_user' => $user,
        ]);
    }

    // GET /api/mr/non-active-gps
    public function nonActiveGps(Request $request)
    {
        $fifteenDaysAgo = Carbon::now()->subDays(15);
        $gps = Gp::with('user')
            ->where('created_at', '<=', $fifteenDaysAgo)
            ->whereDoesntHave('referrals')
            ->get();

        return response()->json([
            'gps' => $gps,
        ]);
    }

    // GET /api/mr/expiring-subscriptions
    public function expiringSubscriptions(Request $request)
    {
        $sevenDaysLater = Carbon::now()->addDays(7);
        $subscriptions = UserSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereBetween('expires_at', [Carbon::now(), $sevenDaysLater])
            ->get();

        return response()->json([
            'subscriptions' => $subscriptions,
        ]);
    }

    // POST /api/mr/payment-link
    public function generatePaymentLink(Request $request)
    {
        $request->validate([
            'doctor_user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $doctor = User::findOrFail($request->doctor_user_id);

        $paymentUrl = url('/payments/razorpay/checkout?user_id='.$doctor->id.'&plan_id='.$plan->id);
        $qrPayload = 'upi://pay?pa=specialistconnectpro@icici&pn=SpecialistConnectPro&am='.$plan->price.'&tn=Subscription_'.$plan->id;

        return response()->json([
            'doctor' => $doctor,
            'plan' => $plan,
            'payment_url' => $paymentUrl,
            'qr_payload' => $qrPayload,
        ]);
    }

    // GET /api/mr/collaterals
    public function getCollaterals(Request $request)
    {
        $collaterals = Collateral::where('is_active', true)->latest()->get();

        return response()->json([
            'collaterals' => $collaterals,
        ]);
    }

    // POST /api/mr/leaves
    public function applyLeave(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $leave = MrLeave::create([
            'mr_user_id' => $user->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Leave application submitted',
            'leave' => $leave,
        ]);
    }

    // GET /api/mr/leaves
    public function getLeaves(Request $request)
    {
        $user = $request->user();
        $leaves = MrLeave::where('mr_user_id', $user->id)->latest()->get();

        return response()->json([
            'leaves' => $leaves,
        ]);
    }

    // GET /api/mr/calendar
    public function calendar(Request $request)
    {
        $user = $request->user();
        $year = (int) $request->query('year', date('Y'));
        $month = (int) $request->query('month', date('m'));

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $visits = MrVisit::where('mr_user_id', $user->id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->get();

        $attendances = MrAttendance::where('mr_user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $leaves = MrLeave::where('mr_user_id', $user->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })->get();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'visits' => $visits,
            'attendances' => $attendances,
            'leaves' => $leaves,
        ]);
    }

    // GET /api/mr/profile
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $mr = Mr::where('user_id', $user->id)->first();

        return response()->json([
            'user' => $user,
            'mr_profile' => $mr,
            'shift_settings' => [
                'shift_start' => '09:30 AM',
                'shift_end' => '06:30 PM',
                'attendance_popup_enabled' => true,
            ],
        ]);
    }

    // POST /api/mr/change-password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $user = $request->user();
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully!']);
    }
}
