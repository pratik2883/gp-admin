<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class OtpSettings extends Settings
{
    public bool $enable_otp_login = false;

    public ?string $firebase_project_id = null;

    public ?string $firebase_api_key = null;

    public ?string $firebase_app_id = null;

    public ?string $firebase_sender_id = null;

    public static function group(): string
    {
        return 'otp';
    }
}
