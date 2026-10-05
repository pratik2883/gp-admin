<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrAttendance extends Model
{
    protected $fillable = [
        'mr_user_id',
        'date',
        'check_in_at',
        'check_out_at',
        'check_in_lat',
        'check_in_lng',
        'selfie_path',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_in_lat' => 'float',
        'check_in_lng' => 'float',
    ];

    public function mrUser()
    {
        return $this->belongsTo(User::class, 'mr_user_id');
    }
}
