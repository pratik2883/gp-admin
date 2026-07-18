<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticReferral;
use App\Models\DiagnosticService;
use App\Models\Specialist;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpecialistDiagnosticReferralController extends Controller
{
    // POST /api/specialist/diagnostic-referrals
    public function store(Request $request)
    {
        $user = $request->user();
        $specialist = Specialist::where('user_id', $user->id)->firstOrFail();
        $general = app(GeneralSettings::class);

        $allowed = $general->allowedMimeTypes();
        $mimes = implode(',', $allowed);
        $maxKb = max(1, (int) floor($general->maxUploadBytes() / 1024));

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

        $data = $request->validate([
            'diagnostic_center_id' => 'required|integer|exists:diagnostic_centers,id',
            'diagnostic_service_ids' => 'required|array|min:1',
            'diagnostic_service_ids.*' => 'integer|exists:diagnostic_services,id',
            'priority' => ['nullable', Rule::in(['routine', 'urgent'])],
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

        $maxId = DiagnosticReferral::max('id') ?? 0;
        $leadCode = 'DSR-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);

        $diagnosticReferral = DiagnosticReferral::create([
            'lead_code' => $leadCode,
            'gp_id' => null,
            'specialist_id' => $specialist->id,
            'diagnostic_center_id' => $centerId,
            'patient_name' => $data['patient_name'],
            'patient_mobile' => $data['patient_mobile'] ?? null,
            'patient_age' => $data['patient_age'] ?? null,
            'patient_gender' => $data['patient_gender'] ?? null,
            'case_summary' => $data['case_summary'],
            'appointment_type' => $data['appointment_type'],
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

        return response()->json([
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
        ], 201);
    }
}
