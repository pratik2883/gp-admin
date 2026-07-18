<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Gp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'registration_number',
        'designation',
        'registration_type',
        'registration_council',
        'registration_valid_until',
        'clinic_name',
        'address_line',
        'city',
        'default_location_id',
        'pincode',
        'state',
        'country',
        'status',
        'preferred_specialist_id',
        'preferred_location',
    ];

    protected $casts = [
        'registration_valid_until' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    protected static function booted(): void
    {
        static::created(function (Gp $gp) {
            try {
                $admins = User::admins()->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\NewGpRegisteredNotification($gp));
                }
            } catch (\Throwable $e) {
                Log::error('Failed to send new GP notification: ' . $e->getMessage(), [
                    'gp_id' => $gp->id,
                    'user_id' => $gp->user_id,
                ]);
            }
        });
    }
}
