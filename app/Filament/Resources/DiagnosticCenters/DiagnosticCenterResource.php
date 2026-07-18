<?php

namespace App\Filament\Resources\DiagnosticCenters;

use App\Filament\Resources\DiagnosticCenters\Pages\ManageDiagnosticCenters;
use App\Filament\Resources\DiagnosticCenters\Pages\ManageDiagnosticCenterServices;
use App\Models\DiagnosticCenter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiagnosticCenterResource extends Resource
{
    protected static ?string $model = DiagnosticCenter::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Directory';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 3;

    public static function getPluralLabel(): ?string
    {
        return 'Diagnostic Centers';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['location'])
            ->withCount('services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Center Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Center Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('center_type')
                            ->label('Center Type')
                            ->options([
                                'lab' => 'Lab / Pathology',
                                'imaging' => 'Radiology / Imaging',
                                'cardiology' => 'Cardiology Diagnostics',
                                'multi' => 'Multi-service Center',
                            ])
                            ->required(),
                        Select::make('location_id')
                            ->label('City / Location')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->optionsLimit(50)
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(190),
                        TextInput::make('mobile_number')
                            ->label('Mobile Number')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        TextInput::make('alternate_number')
                            ->label('Alternate Number')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('address')
                            ->label('Address')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('micro_area')
                            ->label('Area / Micro Area')
                            ->required()
                            ->maxLength(190),
                    ])
                    ->columns(2),

                Section::make('Services')
                    ->schema([
                        Select::make('service_type_ids')
                            ->label('Services')
                            ->multiple()
                            ->searchable()
                            ->optionsLimit(100)
                            ->options(fn () => DB::table('diagnostic_service_types')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(190),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $name = trim((string) ($data['name'] ?? ''));
                                $base = \Illuminate\Support\Str::slug($name);
                                $code = $base !== '' ? $base : 'service';
                                $i = 1;
                                $candidate = $code;
                                while (DB::table('diagnostic_service_types')->where('code', $candidate)->exists()) {
                                    $i++;
                                    $candidate = $code.'-'.$i;
                                }

                                return DB::table('diagnostic_service_types')->insertGetId([
                                    'name' => $name,
                                    'code' => $candidate,
                                    'is_active' => true,
                                    'sort_order' => 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            })
                            ->afterStateHydrated(function (Select $component, $state, ?DiagnosticCenter $record) {
                                if (! $record) {
                                    return;
                                }
                                $ids = $record->services()
                                    ->where('status', 'active')
                                    ->whereNotNull('diagnostic_service_type_id')
                                    ->pluck('diagnostic_service_type_id')
                                    ->unique()
                                    ->values()
                                    ->all();
                                $component->state($ids);
                            })
                            ->dehydrated(false)
                            ->required()
                            ->saveRelationshipsUsing(function ($record, $state) {
                                if (! $record instanceof DiagnosticCenter) {
                                    return;
                                }

                                $ids = collect($state ?? [])
                                    ->map(fn ($v) => (int) $v)
                                    ->filter()
                                    ->unique()
                                    ->values();

                                $types = DB::table('diagnostic_service_types')
                                    ->whereIn('id', $ids)
                                    ->get(['id', 'name'])
                                    ->keyBy('id');

                                $existing = $record->services()->get();
                                $existingByType = $existing
                                    ->whereNotNull('diagnostic_service_type_id')
                                    ->groupBy('diagnostic_service_type_id');

                                foreach ($ids as $typeId) {
                                    $name = $types->get($typeId)?->name;
                                    if (! $name) {
                                        continue;
                                    }

                                    $current = $existingByType->get($typeId);
                                    if ($current && $current->count() > 0) {
                                        foreach ($current as $svc) {
                                            $svc->update([
                                                'name' => $name,
                                                'status' => 'active',
                                            ]);
                                        }

                                        continue;
                                    }

                                    $record->services()->create([
                                        'diagnostic_service_type_id' => $typeId,
                                        'name' => $name,
                                        'status' => 'active',
                                    ]);
                                }

                                $toDeactivate = $existing
                                    ->whereNotNull('diagnostic_service_type_id')
                                    ->whereNotIn('diagnostic_service_type_id', $ids->all());

                                foreach ($toDeactivate as $svc) {
                                    $svc->update(['status' => 'inactive']);
                                }
                            }),
                    ])
                    ->columns(1),

                Section::make('Working Hours')
                    ->schema([
                        TimePicker::make('opening_time')
                            ->label('Opening Time')
                            ->seconds(false)
                            ->displayFormat('h:i A')
                            ->required(),
                        TimePicker::make('closing_time')
                            ->label('Closing Time')
                            ->seconds(false)
                            ->displayFormat('h:i A')
                            ->required(),
                        Select::make('available_days')
                            ->label('Available Days')
                            ->multiple()
                            ->searchable()
                            ->placeholder('Select available days')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->columnSpanFull()
                            ->afterStateHydrated(function (Select $component, $state) {
                                if (! is_array($state)) {
                                    return;
                                }
                                $map = [
                                    'mon' => 'monday',
                                    'tue' => 'tuesday',
                                    'wed' => 'wednesday',
                                    'thu' => 'thursday',
                                    'fri' => 'friday',
                                    'sat' => 'saturday',
                                    'sun' => 'sunday',
                                ];
                                $normalized = collect($state)
                                    ->map(fn ($v) => $map[$v] ?? $v)
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all();
                                $component->state($normalized);
                            })
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $off = $get('weekly_off');
                                if (! $off || ! is_array($state)) {
                                    return;
                                }
                                $set('available_days', array_values(array_filter($state, fn ($d) => $d !== $off)));
                            }),
                        Select::make('weekly_off')
                            ->label('Weekly Off')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->placeholder('None')
                            ->afterStateHydrated(function (Select $component, $state) {
                                $map = [
                                    'mon' => 'monday',
                                    'tue' => 'tuesday',
                                    'wed' => 'wednesday',
                                    'thu' => 'thursday',
                                    'fri' => 'friday',
                                    'sat' => 'saturday',
                                    'sun' => 'sunday',
                                ];
                                if (is_string($state) && isset($map[$state])) {
                                    $component->state($map[$state]);
                                }
                            })
                            ->live()
                            ->rule(fn (Get $get) => Rule::notIn($get('available_days') ?? []))
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (! is_string($state) || $state === '') {
                                    return;
                                }
                                $days = $get('available_days');
                                if (! is_array($days)) {
                                    return;
                                }
                                $set('available_days', array_values(array_filter($days, fn ($d) => $d !== $state)));
                            }),
                    ])
                    ->columns(2),

                Section::make('Authorized Person Details')
                    ->schema([
                        TextInput::make('authorized_person_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(190),
                        Select::make('authorized_person_role')
                            ->label('Role')
                            ->options([
                                'center_head' => 'Center Head',
                                'lab_director' => 'Lab Director',
                                'manager' => 'Manager',
                                'administrator' => 'Administrator',
                                'coordinator' => 'Coordinator',
                                'reception_head' => 'Reception Head',
                                'owner' => 'Owner',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->searchable(),
                        TextInput::make('authorized_person_mobile')
                            ->label('Mobile')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        TextInput::make('authorized_person_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(190),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Center Name'),
                TextEntry::make('location.name')
                    ->label('Location')
                    ->placeholder('-'),
                TextEntry::make('center_type')
                    ->label('Center Type')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->placeholder('-'),
                TextEntry::make('mobile_number')
                    ->label('Mobile')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('micro_area')
                    ->label('Micro Area')
                    ->placeholder('-'),
                TextEntry::make('services_count')
                    ->label('Services')
                    ->placeholder('0'),
                TextEntry::make('opening_time')
                    ->placeholder('-'),
                TextEntry::make('closing_time')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Center')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('center_type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('services_count')
                    ->label('Services')
                    ->numeric()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added On')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('manageServices')
                    ->label('Services')
                    ->icon('heroicon-o-queue-list')
                    ->url(fn (DiagnosticCenter $record): string => static::getUrl('services', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiagnosticCenters::route('/'),
            'services' => ManageDiagnosticCenterServices::route('/{record}/services'),
        ];
    }
}
