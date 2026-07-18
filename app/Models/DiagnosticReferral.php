<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_code',
        'gp_id',
        'specialist_id',
        'diagnostic_center_id',
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
        'accepted_at' => 'datetime',
        'consulted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function gp(): BelongsTo
    {
        return $this->belongsTo(Gp::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(DiagnosticCenter::class, 'diagnostic_center_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(DiagnosticService::class, 'diagnostic_referral_service');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DiagnosticReferralFile::class);
    }
}
