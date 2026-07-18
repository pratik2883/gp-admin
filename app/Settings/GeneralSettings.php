<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $app_name = 'Admin Panel';

    public ?string $app_logo_path = null;

    public ?string $primary_color = '#2563eb';

    public ?int $default_location_id = null;

    public int $max_upload_size_mb = 10;

    public array $allowed_file_types = ['pdf', 'jpg', 'jpeg', 'png'];

    public bool $allow_profile_videos_for_specialists = false;

    public bool $allow_profile_certificates_for_specialists = false;

    public bool $enable_google_address_autocomplete = false;

    public ?string $google_places_api_key_android = null;

    public ?string $google_places_api_key_ios = null;

    public string $google_places_country_code = 'IN';

    public bool $enable_ccavenue_payments = false;

    public bool $ccavenue_test_mode = true;

    public bool $ccavenue_mock_mode = true;

    public ?string $ccavenue_merchant_id = null;

    public ?string $ccavenue_access_code = null;

    public ?string $ccavenue_working_key = null;

    public string $ccavenue_currency = 'INR';

    public bool $enable_razorpay_payments = false;

    public bool $razorpay_mock_mode = true;

    public ?string $razorpay_key_id = null;

    public ?string $razorpay_key_secret = null;

    public ?string $razorpay_webhook_secret = null;

    public ?string $razorpay_webhook_url = null;

    public static function group(): string
    {
        return 'general';
    }

    public function maxUploadBytes(): int
    {
        return $this->max_upload_size_mb * 1024 * 1024;
    }

    public function allowedMimeTypes(): array
    {
        return $this->allowed_file_types;
    }
}
