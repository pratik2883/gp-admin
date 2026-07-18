<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'category_snapshot',
        'plan_name_snapshot',
        'plan_family_snapshot',
        'bed_slab_snapshot',
        'duration_months_snapshot',
        'price_snapshot',
        'currency_snapshot',
        'status',
        'payment_status',
        'starts_at',
        'ends_at',
        'activated_at',
        'trial_ends_at',
        'bonus_months_credited',
        'expiry_reminder_sent_at',
        'expired_at',
        'cancelled_at',
        'payment_reference',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'duration_months_snapshot' => 'integer',
        'price_snapshot' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'activated_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'bonus_months_credited' => 'boolean',
        'expiry_reminder_sent_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class)->latest('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function markActive(?Carbon $startsAt = null): void
    {
        $startsAt ??= now();
        $endsAt = (clone $startsAt)->addMonths((int) $this->duration_months_snapshot);

        $this->forceFill([
            'status' => 'active',
            'payment_status' => $this->payment_status === 'paid' ? 'paid' : 'paid',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'activated_at' => now(),
            'expired_at' => null,
            'cancelled_at' => null,
        ])->save();
    }

    public function markExpired(): void
    {
        $this->forceFill([
            'status' => 'expired',
            'expired_at' => now(),
        ])->save();
    }
}
