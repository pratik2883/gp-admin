<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrVisit extends Model
{
    protected $fillable = [
        'mr_user_id',
        'doctor_user_id',
        'hospital_id',
        'doctor_type',
        'doctor_name',
        'clinic_name',
        'visit_date',
        'visit_purpose',
        'latitude',
        'longitude',
        'notes',
        'follow_up_date',
        'selfie_path',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function mrUser()
    {
        return $this->belongsTo(User::class, 'mr_user_id');
    }

    public function doctorUser()
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /** Display name for the visited doctor/centre/hospital. */
    public function targetName(): string
    {
        if ($this->doctor_type === 'hospital') {
            return (string) ($this->hospital?->name ?? $this->clinic_name ?? $this->doctor_name ?? 'Hospital');
        }

        return (string) ($this->doctor_name
            ?? optional($this->doctorUser)->name
            ?? $this->clinic_name
            ?? '—');
    }
}
