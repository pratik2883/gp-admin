<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = Referral::with(['gp.user', 'specialist.user', 'hospital']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $from = $request->query('from');
        $to = $request->query('to');
        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
            $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::maxValue();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($city = $request->query('city')) {
            $query->where(function ($q) use ($city) {
                $q->whereHas('hospital', function ($hq) use ($city) {
                    $hq->where('city', $city);
                })->orWhereHas('specialist', function ($sq) use ($city) {
                    $sq->where('clinic_city', $city);
                });
            });
        }

        if ($gpId = $request->query('gp')) {
            $query->where('gp_id', $gpId);
        }
        if ($specId = $request->query('specialist')) {
            $query->where('specialist_id', $specId);
        }

        $items = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Referral::with(['gp.user', 'specialist.user', 'hospital', 'files'])->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Referral::with(['gp.user', 'specialist.user'])->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['sent', 'accepted', 'consulted', 'closed'])],
        ]);
        $newStatus = $data['status'];
        $allowed = [
            'accepted' => ['sent'],
            'consulted' => ['accepted'],
            'closed' => ['sent', 'accepted', 'consulted'],
            'sent' => ['accepted', 'consulted', 'closed', 'sent'],
        ];
        $statusChanged = $newStatus !== $item->status;
        if ($statusChanged) {
            if (isset($allowed[$newStatus]) && ! in_array($item->status, $allowed[$newStatus], true)) {
                return response()->json(['message' => 'Invalid status transition'], 422);
            }
            $item->status = $newStatus;
            $now = now();
            if ($newStatus === 'accepted') {
                $item->accepted_at = $now;
            } elseif ($newStatus === 'consulted') {
                $item->consulted_at = $now;
            } elseif ($newStatus === 'closed') {
                $item->closed_at = $now;
            }
        }
        $item->save();

        if ($statusChanged) {
            if ($newStatus === 'accepted') {
                $gpUser = $item->gp?->user;
                if ($gpUser) {
                    $gpUser->notify(new \App\Notifications\ReferralAcceptedNotification($item));
                }
            } elseif ($newStatus === 'consulted') {
                $gpUser = $item->gp?->user;
                if ($gpUser) {
                    $gpUser->notify(new \App\Notifications\ReferralConsultedNotification($item));
                }
            } elseif ($newStatus === 'closed') {
                $gpUser = $item->gp?->user;
                if ($gpUser) {
                    $gpUser->notify(new \App\Notifications\ReferralClosedNotification($item));
                }
            }
        }

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = Referral::findOrFail($id);
        $item->delete();

        return response()->noContent();
    }
}
