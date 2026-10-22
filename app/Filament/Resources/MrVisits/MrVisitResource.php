<?php

namespace App\Filament\Resources\MrVisits;

use App\Filament\Resources\MrVisits\Pages\ManageMrVisits;
use App\Models\MrVisit;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daily Call Report — every field visit an MR logged, with the selfie and GPS
 * proof. This is the read view the admin panel never had.
 */
class MrVisitResource extends Resource
{
    protected static ?string $model = MrVisit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'DCR Visits';
    }

    public static function getPluralModelLabel(): string
    {
        return 'DCR Visits';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['mrUser', 'doctorUser', 'hospital']);
    }

    public static function typeLabel(?string $state): string
    {
        return match ($state) {
            'gp' => 'GP',
            'specialist' => 'Specialist',
            'diagnostic_center' => 'Diagnostic',
            'hospital' => 'Hospital',
            default => (string) $state,
        };
    }

    /**
     * Admin can log a visit on behalf of an MR and correct a wrong entry — the
     * panel is the fallback when the field app was offline or a number was
     * mistyped.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')->schema([
                Select::make('mr_user_id')
                    ->label('MR')
                    ->options(fn () => User::query()->where('role', 'mr')
                        ->orderBy('name')->limit(300)->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->required(),
                DatePicker::make('visit_date')
                    ->label('Visit date')
                    ->default(fn () => now())
                    ->required(),
                Select::make('doctor_type')
                    ->label('Doctor type')
                    ->options([
                        'gp' => 'GP',
                        'specialist' => 'Specialist',
                        'diagnostic_center' => 'Diagnostic Center',
                        'hospital' => 'Hospital',
                    ])
                    ->default('gp')
                    ->live()
                    ->required(),
                Select::make('hospital_id')
                    ->label('Hospital')
                    ->options(fn () => Hospital::query()->orderBy('name')
                        ->limit(300)->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('doctor_type') === 'hospital'),
                TextInput::make('doctor_name')
                    ->label('Doctor / contact name')
                    ->maxLength(190),
                TextInput::make('clinic_name')
                    ->label('Clinic / centre name')
                    ->maxLength(190),
                Select::make('visit_purpose')
                    ->options([
                        'pitching' => 'New pitching & onboarding',
                        'training' => 'App training & demo',
                        'renewal' => 'Subscription renewal',
                        'feedback' => 'Referral feedback',
                        'complaint' => 'Complaint & issue resolution',
                        'other' => 'Other',
                    ])
                    ->default('pitching')
                    ->required(),
                DatePicker::make('follow_up_date')->label('Follow-up date'),
                TextInput::make('latitude')->numeric(),
                TextInput::make('longitude')->numeric(),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visit')->schema([
                TextEntry::make('visit_date')->label('Visit date')->date('d M Y'),
                TextEntry::make('mrUser.name')->label('MR'),
                TextEntry::make('doctor_type')->label('Type')->formatStateUsing(fn (?string $state) => static::typeLabel($state)),
                TextEntry::make('target')
                    ->label('Doctor / Hospital')
                    ->state(fn (MrVisit $record): string => $record->targetName()),
                TextEntry::make('clinic_name')->label('Clinic / Centre')->placeholder('-'),
                TextEntry::make('visit_purpose')->label('Purpose')->badge(),
                TextEntry::make('follow_up_date')->label('Follow-up')->date('d M Y')->placeholder('-'),
                TextEntry::make('notes')->label('Notes')->placeholder('-')->columnSpanFull(),
            ])->columns(3),

            Section::make('Proof')->schema([
                ImageEntry::make('selfie_path')->label('Selfie')->disk('public')->height(220),
                TextEntry::make('map')
                    ->label('Geotag')
                    ->state(fn (MrVisit $record): string => $record->latitude
                        ? $record->latitude.', '.$record->longitude
                        : 'No GPS captured')
                    ->url(fn (MrVisit $record): ?string => $record->latitude
                        ? 'https://www.google.com/maps?q='.$record->latitude.','.$record->longitude
                        : null)
                    ->openUrlInNewTab(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visit_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('mrUser.name')
                    ->label('MR')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::typeLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'gp' => 'success',
                        'specialist' => 'info',
                        'diagnostic_center' => 'warning',
                        'hospital' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('doctor_name')
                    ->label('Doctor / Hospital')
                    ->state(fn (MrVisit $record): string => $record->targetName())
                    ->searchable()
                    ->limit(32),
                TextColumn::make('visit_purpose')
                    ->label('Purpose')
                    ->badge()
                    ->toggleable(),
                ImageColumn::make('selfie_path')
                    ->label('Selfie')
                    ->disk('public')
                    ->height(36)
                    ->width(36)
                    ->square(),
                TextColumn::make('latitude')
                    ->label('Geo')
                    ->state(fn (MrVisit $record): string => $record->latitude ? 'Open map' : '—')
                    ->url(fn (MrVisit $record): ?string => $record->latitude
                        ? 'https://www.google.com/maps?q='.$record->latitude.','.$record->longitude
                        : null)
                    ->openUrlInNewTab()
                    ->toggleable(),
                TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('mr_user_id')
                    ->label('MR')
                    ->relationship('mrUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('doctor_type')
                    ->label('Doctor type')
                    ->options([
                        'gp' => 'GP',
                        'specialist' => 'Specialist',
                        'diagnostic_center' => 'Diagnostic Center',
                        'hospital' => 'Hospital',
                    ]),
                Filter::make('visit_date')
                    ->label('Visit date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('visit_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('visit_date', '<=', $date));
                    }),
            ])
            ->defaultSort('visit_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->label('Correct'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMrVisits::route('/'),
        ];
    }
}
