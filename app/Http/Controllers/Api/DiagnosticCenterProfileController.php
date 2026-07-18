<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiagnosticCenterProfileController extends Controller
{
    // GET /api/diagnostic/profile
    public function show(Request $request)
    {
        $user = $request->user();
        $center = DiagnosticCenter::with('location')
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'profile' => $this->profilePayload($center),
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
        ]);
    }

    // PATCH /api/diagnostic/profile
    public function update(Request $request)
    {
        $user = $request->user();
        $center = DiagnosticCenter::where('user_id', $user->id)->firstOrFail();

        $serviceTypeIds = $request->input('service_type_ids') ?? $request->input('services');

        $data = $request->validate([
            'center_name' => 'nullable|string|max:255',
            'name' => 'required_without:center_name|string|max:255',
            'center_type' => 'required|string|max:80',
            'location_id' => 'required|integer|exists:locations,id',
            'address' => 'required|string|max:255',
            'micro_area' => 'required|string|max:190',
            'email' => 'required|email|max:190',
            'mobile_number' => 'required|string|max:20',
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
            'services' => 'nullable|array|min:1',
            'services.*' => 'integer|exists:diagnostic_service_types,id',
        ]);

        $center->name = $data['center_name'] ?? $data['name'];
        $center->center_type = $data['center_type'];
        $center->location_id = (int) $data['location_id'];
        $center->address = $data['address'];
        $center->micro_area = $data['micro_area'];
        $center->email = $data['email'];
        $center->mobile_number = $data['mobile_number'];
        $center->alternate_number = $data['alternate_number'] ?? null;
        $center->opening_time = $this->normalizeTime($data['opening_time']);
        $center->closing_time = $this->normalizeTime($data['closing_time']);
        $center->available_days = $data['available_days'] ?? [];
        $center->weekly_off = $data['weekly_off'] ?? null;
        $center->authorized_person_name = $data['authorized_person_name'];
        $center->authorized_person_role = $data['authorized_person_role'];
        $center->authorized_person_mobile = $data['authorized_person_mobile'];
        $center->authorized_person_email = $data['authorized_person_email'];
        $center->status = $center->status ?: 'active';

        $center->save();

        $ids = collect($serviceTypeIds ?? $data['service_type_ids'] ?? $data['services'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $types = DB::table('diagnostic_service_types')
                ->whereIn('id', $ids)
                ->get(['id', 'name'])
                ->keyBy('id');

            $existing = DiagnosticService::query()
                ->where('diagnostic_center_id', $center->id)
                ->get();

            $existingByType = $existing
                ->whereNotNull('diagnostic_service_type_id')
                ->groupBy('diagnostic_service_type_id');

            foreach ($ids as $typeId) {
                $name = $types->get($typeId)?->name;
                if (! $name) {
                    continue;
                }

                $current = $existingByType->get($typeId);
                if ($current && $current->count() > 0) {
                    foreach ($current as $svc) {
                        $svc->update([
                            'name' => $name,
                            'status' => 'active',
                        ]);
                    }
                } else {
                    DiagnosticService::create([
                        'diagnostic_center_id' => $center->id,
                        'diagnostic_service_type_id' => $typeId,
                        'name' => $name,
                        'status' => 'active',
                    ]);
                }
            }

            $toDeactivate = $existing
                ->whereNotNull('diagnostic_service_type_id')
                ->whereNotIn('diagnostic_service_type_id', $ids->all());

            foreach ($toDeactivate as $svc) {
                $svc->update(['status' => 'inactive']);
            }
        }

        $center->load('location');

        return response()->json([
            'message' => 'Diagnostic center profile updated',
            'profile' => $this->profilePayload($center),
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
        ]);
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

    private function profilePayload(DiagnosticCenter $center): array
    {
        $serviceTypeIds = DiagnosticService::query()
            ->where('diagnostic_center_id', $center->id)
            ->where('status', 'active')
            ->whereNotNull('diagnostic_service_type_id')
            ->pluck('diagnostic_service_type_id')
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $center->id,
            'center_name' => $center->name,
            'name' => $center->name,
            'center_type' => $center->center_type,
            'address' => $center->address,
            'micro_area' => $center->micro_area,
            'location_id' => $center->location_id,
            'location_name' => $center->location?->name,
            'email' => $center->email,
            'mobile_number' => $center->mobile_number,
            'alternate_number' => $center->alternate_number,
            'services' => $serviceTypeIds,
            'service_type_ids' => $serviceTypeIds,
            'opening_time' => $center->opening_time ? substr((string) $center->opening_time, 0, 5) : null,
            'closing_time' => $center->closing_time ? substr((string) $center->closing_time, 0, 5) : null,
            'available_days' => $center->available_days ?? [],
            'weekly_off' => $center->weekly_off,
            'authorized_person_name' => $center->authorized_person_name,
            'authorized_person_role' => $center->authorized_person_role,
            'authorized_person_mobile' => $center->authorized_person_mobile,
            'authorized_person_email' => $center->authorized_person_email,
            'status' => $center->status,
        ];
    }
}
