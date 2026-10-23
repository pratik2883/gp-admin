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

    /**
     * Types that are always safe to place on a web-served disk. Anything the
     * panel adds (svg, html, php, ...) is dropped here: the list feeds the
     * `mimes:` validation rule for patient documents, and an active type would
     * be rendered inline from the application's own origin.
     */
    private const SAFE_UPLOAD_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public function allowedMimeTypes(): array
    {
        $configured = array_map(
            static fn ($type) => strtolower(trim((string) $type)),
            (array) $this->allowed_file_types,
        );

        $safe = array_values(array_intersect($configured, self::SAFE_UPLOAD_TYPES));

        // Never hand back an empty rule: a blank `mimes:` list would disable the
        // check entirely instead of tightening it.
        return $safe !== [] ? $safe : self::SAFE_UPLOAD_TYPES;
    }
}
