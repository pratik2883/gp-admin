<?php

namespace App\Filament\Resources\DiagnosticReferrals;

use App\Filament\Resources\DiagnosticReferrals\Pages\ManageDiagnosticReferrals;
use App\Models\DiagnosticCenter;
use App\Models\DiagnosticReferral;
use App\Models\Gp;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DiagnosticReferralResource extends Resource
{
    protected static ?string $model = DiagnosticReferral::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Referrals';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 2;

    public static function getPluralLabel(): ?string
    {
        return 'Diagnostic Referrals';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['gp.user', 'center.location', 'services']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Referral')
                ->schema([
                    TextInput::make('lead_code')
                        ->label('Lead ID')
                        ->disabled(),
                    Select::make('gp_id')
                        ->relationship('gp', 'id', fn ($q) => $q->with('user'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "GP #{$record->id}")
                        ->searchable()
                        ->optionsLimit(25)
                        ->disabled(),
                    Select::make('diagnostic_center_id')
                        ->label('Diagnostic Center')
                        ->relationship('center', 'name', fn ($q) => $q->with('location'))
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            $name = $record->name ?? "Center #{$record->id}";
                            $city = $record->location?->name;

                            return $city ? "{$name} ({$city})" : $name;
                        })
                        ->searchable()
                        ->optionsLimit(50)
                        ->disabled(),
                    Select::make('appointment_type')
                        ->options([
                            'opd' => 'OPD',
                            'ipd' => 'IPD',
                        ])
                        ->disabled(),
                    Select::make('priority')
                        ->options([
                            'routine' => 'Routine',
                            'urgent' => 'Urgent',
                        ])
                        ->disabled(),
                    Select::make('status')
                        ->options([
                            'sent' => 'Sent',
                            'accepted' => 'Accepted',
                            'consulted' => 'Consulted',
                            'closed' => 'Closed',
                        ])
                        ->disabled(),
                ])
                ->columns(2),
            Section::make('Patient')
                ->schema([
                    TextInput::make('patient_name')
                        ->disabled(),
                    TextInput::make('patient_mobile')
                        ->disabled(),
                    TextInput::make('patient_age')
                        ->disabled(),
                    TextInput::make('patient_gender')
                        ->disabled(),
                    Textarea::make('case_summary')
                        ->rows(4)
                        ->columnSpanFull()
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\Section::make('Referral')
                ->schema([
                    TextEntry::make('lead_code')->label('Lead ID'),
                    TextEntry::make('gp.user.name')->label('GP'),
                    TextEntry::make('center.name')->label('Diagnostic Center'),
                    TextEntry::make('center.location.name')->label('Location')->placeholder('-'),
                    TextEntry::make('appointment_type')
                        ->label('Type')
                        ->formatStateUsing(fn ($s) => $s ? strtoupper($s) : null),
                    TextEntry::make('priority')->badge()->placeholder('-'),
                    TextEntry::make('status')->label('Current Status')->badge(),
                ])
                ->columns(2),
            \Filament\Infolists\Components\Section::make('Patient')
                ->schema([
                    TextEntry::make('patient_name'),
                    TextEntry::make('patient_mobile')->placeholder('-'),
                    TextEntry::make('patient_age')->placeholder('-'),
                    TextEntry::make('patient_gender')->badge()->placeholder('-'),
                    TextEntry::make('case_summary')->columnSpanFull(),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_code')
                    ->label('Lead ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->searchable(),
                TextColumn::make('gp.user.name')
                    ->label('GP')
                    ->searchable(),
                TextColumn::make('center.name')
                    ->label('Center')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'warning',
                        'accepted' => 'info',
                        'consulted' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'consulted' => 'Consulted',
                        'closed' => 'Closed',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'consulted' => 'Consulted',
                        'closed' => 'Closed',
                    ])
                    ->multiple()
                    ->query(function ($query, array $data) {
                        $values = array_keys(array_filter($data));

                        return $query->when($values, fn ($q) => $q->whereIn('status', $values));
                    }),
                SelectFilter::make('diagnostic_center_id')
                    ->label('Center')
                    ->relationship('center', 'name')
                    ->searchable()
                    ->optionsLimit(25),
                SelectFilter::make('gp_user_id')
                    ->label('GP')
                    ->relationship('gp.user', 'name')
                    ->searchable()
                    ->optionsLimit(25),
                Filter::make('location')
                    ->form([
                        Select::make('location_id')
                            ->label('Location')
                            ->options(fn () => Cache::remember('diagnostic_referrals.filters.locations', 600, function (): array {
                                $cities = collect()
                                    ->merge(DiagnosticCenter::query()->with('location')->get()->map(fn ($c) => $c->location?->name)->filter()->all())
                                    ->merge(Gp::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->toArray())
                                    ->filter()
                                    ->unique()
                                    ->sort()
                                    ->values();

                                return $cities->combine($cities)->toArray();
                            }))
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $location = $data['location_id'] ?? null;
                        if (! $location) {
                            return $query;
                        }

                        return $query->whereHas('center.location', fn ($q) => $q->where('name', $location));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiagnosticReferrals::route('/'),
        ];
    }
}
