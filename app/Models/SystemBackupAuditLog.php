<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackupAuditLog extends Model
{
    protected $fillable = [
        'system_backup_id',
        'user_id',
        'action',
        'ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function backup(): BelongsTo
    {
        return $this->belongsTo(SystemBackup::class, 'system_backup_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

