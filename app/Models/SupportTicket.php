<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no',
        'user_id',
        'role_type',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_admin_id',
        'last_reply_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (SupportTicket $ticket) {
            if (filled($ticket->ticket_no)) {
                return;
            }

            $ticket->forceFill([
                'ticket_no' => 'ST-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || $this->closed_at !== null;
    }
}
