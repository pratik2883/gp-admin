<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'icon_key',
        'plain_label',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function specialists(): HasMany
    {
        return $this->hasMany(Specialist::class);
    }

    public function specialistAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class, 'specialist_specialties')->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Specialty $specialty) {
            if (blank($specialty->code) && filled($specialty->name)) {
                $specialty->code = Str::slug($specialty->name);
            }
        });
    }
}
