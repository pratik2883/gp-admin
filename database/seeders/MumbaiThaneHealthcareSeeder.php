<?php

namespace Database\Seeders;

use App\Models\DiagnosticCenter;
use App\Models\DiagnosticService;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Location;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MumbaiThaneHealthcareSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ($this->withoutModelEvents(function (): void {
            $now = now();

            $locations = [
                ['city' => 'Mumbai', 'name' => 'Andheri West'],
                ['city' => 'Mumbai', 'name' => 'Andheri East'],
                ['city' => 'Mumbai', 'name' => 'Bandra West'],
                ['city' => 'Mumbai', 'name' => 'Bandra East'],
                ['city' => 'Mumbai', 'name' => 'Borivali West'],
                ['city' => 'Mumbai', 'name' => 'Borivali East'],
                ['city' => 'Mumbai', 'name' => 'Kandivali West'],
                ['city' => 'Mumbai', 'name' => 'Kandivali East'],
                ['city' => 'Mumbai', 'name' => 'Malad West'],
                ['city' => 'Mumbai', 'name' => 'Malad East'],
                ['city' => 'Mumbai', 'name' => 'Goregaon West'],
                ['city' => 'Mumbai', 'name' => 'Goregaon East'],
                ['city' => 'Mumbai', 'name' => 'Vile Parle West'],
                ['city' => 'Mumbai', 'name' => 'Vile Parle East'],
                ['city' => 'Mumbai', 'name' => 'Santacruz West'],
                ['city' => 'Mumbai', 'name' => 'Santacruz East'],
                ['city' => 'Mumbai', 'name' => 'Juhu'],
                ['city' => 'Mumbai', 'name' => 'Powai'],
                ['city' => 'Mumbai', 'name' => 'Ghatkopar East'],
                ['city' => 'Mumbai', 'name' => 'Chembur'],
                ['city' => 'Mumbai', 'name' => 'Dadar West'],
                ['city' => 'Mumbai', 'name' => 'Sion'],
                ['city' => 'Mumbai', 'name' => 'Mulund West'],
                ['city' => 'Mumbai', 'name' => 'Bhandup West'],
                ['city' => 'Thane', 'name' => 'Thane West'],
                ['city' => 'Thane', 'name' => 'Thane East'],
                ['city' => 'Thane', 'name' => 'Ghodbunder Road'],
                ['city' => 'Thane', 'name' => 'Wagle Estate'],
                ['city' => 'Thane', 'name' => 'Kopri'],
                ['city' => 'Thane', 'name' => 'Kalwa'],
            ];

            $locationIds = [];
            foreach ($locations as $i => $loc) {
                $status = ($i % 17 === 0) ? 'inactive' : 'active';
                $model = Location::updateOrCreate(
                    ['name' => $loc['name']],
                    ['status' => $status, 'updated_at' => $now]
                );
                $locationIds[] = $model->id;
            }

            $specialties = [
                ['name' => 'Cardiology', 'icon_key' => 'cardiology'],
                ['name' => 'Gynecology', 'icon_key' => 'gynecology'],
                ['name' => 'Orthopedics', 'icon_key' => 'orthopedics'],
                ['name' => 'Pediatrics', 'icon_key' => 'pediatrics'],
                ['name' => 'ENT', 'icon_key' => 'ent'],
                ['name' => 'Dermatology', 'icon_key' => 'dermatology'],
                ['name' => 'Neurology', 'icon_key' => 'neurology'],
                ['name' => 'Gastroenterology', 'icon_key' => 'gastroenterology'],
                ['name' => 'Pulmonology', 'icon_key' => 'pulmonology'],
                ['name' => 'Urology', 'icon_key' => 'urology'],
                ['name' => 'Ophthalmology', 'icon_key' => 'ophthalmology'],
                ['name' => 'Dentist', 'icon_key' => 'dentist'],
                ['name' => 'Physiotherapist', 'icon_key' => 'physiotherapist'],
                ['name' => 'Clinical Dietitian', 'icon_key' => 'clinical_dietitian'],
            ];

            $specialtyByName = [];
            foreach ($specialties as $idx => $s) {
                $model = Specialty::updateOrCreate(
                    ['name' => $s['name']],
                    [
                        'code' => Str::slug($s['name']),
                        'icon_key' => $s['icon_key'],
                        'plain_label' => $s['name'],
                        'description' => 'Seeded specialty for Mumbai/Thane demo dataset.',
                        'is_active' => true,
                        'sort_order' => $idx + 1,
                        'updated_at' => $now,
                    ]
                );
                $specialtyByName[$s['name']] = $model;
            }

            $dxTypes = [
                ['name' => 'Pathology (Blood & Urine)', 'code' => 'pathology'],
                ['name' => 'X-Ray', 'code' => 'x-ray'],
                ['name' => 'Ultrasound (USG)', 'code' => 'ultrasound'],
                ['name' => 'ECG', 'code' => 'ecg'],
                ['name' => '2D Echo', 'code' => '2d-echo'],
                ['name' => 'TMT', 'code' => 'tmt'],
                ['name' => 'CT Scan', 'code' => 'ct-scan'],
                ['name' => 'MRI', 'code' => 'mri'],
                ['name' => 'Mammography', 'code' => 'mammography'],
                ['name' => 'PFT (Pulmonary Function Test)', 'code' => 'pft'],
                ['name' => 'Endoscopy', 'code' => 'endoscopy'],
                ['name' => 'Audiometry', 'code' => 'audiometry'],
            ];

            $dxTypeIds = [];
            foreach ($dxTypes as $i => $t) {
                $existingId = DB::table('diagnostic_service_types')->where('code', $t['code'])->value('id');
                if ($existingId) {
                    DB::table('diagnostic_service_types')->where('id', $existingId)->update([
                        'name' => $t['name'],
                        'is_active' => true,
                        'sort_order' => $i + 1,
                        'updated_at' => $now,
                    ]);
                    $dxTypeIds[$t['code']] = (int) $existingId;
                    continue;
                }

                $id = DB::table('diagnostic_service_types')->insertGetId([
                    'name' => $t['name'],
                    'code' => $t['code'],
                    'is_active' => true,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $dxTypeIds[$t['code']] = (int) $id;
            }

            $hospitalTemplates = [
                ['name' => 'Sahyadri Multispeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Aarogya General Hospital', 'type' => 'General Hospital'],
                ['name' => 'MetroCare Superspeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Cityline Municipal Hospital', 'type' => 'Municipal Hospital'],
                ['name' => 'Niramaya Women & Child Hospital', 'type' => 'Single-Specialty'],
                ['name' => 'Lotus Heart Institute', 'type' => 'Single-Specialty'],
                ['name' => 'OrthoPlus Bone & Joint Centre', 'type' => 'Single-Specialty'],
                ['name' => 'NeuroVista Clinic & Daycare', 'type' => 'Single-Specialty'],
                ['name' => 'Shree Seva Hospital', 'type' => 'General Hospital'],
                ['name' => 'Coastal Multispeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Harbor General Hospital', 'type' => 'General Hospital'],
                ['name' => 'Civic Care Hospital', 'type' => 'Municipal Hospital'],
                ['name' => 'Sunrise Multispeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Evergreen Community Hospital', 'type' => 'General Hospital'],
                ['name' => 'Apex Eye Hospital', 'type' => 'Single-Specialty'],
                ['name' => 'Prana Gastro & Liver Hospital', 'type' => 'Single-Specialty'],
                ['name' => 'BreathWell Chest Hospital', 'type' => 'Single-Specialty'],
                ['name' => 'Harmony Children Hospital', 'type' => 'Single-Specialty'],
                ['name' => 'Sterling Multispeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Greenfield Hospital', 'type' => 'General Hospital'],
                ['name' => 'Sankalp Hospital', 'type' => 'General Hospital'],
                ['name' => 'Thane Civic Hospital', 'type' => 'Municipal Hospital'],
                ['name' => 'Lakeview Hospital', 'type' => 'General Hospital'],
                ['name' => 'Westside Multispeciality Hospital', 'type' => 'Multi-Speciality'],
                ['name' => 'Trinity Hospital', 'type' => 'General Hospital'],
            ];

            $hospitalIds = [];
            foreach ($hospitalTemplates as $i => $h) {
                $locationId = $locationIds[$i % count($locationIds)];
                $location = Location::find($locationId);
                $city = $this->cityForLocationName($location?->name);
                $pincode = $this->pincodeForCity($city, $i);
                $status = ($i % 13 === 0) ? 'inactive' : 'active';

                $hospital = Hospital::updateOrCreate(
                    ['name' => $h['name'].' - '.$location?->name],
                    [
                        'location_id' => $locationId,
                        'hospital_type' => $h['type'],
                        'address' => $this->streetAddress($location?->name, $city, $pincode),
                        'micro_area' => $location?->name,
                        'city' => $city,
                        'pincode' => $pincode,
                        'state' => 'Maharashtra',
                        'country' => 'India',
                        'contact_number' => $this->mobileFromBase(9123000000, $i),
                        'email' => $this->emailForOrg($h['name'], 'hosp', $i),
                        'admin_name' => $this->personName($i + 10),
                        'admin_designation' => ($i % 4 === 0) ? 'Administrator' : 'Operations Manager',
                        'admin_mobile' => $this->mobileFromBase(9133000000, $i),
                        'admin_email' => $this->emailForOrg($h['name'], 'admin', $i),
                        'status' => $status,
                        'updated_at' => $now,
                    ]
                );
                $hospitalIds[] = $hospital->id;
            }

            $gpFirstNames = ['Aarav', 'Vihaan', 'Arjun', 'Kabir', 'Ishaan', 'Riya', 'Anaya', 'Sara', 'Myra', 'Ira', 'Aditya', 'Neha', 'Karan', 'Nikita', 'Rahul', 'Sneha', 'Siddharth', 'Meera', 'Pranav', 'Isha'];
            $gpLastNames = ['Shah', 'Mehta', 'Iyer', 'Kulkarni', 'Patil', 'Desai', 'Bhat', 'Joshi', 'Nair', 'Rao', 'Jain', 'Kapoor', 'Chavan', 'Kamble', 'Sane', 'Gokhale'];
            $clinicPrefixes = ['Family Care', 'Health First', 'City Clinic', 'Wellness Point', 'Care & Cure', 'Prime Health', 'Sai Clinic', 'Shree Clinic', 'Healing Hands', 'Neighborhood Clinic'];

            for ($i = 1; $i <= 40; $i++) {
                $locationId = $locationIds[($i * 3) % count($locationIds)];
                $location = Location::find($locationId);
                $city = $this->cityForLocationName($location?->name);
                $pincode = $this->pincodeForCity($city, 100 + $i);

                $name = 'Dr. '.$gpFirstNames[$i % count($gpFirstNames)].' '.$gpLastNames[$i % count($gpLastNames)];
                $email = sprintf('gp%03d@mumbai-thane-demo.test', $i);
                $mobile = $this->mobileFromBase(9000100000, $i);

                $user = User::updateOrCreate(
                    ['mobile' => $mobile],
                    [
                        'name' => $name,
                        'email' => $email,
                        'role' => 'gp',
                        'status' => ($i % 19 === 0) ? 'blocked' : 'active',
                        'password' => Hash::make('password'),
                        'updated_at' => $now,
                    ]
                );

                $gpStatus = ($i % 9 === 0) ? 'pending' : 'approved';
                if ($user->status === 'blocked') {
                    $gpStatus = 'blocked';
                }

                Gp::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'registration_number' => 'MMC/'.str_pad((string) (40000 + $i), 5, '0', STR_PAD_LEFT),
                        'designation' => 'Family Physician',
                        'registration_type' => 'mbbs',
                        'registration_council' => 'Maharashtra Medical Council',
                        'registration_valid_until' => Carbon::now()->addYears(3)->toDateString(),
                        'clinic_name' => $clinicPrefixes[$i % count($clinicPrefixes)].' - '.$location?->name,
                        'address_line' => $this->streetAddress($location?->name, $city, $pincode),
                        'city' => $city,
                        'default_location_id' => $locationId,
                        'pincode' => $pincode,
                        'state' => 'Maharashtra',
                        'country' => 'India',
                        'status' => $gpStatus,
                        'preferred_location' => $location?->name,
                        'updated_at' => $now,
                    ]
                );
            }

            $specFirstNames = ['Aditi', 'Aarohi', 'Dev', 'Rohan', 'Nisha', 'Tanvi', 'Ritvik', 'Sanjana', 'Harsh', 'Aniket', 'Priya', 'Sonal', 'Vivek', 'Mihir', 'Pooja', 'Aman', 'Shreya', 'Nitin', 'Kavya', 'Raj'];
            $specLastNames = ['Deshmukh', 'Bose', 'Mukherjee', 'Khan', 'Singh', 'Shetty', 'Naik', 'Saxena', 'Hegde', 'Gandhi', 'Goyal', 'Banerjee', 'Pillai', 'Soman', 'Mishra', 'Dutta'];
            $languages = [
                ['English', 'Hindi', 'Marathi'],
                ['English', 'Hindi'],
                ['Marathi', 'Hindi'],
                ['English', 'Marathi'],
            ];

            $specializationPool = [
                'Cardiology', 'Cardiology', 'Cardiology',
                'Gynecology', 'Gynecology', 'Gynecology',
                'Orthopedics', 'Orthopedics', 'Orthopedics',
                'Pediatrics', 'Pediatrics', 'Pediatrics',
                'ENT', 'ENT',
                'Dermatology', 'Dermatology',
                'Neurology',
                'Gastroenterology',
                'Pulmonology',
                'Urology',
                'Ophthalmology',
            ];

            $departments = [
                'Cardiology' => 'Cardiology',
                'Gynecology' => 'Obstetrics & Gynecology',
                'Orthopedics' => 'Orthopedics',
                'Pediatrics' => 'Pediatrics',
                'ENT' => 'ENT',
                'Dermatology' => 'Dermatology',
                'Neurology' => 'Neurology',
                'Gastroenterology' => 'Gastroenterology',
                'Pulmonology' => 'Pulmonology',
                'Urology' => 'Urology',
                'Ophthalmology' => 'Ophthalmology',
            ];

            for ($i = 1; $i <= 60; $i++) {
                $specName = 'Dr. '.$specFirstNames[$i % count($specFirstNames)].' '.$specLastNames[$i % count($specLastNames)];
                $email = sprintf('spec%03d@mumbai-thane-demo.test', $i);
                $mobile = $this->mobileFromBase(9000200000, $i);

                $primary = $specializationPool[$i % count($specializationPool)];
                $specialty = $specialtyByName[$primary] ?? null;

                $locationId = $locationIds[($i * 5) % count($locationIds)];
                $location = Location::find($locationId);
                $city = $this->cityForLocationName($location?->name);
                $pincode = $this->pincodeForCity($city, 200 + $i);

                $isPremium = ($i % 7 === 0);
                $isActive = ($i % 23 !== 0);
                $fee = 600 + (($i * 50) % 900);
                $timing = ($i % 2 === 0)
                    ? 'Mon-Sat 10:00–13:00, 17:00–20:00'
                    : 'Mon-Sat 11:00–14:00, 18:00–21:00';

                $user = User::updateOrCreate(
                    ['mobile' => $mobile],
                    [
                        'name' => $specName,
                        'email' => $email,
                        'role' => 'specialist',
                        'status' => $isActive ? 'active' : 'blocked',
                        'password' => Hash::make('password'),
                        'updated_at' => $now,
                    ]
                );

                $clinicName = $this->specialistClinicName($primary, $location?->name, $i);
                $clinicStreet = $this->streetLine($location?->name, $i);
                $clinicAddress = $clinicStreet.', '.$location?->name.', '.$city.' - '.$pincode;

                $specialist = Specialist::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'primary_specialization' => $primary,
                        'specialty_id' => $specialty?->id,
                        'whatsapp_number' => $mobile,
                        'education_primary' => [
                            'degree' => 'MBBS',
                            'university' => 'MUHS',
                            'year' => 2005 + ($i % 10),
                        ],
                        'education_postgrad' => [
                            'degree' => $this->postgradForSpecialty($primary),
                            'university' => 'KEM / Sion / JJ (Training)',
                            'year' => 2010 + ($i % 10),
                        ],
                        'additional_qualifications' => [
                            'Fellowship (Clinical Practice)',
                        ],
                        'medical_council_registration_no' => 'MMC/'.str_pad((string) (70000 + $i), 5, '0', STR_PAD_LEFT),
                        'medical_council_name' => 'Maharashtra Medical Council',
                        'years_of_experience' => 5 + ($i % 18),
                        'sub_specialties' => $this->subSpecialtiesFor($primary),
                        'key_procedures' => $this->proceduresFor($primary),
                        'languages' => $languages[$i % count($languages)],
                        'consultation_in_person' => true,
                        'consultation_teleconsult' => ($i % 4 === 0),
                        'bio' => "Consultation fee: ₹{$fee}. Timings: {$timing}. Referral appointments available.",
                        'clinic_name' => $clinicName,
                        'clinic_address' => $clinicAddress,
                        'clinic_street' => $clinicStreet,
                        'clinic_area' => $location?->name,
                        'clinic_city' => $city,
                        'clinic_pincode' => $pincode,
                        'location_id' => $locationId,
                        'is_premium' => $isPremium,
                        'priority_order' => $isPremium ? ($i % 10) + 1 : null,
                        'is_active' => $isActive,
                        'updated_at' => $now,
                    ]
                );

                if ($i % 2 === 0) {
                    $hospitalId = $hospitalIds[$i % count($hospitalIds)];
                    $hospital = Hospital::find($hospitalId);
                    if ($hospital) {
                        $specialist->hospital_name = $hospital->name;
                        $specialist->save();
                        $specialist->hospitals()->syncWithoutDetaching([
                            $hospital->id => [
                                'is_super_specialist' => ($i % 6 === 0),
                                'department' => $departments[$primary] ?? $primary,
                                'role' => ($i % 5 === 0) ? 'Visiting Consultant' : 'Consultant',
                            ],
                        ]);
                    }
                }
            }

            $centerNames = [
                'Aarogya Diagnostics',
                'MetroScan Imaging',
                'Thane Pathology & Imaging',
                'CityLab Diagnostics',
                'Prime Radiology Centre',
                'Sahyadri Diagnostic Hub',
                'Sunrise Imaging & Labs',
                'WellCheck Diagnostics',
                'Niramaya Diagnostics',
                'CarePlus Pathology',
                'Westside Imaging',
                'Lakeview Diagnostics',
                'Harbor Radiology',
                'Greenfield Diagnostics',
                'Trinity Diagnostics',
                'Pulse Diagnostics',
                'VisionScan Imaging',
                'OrthoPlus Imaging',
                'HeartLine Diagnostics',
                'NeuroVista Diagnostics',
                'Harmony Diagnostics',
                'Civic Diagnostics',
                'Coastal Diagnostics',
                'Apex Diagnostics',
                'Sterling Diagnostics',
            ];

            $authRoles = ['center_head', 'lab_director', 'manager', 'administrator', 'coordinator', 'reception_head', 'owner', 'other'];

            for ($i = 1; $i <= 25; $i++) {
                $locationId = $locationIds[($i * 7) % count($locationIds)];
                $location = Location::find($locationId);
                $city = $this->cityForLocationName($location?->name);
                $pincode = $this->pincodeForCity($city, 300 + $i);

                $centerName = $centerNames[$i - 1].' - '.$location?->name;
                $email = sprintf('dx%03d@mumbai-thane-demo.test', $i);
                $mobile = $this->mobileFromBase(9000300000, $i);

                $user = User::updateOrCreate(
                    ['mobile' => $mobile],
                    [
                        'name' => $centerName,
                        'email' => $email,
                        'role' => 'specialist',
                        'status' => ($i % 21 === 0) ? 'blocked' : 'active',
                        'password' => Hash::make('password'),
                        'updated_at' => $now,
                    ]
                );

                $centerType = match (true) {
                    $i % 4 === 0 => 'imaging',
                    $i % 5 === 0 => 'lab',
                    default => 'multi',
                };

                $centerStatus = ($user->status === 'blocked') ? 'inactive' : 'active';

                $center = DiagnosticCenter::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'location_id' => $locationId,
                        'name' => $centerName,
                        'center_type' => $centerType,
                        'address' => $this->streetAddress($location?->name, $city, $pincode),
                        'micro_area' => $location?->name,
                        'email' => $email,
                        'mobile_number' => $mobile,
                        'alternate_number' => $this->mobileFromBase(9190900000, $i),
                        'opening_time' => '08:00:00',
                        'closing_time' => '20:00:00',
                        'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                        'weekly_off' => 'sunday',
                        'authorized_person_name' => $this->personName(200 + $i),
                        'authorized_person_role' => $authRoles[$i % count($authRoles)],
                        'authorized_person_mobile' => $this->mobileFromBase(9180800000, $i),
                        'authorized_person_email' => sprintf('contact%03d@mumbai-thane-demo.test', $i),
                        'status' => $centerStatus,
                        'updated_at' => $now,
                    ]
                );

                $serviceCodes = match ($centerType) {
                    'lab' => ['pathology', 'ecg', 'pft'],
                    'imaging' => ['x-ray', 'ultrasound', 'ct-scan', 'mri', 'mammography'],
                    default => ['pathology', 'x-ray', 'ultrasound', 'ct-scan', 'mri', 'ecg', '2d-echo', 'tmt'],
                };

                $picked = collect($serviceCodes)
                    ->map(fn ($c) => $dxTypeIds[$c] ?? null)
                    ->filter()
                    ->values();

                $types = DB::table('diagnostic_service_types')
                    ->whereIn('id', $picked->all())
                    ->get(['id', 'name'])
                    ->keyBy('id');

                foreach ($picked as $typeId) {
                    $name = (string) ($types->get($typeId)?->name ?? '');
                    if ($name === '') {
                        continue;
                    }
                    DiagnosticService::updateOrCreate(
                        [
                            'diagnostic_center_id' => $center->id,
                            'diagnostic_service_type_id' => (int) $typeId,
                        ],
                        [
                            'name' => $name,
                            'status' => 'active',
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }))();
    }

    private function cityForLocationName(?string $locationName): string
    {
        $name = strtolower((string) $locationName);
        if (str_contains($name, 'thane') || str_contains($name, 'ghodbunder') || str_contains($name, 'wagle') || str_contains($name, 'kalwa') || str_contains($name, 'kopri')) {
            return 'Thane';
        }
        return 'Mumbai';
    }

    private function pincodeForCity(string $city, int $seed): string
    {
        $base = ($city === 'Thane') ? 400600 : 400050;
        return (string) ($base + ($seed % 50));
    }

    private function mobileFromBase(int $base, int $i): string
    {
        return (string) ($base + ($i % 8000));
    }

    private function emailForOrg(string $name, string $tag, int $i): string
    {
        $slug = Str::slug($name);
        $slug = $slug !== '' ? $slug : 'org';
        return "{$tag}{$i}@{$slug}.test";
    }

    private function personName(int $seed): string
    {
        $first = ['Nilesh', 'Prerna', 'Saurabh', 'Ayesha', 'Rakesh', 'Komal', 'Vijay', 'Snehal', 'Imran', 'Pallavi', 'Rupesh', 'Anjali'];
        $last = ['Patil', 'Shinde', 'Mehta', 'Jadhav', 'Rane', 'Desai', 'Kulkarni', 'Jain', 'Shetty', 'Naik', 'More', 'Kadam'];
        return $first[$seed % count($first)].' '.$last[($seed * 3) % count($last)];
    }

    private function streetLine(?string $area, int $seed): string
    {
        $roads = ['Link Road', 'SV Road', 'LBS Marg', 'Station Road', 'MG Road', 'Ghodbunder Road', 'Eastern Express Highway', 'Western Express Highway'];
        $bldg = ['Sai Plaza', 'Shree Complex', 'Metro Tower', 'Apex House', 'Lake View', 'Sunrise Heights', 'Greenfield Residency', 'Trinity Arcade'];
        $floor = ($seed % 5) + 1;
        $road = $roads[$seed % count($roads)];
        $building = $bldg[($seed * 2) % count($bldg)];
        $area = $area ?: 'Locality';
        return "Shop {$floor}02, {$building}, {$road}, {$area}";
    }

    private function streetAddress(?string $area, string $city, string $pincode): string
    {
        $area = $area ?: $city;
        return $this->streetLine($area, (int) substr($pincode, -2)).", {$city}, Maharashtra {$pincode}";
    }

    private function specialistClinicName(string $primary, ?string $area, int $seed): string
    {
        $area = $area ?: 'Clinic';
        $map = [
            'Cardiology' => ['HeartCare Clinic', 'CardioFirst Centre', 'Pulse Heart Clinic'],
            'Gynecology' => ['WomenCare Clinic', 'Mother & Child Clinic', 'Bloom Gyne Clinic'],
            'Orthopedics' => ['Bone & Joint Clinic', 'OrthoPlus Clinic', 'Spine & Ortho Centre'],
            'Pediatrics' => ['Little Stars Clinic', 'KidsCare Clinic', 'Child Health Centre'],
            'ENT' => ['ENT Care Clinic', 'Hearing & Sinus Clinic', 'Voice ENT Centre'],
            'Dermatology' => ['Skin & Hair Clinic', 'DermaCare Centre', 'ClearSkin Clinic'],
            'Neurology' => ['NeuroCare Clinic', 'Brain & Nerve Centre', 'NeuroVista Clinic'],
            'Gastroenterology' => ['Gastro & Liver Clinic', 'Digestive Care Centre', 'GutWell Clinic'],
            'Pulmonology' => ['Chest & Allergy Clinic', 'BreathWell Clinic', 'PulmoCare Centre'],
            'Urology' => ['UroCare Clinic', 'Kidney & Uro Centre', 'UroPlus Clinic'],
            'Ophthalmology' => ['EyeCare Clinic', 'VisionPlus Centre', 'ClearView Eye Clinic'],
        ];
        $choices = $map[$primary] ?? ['Specialist Clinic'];
        $name = $choices[$seed % count($choices)];
        return "{$name} - {$area}";
    }

    private function postgradForSpecialty(string $primary): string
    {
        return match ($primary) {
            'Cardiology' => 'DM (Cardiology)',
            'Gynecology' => 'MS (OBGYN)',
            'Orthopedics' => 'MS (Orthopedics)',
            'Pediatrics' => 'MD (Pediatrics)',
            'ENT' => 'MS (ENT)',
            'Dermatology' => 'MD (Dermatology)',
            'Neurology' => 'DM (Neurology)',
            'Gastroenterology' => 'DM (Gastroenterology)',
            'Pulmonology' => 'DM (Pulmonology)',
            'Urology' => 'MCh (Urology)',
            'Ophthalmology' => 'MS (Ophthalmology)',
            default => 'MD',
        };
    }

    private function subSpecialtiesFor(string $primary): string
    {
        return match ($primary) {
            'Cardiology' => 'Interventional cardiology, hypertension, heart failure',
            'Gynecology' => 'High-risk pregnancy, infertility, laparoscopic gynecology',
            'Orthopedics' => 'Arthroscopy, joint replacement, sports injuries',
            'Pediatrics' => 'Newborn care, immunization, pediatric asthma',
            'ENT' => 'Sinus, allergy, vertigo, hearing',
            'Dermatology' => 'Acne, eczema, hair fall, cosmetic dermatology',
            'Neurology' => 'Headache, epilepsy, stroke care',
            'Gastroenterology' => 'Acidity, liver disorders, IBS',
            'Pulmonology' => 'Asthma, COPD, sleep apnea',
            'Urology' => 'Kidney stones, prostate, UTIs',
            'Ophthalmology' => 'Cataract, glaucoma, diabetic eye',
            default => 'General specialty care',
        };
    }

    private function proceduresFor(string $primary): string
    {
        return match ($primary) {
            'Cardiology' => 'ECG review, 2D Echo review, angiography counseling',
            'Gynecology' => 'Antenatal care, ultrasound review, laparoscopy consult',
            'Orthopedics' => 'Fracture management, physiotherapy plan, injection therapy',
            'Pediatrics' => 'Growth assessment, vaccination, nebulization support',
            'ENT' => 'Endoscopy consult, audiometry review, allergy management',
            'Dermatology' => 'Chemical peel, wart removal, acne scar treatment',
            'Neurology' => 'EEG review, migraine management, stroke follow-up',
            'Gastroenterology' => 'Endoscopy consult, diet plan, liver panel review',
            'Pulmonology' => 'PFT review, inhaler counseling, sleep study consult',
            'Urology' => 'Stone evaluation, uroflow review, prostate counseling',
            'Ophthalmology' => 'Refraction, cataract counseling, retina screening',
            default => 'Clinical evaluation and treatment planning',
        };
    }
}
