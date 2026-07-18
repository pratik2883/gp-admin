<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];

    public function specialists(): HasMany
    {
        return $this->hasMany(Specialist::class);
    }

    public function gps(): HasMany
    {
        return $this->hasMany(Gp::class, 'default_location_id');
    }

    public function hospitals()
    {
        return $this->hasMany(Hospital::class);
    }

    public function nearbyLocations(): BelongsToMany
    {
        return $this->belongsToMany(
            Location::class,
            'nearby_location_mappings',
            'location_id',
            'nearby_location_id'
        )
            ->withPivot(['sort_order'])
            ->orderByPivot('sort_order');
    }

    public function isNearbyToLocations(): BelongsToMany
    {
        return $this->belongsToMany(
            Location::class,
            'nearby_location_mappings',
            'nearby_location_id',
            'location_id'
        )
            ->withPivot(['sort_order'])
            ->orderByPivot('sort_order');
    }
}
