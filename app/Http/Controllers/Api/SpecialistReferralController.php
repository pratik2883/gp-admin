<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Referral;
use App\Models\Specialist;
use App\Settings\WorkflowSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialistReferralController extends Controller
{
    // GET /api/specialist/referrals?status=&q=&page=&per_page=
    public function index(Request $request)
    {
        $user = $request->user();
        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();

        $query = Referral::with(['gp', 'hospital'])
            ->where('specialist_id', $specialist->id)
            ->where('referral_type', '!=', 'hospital');

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
                    ->orWhereHas('gp.user', fn ($u) => $u->where('name', 'like', '%'.$q.'%'));
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $referrals = $query->orderByDesc('created_at')->paginate($perPage);
        $referrals->getCollection()->load(['gp.user', 'hospital', 'specialist.user']);

        return ReferralResource::collection($referrals);
    }

    // GET /api/specialist/referrals/{id}
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();

        $referral = Referral::with(['gp', 'hospital', 'files'])
            ->where('specialist_id', $specialist->id)
            ->where('referral_type', '!=', 'hospital')
            ->findOrFail($id);

        return new ReferralResource($referral);
    }

    // POST /api/specialist/referrals/{id}/accept
    public function accept(Request $request, $id)
    {
        return $this->updateStatus($request, $id, 'accepted');
    }

    // POST /api/specialist/referrals/{id}/consult
    public function consult(Request $request, $id)
    {
        return $this->updateStatus($request, $id, 'consulted');
    }

    // POST /api/specialist/referrals/{id}/close
    public function close(Request $request, $id)
    {
        return $this->updateStatus($request, $id, 'closed');
    }

    // PATCH /api/specialist/leads/{id}/status
    public function status(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'consulted', 'closed'])],
        ]);

        return $this->updateStatus($request, $id, $data['status']);
    }

    protected function updateStatus(Request $request, $id, string $newStatus)
    {
        $user = $request->user();
        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();

        /** @var Referral $referral */
        $referral = Referral::with(['gp.user', 'specialist.user'])
            ->where('specialist_id', $specialist->id)
            ->where('referral_type', '!=', 'hospital')
            ->findOrFail($id);

        $workflow = app(WorkflowSettings::class);
        $allowed = [];
        if ($workflow->force_strict_status_flow ?? false) {
            $allowed = [
                'accepted' => ['sent'],
                'consulted' => ['accepted'],
                'closed' => ['consulted'],
            ];
        } else {
            $allowed = [
                'accepted' => ['sent'],
                'consulted' => ['accepted'],
                'closed' => ($workflow->allow_direct_sent_to_closed ?? false)
                    ? ['sent', 'accepted', 'consulted']
                    : ['accepted', 'consulted'],
            ];
        }

        if (! in_array($referral->status, $allowed[$newStatus] ?? [], true)) {
            return response()->json([
                'message' => 'Invalid status transition',
                'current_status' => $referral->status,
                'requested_status' => $newStatus,
                'allowed_from' => $allowed[$newStatus] ?? [],
            ], 422);
        }

        $referral->status = $newStatus;

        $now = now();
        if ($newStatus === 'accepted') {
            $referral->accepted_at = $now;
        } elseif ($newStatus === 'consulted') {
            $referral->consulted_at = $now;
        } elseif ($newStatus === 'closed') {
            $referral->closed_at = $now;
        }

        $referral->save();

        if ($newStatus === 'accepted') {
            $gpUser = $referral->gp?->user;
            if ($gpUser) {
                $gpUser->notify(new \App\Notifications\ReferralAcceptedNotification($referral));
            }
        } elseif ($newStatus === 'consulted') {
            $gpUser = $referral->gp?->user;
            if ($gpUser) {
                $gpUser->notify(new \App\Notifications\ReferralConsultedNotification($referral));
            }
        } elseif ($newStatus === 'closed') {
            $gpUser = $referral->gp?->user;
            if ($gpUser) {
                $gpUser->notify(new \App\Notifications\ReferralClosedNotification($referral));
            }
        }

        // option: log status history in a separate table

        $referral->load(['gp.user', 'hospital', 'specialist.user']);

        return new ReferralResource($referral);
    }
}
