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
use Illuminate\Support\Facades\DB;
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
        // M3-20: `user_subscriptions` has no `expires_at` column — the expiry column
        // is `ends_at`. This 500'd the whole dashboard with SQLSTATE 42S22.
        $sevenDaysLater = Carbon::now()->addDays(7);
        $expiringSubscriptionsCount = UserSubscription::where('status', 'active')
            ->whereBetween('ends_at', [Carbon::now(), $sevenDaysLater])
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
        $data = $request->validate([
            'role' => 'required|in:gp,specialist,diagnostic_center',
            'name' => 'required|string|max:190',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'email' => 'nullable|email|max:190|unique:users,email',

            // Shared practice details
            'degree' => 'nullable|string|max:120',
            'clinic_name' => 'nullable|string|max:190',
            'hospital_name' => 'nullable|string|max:190',
            'address' => 'nullable|string|max:255',
            'clinic_city' => 'nullable|string|max:120',
            'clinic_pincode' => 'nullable|string|max:12',
            'registration_number' => 'nullable|string|max:100',
            'council_name' => 'nullable|string|max:150',

            // Specialist-only details
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'years_of_experience' => 'nullable|integer|min:0|max:80',
            'bio' => 'nullable|string|max:2000',

            // Diagnostic-center-only details
            'center_name' => 'nullable|string|max:190',
            'center_type' => 'nullable|string|max:80',
            'micro_area' => 'nullable|string|max:190',
            'alternate_number' => 'nullable|string|max:20',
            'authorized_person_name' => 'nullable|string|max:190',
            'authorized_person_role' => 'nullable|string|max:120',

            // Geotag + capture proofs
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'profile_photo' => 'nullable|image|max:5048',
            'clinic_photo' => 'nullable|image|max:5048',
            'visiting_card' => 'nullable|image|max:5048',
        ]);

        $role = $data['role'];
        $mrUserId = $request->user()->id;
        // `users.role` is an enum of gp/specialist/hospital_admin/admin, so a
        // diagnostic center is stored the way the rest of the platform models
        // it: role=specialist with role_subtype=diagnostic_center. Writing
        // role='diagnostic_center' directly raised a data-truncation error and
        // 500'd the whole request.
        $userRole = $role === 'diagnostic_center' ? 'specialist' : $role;
        $roleSubtype = match ($role) {
            'diagnostic_center' => 'diagnostic_center',
            'specialist' => 'specialist',
            default => null,
        };

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? $data['mobile'].'@specialistconnectpro.com',
            'password' => Hash::make(Str::random(12)),
            'role' => $userRole,
            'role_subtype' => $roleSubtype,
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

        $visitingCardPath = null;
        if ($request->hasFile('visiting_card')) {
            $visitingCardPath = $request->file('visiting_card')->store('mr/visiting-cards/'.$user->id, 'public');
        }

        // The profile row is created in one transaction with the user so a
        // failure never leaves a login-able account with no doctor record.
        DB::transaction(function () use (
            $role,
            $data,
            $user,
            $mrUserId,
            $profilePhotoPath,
            $clinicPhotoPath,
            $visitingCardPath
        ) {
            if ($role === 'gp') {
                Gp::create([
                    'user_id' => $user->id,
                    'clinic_name' => $data['clinic_name'] ?? $data['hospital_name'] ?? $data['name'].' Clinic',
                    'address_line' => $data['address'] ?? null,
                    'city' => $data['clinic_city'] ?? null,
                    'pincode' => $data['clinic_pincode'] ?? null,
                    'degree' => $data['degree'] ?? null,
                    'registration_number' => $data['registration_number'] ?? null,
                    'registration_council' => $data['council_name'] ?? null,
                    'profile_photo_path' => $profilePhotoPath,
                    'clinic_photo_path' => $clinicPhotoPath,
                    'visiting_card_path' => $visitingCardPath,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'onboarded_by_mr_id' => $mrUserId,
                ]);
            } elseif ($role === 'specialist') {
                Specialist::create([
                    'user_id' => $user->id,
                    'specialty_id' => $data['specialty_id'] ?? null,
                    'clinic_name' => $data['clinic_name'] ?? $data['hospital_name'] ?? null,
                    'hospital_name' => $data['hospital_name'] ?? $data['clinic_name'] ?? $data['name'].' Hospital',
                    'clinic_address' => $data['address'] ?? null,
                    'clinic_city' => $data['clinic_city'] ?? null,
                    'clinic_pincode' => $data['clinic_pincode'] ?? null,
                    'medical_council_registration_no' => $data['registration_number'] ?? null,
                    'medical_council_name' => $data['council_name'] ?? null,
                    'whatsapp_number' => $data['whatsapp_number'] ?? $data['mobile'],
                    'years_of_experience' => $data['years_of_experience'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'education_primary' => isset($data['degree']) ? ['degree' => $data['degree']] : null,
                    'profile_photo_path' => $profilePhotoPath,
                    'clinic_photo_path' => $clinicPhotoPath,
                    'visiting_card_path' => $visitingCardPath,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'onboarded_by_mr_id' => $mrUserId,
                    'is_active' => true,
                ]);
            } elseif ($role === 'diagnostic_center') {
                DiagnosticCenter::create([
                    'user_id' => $user->id,
                    // The column is `name`; the previous `center_name` key was
                    // silently discarded, so every field-onboarded center ended
                    // up with an empty name.
                    'name' => $data['center_name'] ?? $data['name'],
                    'center_type' => $data['center_type'] ?? null,
                    'address' => $data['address'] ?? null,
                    'micro_area' => $data['micro_area'] ?? null,
                    'email' => $data['email'] ?? null,
                    'mobile_number' => $data['mobile'],
                    'alternate_number' => $data['alternate_number'] ?? null,
                    'authorized_person_name' => $data['authorized_person_name'] ?? null,
                    'authorized_person_role' => $data['authorized_person_role'] ?? null,
                    'profile_photo_path' => $profilePhotoPath,
                    'clinic_photo_path' => $clinicPhotoPath,
                    'visiting_card_path' => $visitingCardPath,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'onboarded_by_mr_id' => $mrUserId,
                    'status' => 'active',
                ]);
            }
        });

        return response()->json([
            'message' => 'Doctor onboarded successfully!',
            'doctor_user' => $user->fresh(),
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
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        // Two independent bugs lived here, the second masked by the first:
        //   1. M3-20 -- `expires_at` does not exist; the column is `ends_at`.
        //   2. `with(['user', 'plan'])` referenced an undefined relation. The model
        //      defines `subscriptionPlan()`, so even with the column fixed this
        //      threw RelationNotFoundException.
        $paginator = UserSubscription::with(['user', 'subscriptionPlan'])
            ->where('status', 'active')
            ->whereBetween('ends_at', [Carbon::now(), $sevenDaysLater])
            ->orderBy('ends_at')
            ->paginate($perPage);

        // Explicit shape instead of leaking Eloquent relation keys: the MR app reads
        // `plan` and `expires_at`, and `with(['subscriptionPlan'])` would have
        // serialised as `subscription_plan`. Also keeps the response bounded.
        $items = collect($paginator->items())->map(static function (UserSubscription $subscription): array {
            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'expires_at' => $subscription->ends_at?->toDateString(),
                'plan_name' => $subscription->plan_name_snapshot,
                'price' => $subscription->price_snapshot,
                'plan' => [
                    'id' => $subscription->subscriptionPlan?->id,
                    'name' => $subscription->subscriptionPlan?->name,
                    'price' => $subscription->subscriptionPlan?->price,
                ],
                'user' => [
                    'id' => $subscription->user?->id,
                    'name' => $subscription->user?->name,
                    'mobile' => $subscription->user?->mobile,
                    'email' => $subscription->user?->email,
                ],
            ];
        })->values();

        return response()->json([
            'subscriptions' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
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
