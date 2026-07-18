<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Referral;
use App\Models\Specialist;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpecialistHospitalReferralController extends Controller
{
    public function locations(Request $request)
    {
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

    public function hospitals(Request $request)
    {
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

    public function hospitalDepartments(Request $request, int $id)
    {
        $hospital = Hospital::query()->findOrFail($id);

        $departments = DB::table('hospital_specialist')
            ->where('hospital_id', $hospital->id)
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->map(fn ($value) => (string) $value)
            ->values();

        return response()->json([
            'hospital_id' => $hospital->id,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();
        $general = app(GeneralSettings::class);

        $allowed = $general->allowedMimeTypes();
        $mimes = implode(',', $allowed);
        $maxKb = max(1, (int) floor($general->maxUploadBytes() / 1024));

        $data = $request->validate([
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
        ]);

        $maxId = Referral::max('id') ?? 0;
        $leadCode = 'SHR-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);

        $referral = Referral::create([
            'lead_code' => $leadCode,
            'gp_id' => null,
            // Keep sender specialist on record, but exclude hospital referrals from specialist lead inbox queries.
            'specialist_id' => $specialist->id,
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

        $referral->load(['specialist.user', 'hospital', 'files']);

        return (new ReferralResource($referral))->response()->setStatusCode(201);
    }
}
