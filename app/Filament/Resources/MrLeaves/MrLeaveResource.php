<?php

namespace App\Filament\Resources\MrLeaves;

use App\Filament\Resources\MrLeaves\Pages\ManageMrLeaves;
use App\Models\MrLeave;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Leave requests used to disappear into the database: `approved_by` existed but
 * nothing in the panel could approve, reject or even list them.
 */
class MrLeaveResource extends Resource
{
    protected static ?string $model = MrLeave::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Leaves';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MR Leave Requests';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['mrUser', 'approver']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = MrLeave::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('mr_user_id')
                ->label('MR')
                ->options(fn () => User::query()->where('role', 'mr')
                    ->orderBy('name')->limit(300)->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),
            DatePicker::make('start_date')
                ->label('From')
                ->required(),
            DatePicker::make('end_date')
                ->label('To')
                ->required()
                ->afterOrEqual('start_date'),
            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required(),
            Textarea::make('reason')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Leave request')->schema([
                TextEntry::make('mrUser.name')->label('MR'),
                TextEntry::make('start_date')->label('From')->date('d M Y'),
                TextEntry::make('end_date')->label('To')->date('d M Y'),
                TextEntry::make('days')
                    ->label('Days')
                    ->state(fn (MrLeave $record): string => (string) ($record->start_date->diffInDays($record->end_date) + 1)),
                TextEntry::make('status')->badge(),
                TextEntry::make('reason')->label('Reason')->columnSpanFull(),
                TextEntry::make('approver.name')->label('Actioned by')->placeholder('-'),
                TextEntry::make('updated_at')->label('Last update')->dateTime('d M Y, h:i A'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mrUser.name')
                    ->label('MR')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('From')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('To')
                    ->date('d M Y'),
                TextColumn::make('days')
                    ->label('Days')
                    ->state(fn (MrLeave $record): string => (string) ($record->start_date->diffInDays($record->end_date) + 1)),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('approver.name')
                    ->label('Actioned by')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('mr_user_id')
                    ->label('MR')
                    ->relationship('mrUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MrLeave $record): bool => $record->status !== 'approved')
                    ->action(function (MrLeave $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title("Leave approved for {$record->mrUser?->name}")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MrLeave $record): bool => $record->status !== 'rejected')
                    ->action(function (MrLeave $record): void {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title("Leave rejected for {$record->mrUser?->name}")
                            ->warning()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMrLeaves::route('/'),
        ];
    }
}
