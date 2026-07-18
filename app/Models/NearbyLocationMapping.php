<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NearbyLocationMapping extends Model
{
    use HasFactory;

    protected $table = 'nearby_location_mappings';

    protected $fillable = [
        'location_id',
        'nearby_location_id',
        'sort_order',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function nearbyLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'nearby_location_id');
    }
}
