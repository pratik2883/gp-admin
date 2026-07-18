<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackup extends Model
{
    protected $fillable = [
        'type',
        'status',
        'disk',
        'path',
        'size_bytes',
        'sha256',
        'manifest',
        'started_at',
        'completed_at',
        'verified_at',
        'verification_status',
        'verification_message',
        'created_by',
    ];

    protected $casts = [
        'manifest' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

