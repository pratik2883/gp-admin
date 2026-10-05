<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\Storage;

class ReferralResource extends JsonResource
{
    public function toArray($request): array
    {
        $specialist = $this->whenLoaded('specialist');
        $hospital = $this->whenLoaded('hospital');

        $specialistDetails = null;
        if ($specialist && ! $specialist instanceof MissingValue) {
            $primaryHospital = $specialist->hospitals->first();
            $specialistDetails = [
                'id' => $specialist->id,
                'name' => $specialist->user?->name,
                'profile_photo' => $specialist->profile_photo_path
                    ? (is_file(public_path($specialist->profile_photo_path)) ? url($specialist->profile_photo_path) : Storage::disk('public')->url($specialist->profile_photo_path))
                    : null,
                'speciality' => $specialist->specialty?->plain_label
                    ?? $specialist->specialty?->name
                    ?? $specialist->primary_specialization,
                'hospital_name' => $specialist->hospital_name ?: ($primaryHospital?->name ?? null),
                'clinic_address' => $specialist->clinic_address,
                'years_of_experience' => $specialist->years_of_experience,
                'languages' => $specialist->languages ?? [],
                'consultation_flags' => [
                    'in_person' => (bool) $specialist->consultation_in_person,
                    'teleconsult' => (bool) $specialist->consultation_teleconsult,
                ],
            ];
        }

        $hospitalDetails = null;
        if ($hospital && ! $hospital instanceof MissingValue) {
            $hospitalDetails = [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'address' => $hospital->address,
                'micro_area' => $hospital->micro_area,
                'city' => $hospital->city,
            ];
        }

        return [
            'id' => $this->id,
            'lead_code' => $this->lead_code,
            'referral_type' => $this->referral_type ?? 'specialist',
            'status' => $this->status,
            'appointment_type' => $this->appointment_type,
            'priority' => $this->priority ?? 'routine',
            'department' => $this->department,
            'patient_name' => $this->patient_name,
            'patient_mobile' => $this->patient_mobile,
            'patient_age' => $this->patient_age,
            'patient_gender' => $this->patient_gender,
            'case_summary' => $this->case_summary,
            'specialist' => $specialistDetails,
            'hospital' => $hospitalDetails,
            'gp' => $this->whenLoaded('gp', fn () => [
                'id' => $this->gp?->id,
                'name' => $this->gp?->user?->name,
            ]),
            'gp_name' => $this->whenLoaded('gp', fn () => $this->gp?->user?->name),
            'notes' => $this->case_summary,
            'hospital_name' => $hospitalDetails['name'] ?? $this->hospital?->name,
            'files' => $this->whenLoaded('files', fn () => $this->files->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->original_name,
                    'mime_type' => $f->mime_type,
                    'size' => $f->size,
                    'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
                ];
            })->values()),
            'attachments' => $this->whenLoaded('files', fn () => $this->files->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->original_name,
                    'url' => $f->file_path ? Storage::disk('public')->url($f->file_path) : null,
                ];
            })->values()),
            'created_at' => $this->created_at,
            'accepted_at' => $this->accepted_at,
            'consulted_at' => $this->consulted_at,
            'closed_at' => $this->closed_at,
        ];
    }
}
