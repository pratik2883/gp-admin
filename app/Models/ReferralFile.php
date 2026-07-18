<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_id',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }
}
