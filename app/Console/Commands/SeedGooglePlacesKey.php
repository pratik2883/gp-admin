<?php

namespace App\Console\Commands;

use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

class SeedGooglePlacesKey extends Command
{
    protected $signature = 'google:seed-key
        {--android= : Android Google Places API key}
        {--ios= : iOS Google Places API key}
        {--country=IN : Country code (default: IN)}
        {--enable : Enable the address autocomplete feature}
        {--disable : Disable the address autocomplete feature}';

    protected $description = 'Set Google Places API keys and toggle the feature';

    public function handle(): int
    {
        $settings = app(GeneralSettings::class);

        $changed = false;

        if ($android = $this->option('android')) {
            $settings->google_places_api_key_android = $android;
            $this->info("Android key set: {$android}");
            $changed = true;
        }

        if ($ios = $this->option('ios')) {
            $settings->google_places_api_key_ios = $ios;
            $this->info("iOS key set: {$ios}");
            $changed = true;
        }

        if ($country = $this->option('country')) {
            $settings->google_places_country_code = strtoupper(trim($country));
            $this->info("Country code set: {$settings->google_places_country_code}");
            $changed = true;
        }

        if ($this->option('enable')) {
            $settings->enable_google_address_autocomplete = true;
            $this->info('Feature enabled');
            $changed = true;
        }

        if ($this->option('disable')) {
            $settings->enable_google_address_autocomplete = false;
            $this->info('Feature disabled');
            $changed = true;
        }

        if (! $changed) {
            $this->table(['Setting', 'Value'], [
                ['enable_google_address_autocomplete', $settings->enable_google_address_autocomplete ? 'true' : 'false'],
                ['google_places_api_key_android', $settings->google_places_api_key_android ?? '(not set)'],
                ['google_places_api_key_ios', $settings->google_places_api_key_ios ?? '(not set)'],
                ['google_places_country_code', $settings->google_places_country_code],
            ]);

            $this->newLine();
            $this->warn('Use --android, --ios, --country, --enable/--disable to update values.');

            return self::SUCCESS;
        }

        try {
            $settings->save();
        } catch (MissingSettings) {
            $this->ensureSettingsExist($settings);
            $settings->refresh();

            if ($android = $this->option('android')) {
                $settings->google_places_api_key_android = $android;
            }
            if ($ios = $this->option('ios')) {
                $settings->google_places_api_key_ios = $ios;
            }
            if ($country = $this->option('country')) {
                $settings->google_places_country_code = strtoupper(trim($country));
            }
            if ($this->option('enable')) {
                $settings->enable_google_address_autocomplete = true;
            }
            if ($this->option('disable')) {
                $settings->enable_google_address_autocomplete = false;
            }

            $settings->save();
        }

        $this->newLine();
        $this->info('Settings saved successfully!');

        return self::SUCCESS;
    }

    private function ensureSettingsExist(GeneralSettings $store): void
    {
        $group = $store::group();
        $ref = new \ReflectionClass($store);
        $properties = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $exists = SettingsProperty::query()
                ->where('group', $group)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            SettingsProperty::query()->create([
                'group' => $group,
                'name' => $name,
                'payload' => json_encode($store->{$name}),
                'locked' => false,
            ]);
        }
    }
}
