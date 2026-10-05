<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mr extends Model
{
    protected $fillable = [
        'user_id',
        'employee_code',
        'territory_zone',
        'headquarters_city',
        'daily_visit_target',
        'monthly_subscription_target',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visits()
    {
        return $this->hasMany(MrVisit::class, 'mr_user_id', 'user_id');
    }

    public function attendances()
    {
        return $this->hasMany(MrAttendance::class, 'mr_user_id', 'user_id');
    }

    public function leaves()
    {
        return $this->hasMany(MrLeave::class, 'mr_user_id', 'user_id');
    }
}
