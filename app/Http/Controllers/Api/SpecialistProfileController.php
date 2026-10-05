<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Services\SubscriptionService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $requiredRule = 'sometimes';

        Log::info('SP Profile Update - raw input', [
            'all' => $request->all(),
            'files' => array_keys($request->allFiles()),
            'hospital_name_raw' => $request->input('hospital_name'),
            'has_profile_photo' => $request->hasFile('profile_photo'),
        ]);

        $data = $request->validate([
            // profile
            'full_name' => 'nullable|string|max:190',
            'name' => 'sometimes|nullable|string|max:190',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'mobile' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'mobile')->ignore($user->id),
            ],
            'whatsapp_number' => 'nullable|string|max:20',
            'primary_specialization' => 'nullable|string|max:100',
            'speciality' => 'nullable|string|max:100',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'specialty_code' => 'nullable|string|max:120',
            'additional_specialty_ids' => 'nullable|array',
            'additional_specialty_ids.*' => 'integer|exists:specialties,id',

            'hospital_name' => 'nullable|string|max:190',
            'clinic_street' => 'nullable|string|max:255',
            'clinic_area' => 'nullable|string|max:190',
            'clinic_city' => 'nullable|string|max:100',
            'clinic_pincode' => 'nullable|string|max:12',
            'registration_no' => 'nullable|string|max:100',
            'council_name' => 'nullable|string|max:150',
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
            'clinic_timings' => 'nullable|string|max:500',
            'hospital_visiting_hours' => 'nullable|string|max:500',
            'available_days' => 'nullable|array',
            'available_days.*' => 'string|max:20',
            'show_mobile_number' => 'nullable|boolean',
            'show_whatsapp_number' => 'nullable|boolean',
            'bio' => 'nullable|string',
            'videos' => $settings->allow_profile_videos_for_specialists ? 'nullable|array' : 'prohibited',
            'videos.*' => $settings->allow_profile_videos_for_specialists ? 'nullable|url|max:500' : 'prohibited',
            'certificates' => $settings->allow_profile_certificates_for_specialists ? 'nullable|array' : 'prohibited',
            'certificates.*' => $settings->allow_profile_certificates_for_specialists ? 'file|max:10240' : 'prohibited',

            // profile photo
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'profile_photo_base64' => 'nullable|string',
            'profile_photo_mime' => 'nullable|string|max:30',

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

        Log::info('SP Profile Update - validated data', [
            'hospital_name' => $data['hospital_name'] ?? 'NOT_IN_DATA',
            'name' => $data['name'] ?? 'NOT_IN_DATA',
            'profile_photo' => $request->hasFile('profile_photo'),
            'data_keys' => array_keys($data),
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

        // profile photo upload (multipart file)
        if ($request->hasFile('profile_photo')) {
            if ($specialist->profile_photo_path) {
                Storage::disk('public')->delete($specialist->profile_photo_path);
            }
            $path = $request->file('profile_photo')
                ->store('specialists/'.$user->id, 'public');
            $specialist->profile_photo_path = $path;
        } elseif (! empty($data['profile_photo_base64'])) {
            // profile photo upload (base64 — saves directly to public dir)
            $raw = $data['profile_photo_base64'];
            $mime = $data['profile_photo_mime'] ?? 'image/jpeg';
            if (str_starts_with($raw, 'data:')) {
                $parts = explode(';', $raw, 2);
                if (count($parts) === 2 && str_starts_with($parts[0], 'data:')) {
                    $mime = substr($parts[0], 5);
                    $raw = $parts[1];
                }
                if (str_starts_with($raw, 'base64,')) {
                    $raw = substr($raw, 7);
                }
            }
            $decoded = base64_decode($raw, true);
            if ($decoded !== false && strlen($decoded) > 0) {
                $ext = match($mime) {
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $filename = 'profile_'.time().'.'.$ext;
                $publicDir = public_path('specialists/'.$user->id);
                if (! is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                file_put_contents($publicDir.'/'.$filename, $decoded);
                $storedPath = 'specialists/'.$user->id.'/'.$filename;
                if ($specialist->profile_photo_path) {
                    Storage::disk('public')->delete($specialist->profile_photo_path);
                }
                $specialist->profile_photo_path = $storedPath;
            }
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
        $specialist->consultation_in_person = filter_var(data_get($data, 'consultation_flags.in_person', $specialist->consultation_in_person ?? true), FILTER_VALIDATE_BOOLEAN);
        $specialist->consultation_teleconsult = filter_var(data_get($data, 'consultation_flags.teleconsult', $specialist->consultation_teleconsult ?? false), FILTER_VALIDATE_BOOLEAN);
        if (array_key_exists('clinic_timings', $data)) {
            $specialist->clinic_timings = $data['clinic_timings'];
        }
        if (array_key_exists('hospital_visiting_hours', $data)) {
            $specialist->hospital_visiting_hours = $data['hospital_visiting_hours'];
        }
        if (array_key_exists('available_days', $data)) {
            $specialist->available_days = $data['available_days'];
        }
        if (array_key_exists('show_mobile_number', $data)) {
            $specialist->show_mobile_number = filter_var($data['show_mobile_number'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('show_whatsapp_number', $data)) {
            $specialist->show_whatsapp_number = filter_var($data['show_whatsapp_number'], FILTER_VALIDATE_BOOLEAN);
        }
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
        } elseif ($settings->allow_profile_certificates_for_specialists && ! empty($data['certificates_base64']) && is_array($data['certificates_base64'])) {
            $existing = is_array($specialist->certificates) ? $specialist->certificates : [];
            $uploaded = [];
            foreach ($data['certificates_base64'] as $cert) {
                if (empty($cert['data']) || empty($cert['mime'])) {
                    continue;
                }
                $decoded = base64_decode($cert['data'], true);
                if ($decoded === false || strlen($decoded) === 0) {
                    continue;
                }
                $ext = match($cert['mime']) {
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $filename = 'cert_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $publicDir = public_path('specialists/'.$user->id.'/certificates');
                if (! is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                $storedPath = 'specialists/'.$user->id.'/certificates/'.$filename;
                file_put_contents($publicDir.'/'.$filename, $decoded);
                $uploaded[] = $storedPath;
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

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

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
            'clinic_name' => $specialist->clinic_name,
            'whatsapp_number' => $specialist->whatsapp_number ?: $user->mobile,
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
            'clinic_timings' => $specialist->clinic_timings,
            'hospital_visiting_hours' => $specialist->hospital_visiting_hours,
            'available_days' => $specialist->available_days ?? [],
            'show_mobile_number' => (bool) ($specialist->show_mobile_number ?? true),
            'show_whatsapp_number' => (bool) ($specialist->show_whatsapp_number ?? true),
            'bio' => $specialist->bio,
            'videos' => $settings->allow_profile_videos_for_specialists ? ($specialist->videos ?? []) : [],
            'certificates' => $settings->allow_profile_certificates_for_specialists
                ? collect($specialist->certificates ?? [])
                    ->map(function ($p) {
                        if (! $p) return null;
                        if (is_file(public_path($p))) return url($p);
                        return Storage::disk('public')->url($p);
                    })
                    ->filter()
                    ->values()
                    ->all()
                : [],
            'profile_photo' => $specialist->profile_photo_path
                ? (is_file(public_path($specialist->profile_photo_path)) ? url($specialist->profile_photo_path) : $publicDisk->url($specialist->profile_photo_path))
                : null,
        ];
    }
}
