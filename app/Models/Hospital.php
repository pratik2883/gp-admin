<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'hospital_type',
        'address',
        'micro_area',
        'city',
        'pincode',
        'state',
        'country',
        'contact_number',
        'email',
        'admin_name',
        'admin_designation',
        'admin_mobile',
        'admin_email',
        'status',
    ];

    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'hospital_specialist')
            ->withPivot(['is_super_specialist', 'department', 'role'])
            ->withTimestamps();
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
