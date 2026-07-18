<?php

namespace App\Filament\Pages\Settings;

use App\Models\Location;
use App\Settings\GeneralSettings as GeneralSettingsStore;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

class GeneralSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'General';

    protected string $view = 'filament.pages.settings.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = app(GeneralSettingsStore::class);
        $this->form->fill([
            'app_name' => $store->app_name,
            'app_logo_path' => $store->app_logo_path,
            'primary_color' => $store->primary_color,
            'default_location_id' => $store->default_location_id,
            'max_upload_size_mb' => $store->max_upload_size_mb,
            'allowed_file_types' => $store->allowed_file_types,
            'allow_profile_videos_for_specialists' => $store->allow_profile_videos_for_specialists,
            'allow_profile_certificates_for_specialists' => $store->allow_profile_certificates_for_specialists,
            'enable_google_address_autocomplete' => $store->enable_google_address_autocomplete,
            'google_places_api_key_android' => $store->google_places_api_key_android,
            'google_places_api_key_ios' => $store->google_places_api_key_ios,
            'google_places_country_code' => $store->google_places_country_code,
            'enable_ccavenue_payments' => $store->enable_ccavenue_payments,
            'ccavenue_test_mode' => $store->ccavenue_test_mode,
            'ccavenue_mock_mode' => $store->ccavenue_mock_mode,
            'ccavenue_merchant_id' => $store->ccavenue_merchant_id,
            'ccavenue_access_code' => $store->ccavenue_access_code,
            'ccavenue_working_key' => $store->ccavenue_working_key,
            'ccavenue_currency' => $store->ccavenue_currency,
            'enable_razorpay_payments' => $store->enable_razorpay_payments,
            'razorpay_mock_mode' => $store->razorpay_mock_mode,
            'razorpay_key_id' => $store->razorpay_key_id,
            'razorpay_key_secret' => $store->razorpay_key_secret,
            'razorpay_webhook_secret' => $store->razorpay_webhook_secret,
            'razorpay_webhook_url' => route('payments.razorpay.webhook'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Branding')
                    ->schema([
                        Forms\Components\TextInput::make('app_name')
                            ->label('App name')
                            ->required(),
                        Forms\Components\FileUpload::make('app_logo_path')
                            ->label('App logo')
                            ->image()
                            ->directory('branding')
                            ->imageEditor()
                            ->imagePreviewHeight('150')
                            ->downloadable(),
                        Forms\Components\TextInput::make('primary_color')
                            ->label('Primary color (hex)')
                            ->placeholder('#2563eb'),
                    ])
                    ->columns(2),

                Section::make('Defaults')
                    ->schema([
                        Forms\Components\Select::make('default_location_id')
                            ->label('Default Location')
                            ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->native(false),
                    ]),

                Section::make('Uploads')
                    ->schema([
                        Forms\Components\TextInput::make('max_upload_size_mb')
                            ->label('Max upload size (MB)')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TagsInput::make('allowed_file_types')
                            ->label('Allowed file types')
                            ->placeholder('Add extension like pdf, jpg'),
                    ])
                    ->columns(2),

                Section::make('Specialist Profile Content')
                    ->schema([
                        Forms\Components\Toggle::make('allow_profile_videos_for_specialists')
                            ->label('Allow profile videos for specialists'),
                        Forms\Components\Toggle::make('allow_profile_certificates_for_specialists')
                            ->label('Allow profile certificates for specialists'),
                    ])
                    ->columns(2),

                Section::make('Address Autocomplete')
                    ->schema([
                        Forms\Components\Toggle::make('enable_google_address_autocomplete')
                            ->label('Enable Google address autocomplete in app')
                            ->helperText('When disabled, users will continue to type address manually in the app.'),
                        Forms\Components\TextInput::make('google_places_country_code')
                            ->label('Default country code')
                            ->placeholder('IN')
                            ->maxLength(2)
                            ->formatStateUsing(fn (?string $state) => strtoupper((string) $state))
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
                        Forms\Components\TextInput::make('google_places_api_key_android')
                            ->label('Google Places API key (Android)')
                            ->password()
                            ->revealable()
                            ->helperText('Use an Android-restricted key for the app package and SHA-1.'),
                        Forms\Components\TextInput::make('google_places_api_key_ios')
                            ->label('Google Places API key (iOS)')
                            ->password()
                            ->revealable()
                            ->helperText('Use an iOS-restricted key for the bundle identifier.'),
                    ])
                    ->columns(2),

                Section::make('CCAvenue Payments')
                    ->schema([
                        Forms\Components\Toggle::make('enable_ccavenue_payments')
                            ->label('Enable CCAvenue subscription payments')
                            ->helperText('When enabled, specialist, hospital, and diagnostic subscriptions will move to payment flow.'),
                        Forms\Components\Toggle::make('ccavenue_test_mode')
                            ->label('Use CCAvenue test environment'),
                        Forms\Components\Toggle::make('ccavenue_mock_mode')
                            ->label('Enable mock payment mode')
                            ->helperText('Use this for local testing without real CCAvenue credentials.'),
                        Forms\Components\TextInput::make('ccavenue_currency')
                            ->label('Currency')
                            ->placeholder('INR')
                            ->maxLength(10)
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
                        Forms\Components\TextInput::make('ccavenue_merchant_id')
                            ->label('Merchant ID')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('ccavenue_access_code')
                            ->label('Access Code')
                            ->password()
                            ->revealable(),
                        Forms\Components\TextInput::make('ccavenue_working_key')
                            ->label('Working Key')
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Razorpay Payments')
                    ->schema([
                        Forms\Components\Toggle::make('enable_razorpay_payments')
                            ->label('Enable Razorpay subscription payments')
                            ->helperText('When enabled, specialist, hospital, and diagnostic subscriptions will use Razorpay payment flow.'),
                        Forms\Components\Toggle::make('razorpay_mock_mode')
                            ->label('Enable mock payment mode')
                            ->helperText('Use this for local testing without real Razorpay credentials.'),
                        Forms\Components\TextInput::make('razorpay_key_id')
                            ->label('Key ID')
                            ->maxLength(100)
                            ->helperText('Your Razorpay API Key ID from the Dashboard.'),
                        Forms\Components\TextInput::make('razorpay_key_secret')
                            ->label('Key Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Your Razorpay API Key Secret from the Dashboard.'),
                        Forms\Components\TextInput::make('razorpay_webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->helperText('Secret used to validate Razorpay webhook signatures.'),
                        Forms\Components\TextInput::make('razorpay_webhook_url')
                            ->label('Webhook URL')
                            ->columnSpanFull()
                            ->disabled()
                            ->helperText('Configure this URL in your Razorpay Dashboard > Webhooks.'),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(GeneralSettingsStore::class);

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
            ->title('General settings saved')
            ->success()
            ->send();
    }

    private function ensureSettingsExist(GeneralSettingsStore $store): void
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
