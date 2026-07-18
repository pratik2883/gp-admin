<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Services\SubscriptionService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpecialistProfileController extends Controller
{
    // GET /api/specialist/profile
    public function show(Request $request)
    {
        $user = $request->user();

        $specialist = Specialist::firstOrCreate(
            ['user_id' => $user->id],
            []
        );
        $specialist->load(['specialty', 'additionalSpecialties']);
        $settings = app(GeneralSettings::class);

        return response()->json([
            'profile' => $this->profilePayload($specialist, $user, $settings),
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            'feature_flags' => [
                'allow_profile_videos_for_specialists' => (bool) $settings->allow_profile_videos_for_specialists,
                'allow_profile_certificates_for_specialists' => (bool) $settings->allow_profile_certificates_for_specialists,
            ],
            'user' => $user,
            'specialist' => $specialist,
        ]);
    }

    // POST /api/specialist/profile
    public function update(Request $request)
    {
        $user = $request->user();
        $settings = app(GeneralSettings::class);
        $specialist = Specialist::firstOrCreate(['user_id' => $user->id], []);
        $isSetupRequest = $request->is('api/specialist/setup');
        $requiredRule = $isSetupRequest ? 'sometimes' : 'required';

        $data = $request->validate([
            // profile
            'full_name' => 'nullable|string|max:190',
            'name' => $requiredRule.'|string|max:190',
            'email' => [
                $requiredRule,
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'mobile' => [
                $requiredRule,
                'string',
                'max:20',
                Rule::unique('users', 'mobile')->ignore($user->id),
            ],
            'whatsapp_number' => 'nullable|string|max:20',
            'primary_specialization' => 'nullable|string|max:100',
            'speciality' => 'nullable|string|max:100',
            'specialty_id' => $isSetupRequest ? 'nullable|integer|exists:specialties,id' : 'required_without:specialty_code|integer|exists:specialties,id',
            'specialty_code' => $isSetupRequest ? 'nullable|string|max:120' : 'required_without:specialty_id|string|max:120',
            'additional_specialty_ids' => 'nullable|array',
            'additional_specialty_ids.*' => 'integer|exists:specialties,id',

            'hospital_name' => $requiredRule.'|string|max:190',
            'clinic_street' => $requiredRule.'|string|max:255',
            'clinic_area' => $requiredRule.'|string|max:190',
            'clinic_city' => $requiredRule.'|string|max:100',
            'clinic_pincode' => $requiredRule.'|string|max:12',
            'registration_no' => $requiredRule.'|string|max:100',
            'council_name' => $requiredRule.'|string|max:150',
            'years_of_experience' => 'nullable|integer|min:0|max:80',
            'qualifications' => 'nullable|array',
            'qualifications.*' => 'string|max:120',
            'sub_specialties' => 'nullable|string',
            'key_procedures' => 'nullable|string',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:40',
            'consultation_flags' => 'nullable|array',
            'consultation_flags.in_person' => 'nullable|boolean',
            'consultation_flags.teleconsult' => 'nullable|boolean',
            'bio' => 'nullable|string',
            'videos' => $settings->allow_profile_videos_for_specialists ? 'nullable|array' : 'prohibited',
            'videos.*' => $settings->allow_profile_videos_for_specialists ? 'nullable|url|max:500' : 'prohibited',
            'certificates' => $settings->allow_profile_certificates_for_specialists ? 'nullable|array' : 'prohibited',
            'certificates.*' => $settings->allow_profile_certificates_for_specialists ? 'file|max:10240' : 'prohibited',

            // profile photo
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // education – expect JSON or simple arrays from app
            'education_primary' => 'nullable|array',
            'education_primary.degree' => 'nullable|string|max:100',
            'education_primary.university' => 'nullable|string|max:150',
            'education_primary.year' => 'nullable|string|max:10',

            'education_postgrad' => 'nullable|array',
            'education_postgrad.degree' => 'nullable|string|max:100',
            'education_postgrad.university' => 'nullable|string|max:150',
            'education_postgrad.year' => 'nullable|string|max:10',

            'additional_qualifications' => 'nullable|array',
            'clinic_name' => 'nullable|string|max:190',
        ]);

        if (array_key_exists('name', $data) && filled($data['name'])) {
            $user->name = $data['name'];
        } elseif (array_key_exists('full_name', $data) && filled($data['full_name'])) {
            $user->name = $data['full_name'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (array_key_exists('mobile', $data)) {
            $user->mobile = $data['mobile'];
        }
        if ($user->isDirty()) {
            $user->save();
        }

        // profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Option: delete old file if exists
            if ($specialist->profile_photo_path) {
                Storage::disk('public')->delete($specialist->profile_photo_path);
            }

            $path = $request->file('profile_photo')
                ->store('specialists/'.$user->id, 'public');
            $specialist->profile_photo_path = $path;
        }

        // JSON fields
        $specialist->whatsapp_number = $data['whatsapp_number'] ?? $specialist->whatsapp_number;
        if (array_key_exists('hospital_name', $data)) {
            $specialist->hospital_name = $data['hospital_name'];
        }
        if (array_key_exists('clinic_street', $data)) {
            $specialist->clinic_street = $data['clinic_street'];
        }
        if (array_key_exists('clinic_area', $data)) {
            $specialist->clinic_area = $data['clinic_area'];
        }
        if (array_key_exists('clinic_city', $data)) {
            $specialist->clinic_city = $data['clinic_city'];
        }
        if (array_key_exists('clinic_pincode', $data)) {
            $specialist->clinic_pincode = $data['clinic_pincode'];
        }
        if (
            array_key_exists('clinic_street', $data)
            || array_key_exists('clinic_area', $data)
            || array_key_exists('clinic_city', $data)
            || array_key_exists('clinic_pincode', $data)
        ) {
            $specialist->clinic_address = trim(implode(', ', array_filter([
                $specialist->clinic_street,
                $specialist->clinic_area,
                $specialist->clinic_city,
                $specialist->clinic_pincode,
            ])));
        }
        if (array_key_exists('registration_no', $data)) {
            $specialist->medical_council_registration_no = $data['registration_no'];
        }
        if (array_key_exists('council_name', $data)) {
            $specialist->medical_council_name = $data['council_name'];
        }
        $specialist->years_of_experience = $data['years_of_experience'] ?? $specialist->years_of_experience;
        $specialist->sub_specialties = $data['sub_specialties'] ?? $specialist->sub_specialties;
        $specialist->key_procedures = $data['key_procedures'] ?? $specialist->key_procedures;
        $specialist->bio = $data['bio'] ?? $specialist->bio;
        $specialist->languages = $data['languages'] ?? $specialist->languages;
        if (array_key_exists('qualifications', $data)) {
            $specialist->additional_qualifications = $data['qualifications'];
        }
        $specialist->consultation_in_person = (bool) data_get($data, 'consultation_flags.in_person', $specialist->consultation_in_person ?? true);
        $specialist->consultation_teleconsult = (bool) data_get($data, 'consultation_flags.teleconsult', $specialist->consultation_teleconsult ?? false);
        if ($settings->allow_profile_videos_for_specialists && array_key_exists('videos', $data)) {
            $specialist->videos = $data['videos'];
        }
        if ($settings->allow_profile_certificates_for_specialists && $request->hasFile('certificates')) {
            $existing = is_array($specialist->certificates) ? $specialist->certificates : [];
            $uploaded = [];
            foreach ((array) $request->file('certificates') as $file) {
                if (! $file) {
                    continue;
                }
                $uploaded[] = $file->store('specialists/'.$user->id.'/certificates', 'public');
            }
            $specialist->certificates = array_values(array_merge($existing, $uploaded));
        }

        if (array_key_exists('specialty_id', $data)) {
            $specialist->specialty_id = $data['specialty_id'];
        } elseif (! empty($data['specialty_code'])) {
            $specialist->specialty_id = Specialty::query()
                ->where('code', $data['specialty_code'])
                ->value('id');
        }

        $legacySpecialtyText = $data['primary_specialization'] ?? $data['speciality'] ?? null;
        if (filled($legacySpecialtyText) && ! array_key_exists('specialty_id', $data) && empty($data['specialty_code'])) {
            $match = Specialty::query()
                ->where('code', $legacySpecialtyText)
                ->orWhere('name', $legacySpecialtyText)
                ->orWhere('plain_label', $legacySpecialtyText)
                ->first();
            if ($match) {
                $specialist->specialty_id = $match->id;
            } else {
                $specialist->primary_specialization = $legacySpecialtyText;
            }
        }

        if (isset($data['education_primary'])) {
            $specialist->education_primary = $data['education_primary'];
        }
        if (isset($data['education_postgrad'])) {
            $specialist->education_postgrad = $data['education_postgrad'];
        }
        if (isset($data['additional_qualifications'])) {
            $specialist->additional_qualifications = $data['additional_qualifications'];
        }

        $specialist->clinic_name = $data['clinic_name'] ?? $specialist->clinic_name;

        $specialist->save();
        if (array_key_exists('additional_specialty_ids', $data)) {
            $ids = collect($data['additional_specialty_ids'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $primaryId = (int) ($specialist->specialty_id ?? 0);
            $ids = array_values(array_filter($ids, fn ($id) => $primaryId <= 0 || $id !== $primaryId));
            $specialist->additionalSpecialties()->sync($ids);
        }
        $specialist->load(['specialty', 'additionalSpecialties']);

        return response()->json([
            'message' => 'Specialist profile updated',
            'profile' => $this->profilePayload($specialist, $user, $settings),
            'subscription' => app(SubscriptionService::class)->latestSummaryFor($user),
            'feature_flags' => [
                'allow_profile_videos_for_specialists' => (bool) $settings->allow_profile_videos_for_specialists,
                'allow_profile_certificates_for_specialists' => (bool) $settings->allow_profile_certificates_for_specialists,
            ],
            'user' => $user,
            'specialist' => $specialist,
        ]);
    }

    private function profilePayload(Specialist $specialist, $user, GeneralSettings $settings): array
    {
        $specialtyLabel = $specialist->specialty?->plain_label ?: ($specialist->specialty?->name ?: $specialist->primary_specialization);

        $additionalIds = $specialist->additionalSpecialties?->pluck('id')->values()->all() ?? [];
        $additionalLabels = $specialist->additionalSpecialties?->map(fn ($s) => $s->plain_label ?: $s->name)->values()->all() ?? [];

        return [
            'id' => $specialist->id,
            'full_name' => $user->name,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'speciality' => $specialtyLabel,
            'primary_specialty_code' => $specialist->specialty?->code,
            'primary_specialty_label' => $specialtyLabel,
            'additional_specialty_ids' => $additionalIds,
            'additional_specialty_labels' => $additionalLabels,
            'hospital_name' => $specialist->hospital_name,
            'clinic_street' => $specialist->clinic_street,
            'clinic_area' => $specialist->clinic_area,
            'clinic_city' => $specialist->clinic_city,
            'clinic_pincode' => $specialist->clinic_pincode,
            'clinic_address' => $specialist->clinic_address,
            'registration_no' => $specialist->medical_council_registration_no,
            'council_name' => $specialist->medical_council_name,
            'qualifications' => $specialist->additional_qualifications ?? [],
            'years_of_experience' => $specialist->years_of_experience,
            'sub_specialties' => $specialist->sub_specialties,
            'key_procedures' => $specialist->key_procedures,
            'languages' => $specialist->languages ?? [],
            'consultation_flags' => [
                'in_person' => (bool) $specialist->consultation_in_person,
                'teleconsult' => (bool) $specialist->consultation_teleconsult,
            ],
            'consultation_in_person' => (bool) $specialist->consultation_in_person,
            'consultation_teleconsult' => (bool) $specialist->consultation_teleconsult,
            'bio' => $specialist->bio,
            'videos' => $settings->allow_profile_videos_for_specialists ? ($specialist->videos ?? []) : [],
            'certificates' => $settings->allow_profile_certificates_for_specialists
                ? collect($specialist->certificates ?? [])
                    ->map(fn ($p) => $p ? Storage::disk('public')->url($p) : null)
                    ->filter()
                    ->values()
                    ->all()
                : [],
            'profile_photo' => $specialist->profile_photo_path ? Storage::disk('public')->url($specialist->profile_photo_path) : null,
        ];
    }
}
