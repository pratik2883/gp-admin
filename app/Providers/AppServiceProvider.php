<?php

namespace App\Providers;

use App\Http\Controllers\Api\OtpAuthController;
use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserSubscription;
use App\Settings\NotificationSettings;
use App\Support\AuthPayloadCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Registered before the settings bootstrap below, which can return early.
        $this->configureRateLimiters();
        $this->registerAuthPayloadCacheInvalidation();

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

    /**
     * Named rate limiters referenced from routes/api.php.
     */
    private function configureRateLimiters(): void
    {
        // Baseline API budget. Keyed by the authenticated user so one noisy
        // client cannot consume another's allowance; unauthenticated requests
        // fall back to the (proxy-aware) client IP.
        RateLimiter::for('api', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute(120)->by($userId ? 'user:'.$userId : 'ip:'.$request->ip());
        });

        // OTP sends are keyed by the *normalized destination number* rather than
        // the caller's IP: an attacker cannot fan out across numbers, and users
        // sharing an office/carrier NAT do not exhaust each other's budget.
        RateLimiter::for('otp', function (Request $request) {
            $mobile = OtpAuthController::canonicalize((string) $request->input('mobile', ''));

            return Limit::perMinute(3)->by($mobile !== '' ? 'msisdn:'.$mobile : 'ip:'.$request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute(30)->by($userId ? 'user:'.$userId : 'ip:'.$request->ip());
        });
    }

    /**
     * Drop the memoised /api/auth/me payload whenever one of the rows it
     * aggregates is written. Without this the short TTL would be the only thing
     * bounding staleness.
     */
    private function registerAuthPayloadCacheInvalidation(): void
    {
        $forget = static function (Model $model): void {
            $userId = $model instanceof User
                ? $model->getKey()
                : $model->getAttribute('user_id');

            if ($userId) {
                AuthPayloadCache::forget((int) $userId);
            }
        };

        foreach ([User::class, Gp::class, Specialist::class, DiagnosticCenter::class, UserSubscription::class] as $model) {
            $model::saved($forget);
            $model::deleted($forget);
        }
    }
}
