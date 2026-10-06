<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\DiagnosticReferral;
use App\Models\DiagnosticService;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Notifications\DiagnosticReferralCreatedNotification;
use App\Notifications\ReferralCreatedNotification;
use App\Settings\GeneralSettings;
use App\Settings\WorkflowSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GpReferralController extends Controller
{
    // GET /api/gp/referrals/locations
    public function locations(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $defaultLocationId = $gp->default_location_id;

        $locations = Location::query()
            ->select(['id', 'name'])
            ->where('status', 'active')
            ->whereHas('specialists', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        if ($defaultLocationId && ! $locations->contains('id', $defaultLocationId)) {
            $default = Location::query()
                ->select(['id', 'name'])
                ->where('id', $defaultLocationId)
                ->first();
            if ($default) {
                $locations->prepend($default);
            }
        }

        if ($defaultLocationId) {
            $locations = $locations
                ->sortBy(fn (Location $l) => $l->id === $defaultLocationId ? '0-'.$l->name : '1-'.$l->name)
                ->values();
        }

        return response()->json([
            'default_location_id' => $defaultLocationId,
            'locations' => $locations->values(),
        ]);
    }

    // GET /api/gp/referrals/categories?location_id={id}
    public function categories(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $locationId = (int) $data['location_id'];

        $base = Specialist::query()
            ->where('is_active', true)
            ->where('location_id', $locationId);

        $union = DB::query()
            ->fromSub(function ($q) use ($locationId) {
                $primary = DB::table('specialists')
                    ->where('is_active', true)
                    ->where('location_id', $locationId)
                    ->whereNotNull('specialty_id')
                    ->selectRaw('specialty_id as specialty_id, id as specialist_id');

                $additional = DB::table('specialist_specialties')
                    ->join('specialists', 'specialists.id', '=', 'specialist_specialties.specialist_id')
                    ->where('specialists.is_active', true)
                    ->where('specialists.location_id', $locationId)
                    ->selectRaw('specialist_specialties.specialty_id as specialty_id, specialists.id as specialist_id');

                $q->from($primary->unionAll($additional), 'x');
            }, 't')
            ->selectRaw('specialty_id, COUNT(DISTINCT specialist_id) as cnt')
            ->groupBy('specialty_id');

        $counts = $union->pluck('cnt', 'specialty_id')->map(fn ($v) => (int) $v)->toArray();

        $specialties = Specialty::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys($counts))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'icon_key', 'plain_label', 'sort_order', 'is_active']);

        $items = $specialties
            ->map(fn (Specialty $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->code,
                'icon_key' => $s->icon_key,
                'label' => $s->plain_label ?: $s->name,
                'sort_order' => (int) $s->sort_order,
                'is_active' => (bool) $s->is_active,
                'specialist_count' => $counts[$s->id] ?? 0,
            ])
            ->values();

        return response()->json([
            'location_id' => $locationId,
            'categories' => $items,
        ]);
    }

    // GET /api/gp/referrals/specialists?location_id={id}&category={code}
    public function specialists(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'category' => ['nullable', 'string'],
        ]);

        $locationId = (int) $data['location_id'];
        $specialtyId = array_key_exists('specialty_id', $data) ? (int) $data['specialty_id'] : null;
        $category = trim((string) ($data['category'] ?? ''));

        $query = \App\Models\Specialist::query()
            ->join('users', 'users.id', '=', 'specialists.user_id')
            ->where('specialists.is_active', true)
            ->where('specialists.location_id', $locationId)
            ->select(['specialists.*']);

        if ($specialtyId) {
            $query->where(function ($q) use ($specialtyId) {
                $q->where('specialists.specialty_id', $specialtyId)
                    ->orWhereExists(function ($sq) use ($specialtyId) {
                        $sq->selectRaw('1')
                            ->from('specialist_specialties')
                            ->whereColumn('specialist_specialties.specialist_id', 'specialists.id')
                            ->where('specialist_specialties.specialty_id', $specialtyId);
                    });
            });
        } elseif ($category !== '') {
            $specialty = Specialty::query()
                ->where('code', $category)
                ->orWhere('name', $category)
                ->orWhere('plain_label', $category)
                ->first();

            if ($specialty) {
                $query->where(function ($q) use ($specialty, $category) {
                    $q->where('specialists.specialty_id', $specialty->id)
                        ->orWhere('specialists.primary_specialization', $category)
                        ->orWhere('specialists.primary_specialization', $specialty->name);
                    if (filled($specialty->plain_label)) {
                        $q->orWhere('specialists.primary_specialization', $specialty->plain_label);
                    }
                });
            } else {
                $query->where('specialists.primary_specialization', $category);
            }
        }

        $query
            ->selectRaw('CASE WHEN specialists.priority_order IS NULL THEN 1 ELSE 0 END as priority_is_null')
            ->orderByDesc('specialists.is_premium')
            ->orderBy('priority_is_null')
            ->orderBy('specialists.priority_order')
            ->orderBy('users.name');

        $specialists = $query->get();
        $specialists->load([
            'user',
            'location',
            'specialty',
            'hospitals' => fn ($q) => $q->orderByPivot('created_at'),
        ]);

        $items = $specialists->map(function (\App\Models\Specialist $specialist) {
            $primaryHospital = $specialist->hospitals->first();
            $specialtyLabel = $specialist->specialty?->plain_label ?: ($specialist->specialty?->name ?: $specialist->primary_specialization);
            $linkedHospitals = $specialist->hospitals
                ->map(fn (Hospital $hospital) => [
                    'id' => $hospital->id,
                    'name' => $hospital->name,
                    'department' => $hospital->pivot?->department,
                    'role' => $hospital->pivot?->role,
                    'is_super_specialist' => (bool) ($hospital->pivot?->is_super_specialist ?? false),
                ])
                ->values();

            return [
                'id' => $specialist->id,
                'name' => $specialist->user?->name,
                'profile_photo' => $specialist->profilePhotoUrl(),
                'speciality' => $specialtyLabel,
                'area_name' => $specialist->location?->name,
                'hospital_name' => $specialist->hospital_name ?: $primaryHospital?->name,
                'clinic_address' => $specialist->clinic_address,
                'years_of_experience' => $specialist->years_of_experience,
                'languages' => $specialist->languages ?? [],
                'consultation_flags' => [
                    'in_person' => (bool) $specialist->consultation_in_person,
                    'teleconsult' => (bool) $specialist->consultation_teleconsult,
                ],
                'is_premium' => (bool) $specialist->is_premium,
                'priority_order' => $specialist->priority_order === null ? null : (int) $specialist->priority_order,
                'hospital_id' => $primaryHospital?->id,
                'mapped_hospital_name' => $primaryHospital?->name,
                'is_super_specialist' => (bool) ($primaryHospital?->pivot?->is_super_specialist ?? false),
                'department' => $primaryHospital?->pivot?->department,
                'role' => $primaryHospital?->pivot?->role,
                'linked_hospitals' => $linkedHospitals,
            ];
        })->values();

        return response()->json([
            'location_id' => $locationId,
            'specialty_id' => $specialtyId,
            'specialists' => $items,
        ]);
    }

    // GET /api/gp/referrals/hospitals?location_id={id}
    public function hospitals(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $locationId = (int) $data['location_id'];

        $items = Hospital::query()
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'location_id']);

        return response()->json([
            'location_id' => $locationId,
            'hospitals' => $items,
        ]);
    }

    // GET /api/gp/referrals/hospital-locations
    public function hospitalLocations(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $locations = Location::query()
            ->select(['id', 'name'])
            ->where('status', 'active')
            ->whereHas('hospitals', fn ($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'locations' => $locations->values(),
        ]);
    }

    // GET /api/gp/referrals/hospitals/{id}/departments
    public function hospitalDepartments(Request $request, $id)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $hospital = Hospital::query()->findOrFail($id);

        $departments = DB::table('hospital_specialist')
            ->where('hospital_id', $hospital->id)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->map(fn ($v) => (string) $v)
            ->values();

        return response()->json([
            'hospital_id' => $hospital->id,
            'departments' => $departments,
        ]);
    }

    // GET /api/gp/referrals?status=&from=&to=&q=&page=&per_page=
    public function index(Request $request)
    {
        $user = $request->user();
        // Assuming GP record exists for this user.
        // In a real app, you might want to create it on registration or handle this case.
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $requestedType = strtolower((string) $request->query('referral_type', 'specialist'));

        if ($requestedType === 'diagnostic') {
            $query = DiagnosticReferral::query()
                ->with(['center'])
                ->where('gp_id', $gp->id);

            if ($status = $request->query('status')) {
                $status = strtolower((string) $status);
                if ($status === 'new' || $status === 'pending') {
                    $status = 'sent';
                }
                $query->where('status', $status);
            }

            if ($from = $request->query('from')) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to = $request->query('to')) {
                $query->whereDate('created_at', '<=', $to);
            }

            if ($q = $request->query('q')) {
                $q = trim((string) $q);
                $query->where(function ($inner) use ($q) {
                    $inner
                        ->where('lead_code', 'like', '%'.$q.'%')
                        ->orWhere('patient_name', 'like', '%'.$q.'%')
                        ->orWhere('patient_mobile', 'like', '%'.$q.'%')
                        ->orWhereHas('center', fn ($c) => $c->where('name', 'like', '%'.$q.'%'));
                });
            }

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $p = $query->orderByDesc('created_at')->paginate($perPage);
            $items = $p->getCollection()
                ->map(function (DiagnosticReferral $r) {
                    return [
                        'id' => $r->id,
                        'lead_code' => $r->lead_code,
                        'referral_type' => 'diagnostic',
                        'status' => $r->status,
                        'appointment_type' => $r->appointment_type,
                        'priority' => $r->priority,
                        'patient_name' => $r->patient_name,
                        'patient_mobile' => $r->patient_mobile,
                        'patient_age' => $r->patient_age,
                        'patient_gender' => $r->patient_gender,
                        'case_summary' => $r->case_summary,
                        'diagnostic_center' => [
                            'id' => $r->center?->id,
                            'name' => $r->center?->name,
                        ],
                        'created_at' => $r->created_at,
                        'accepted_at' => $r->accepted_at,
                        'consulted_at' => $r->consulted_at,
                        'closed_at' => $r->closed_at,
                    ];
                })
                ->values();

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => $p->currentPage(),
                    'last_page' => $p->lastPage(),
                    'total' => $p->total(),
                ],
            ]);
        }

        $referralType = in_array($requestedType, ['specialist', 'hospital'], true) ? $requestedType : 'specialist';

        $query = Referral::with(['specialist.user', 'hospital', 'gp.user'])
            ->where('gp_id', $gp->id);

        if ($referralType === 'hospital') {
            $query->where('referral_type', 'hospital');
        } else {
            $query->where(function ($q) {
                $q->whereNull('referral_type')->orWhere('referral_type', 'specialist');
            });
        }

        if ($status = $request->query('status')) {
            $status = strtolower((string) $status);
            if ($status === 'new' || $status === 'pending') {
                $status = 'sent';
            }
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($q = $request->query('q')) {
            $q = trim((string) $q);
            $query->where(function ($inner) use ($q) {
                $inner
                    ->where('lead_code', 'like', '%'.$q.'%')
                    ->orWhere('patient_name', 'like', '%'.$q.'%')
                    ->orWhere('patient_mobile', 'like', '%'.$q.'%')
                    ->orWhereHas('specialist.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', '%'.$q.'%'));
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $referrals = $query->orderByDesc('created_at')->paginate($perPage);

        return ReferralResource::collection($referrals);
    }

    // POST /api/gp/referrals
    public function store(Request $request)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        $general = app(GeneralSettings::class);
        $workflow = app(WorkflowSettings::class);
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $allowed = $general->allowedMimeTypes();
        $mimes = implode(',', $allowed);
        $maxKb = max(1, (int) floor($general->maxUploadBytes() / 1024));

        $referralType = strtolower((string) $request->input('referral_type', 'specialist'));

        $notes = $request->input('notes');
        if ($notes !== null && $request->input('case_summary') === null) {
            $request->merge(['case_summary' => $notes]);
        }

        $visitType = $request->input('visit_type');
        if ($visitType !== null && $request->input('appointment_type') === null) {
            $request->merge(['appointment_type' => $visitType]);
        }

        $appointmentType = strtolower((string) $request->input('appointment_type', ''));
        if (in_array($appointmentType, ['opd', 'ipd'], true)) {
            $request->merge(['appointment_type' => $appointmentType]);
        }

        $age = $request->input('age');
        if ($age !== null && $request->input('patient_age') === null) {
            $request->merge(['patient_age' => $age]);
        }

        $gender = $request->input('gender');
        if ($gender !== null && $request->input('patient_gender') === null) {
            $gender = strtolower((string) $gender);
            $gender = match ($gender) {
                'm' => 'male',
                'f' => 'female',
                default => $gender,
            };
            $request->merge(['patient_gender' => $gender]);
        }

        if ($referralType === 'diagnostic') {
            $rules = [
                'referral_type' => ['required', Rule::in(['diagnostic'])],
                'diagnostic_center_id' => 'required|integer|exists:diagnostic_centers,id',
                'diagnostic_service_ids' => 'required|array|min:1',
                'diagnostic_service_ids.*' => 'integer|exists:diagnostic_services,id',
                'priority' => ['nullable', Rule::in(['routine', 'urgent'])],
                'patient_name' => 'required|string|max:190',
                'patient_mobile' => 'nullable|string|max:20',
                'patient_age' => 'nullable|integer|min:0|max:120',
                'patient_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
                'case_summary' => 'required|string',
                'appointment_type' => ['nullable', Rule::in(['opd', 'ipd'])],
                'reports' => 'nullable|array',
                'reports.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
            ];

            $data = $request->validate($rules);

            $centerId = (int) $data['diagnostic_center_id'];
            $serviceIds = array_map('intval', $data['diagnostic_service_ids']);

            $count = DiagnosticService::query()
                ->where('diagnostic_center_id', $centerId)
                ->whereIn('id', $serviceIds)
                ->count();

            if ($count !== count($serviceIds)) {
                return response()->json([
                    'message' => 'Invalid diagnostic services selection.',
                    'errors' => [
                        'diagnostic_service_ids' => ['All selected services must belong to the selected diagnostic center.'],
                    ],
                ], 422);
            }

            if ($workflow->max_referrals_per_gp_per_day !== null) {
                $countToday = DiagnosticReferral::where('gp_id', $gp->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();
                if ($countToday >= $workflow->max_referrals_per_gp_per_day) {
                    return response()->json(['message' => 'Daily referral limit reached'], 422);
                }
            }

            $maxId = DiagnosticReferral::max('id') ?? 0;
            $leadCode = 'DSR-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);

            $diagnosticReferral = DiagnosticReferral::create([
                'lead_code' => $leadCode,
                'gp_id' => $gp->id,
                'diagnostic_center_id' => $centerId,
                'patient_name' => $data['patient_name'],
                'patient_mobile' => $data['patient_mobile'] ?? null,
                'patient_age' => $data['patient_age'] ?? null,
                'patient_gender' => $data['patient_gender'] ?? null,
                'case_summary' => $data['case_summary'],
                'appointment_type' => $data['appointment_type'] ?? 'opd',
                'priority' => $data['priority'] ?? 'routine',
                'status' => 'sent',
            ]);

            $diagnosticReferral->services()->sync($serviceIds);

            $uploads = $request->file('reports') ?? $request->file('attachments');
            if (is_array($uploads) && count($uploads) > 0) {
                foreach ($uploads as $file) {
                    $path = $file->store('diagnostic-referrals/'.$diagnosticReferral->id, 'public');

                    $diagnosticReferral->files()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            $diagnosticReferral->load(['center', 'services', 'files']);

            $centerUser = $diagnosticReferral->center?->user;
            if ($centerUser) {
                $centerUser->notify(new DiagnosticReferralCreatedNotification($diagnosticReferral));
            }

            return response()->json([
                'data' => [
                    'id' => $diagnosticReferral->id,
                    'lead_code' => $diagnosticReferral->lead_code,
                    'referral_type' => 'diagnostic',
                    'status' => $diagnosticReferral->status,
                    'appointment_type' => $diagnosticReferral->appointment_type,
                    'priority' => $diagnosticReferral->priority,
                    'patient_name' => $diagnosticReferral->patient_name,
                    'patient_mobile' => $diagnosticReferral->patient_mobile,
                    'patient_age' => $diagnosticReferral->patient_age,
                    'patient_gender' => $diagnosticReferral->patient_gender,
                    'case_summary' => $diagnosticReferral->case_summary,
                    'diagnostic_center' => [
                        'id' => $diagnosticReferral->center?->id,
                        'name' => $diagnosticReferral->center?->name,
                    ],
                    'diagnostic_services' => $diagnosticReferral->services->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ])->values(),
                    'files' => $diagnosticReferral->files->map(fn ($f) => [
                        'id' => $f->id,
                        'name' => $f->original_name,
                        'mime_type' => $f->mime_type,
                        'size' => $f->size,
                        'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
                    ])->values(),
                    'created_at' => $diagnosticReferral->created_at,
                ],
            ], 201);
        }

        if ($referralType === 'hospital') {
            $rules = [
                'referral_type' => ['required', Rule::in(['hospital'])],
                'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
                'department' => ['required', 'string', 'max:150'],
                'priority' => ['required', Rule::in(['routine', 'urgent'])],
                'patient_name' => 'required|string|max:190',
                'patient_mobile' => 'nullable|string|max:20',
                'patient_age' => 'nullable|integer|min:0|max:120',
                'patient_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
                'case_summary' => 'required|string',
                'appointment_type' => ['required', Rule::in(['opd', 'ipd'])],
                'reports' => 'nullable|array',
                'reports.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
            ];

            $data = $request->validate($rules);

            if ($workflow->max_referrals_per_gp_per_day !== null) {
                $countToday = Referral::where('gp_id', $gp->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();
                if ($countToday >= $workflow->max_referrals_per_gp_per_day) {
                    return response()->json(['message' => 'Daily referral limit reached'], 422);
                }
            }

            $maxId = Referral::max('id') ?? 0;
            $leadCode = 'HR-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);

            $referral = Referral::create([
                'lead_code' => $leadCode,
                'gp_id' => $gp->id,
                'specialist_id' => null,
                'hospital_id' => $data['hospital_id'],
                'referral_type' => 'hospital',
                'department' => $data['department'],
                'patient_name' => $data['patient_name'],
                'patient_mobile' => $data['patient_mobile'] ?? null,
                'patient_age' => $data['patient_age'] ?? null,
                'patient_gender' => $data['patient_gender'] ?? null,
                'case_summary' => $data['case_summary'],
                'appointment_type' => $data['appointment_type'],
                'priority' => $data['priority'] ?? 'routine',
                'status' => 'sent',
            ]);

            $uploads = $request->file('reports') ?? $request->file('attachments');
            if (is_array($uploads) && count($uploads) > 0) {
                foreach ($uploads as $file) {
                    $path = $file->store('referrals/'.$referral->id, 'public');

                    $referral->files()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            $referral->load(['hospital', 'files', 'gp.user']);

            return (new ReferralResource($referral))->response()->setStatusCode(201);
        }

        $rules = [
            'specialist_id' => 'required|exists:specialists,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'patient_name' => 'required|string|max:190',
            'patient_mobile' => 'nullable|string|max:20',
            'patient_age' => 'nullable|integer|min:0|max:120',
            'patient_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'case_summary' => 'required|string',
            'appointment_type' => ['required', Rule::in(['opd', 'ipd'])],
            'reports' => 'nullable|array',
            'reports.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:'.$mimes.'|max:'.$maxKb,
            'priority' => ['nullable', Rule::in(['routine', 'urgent'])],
        ];
        if ($request->input('appointment_type') === 'ipd') {
            $rules['hospital_id'] = 'required|exists:hospitals,id';
        }

        $data = $request->validate($rules);

        if ($workflow->max_referrals_per_gp_per_day !== null) {
            $countToday = Referral::where('gp_id', $gp->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();
            if ($countToday >= $workflow->max_referrals_per_gp_per_day) {
                return response()->json(['message' => 'Daily referral limit reached'], 422);
            }
        }

        // OPD/IPD logic
        if ($data['appointment_type'] === 'opd') {
            $data['hospital_id'] = null;
        }

        // Generate lead code: SSC-0001, SSC-0002, etc.
        $maxId = Referral::max('id') ?? 0;
        $leadCode = 'SSC-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);

        $referral = Referral::create([
            'lead_code' => $leadCode,
            'gp_id' => $gp->id,
            'specialist_id' => $data['specialist_id'],
            'hospital_id' => $data['hospital_id'] ?? null,
            'referral_type' => 'specialist',
            'patient_name' => $data['patient_name'],
            'patient_mobile' => $data['patient_mobile'] ?? null,
            'patient_age' => $data['patient_age'] ?? null,
            'patient_gender' => $data['patient_gender'] ?? null,
            'case_summary' => $data['case_summary'],
            'appointment_type' => $data['appointment_type'],
            'priority' => $data['priority'] ?? 'routine',
            'status' => 'sent',
        ]);

        // File uploads (optional)
        $uploads = $request->file('reports') ?? $request->file('attachments');
        if (is_array($uploads) && count($uploads) > 0) {
            foreach ($uploads as $file) {
                $path = $file->store('referrals/'.$referral->id, 'public');

                $referral->files()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        $referral->load(['specialist.user', 'hospital', 'files']);
        $specialistUser = $referral->specialist?->user;
        if ($specialistUser) {
            $specialistUser->notify(new ReferralCreatedNotification($referral));
        }

        return (new ReferralResource($referral))->response()->setStatusCode(201);
    }

    // GET /api/gp/referrals/{id}
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $gp = Gp::where('user_id', $user->id)->firstOrFail();
        if ($gp->status !== 'approved') {
            return response()->json([
                'message' => 'GP profile not approved yet',
            ], 403);
        }

        $requestedType = strtolower((string) $request->query('referral_type', 'specialist'));
        if ($requestedType === 'diagnostic') {
            $r = DiagnosticReferral::query()
                ->with(['center', 'services', 'files'])
                ->where('gp_id', $gp->id)
                ->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $r->id,
                    'lead_code' => $r->lead_code,
                    'referral_type' => 'diagnostic',
                    'status' => $r->status,
                    'appointment_type' => $r->appointment_type,
                    'priority' => $r->priority,
                    'patient_name' => $r->patient_name,
                    'patient_mobile' => $r->patient_mobile,
                    'patient_age' => $r->patient_age,
                    'patient_gender' => $r->patient_gender,
                    'case_summary' => $r->case_summary,
                    'diagnostic_center' => [
                        'id' => $r->center?->id,
                        'name' => $r->center?->name,
                    ],
                    'diagnostic_services' => $r->services->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ])->values(),
                    'files' => $r->files->map(fn ($f) => [
                        'id' => $f->id,
                        'name' => $f->original_name,
                        'mime_type' => $f->mime_type,
                        'size' => $f->size,
                        'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
                    ])->values(),
                    'created_at' => $r->created_at,
                    'accepted_at' => $r->accepted_at,
                    'consulted_at' => $r->consulted_at,
                    'closed_at' => $r->closed_at,
                ],
            ]);
        }

        $query = Referral::with(['specialist.user', 'specialist.specialty', 'specialist.hospitals', 'hospital', 'files', 'gp.user'])
            ->where('gp_id', $gp->id);

        if ($request->has('referral_type')) {
            $referralType = in_array($requestedType, ['specialist', 'hospital'], true) ? $requestedType : 'specialist';
            if ($referralType === 'hospital') {
                $query->where('referral_type', 'hospital');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('referral_type')->orWhere('referral_type', 'specialist');
                });
            }
        }

        $referral = $query->whereKey($id)->first();
        if ($referral) {
            return new ReferralResource($referral);
        }

        $r = DiagnosticReferral::query()
            ->with(['center', 'services', 'files'])
            ->where('gp_id', $gp->id)
            ->whereKey($id)
            ->first();

        if (! $r) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'id' => $r->id,
                'lead_code' => $r->lead_code,
                'referral_type' => 'diagnostic',
                'status' => $r->status,
                'appointment_type' => $r->appointment_type,
                'priority' => $r->priority,
                'patient_name' => $r->patient_name,
                'patient_mobile' => $r->patient_mobile,
                'patient_age' => $r->patient_age,
                'patient_gender' => $r->patient_gender,
                'case_summary' => $r->case_summary,
                'diagnostic_center' => [
                    'id' => $r->center?->id,
                    'name' => $r->center?->name,
                ],
                'diagnostic_services' => $r->services->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values(),
                'files' => $r->files->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->original_name,
                    'mime_type' => $f->mime_type,
                    'size' => $f->size,
                    'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
                ])->values(),
                'created_at' => $r->created_at,
                'accepted_at' => $r->accepted_at,
                'consulted_at' => $r->consulted_at,
                'closed_at' => $r->closed_at,
            ],
        ]);
    }
}
