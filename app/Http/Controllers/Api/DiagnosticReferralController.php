<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticReferral;
use App\Notifications\DiagnosticReferralStatusNotification;
use App\Settings\WorkflowSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DiagnosticReferralController extends Controller
{
    private function centerFor(Request $request): DiagnosticCenter
    {
        return DiagnosticCenter::where('user_id', $request->user()->id)->firstOrFail();
    }

    // GET /api/diagnostic/referrals?status=&q=&page=&per_page=
    public function index(Request $request)
    {
        $center = $this->centerFor($request);

        $query = DiagnosticReferral::query()
            ->where('diagnostic_center_id', $center->id);

        if ($status = $request->query('status')) {
            $status = strtolower((string) $status);
            if ($status === 'new' || $status === 'pending') {
                $status = 'sent';
            }
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $q = trim((string) $q);
            $query->where(function ($inner) use ($q) {
                $inner
                    ->where('lead_code', 'like', '%'.$q.'%')
                    ->orWhere('patient_name', 'like', '%'.$q.'%')
                    ->orWhere('patient_mobile', 'like', '%'.$q.'%')
                    ->orWhereHas('gp.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'))
                    ->orWhereHas('specialist.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $items = $query->orderByDesc('created_at')->paginate($perPage);
        $items->getCollection()->load(['center', 'services', 'files', 'gp.user', 'specialist.user']);

        return response()->json([
            'data' => $items->map(fn (DiagnosticReferral $r) => $this->payload($r))->values(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'total' => $items->total(),
        ]);
    }

    // GET /api/diagnostic/referrals/{id}
    public function show(Request $request, $id)
    {
        $center = $this->centerFor($request);

        $r = DiagnosticReferral::with(['center', 'services', 'files', 'gp.user', 'specialist.user'])
            ->where('diagnostic_center_id', $center->id)
            ->findOrFail($id);

        return response()->json(['data' => $this->payload($r)]);
    }

    // PATCH /api/diagnostic/referrals/{id}/status
    public function status(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'consulted', 'closed', 'rejected'])],
        ]);

        $center = $this->centerFor($request);

        /** @var DiagnosticReferral $r */
        $r = DiagnosticReferral::with(['gp.user', 'specialist.user'])
            ->where('diagnostic_center_id', $center->id)
            ->findOrFail($id);

        $workflow = app(WorkflowSettings::class);
        $allowed = [];
        if ($workflow->force_strict_status_flow ?? false) {
            $allowed = [
                'accepted' => ['sent'],
                'rejected' => ['sent'],
                'consulted' => ['accepted'],
                'closed' => ['consulted'],
            ];
        } else {
            $allowed = [
                'accepted' => ['sent'],
                'rejected' => ['sent', 'accepted'],
                'consulted' => ['accepted'],
                'closed' => ($workflow->allow_direct_sent_to_closed ?? false)
                    ? ['sent', 'accepted', 'consulted']
                    : ['accepted', 'consulted'],
            ];
        }

        if (! in_array($r->status, $allowed[$data['status']] ?? [], true)) {
            return response()->json([
                'message' => 'Invalid status transition',
                'current_status' => $r->status,
                'requested_status' => $data['status'],
                'allowed_from' => $allowed[$data['status']] ?? [],
            ], 422);
        }

        $r->status = $data['status'];

        $now = now();
        if ($data['status'] === 'accepted') {
            $r->accepted_at = $now;
        } elseif ($data['status'] === 'consulted') {
            $r->consulted_at = $now;
        } elseif ($data['status'] === 'closed') {
            $r->closed_at = $now;
        }

        $r->save();

        $sender = $r->gp?->user ?? $r->specialist?->user;
        if ($sender) {
            $sender->notify(new DiagnosticReferralStatusNotification($r, $data['status']));
        }

        $r->load(['center', 'services', 'files', 'gp.user', 'specialist.user']);

        return response()->json(['data' => $this->payload($r)]);
    }

    private function payload(DiagnosticReferral $r): array
    {
        $referredBy = optional($r->gp?->user)->name ?? optional($r->specialist?->user)->name ?? '';

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
            'notes' => $r->case_summary,
            'case_summary' => $r->case_summary,
            'gp_name' => $referredBy,
            'referred_by' => $referredBy,
            'hospital_name' => $r->center?->name ?? '',
            'diagnostic_center' => [
                'id' => $r->center?->id,
                'name' => $r->center?->name,
            ],
            'diagnostic_services' => $r->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values(),
            'attachments' => $r->files->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->original_name,
                'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
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
        ];
    }
}