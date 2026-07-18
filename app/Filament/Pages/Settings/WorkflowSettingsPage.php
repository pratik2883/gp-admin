<?php

namespace App\Filament\Pages\Settings;

use App\Settings\WorkflowSettings as WorkflowSettingsStore;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;

class WorkflowSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Workflow';

    protected string $view = 'filament.pages.settings.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $store = app(WorkflowSettingsStore::class);
        $this->form->fill([
            'force_strict_status_flow' => (bool) $store->force_strict_status_flow,
            'allow_direct_sent_to_closed' => (bool) $store->allow_direct_sent_to_closed,
            'require_hospital_for_ipd' => (bool) $store->require_hospital_for_ipd,
            'max_referrals_per_gp_per_day' => $store->max_referrals_per_gp_per_day,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Referral Workflow')
                    ->schema([
                        Forms\Components\Toggle::make('force_strict_status_flow')
                            ->inline(false)
                            ->helperText('If enabled, only Sent → Accepted → Consulted → Closed'),
                        Forms\Components\Toggle::make('allow_direct_sent_to_closed')
                            ->inline(false)
                            ->helperText('If enabled, allow Sent → Closed directly'),
                        Forms\Components\Toggle::make('require_hospital_for_ipd')
                            ->inline(false)
                            ->helperText('If enabled, IPD referrals must have a Hospital set'),
                        Forms\Components\TextInput::make('max_referrals_per_gp_per_day')
                            ->numeric()
                            ->minValue(0)
                            ->label('Max referrals per GP per day')
                            ->helperText('0 or empty for unlimited'),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = app(WorkflowSettingsStore::class);

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
            ->title('Workflow settings saved')
            ->success()
            ->send();
    }

    private function ensureSettingsExist(WorkflowSettingsStore $store): void
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
