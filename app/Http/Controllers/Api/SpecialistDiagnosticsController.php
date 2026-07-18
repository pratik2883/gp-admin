<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticService;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SpecialistDiagnosticsController extends Controller
{
    // GET /api/specialist/diagnostics/locations
    public function locations(Request $request)
    {
        $items = Location::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'locations' => $items,
            'default_location_id' => null,
        ]);
    }

    // GET /api/specialist/diagnostics/centers?location_id={id}&service_type_ids[]=1&day=monday&at=14:30
    public function centers(Request $request)
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer'],
            'day' => ['nullable', 'string'],
            'at' => ['nullable', 'string'],
        ]);

        $locationId = (int) $data['location_id'];
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

    // GET /api/specialist/diagnostics/services?center_id={id}
    public function services(Request $request)
    {
        $data = $request->validate([
            'center_id' => ['required', 'integer', 'exists:diagnostic_centers,id'],
        ]);

        $centerId = (int) $data['center_id'];

        $services = DiagnosticService::query()
            ->where('diagnostic_center_id', $centerId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'diagnostic_service_type_id'])
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
