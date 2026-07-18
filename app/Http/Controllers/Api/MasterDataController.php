<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Settings\GeneralSettings;
use App\Services\CcaVenuePaymentService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function locations(Request $request)
    {
        $items = Location::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function hospitals(Request $request)
    {
        $query = Hospital::query()->select(['id', 'name', 'city', 'location_id'])->orderBy('name');
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }
        if ($locationId = $request->query('location_id')) {
            $query->where('location_id', $locationId);
        }

        return response()->json($query->get());
    }

    public function specialties(Request $request)
    {
        $items = Specialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'icon_key', 'plain_label', 'sort_order', 'is_active']);

        if ($request->query('format') === 'strings') {
            return response()->json(
                $items->map(fn (Specialty $s) => $s->plain_label ?: $s->name)->values()
            );
        }

        return response()->json(
            $items
                ->map(fn (Specialty $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->code,
                    'icon_key' => $s->icon_key,
                    'label' => $s->plain_label ?: $s->name,
                    'sort_order' => (int) $s->sort_order,
                    'is_active' => (bool) $s->is_active,
                ])
                ->values()
        );
    }

    public function specialists(Request $request)
    {
        $query = Specialist::with(['user:id,name', 'specialty:id,name,code,plain_label'])
            ->select(['id', 'user_id', 'primary_specialization', 'clinic_city', 'specialty_id']);
        if ($city = $request->query('city')) {
            $query->where('clinic_city', $city);
        }
        if ($specialty = $request->query('specialty')) {
            $specialtyRecord = Specialty::query()
                ->where('code', $specialty)
                ->orWhere('name', $specialty)
                ->orWhere('plain_label', $specialty)
                ->first();
            if ($specialtyRecord) {
                $query->where(function ($q) use ($specialty, $specialtyRecord) {
                    $q->where('specialty_id', $specialtyRecord->id)
                        ->orWhere('primary_specialization', $specialty)
                        ->orWhere('primary_specialization', $specialtyRecord->name);
                    if (filled($specialtyRecord->plain_label)) {
                        $q->orWhere('primary_specialization', $specialtyRecord->plain_label);
                    }
                });
            } else {
                $query->where('primary_specialization', $specialty);
            }
        }
        $items = $query->orderByDesc('id')->get()->map(function (Specialist $s) {
            $label = $s->specialty?->plain_label ?: ($s->specialty?->name ?: $s->primary_specialization);

            return [
                'id' => $s->id,
                'name' => $s->user?->name,
                'specialty' => $label,
                'city' => $s->clinic_city,
            ];
        });

        return response()->json($items);
    }

    public function diagnosticServiceTypes(Request $request)
    {
        $items = DB::table('diagnostic_service_types')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'sort_order'])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'slug' => $r->code,
                'label' => $r->name,
                'sort_order' => (int) $r->sort_order,
            ])
            ->values();

        return response()->json($items);
    }

    public function featureFlags(Request $request)
    {
        $settings = app(GeneralSettings::class);
        $platform = strtolower((string) $request->query('platform', ''));
        $addressApiKey = match ($platform) {
            'ios' => $settings->google_places_api_key_ios,
            'android' => $settings->google_places_api_key_android,
            default => $settings->google_places_api_key_android ?: $settings->google_places_api_key_ios,
        };
        $addressAutocompleteEnabled = (bool) $settings->enable_google_address_autocomplete && filled($addressApiKey);

        return response()->json([
            'allow_profile_videos_for_specialists' => (bool) $settings->allow_profile_videos_for_specialists,
            'allow_profile_certificates_for_specialists' => (bool) $settings->allow_profile_certificates_for_specialists,
            'enable_google_address_autocomplete' => $addressAutocompleteEnabled,
            'google_places_api_key' => $addressAutocompleteEnabled ? $addressApiKey : null,
            'google_places_country_code' => strtoupper((string) ($settings->google_places_country_code ?: 'IN')),
            'enable_subscription_payments' => app(CcaVenuePaymentService::class)->paymentsEnabled() || app(RazorpayService::class)->paymentsEnabled(),
            'payment_gateway' => app(RazorpayService::class)->paymentsEnabled() ? 'razorpay' : (app(CcaVenuePaymentService::class)->paymentsEnabled() ? 'ccavenue' : null),
        ]);
    }

    public function subscriptionPlans(Request $request)
    {
        $category = (string) $request->query('category', '');
        $paymentsEnabled = app(CcaVenuePaymentService::class)->paymentsEnabled() || app(RazorpayService::class)->paymentsEnabled();

        if (! $paymentsEnabled) {
            return response()->json([
                'category' => $category !== '' ? $category : null,
                'enabled' => false,
                'plans' => [],
                'groups' => [],
            ]);
        }

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderBy('sort_order')
            ->orderBy('duration_months')
            ->orderBy('price')
            ->get();

        $items = $plans->map(fn (SubscriptionPlan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'category' => $plan->category,
            'plan_family' => $plan->plan_family,
            'bed_slab' => $plan->bed_slab,
            'duration_months' => (int) $plan->duration_months,
            'trial_days' => (int) $plan->trial_days,
            'bonus_months' => (int) $plan->bonus_months,
            'is_trialable' => (bool) $plan->is_trialable,
            'offer_label' => $plan->offer_label,
            'offer_badge_color' => $plan->offer_badge_color,
            'price' => (float) $plan->price,
            'currency' => $plan->currency ?: 'INR',
            'description' => $plan->description,
            'feature_points' => $plan->feature_points ?? [],
            'sort_order' => $plan->sort_order,
        ])->values();

        $groups = match ($category) {
            'specialist' => $items
                ->groupBy(fn (array $item) => $item['plan_family'] ?: 'other')
                ->map(fn ($plans, $key) => [
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', (string) $key)).' Plan',
                    'plans' => $plans->values(),
                ])
                ->values(),
            'hospital' => $items
                ->groupBy(fn (array $item) => $item['bed_slab'] ?: 'other')
                ->map(fn ($plans, $key) => [
                    'key' => $key,
                    'label' => match ($key) {
                        'upto_25' => '25 Beds',
                        'upto_50' => '50 Beds',
                        'above_100' => '100+ Beds',
                        default => ucfirst(str_replace('_', ' ', (string) $key)),
                    },
                    'plans' => $plans->values(),
                ])
                ->values(),
            default => collect(),
        };

        return response()->json([
            'category' => $category !== '' ? $category : null,
            'enabled' => true,
            'plans' => $items,
            'groups' => $groups,
        ]);
    }
}
