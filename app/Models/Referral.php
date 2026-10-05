<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_code',
        'gp_id',
        'specialist_id',
        'hospital_id',
        'referral_type',
        'department',
        'patient_name',
        'patient_mobile',
        'patient_age',
        'patient_gender',
        'case_summary',
        'appointment_type',
        'priority',
        'status',
        'accepted_at',
        'consulted_at',
        'closed_at',
    ];

    protected $casts = [
        'patient_age' => 'integer',
        'accepted_at' => 'datetime',
        'consulted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function gp()
    {
        return $this->belongsTo(Gp::class);
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function files()
    {
        return $this->hasMany(ReferralFile::class);
    }

    public function getStatusTimelineAttribute(): array
    {
        return [
            'sent' => $this->created_at,
            'accepted' => $this->accepted_at,
            'consulted' => $this->consulted_at,
            'closed' => $this->closed_at,
        ];
    }
}
