<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
        'clinic_timings',
        'hospital_visiting_hours',
        'available_days',
        'show_mobile_number',
        'show_whatsapp_number',
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
        'available_days' => 'array',
        'videos' => 'array',
        'certificates' => 'array',
        'consultation_in_person' => 'boolean',
        'consultation_teleconsult' => 'boolean',
        'show_mobile_number' => 'boolean',
        'show_whatsapp_number' => 'boolean',
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

    /**
     * Publicly reachable URL for the stored profile photo.
     *
     * Profile photos are written to the "public" disk (storage/app/public,
     * exposed through the public/storage symlink), but photos uploaded by
     * older builds live directly in the public web root. Both are resolved
     * here so the admin panel and the mobile apps always receive a URL that
     * actually points at a file. Returns null when no photo file exists, so
     * clients fall back to their initials placeholder instead of a broken
     * image.
     */
    public function profilePhotoUrl(): ?string
    {
        $path = $this->profile_photo_path;

        if (blank($path)) {
            return null;
        }

        if (is_file(public_path($path))) {
            return url($path);
        }

        if (Storage::disk('public')->exists($path)) {
            // Built from the request host rather than APP_URL so the URL stays
            // correct regardless of how the app is deployed behind a proxy.
            return url('storage/'.$path);
        }

        return null;
    }

    /**
     * Store new profile photo bytes and return the path to persist.
     *
     * Falls back to the public web root when the "public" disk is not
     * writable, so an upload never silently records a path with no file
     * behind it (which is what made photos disappear after upload).
     */
    public function storeProfilePhoto(string $contents, string $extension = 'jpg'): string
    {
        $extension = ltrim(strtolower($extension), '.*');

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $extension = 'jpg';
        }

        $path = 'specialists/'.$this->user_id.'/profile_'.time().'_'.random_int(1000, 9999).'.'.$extension;

        if (Storage::disk('public')->put($path, $contents) !== false) {
            return $path;
        }

        $target = public_path($path);
        $directory = dirname($target);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create directory for specialist profile photo.');
        }

        if (@file_put_contents($target, $contents) === false) {
            throw new RuntimeException('Unable to store specialist profile photo.');
        }

        return $path;
    }

    /**
     * Remove the current profile photo from every location it may live in.
     */
    public function deleteProfilePhotoFiles(): void
    {
        $path = $this->profile_photo_path;

        if (blank($path)) {
            return;
        }

        if (is_file(public_path($path))) {
            @unlink(public_path($path));
        }

        Storage::disk('public')->delete($path);
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
