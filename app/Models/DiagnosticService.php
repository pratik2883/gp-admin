<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticService extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnostic_center_id',
        'diagnostic_service_type_id',
        'name',
        'status',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(DiagnosticCenter::class, 'diagnostic_center_id');
    }
}
