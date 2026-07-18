<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GpDashboardController extends Controller
{
    /**
     * GET /api/gp/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = $user?->gp;

        if (! $gp || $gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $base = Referral::query()->where('gp_id', $gp->id);

        $total = (clone $base)->count();
        $sent = (clone $base)->where('status', 'sent')->count();
        $accepted = (clone $base)->where('status', 'accepted')->count();
        $consulted = (clone $base)->where('status', 'consulted')->count();
        $closed = (clone $base)->where('status', 'closed')->count();

        $recent = (clone $base)
            ->with(['specialist.user', 'hospital'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Referral $ref) {
                return [
                    'id' => $ref->id,
                    'patient_name' => $ref->patient_name,
                    'status' => $ref->status,
                    'referred_on' => optional($ref->created_at)->toDateString(),
                    'specialist_name' => optional($ref->specialist?->user)->name,
                    'specialist_hospital' => optional($ref->hospital)->name,
                ];
            })
            ->values();

        return response()->json([
            'total_referrals' => $total,
            'pending_count' => $sent,
            'accepted_count' => $accepted,
            'consulted_count' => $consulted,
            'closed_count' => $closed,
            'recent_referrals' => $recent,
        ]);
    }

    /**
     * GET /api/gp/dashboard/recommended-specialists
     */
    public function recommendedSpecialists(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = $user?->gp;

        if (! $gp || $gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $gpDefaultAreaId = $gp->default_location_id;
        if (! $gpDefaultAreaId && $gp->city) {
            $gpDefaultAreaId = Location::query()
                ->where('name', $gp->city)
                ->value('id');
        }

        $build = function (?int $locationId) use ($perPage) {
            $query = Specialist::query()
            ->join('users', 'users.id', '=', 'specialists.user_id')
            ->where('specialists.is_active', true)
            ->select(['specialists.*']);

            if (! $locationId) {
                $query
                ->selectRaw("CASE WHEN specialists.is_premium = 1 THEN 'other_area_premium' ELSE 'other_area_other' END as match_type")
                ->selectRaw('CASE WHEN specialists.is_premium = 1 THEN 2 ELSE 3 END as match_rank')
                ->selectRaw('9999 as nearby_sort');
            } else {
                $matchRankSql = <<<'SQL'
CASE
    WHEN specialists.is_premium = 1 AND specialists.location_id = ? THEN 1
    WHEN specialists.is_premium = 1 AND (nlm.nearby_location_id IS NULL) AND (specialists.location_id <> ? OR specialists.location_id IS NULL) THEN 2
    WHEN specialists.is_premium = 1 AND nlm.nearby_location_id IS NOT NULL THEN 3
    WHEN specialists.is_premium = 0 AND nlm.nearby_location_id IS NOT NULL THEN 4
    WHEN specialists.is_premium = 0 AND specialists.location_id = ? THEN 5
    ELSE 999
END as match_rank
SQL;

                $matchTypeSql = <<<'SQL'
CASE
    WHEN specialists.is_premium = 1 AND specialists.location_id = ? THEN 'same_area_premium'
    WHEN specialists.is_premium = 1 AND (nlm.nearby_location_id IS NULL) AND (specialists.location_id <> ? OR specialists.location_id IS NULL) THEN 'other_area_premium'
    WHEN specialists.is_premium = 1 AND nlm.nearby_location_id IS NOT NULL THEN 'nearby_area_premium'
    WHEN specialists.is_premium = 0 AND nlm.nearby_location_id IS NOT NULL THEN 'nearby_area_other'
    WHEN specialists.is_premium = 0 AND specialists.location_id = ? THEN 'same_area_other'
    ELSE 'other'
END as match_type
SQL;

                $query
                ->leftJoin('nearby_location_mappings as nlm', function ($join) use ($locationId) {
                    $join->on('nlm.nearby_location_id', '=', 'specialists.location_id')
                        ->where('nlm.location_id', '=', $locationId);
                })
                ->where(function ($q) use ($locationId) {
                    $q
                        ->where('specialists.is_premium', true)
                        ->orWhere(function ($inner) use ($locationId) {
                            $inner
                                ->where('specialists.is_premium', false)
                                ->where('specialists.location_id', $locationId);
                        })
                        ->orWhere(function ($inner) {
                            $inner
                                ->where('specialists.is_premium', false)
                                ->whereNotNull('nlm.nearby_location_id');
                        });
                })
                ->selectRaw($matchRankSql, [$locationId, $locationId, $locationId])
                ->selectRaw($matchTypeSql, [$locationId, $locationId, $locationId])
                ->selectRaw('CASE WHEN nlm.nearby_location_id IS NOT NULL THEN nlm.sort_order ELSE 9999 END as nearby_sort');
            }

            $query
            ->selectRaw('CASE WHEN specialists.priority_order IS NULL THEN 1 ELSE 0 END as priority_is_null')
            ->orderBy('match_rank')
            ->orderBy('nearby_sort')
            ->orderBy('priority_is_null')
            ->orderBy('specialists.priority_order')
            ->orderBy('users.name')
            ->limit($perPage);

            $specialists = $query->get();
            $specialists->load([
                'user',
                'location',
                'specialty',
                'hospitals' => fn ($q) => $q->orderByPivot('created_at'),
            ]);

            return $specialists;
        };

        $specialists = $build($gpDefaultAreaId);
        if ($specialists->isEmpty() && $gpDefaultAreaId) {
            $specialists = $build(null);
        }

        $specialists->load([
            'user',
            'location',
            'specialty',
            'hospitals' => fn ($q) => $q->orderByPivot('created_at'),
        ]);

        $items = $specialists->map(function (Specialist $specialist) {
            $categoryCode = $specialist->is_premium ? 'premium' : ($specialist->specialty?->code ?: $specialist->primary_specialization);
            $primaryHospital = $specialist->hospitals->first();
            $specialtyLabel = $specialist->specialty?->plain_label ?: ($specialist->specialty?->name ?: $specialist->primary_specialization);

            return [
                'id' => $specialist->id,
                'specialist_id' => $specialist->id,
                'name' => $specialist->user?->name,
                'speciality' => $specialtyLabel,
                'hospital_name' => $specialist->hospital_name ?: $primaryHospital?->name,
                'clinic_address' => $specialist->clinic_address,
                'years_of_experience' => $specialist->years_of_experience,
                'languages' => $specialist->languages ?? [],
                'consultation_flags' => [
                    'in_person' => (bool) $specialist->consultation_in_person,
                    'teleconsult' => (bool) $specialist->consultation_teleconsult,
                ],
                'location_id' => $specialist->location_id,
                'area_name' => $specialist->location?->name,
                'is_premium' => (bool) $specialist->is_premium,
                'category_code' => $categoryCode,
                'match_type' => $specialist->match_type,
                'priority_rank' => (int) ($specialist->match_rank ?? 999),
                'hospital_id' => $primaryHospital?->id,
                'mapped_hospital_name' => $primaryHospital?->name,
                'is_super_specialist' => (bool) ($primaryHospital?->pivot?->is_super_specialist ?? false),
                'department' => $primaryHospital?->pivot?->department,
                'role' => $primaryHospital?->pivot?->role,
            ];
        })->values();

        return response()->json([
            'gp_default_area_id' => $gpDefaultAreaId,
            'recommended_specialists' => $items,
        ]);
    }

    /**
     * GET /api/gp/dashboard/specialty-categories
     */
    public function specialtyCategories(Request $request): JsonResponse
    {
        $user = $request->user();
        $gp = $user?->gp;

        if (! $gp || $gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $locationId = $request->query('location_id') ? (int) $request->query('location_id') : null;
        if (! $locationId) {
            $locationId = $gp->default_location_id ?: null;
        }

        $gpDefaultAreaId = $gp->default_location_id ?: null;
        $effectiveLocationId = $locationId;

        $union = DB::query()
            ->fromSub(function ($q) use ($effectiveLocationId) {
                $primary = DB::table('specialists')
                    ->where('is_active', true)
                    ->when($effectiveLocationId, fn ($inner) => $inner->where('location_id', $effectiveLocationId))
                    ->whereNotNull('specialty_id')
                    ->selectRaw('specialty_id as specialty_id, id as specialist_id');

                $additional = DB::table('specialist_specialties')
                    ->join('specialists', 'specialists.id', '=', 'specialist_specialties.specialist_id')
                    ->where('specialists.is_active', true)
                    ->when($effectiveLocationId, fn ($inner) => $inner->where('specialists.location_id', $effectiveLocationId))
                    ->selectRaw('specialist_specialties.specialty_id as specialty_id, specialists.id as specialist_id');

                $q->from($primary->unionAll($additional), 'x');
            }, 't')
            ->selectRaw('specialty_id, COUNT(DISTINCT specialist_id) as cnt')
            ->groupBy('specialty_id');

        $counts = $union->pluck('cnt', 'specialty_id')->map(fn ($v) => (int) $v)->toArray();

        if (empty($counts) && $effectiveLocationId) {
            $effectiveLocationId = null;

            $fallbackUnion = DB::query()
                ->fromSub(function ($q) {
                    $primary = DB::table('specialists')
                        ->where('is_active', true)
                        ->whereNotNull('specialty_id')
                        ->selectRaw('specialty_id as specialty_id, id as specialist_id');

                    $additional = DB::table('specialist_specialties')
                        ->join('specialists', 'specialists.id', '=', 'specialist_specialties.specialist_id')
                        ->where('specialists.is_active', true)
                        ->selectRaw('specialist_specialties.specialty_id as specialty_id, specialists.id as specialist_id');

                    $q->from($primary->unionAll($additional), 'x');
                }, 't')
                ->selectRaw('specialty_id, COUNT(DISTINCT specialist_id) as cnt')
                ->groupBy('specialty_id');

            $counts = $fallbackUnion->pluck('cnt', 'specialty_id')->map(fn ($v) => (int) $v)->toArray();
        }

        $specialties = Specialty::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys($counts))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'code', 'icon_key', 'plain_label', 'sort_order', 'is_active']);

        $items = $specialties->map(fn (Specialty $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->code,
            'icon_key' => $s->icon_key,
            'label' => $s->plain_label ?: $s->name,
            'sort_order' => (int) $s->sort_order,
            'is_active' => (bool) $s->is_active,
            'specialist_count' => $counts[$s->id] ?? 0,
        ])->values();

        return response()->json([
            'gp_default_area_id' => $gpDefaultAreaId,
            'location_id' => $effectiveLocationId,
            'categories' => $items,
        ]);
    }
}
