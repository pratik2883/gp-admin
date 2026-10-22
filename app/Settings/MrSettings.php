<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Every rule the MR app used to hardcode lives here, so the admin panel owns
 * the behaviour instead of the mobile build:
 *
 *  - shift timings were literals inside MrController::getProfile()
 *  - the 15-day non-active-GP window and the 7-day expiry window were literals
 *  - which doctor categories an MR may register was a fixed validation list
 *  - nothing could switch the field app off at all
 *
 * Defaults reproduce the previous behaviour exactly, so deploying this changes
 * nothing until an admin edits a value.
 */
class MrSettings extends Settings
{
    public bool $mr_app_enabled = true;

    public bool $allow_auto_provision_profile = true;

    // Attendance / shift
    public string $shift_start = '09:30';
    public string $shift_end = '18:30';
    public bool $attendance_popup_enabled = true;
    public bool $selfie_required_on_check_in = false;
    public bool $geotag_required_on_check_in = false;

    // Visits (DCR)
    public bool $geotag_required_on_visit = false;
    public int $max_visits_per_day = 0;

    // Targets applied to a newly created MR profile
    public int $default_daily_visit_target = 10;
    public int $default_monthly_subscription_target = 5;

    // Territory alerts
    public int $non_active_gp_days = 15;
    public int $expiring_subscription_days = 7;

    // Field onboarding permissions
    public bool $allow_onboard_gp = true;
    public bool $allow_onboard_specialist = true;
    public bool $allow_onboard_diagnostic_center = true;
    public bool $allow_onboard_hospital = true;

    // Subscriptions
    public bool $allow_payment_link = true;

    public static function group(): string
    {
        return 'mr';
    }

    /** Doctor categories an MR is currently allowed to register. */
    public function allowedOnboardingRoles(): array
    {
        return array_values(array_filter([
            $this->allow_onboard_gp ? 'gp' : null,
            $this->allow_onboard_specialist ? 'specialist' : null,
            $this->allow_onboard_diagnostic_center ? 'diagnostic_center' : null,
            $this->allow_onboard_hospital ? 'hospital' : null,
        ]));
    }
}
