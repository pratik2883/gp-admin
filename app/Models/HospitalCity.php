<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalCity extends Model
{
    protected $table = 'hospitals';

    protected $primaryKey = 'city';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;
}
