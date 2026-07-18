<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticService;
use App\Models\Gp;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GpDiagnosticsController extends Controller
{
    public function locations(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $locations = Location::query()
            ->select(['locations.id', 'locations.name'])
            ->join('diagnostic_centers', 'diagnostic_centers.location_id', '=', 'locations.id')
            ->where('diagnostic_centers.status', 'active')
            ->distinct()
            ->orderBy('locations.name')
            ->get()
            ->values();

        return response()->json([
            'locations' => $locations,
            'default_location_id' => $gp->default_location_id,
        ]);
    }

    public function centers(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->first();

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer'],
            'day' => ['nullable', 'string'],
            'at' => ['nullable', 'string'],
        ]);

        $locationId = isset($data['location_id']) ? (int) $data['location_id'] : null;

        // Graceful fallback: if no location_id provided, use GP's default location
        if (! $locationId && $gp && $gp->default_location_id) {
            $locationId = (int) $gp->default_location_id;
        }

        // Return empty if still no location
        if (! $locationId) {
            return response()->json([
                'location_id' => null,
                'centers' => [],
            ]);
        }

        $serviceTypeIds = collect($data['service_type_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->values();
        $day = isset($data['day']) ? strtolower(trim((string) $data['day'])) : null;
        $at = isset($data['at']) ? trim((string) $data['at']) : null;
        $atTime = $at ? $this->normalizeTime($at) : null;

        $centers = $this->centersQuery($locationId, $serviceTypeIds->all(), $day, $atTime)
            ->get()
            ->values();

        return response()->json([
            'location_id' => $locationId,
            'centers' => $centers,
        ]);
    }

    public function services(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $data = $request->validate([
            'center_id' => ['required', 'integer', 'exists:diagnostic_centers,id'],
        ]);

        $centerId = (int) $data['center_id'];

        $services = DiagnosticService::query()
            ->where('diagnostic_center_id', $centerId)
            ->where('status', 'active')
            ->select(['id', 'name', 'diagnostic_service_type_id'])
            ->orderBy('name')
            ->get()
            ->values();

        return response()->json([
            'center_id' => $centerId,
            'services' => $services,
        ]);
    }

    private function centersQuery(int $locationId, array $serviceTypeIds, ?string $day, ?string $atTime)
    {
        $query = DiagnosticCenter::query()
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->select([
                'id',
                'name',
                'center_type',
                'address',
                'micro_area',
                'email',
                'mobile_number',
                'alternate_number',
                'opening_time',
                'closing_time',
                'available_days',
                'weekly_off',
            ])
            ->orderBy('name');

        if (count($serviceTypeIds) > 0) {
            $query->whereIn('id', function ($sub) use ($serviceTypeIds) {
                $sub->select('diagnostic_center_id')
                    ->from('diagnostic_services')
                    ->where('status', 'active')
                    ->whereIn('diagnostic_service_type_id', $serviceTypeIds);
            });
        }

        if ($day) {
            $query->where(function ($q) use ($day) {
                $q->whereNull('weekly_off')->orWhere('weekly_off', '!=', $day);
            });
            $query->where(function ($q) use ($day) {
                $q->whereNull('available_days')->orWhereJsonContains('available_days', $day);
            });
        }

        if ($atTime) {
            $query->where(function ($q) use ($atTime) {
                $q->whereNull('opening_time')->orWhereTime('opening_time', '<=', $atTime);
            });
            $query->where(function ($q) use ($atTime) {
                $q->whereNull('closing_time')->orWhereTime('closing_time', '>=', $atTime);
            });
        }

        return $query;
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
