<?php

namespace App\Console\Commands;

use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugGooglePlaces extends Command
{
    protected $signature = 'google:debug
        {--platform=android : Platform to test (android|ios)}
        {--test-query=Mumbai : Test autocomplete query}
        {--skip-api : Skip the actual Google API call}';

    protected $description = 'Debug Google Places integration end-to-end';

    public function handle(): int
    {
        $settings = app(GeneralSettings::class);

        $this->newLine();
        $this->info('=== Google Places Configuration ===');
        $this->table(['Setting', 'Value'], [
            ['enable_google_address_autocomplete', $settings->enable_google_address_autocomplete ? 'true' : 'false'],
            ['google_places_api_key_android', $settings->google_places_api_key_android ? substr($settings->google_places_api_key_android, 0, 8).'...' : '(not set)'],
            ['google_places_api_key_ios', $settings->google_places_api_key_ios ? substr($settings->google_places_api_key_ios, 0, 8).'...' : '(not set)'],
            ['google_places_country_code', $settings->google_places_country_code],
        ]);

        $platform = $this->option('platform');
        $apiKey = match ($platform) {
            'ios' => $settings->google_places_api_key_ios,
            default => $settings->google_places_api_key_android,
        };
        $enabled = (bool) $settings->enable_google_address_autocomplete && filled($apiKey);

        $this->newLine();
        $this->info("=== Feature Flag Response (platform: {$platform}) ===");
        $this->table(['Field', 'Value'], [
            ['platform', $platform],
            ['api_key_present', $apiKey ? 'true' : 'false'],
            ['feature_enabled', $enabled ? 'true' : 'false'],
            ['country_code', $settings->google_places_country_code],
        ]);

        if (! $enabled) {
            $this->newLine();
            if (! $settings->enable_google_address_autocomplete) {
                $this->warn('  → Feature is disabled. Enable it via admin panel or --enable flag.');
            }
            if (! filled($apiKey)) {
                $this->warn("  → No API key set for platform: {$platform}");
            }

            return self::FAILURE;
        }

        if ($this->option('skip-api')) {
            return self::SUCCESS;
        }

        $query = $this->option('test-query');
        $this->newLine();
        $this->info("=== Testing Google Places Autocomplete API ===");
        $this->line("Query: \"{$query}\" (country: {$settings->google_places_country_code})");

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                'input' => $query,
                'key' => $apiKey,
                'components' => "country:{$settings->google_places_country_code}",
                'types' => 'establishment|geocode',
            ]);

            if ($response->failed()) {
                $this->error("HTTP Error: {$response->status()}");
                $this->line($response->body());

                return self::FAILURE;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                $this->error("Google API Error: {$data['status']}");
                if (isset($data['error_message'])) {
                    $this->line("Message: {$data['error_message']}");
                }
                $this->newLine();
                $this->warn('Common issues:');
                $this->warn('  1. API key is invalid or restricted');
                $this->warn('  2. Places API is not enabled in Google Cloud Console');
                $this->warn('  3. Billing is not set up for the Google Cloud project');
                $this->warn('  4. API key restrictions (HTTP referrer, IP, app package) do not match');

                return self::FAILURE;
            }

            $this->info('✅ API responded successfully!');
            $this->line("Number of predictions: ".count($data['predictions'] ?? []));
            foreach (array_slice($data['predictions'] ?? [], 0, 3) as $prediction) {
                $this->line("  - {$prediction['description']}");
            }
        } catch (\Exception $e) {
            $this->error("Exception: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
