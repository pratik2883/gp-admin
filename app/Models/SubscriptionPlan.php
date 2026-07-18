<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'plan_family',
        'bed_slab',
        'duration_months',
        'trial_days',
        'bonus_months',
        'is_trialable',
        'offer_label',
        'offer_badge_color',
        'price',
        'currency',
        'description',
        'feature_points',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'trial_days' => 'integer',
        'bonus_months' => 'integer',
        'is_trialable' => 'boolean',
        'price' => 'decimal:2',
        'feature_points' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    protected static function booted(): void
    {
        static::saving(function (SubscriptionPlan $plan) {
            if (blank($plan->slug)) {
                $parts = array_filter([
                    $plan->category,
                    $plan->plan_family,
                    $plan->bed_slab,
                    $plan->duration_months ? $plan->duration_months.'-months' : null,
                    $plan->name,
                ]);

                $plan->slug = Str::slug(implode('-', $parts));
            }
        });
    }
}
