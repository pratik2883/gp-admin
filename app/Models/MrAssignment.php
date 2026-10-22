<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MrAssignment extends Model
{
    protected $fillable = [
        'mr_user_id',
        'assignable_type',
        'assignable_id',
        'lead_name',
        'lead_mobile',
        'lead_city',
        'title',
        'purpose',
        'priority',
        'due_date',
        'status',
        'notes',
        'mr_remarks',
        'assigned_by',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function mrUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mr_user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Human label for whichever target this assignment points at. */
    public function targetName(): string
    {
        if (filled($this->lead_name)) {
            return (string) $this->lead_name;
        }

        $target = $this->assignable;

        return match (true) {
            $target instanceof User => (string) ($target->name ?? 'Doctor'),
            $target instanceof Hospital => (string) ($target->name ?? 'Hospital'),
            $target instanceof Gp => (string) ($target->clinic_name ?? optional($target->user)->name ?? 'GP'),
            $target instanceof Specialist => (string) (optional($target->user)->name ?? $target->hospital_name ?? 'Specialist'),
            $target instanceof DiagnosticCenter => (string) ($target->name ?? 'Diagnostic Center'),
            default => '—',
        };
    }

    public function targetType(): string
    {
        return match (true) {
            filled($this->lead_name) => 'lead',
            $this->assignable instanceof User => ($this->assignable->role === 'gp' ? 'gp' : 'specialist'),
            $this->assignable instanceof Hospital => 'hospital',
            $this->assignable instanceof Gp => 'gp',
            $this->assignable instanceof Specialist => 'specialist',
            $this->assignable instanceof DiagnosticCenter => 'diagnostic_center',
            default => 'other',
        };
    }
}
