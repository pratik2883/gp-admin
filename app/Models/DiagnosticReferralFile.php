<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticReferralFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnostic_referral_id',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    public function diagnosticReferral(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReferral::class);
    }
}
