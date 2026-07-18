<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'name',
        'center_type',
        'address',
        'micro_area',
        'email',
        'mobile_number',
        'alternate_number',
        'opening_time',
        'closing_time',
        'available_days',
        'weekly_off',
        'authorized_person_name',
        'authorized_person_role',
        'authorized_person_mobile',
        'authorized_person_email',
        'status',
    ];

    protected $casts = [
        'available_days' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(DiagnosticService::class);
    }

    protected static function booted(): void
    {
        static::saving(function (DiagnosticCenter $center) {
            $map = [
                'mon' => 'monday',
                'tue' => 'tuesday',
                'wed' => 'wednesday',
                'thu' => 'thursday',
                'fri' => 'friday',
                'sat' => 'saturday',
                'sun' => 'sunday',
            ];

            $days = $center->available_days;
            if (is_array($days)) {
                $center->available_days = collect($days)
                    ->map(fn ($v) => is_string($v) ? ($map[$v] ?? strtolower($v)) : null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if (is_string($center->weekly_off) && isset($map[$center->weekly_off])) {
                $center->weekly_off = $map[$center->weekly_off];
            }

            if (filled($center->weekly_off) && is_array($center->available_days)) {
                $center->available_days = array_values(array_filter(
                    $center->available_days,
                    fn ($d) => $d !== $center->weekly_off
                ));
            }
        });
    }
}
