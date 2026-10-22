<?php

namespace App\Filament\Resources\MrAttendances;

use App\Filament\Resources\MrAttendances\Pages\ManageMrAttendances;
use App\Models\MrAttendance;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field attendance: who checked in, when, from where, and with which selfie.
 * Shift starts 09:30, so anything later is flagged as late.
 */
class MrAttendanceResource extends Resource
{
    protected static ?string $model = MrAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Attendance';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MR Attendance';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('mrUser');
    }

    private static function duration(MrAttendance $record): string
    {
        if (! $record->check_in_at || ! $record->check_out_at) {
            return '—';
        }

        $minutes = $record->check_in_at->diffInMinutes($record->check_out_at);

        return intdiv((int) $minutes, 60).'h '.((int) $minutes % 60).'m';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Attendance')->schema([
                TextEntry::make('date')->label('Date')->date('d M Y'),
                TextEntry::make('mrUser.name')->label('MR'),
                TextEntry::make('status')->badge(),
                TextEntry::make('check_in_at')->label('Check in')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('check_out_at')->label('Check out')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('duration')
                    ->label('Duration')
                    ->state(fn (MrAttendance $record): string => static::duration($record)),
            ])->columns(3),

            Section::make('Proof')->schema([
                ImageEntry::make('selfie_path')->label('Check-in selfie')->disk('public')->height(220),
                TextEntry::make('map')
                    ->label('Check-in location')
                    ->state(fn (MrAttendance $record): string => $record->check_in_lat
                        ? $record->check_in_lat.', '.$record->check_in_lng
                        : 'No GPS captured')
                    ->url(fn (MrAttendance $record): ?string => $record->check_in_lat
                        ? 'https://www.google.com/maps?q='.$record->check_in_lat.','.$record->check_in_lng
                        : null)
                    ->openUrlInNewTab(),
            ])->columns(2),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('mr_user_id')
                ->label('MR')
                ->options(fn () => \App\Models\User::query()->where('role', 'mr')
                    ->orderBy('name')->limit(300)->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),
            \Filament\Forms\Components\DatePicker::make('date')
                ->default(fn () => now())
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'present' => 'Present',
                    'half_day' => 'Half day',
                    'absent' => 'Absent',
                ])
                ->required(),
            DateTimePicker::make('check_in_at')->label('Check in'),
            DateTimePicker::make('check_out_at')->label('Check out'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('mrUser.name')
                    ->label('MR')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('check_in_at')
                    ->label('Check in')
                    ->dateTime('h:i A')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('late')
                    ->label('Shift')
                    ->badge()
                    ->state(fn (MrAttendance $record): string => match (true) {
                        ! $record->check_in_at => 'No check-in',
                        $record->check_in_at->gt(Carbon::parse($record->check_in_at->toDateString().' 09:30')) => 'Late',
                        default => 'On time',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Late' => 'danger',
                        'On time' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('check_out_at')
                    ->label('Check out')
                    ->dateTime('h:i A')
                    ->placeholder('—'),
                TextColumn::make('duration')
                    ->label('Hours')
                    ->state(fn (MrAttendance $record): string => static::duration($record)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'half_day' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                ImageColumn::make('selfie_path')
                    ->label('Selfie')
                    ->disk('public')
                    ->height(36)
                    ->width(36)
                    ->square(),
            ])
            ->filters([
                SelectFilter::make('mr_user_id')
                    ->label('MR')
                    ->relationship('mrUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'half_day' => 'Half day',
                        'absent' => 'Absent',
                    ]),
                Filter::make('date')
                    ->label('Date range')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Adjust'),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMrAttendances::route('/'),
        ];
    }
}
