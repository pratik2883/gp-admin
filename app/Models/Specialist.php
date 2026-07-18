<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_photo_path',
        'whatsapp_number',
        'primary_specialization',
        'specialty_id',
        'education_primary',
        'education_postgrad',
        'additional_qualifications',
        'medical_council_registration_no',
        'medical_council_name',
        'years_of_experience',
        'sub_specialties',
        'key_procedures',
        'languages',
        'consultation_in_person',
        'consultation_teleconsult',
        'bio',
        'videos',
        'certificates',
        'clinic_name',
        'hospital_name',
        'clinic_address',
        'clinic_street',
        'clinic_area',
        'clinic_city',
        'clinic_pincode',
        'location_id',
        'is_premium',
        'priority_order',
        'is_active',
    ];

    protected $casts = [
        'education_primary' => 'array',
        'education_postgrad' => 'array',
        'additional_qualifications' => 'array',
        'languages' => 'array',
        'videos' => 'array',
        'certificates' => 'array',
        'consultation_in_person' => 'boolean',
        'consultation_teleconsult' => 'boolean',
        'years_of_experience' => 'integer',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function additionalSpecialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'specialist_specialties')->withTimestamps();
    }

    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_specialist')
            ->withPivot(['is_super_specialist', 'department', 'role'])
            ->withTimestamps();
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Specialist $specialist) {
            if (! $specialist->specialty_id && filled($specialist->primary_specialization)) {
                $match = Specialty::query()
                    ->where('code', $specialist->primary_specialization)
                    ->orWhere('name', $specialist->primary_specialization)
                    ->orWhere('plain_label', $specialist->primary_specialization)
                    ->first();
                if ($match) {
                    $specialist->specialty_id = $match->id;
                }
            }

            if ($specialist->specialty_id) {
                $name = Specialty::query()->whereKey($specialist->specialty_id)->value('name');
                if (filled($name)) {
                    $specialist->primary_specialization = $name;
                }
            }
        });

        static::created(function (Specialist $specialist) {
            $admins = User::admins()->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\NewSpecialistRegisteredNotification($specialist));
            }
        });
    }
}
