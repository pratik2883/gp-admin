<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticService;
use App\Models\Specialist;
use App\Models\User;
use App\Services\CcaVenuePaymentService;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiagnosticCenterAuthController extends Controller
{
    // POST /api/diagnostic/register
    public function register(Request $request)
    {
        $subscriptions = app(SubscriptionService::class);
        $razorpay = app(RazorpayService::class);
        $cca = app(CcaVenuePaymentService::class);
        $paymentsEnabled = $razorpay->paymentsEnabled() || $cca->paymentsEnabled();
        $gateway = $razorpay->paymentsEnabled() ? 'razorpay' : ($cca->paymentsEnabled() ? 'ccavenue' : null);
        $validator = Validator::make($request->all(), [
            'center_name' => 'nullable|string|max:255',
            'name' => 'required_without:center_name|string|max:255',
            'email' => 'required|email|max:190|unique:users,email',
            'mobile_number' => 'required|string|max:20|unique:users,mobile',
            'password' => 'required|string|min:6',
            'subscription_plan_id' => [$paymentsEnabled ? 'required' : 'nullable', 'integer'],
            'location_id' => 'required|integer|exists:locations,id',
            'center_type' => 'required|string|max:80',
            'address' => 'required|string|max:255',
            'micro_area' => 'required|string|max:190',
            'alternate_number' => 'nullable|string|max:20',
            'opening_time' => 'required|string|max:20',
            'closing_time' => 'required|string|max:20',
            'available_days' => 'nullable|array',
            'available_days.*' => 'string|max:20',
            'weekly_off' => 'nullable|string|max:20',
            'authorized_person_name' => 'required|string|max:190',
            'authorized_person_role' => [
                'required',
                'string',
                Rule::in([
                    'center_head',
                    'lab_director',
                    'manager',
                    'administrator',
                    'coordinator',
                    'reception_head',
                    'owner',
                    'other',
                ]),
            ],
            'authorized_person_mobile' => 'required|string|max:20',
            'authorized_person_email' => 'required|email|max:190',
            'service_type_ids' => 'nullable|array|min:1',
            'service_type_ids.*' => 'integer|exists:diagnostic_service_types,id',
        ]);
        $data = $validator->validate();

        $plan = null;
        if ($paymentsEnabled && ! empty($data['subscription_plan_id'])) {
            $plan = $subscriptions->requireSelectablePlan($data['subscription_plan_id'], 'diagnostic_center');
        }

        [$user, $paymentPayload] = DB::transaction(function () use ($data, $subscriptions, $plan, $paymentsEnabled, $gateway, $razorpay, $cca) {
            $user = User::create([
                'name' => $data['center_name'] ?? $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile_number'],
                'role' => 'specialist',
                'role_subtype' => 'diagnostic_center',
                'status' => 'active',
                'password' => Hash::make($data['password']),
            ]);

            Specialist::firstOrCreate(['user_id' => $user->id], []);

            $center = DiagnosticCenter::create([
                'user_id' => $user->id,
                'location_id' => (int) $data['location_id'],
                'name' => $data['center_name'] ?? $data['name'],
                'center_type' => $data['center_type'],
                'address' => $data['address'],
                'micro_area' => $data['micro_area'],
                'email' => $data['email'],
                'mobile_number' => $data['mobile_number'],
                'alternate_number' => $data['alternate_number'] ?? null,
                'opening_time' => $this->normalizeTime($data['opening_time']),
                'closing_time' => $this->normalizeTime($data['closing_time']),
                'available_days' => $data['available_days'] ?? [],
                'weekly_off' => $data['weekly_off'] ?? null,
                'authorized_person_name' => $data['authorized_person_name'],
                'authorized_person_role' => $data['authorized_person_role'],
                'authorized_person_mobile' => $data['authorized_person_mobile'],
                'authorized_person_email' => $data['authorized_person_email'],
                'status' => 'active',
            ]);

            $serviceTypeIds = collect($data['service_type_ids'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->unique()
                ->values();

            if ($serviceTypeIds->isNotEmpty()) {
                $types = DB::table('diagnostic_service_types')
                    ->whereIn('id', $serviceTypeIds)
                    ->get(['id', 'name'])
                    ->keyBy('id');

                foreach ($serviceTypeIds as $typeId) {
                    $name = $types->get($typeId)?->name;
                    if (! $name) {
                        continue;
                    }
                    DiagnosticService::create([
                        'diagnostic_center_id' => $center->id,
                        'diagnostic_service_type_id' => $typeId,
                        'name' => $name,
                        'status' => 'active',
                    ]);
                }
            }

            $paymentPayload = null;
            if ($paymentsEnabled && $plan !== null) {
                $subscription = $subscriptions->createPendingSubscription($user, $plan, [
                    'source' => 'diagnostic_register',
                    'role_subtype' => 'diagnostic_center',
                ], true);

                if ($gateway === 'razorpay') {
                    $paymentPayload = $razorpay->createOrder($subscription, $user);
                } else {
                    $transaction = $cca->createTransaction($subscription, $user);
                    $paymentPayload = $cca->buildCheckoutPayload($transaction, $user);
                }
            }

            return [$user, $paymentPayload];
        });

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'role_subtype' => $user->role_subtype,
                'gp_id' => $user->gp()->value('id'),
                'specialist_id' => $user->specialist()->value('id'),
                'diagnostic_center_id' => $user->diagnosticCenter()->value('id'),
                'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            ],
            'payment' => $paymentPayload,
        ], 201);
    }

    private function normalizeTime(string $value): string
    {
        $value = trim($value);

        foreach (['H:i', 'H:i:s', 'h:i A', 'g:i A'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $value);

                return $dt->format('H:i:s');
            } catch (\Throwable $e) {
            }
        }

        $ts = strtotime($value);
        if ($ts !== false) {
            return date('H:i:s', $ts);
        }

        return $value;
    }
}
