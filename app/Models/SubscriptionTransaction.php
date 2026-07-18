<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_subscription_id',
        'transaction_uuid',
        'gateway',
        'order_id',
        'tracking_id',
        'status',
        'amount',
        'currency',
        'payment_reference',
        'order_status',
        'failure_message',
        'request_payload',
        'response_payload',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }
}
