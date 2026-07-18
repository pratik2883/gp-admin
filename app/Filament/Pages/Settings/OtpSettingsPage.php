<?php

namespace App\Filament\Pages\Settings;

use App\Settings\OtpSettings as OtpSettingsStore;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

class OtpSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'OTP / Firebase';

    protected string $view = 'filament.pages.settings.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = app(OtpSettingsStore::class);
        $this->form->fill([
            'enable_otp_login' => (bool) $store->enable_otp_login,
            'firebase_project_id' => $store->firebase_project_id,
            'firebase_api_key' => $store->firebase_api_key,
            'firebase_app_id' => $store->firebase_app_id,
            'firebase_sender_id' => $store->firebase_sender_id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Firebase OTP')
                    ->schema([
                        Forms\Components\Toggle::make('enable_otp_login')
                            ->inline(false)
                            ->helperText('Used mainly by the mobile app for Firebase OTP login'),
                        Forms\Components\TextInput::make('firebase_project_id')
                            ->label('Project ID'),
                        Forms\Components\TextInput::make('firebase_api_key')
                            ->label('API Key'),
                        Forms\Components\TextInput::make('firebase_app_id')
                            ->label('App ID'),
                        Forms\Components\TextInput::make('firebase_sender_id')
                            ->label('Sender ID'),
                        Forms\Components\Placeholder::make('otp_note')
                            ->label('')
                            ->content('These settings are stored for client integration; backend token verification uses Project ID.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(OtpSettingsStore::class);

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
            ->title('OTP / Firebase settings saved')
            ->success()
            ->send();
    }

    private function ensureSettingsExist(OtpSettingsStore $store): void
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
