<?php

namespace App\Providers;

use App\Settings\NotificationSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $settings = app(NotificationSettings::class);
            $currentFrom = (array) config('mail.from', []);

            config([
                'mail.from.address' => $settings->mail_from_address ?: ($currentFrom['address'] ?? config('mail.from.address')),
                'mail.from.name' => $settings->mail_from_name ?: ($currentFrom['name'] ?? config('mail.from.name')),
            ]);

            if (filled($settings->mail_host)) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings->mail_host,
                    'mail.mailers.smtp.port' => (int) ($settings->mail_port ?: 587),
                    'mail.mailers.smtp.username' => $settings->mail_username,
                    'mail.mailers.smtp.password' => $settings->mail_password,
                    'mail.mailers.smtp.encryption' => $settings->mail_encryption ?: null,
                ]);
            }
        } catch (Throwable) {
            // Ignore settings bootstrap errors so local setup and early boot still work.
        }
    }
}
