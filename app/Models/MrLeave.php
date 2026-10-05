<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrLeave extends Model
{
    protected $fillable = [
        'mr_user_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function mrUser()
    {
        return $this->belongsTo(User::class, 'mr_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
