<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WorkflowSettings extends Settings
{
    public bool $force_strict_status_flow = true;

    public bool $allow_direct_sent_to_closed = false;

    public bool $require_hospital_for_ipd = true;

    public ?int $max_referrals_per_gp_per_day = null;

    public static function group(): string
    {
        return 'workflow';
    }
}
