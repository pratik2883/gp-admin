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

    public function assignments()
    {
        return $this->hasMany(MrAssignment::class, 'mr_user_id', 'user_id');
    }

    /**
     * Doctors this MR registered in the field, across every profile table.
     */
    public function onboardedGps()
    {
        return $this->hasMany(Gp::class, 'onboarded_by_mr_id', 'user_id');
    }

    public function onboardedSpecialists()
    {
        return $this->hasMany(Specialist::class, 'onboarded_by_mr_id', 'user_id');
    }

    public function onboardedDiagnosticCenters()
    {
        return $this->hasMany(DiagnosticCenter::class, 'onboarded_by_mr_id', 'user_id');
    }

    public function onboardedHospitals()
    {
        return $this->hasMany(Hospital::class, 'onboarded_by_mr_id', 'user_id');
    }
}
