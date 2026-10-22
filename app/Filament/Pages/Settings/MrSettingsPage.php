<?php

namespace App\Filament\Pages\Settings;

use App\Settings\MrSettings as MrSettingsStore;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * The control panel for the MR field app: switch it on/off, set shift timings,
 * decide which doctor categories can be registered in the field, cap daily
 * visits and tune the two alert windows.
 */
class MrSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?string $navigationLabel = 'MR Settings';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'MR App Settings';

    protected static ?string $slug = 'mr-settings';

    protected string $view = 'filament.pages.settings.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = app(MrSettingsStore::class);

        $this->form->fill([
            'mr_app_enabled' => (bool) $store->mr_app_enabled,
            'allow_auto_provision_profile' => (bool) $store->allow_auto_provision_profile,
            'shift_start' => $store->shift_start,
            'shift_end' => $store->shift_end,
            'attendance_popup_enabled' => (bool) $store->attendance_popup_enabled,
            'selfie_required_on_check_in' => (bool) $store->selfie_required_on_check_in,
            'geotag_required_on_check_in' => (bool) $store->geotag_required_on_check_in,
            'geotag_required_on_visit' => (bool) $store->geotag_required_on_visit,
            'max_visits_per_day' => $store->max_visits_per_day,
            'default_daily_visit_target' => $store->default_daily_visit_target,
            'default_monthly_subscription_target' => $store->default_monthly_subscription_target,
            'non_active_gp_days' => $store->non_active_gp_days,
            'expiring_subscription_days' => $store->expiring_subscription_days,
            'allow_onboard_gp' => (bool) $store->allow_onboard_gp,
            'allow_onboard_specialist' => (bool) $store->allow_onboard_specialist,
            'allow_onboard_diagnostic_center' => (bool) $store->allow_onboard_diagnostic_center,
            'allow_onboard_hospital' => (bool) $store->allow_onboard_hospital,
            'allow_payment_link' => (bool) $store->allow_payment_link,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Access')
                    ->description('Turn the field app off to lock every MR out without deleting anyone.')
                    ->schema([
                        Forms\Components\Toggle::make('mr_app_enabled')
                            ->label('MR app enabled')
                            ->inline(false)
                            ->helperText('When off, every /api/mr/* request is rejected for all MR accounts.'),
                        Forms\Components\Toggle::make('allow_auto_provision_profile')
                            ->label('Auto-create MR profile on first load')
                            ->inline(false)
                            ->helperText('Off means an MR without a profile row gets an error instead of a default profile.'),
                    ])
                    ->columns(2),

                Section::make('Shift & attendance')
                    ->schema([
                        Forms\Components\TimePicker::make('shift_start')
                            ->label('Shift start')
                            ->seconds(false)
                            ->required(),
                        Forms\Components\TimePicker::make('shift_end')
                            ->label('Shift end')
                            ->seconds(false)
                            ->required(),
                        Forms\Components\Toggle::make('attendance_popup_enabled')
                            ->label('Show check-in reminder popup')
                            ->inline(false),
                        Forms\Components\Toggle::make('selfie_required_on_check_in')
                            ->label('Selfie mandatory on check-in')
                            ->inline(false),
                        Forms\Components\Toggle::make('geotag_required_on_check_in')
                            ->label('GPS mandatory on check-in')
                            ->inline(false),
                        Forms\Components\Toggle::make('geotag_required_on_visit')
                            ->label('GPS mandatory on DCR visit')
                            ->inline(false),
                    ])
                    ->columns(2),

                Section::make('Visits & targets')
                    ->schema([
                        Forms\Components\TextInput::make('max_visits_per_day')
                            ->label('Max visits an MR can log per day')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('0 = unlimited'),
                        Forms\Components\TextInput::make('default_daily_visit_target')
                            ->label('Default daily visit target (new MR)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('default_monthly_subscription_target')
                            ->label('Default monthly subscription target (new MR)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Territory alerts')
                    ->schema([
                        Forms\Components\TextInput::make('non_active_gp_days')
                            ->label('Non-active GP window (days)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('A GP with no referral after this many days is flagged.'),
                        Forms\Components\TextInput::make('expiring_subscription_days')
                            ->label('Expiring subscription window (days)')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2),

                Section::make('Field onboarding permissions')
                    ->description('Which categories an MR is allowed to register from the app.')
                    ->schema([
                        Forms\Components\Toggle::make('allow_onboard_gp')->label('GP doctor')->inline(false),
                        Forms\Components\Toggle::make('allow_onboard_specialist')->label('Specialist')->inline(false),
                        Forms\Components\Toggle::make('allow_onboard_diagnostic_center')->label('Diagnostic center')->inline(false),
                        Forms\Components\Toggle::make('allow_onboard_hospital')->label('Hospital')->inline(false),
                    ])
                    ->columns(2),

                Section::make('Subscriptions')
                    ->schema([
                        Forms\Components\Toggle::make('allow_payment_link')
                            ->label('MR can generate payment links')
                            ->inline(false),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(MrSettingsStore::class);

        foreach ($data as $key => $value) {
            if (property_exists($store, $key)) {
                $store->{$key} = $value;
            }
        }

        try {
            $store->save();
        } catch (MissingSettings) {
            $this->ensureSettingsExist($store);
            $store->refresh();

            foreach ($data as $key => $value) {
                if (property_exists($store, $key)) {
                    $store->{$key} = $value;
                }
            }

            $store->save();
        }

        FilamentNotification::make()
            ->title('MR settings saved')
            ->success()
            ->send();
    }

    private function ensureSettingsExist(MrSettingsStore $store): void
    {
        $group = $store::group();
        $ref = new \ReflectionClass($store);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
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
