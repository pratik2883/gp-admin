<?php

namespace App\Filament\Resources\UserSubscriptions;

use App\Filament\Resources\UserSubscriptions\Pages\ManageUserSubscriptions;
use App\Models\UserSubscription;
use App\Services\SubscriptionNotificationService;
use App\Services\SubscriptionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserSubscriptionResource extends Resource
{
    protected static ?string $model = UserSubscription::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Subscriptions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'User Subscriptions';
    }

    public static function getPluralLabel(): ?string
    {
        return 'User Subscriptions';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['user', 'subscriptionPlan']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('plan_name_snapshot')
                    ->label('Plan')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending_activation' => 'Pending Activation',
                        'pending_payment' => 'Pending Payment',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                TextInput::make('payment_reference'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Textarea::make('notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('User'),
                TextEntry::make('user.mobile')->label('Mobile'),
                TextEntry::make('plan_name_snapshot')->label('Plan'),
                TextEntry::make('category_snapshot')->badge(),
                TextEntry::make('plan_family_snapshot')->badge()->placeholder('-'),
                TextEntry::make('bed_slab_snapshot')->badge()->placeholder('-'),
                TextEntry::make('duration_months_snapshot')->label('Duration (Months)'),
                TextEntry::make('price_snapshot')->label('Price'),
                TextEntry::make('currency_snapshot')->label('Currency'),
                TextEntry::make('status')->badge(),
                TextEntry::make('payment_status')->badge(),
                TextEntry::make('starts_at')->dateTime()->placeholder('-'),
                TextEntry::make('ends_at')->dateTime()->placeholder('-'),
                TextEntry::make('payment_reference')->placeholder('-'),
                TextEntry::make('notes')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('user.mobile')
                    ->label('Mobile')
                    ->searchable(),
                TextColumn::make('plan_name_snapshot')
                    ->label('Plan')
                    ->searchable(),
                TextColumn::make('category_snapshot')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_snapshot')
                    ->label('Category')
                    ->options([
                        'specialist' => 'Specialist',
                        'hospital' => 'Hospital',
                        'diagnostic_center' => 'Diagnostic Center',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending_activation' => 'Pending Activation',
                        'pending_payment' => 'Pending Payment',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UserSubscription $record): bool => $record->status !== 'active')
                    ->requiresConfirmation()
                    ->action(function (UserSubscription $record): void {
                        $record->markActive();
                        app(SubscriptionService::class)->syncPremiumFlag($record->user);
                        $record->loadMissing('user');
                        app(SubscriptionNotificationService::class)->notifyActivated($record);
                        Notification::make()->title('Subscription activated')->success()->send();
                    }),
                Action::make('expire')
                    ->label('Expire')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (UserSubscription $record): bool => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (UserSubscription $record): void {
                        $record->forceFill([
                            'status' => 'expired',
                            'expired_at' => now(),
                            'ends_at' => now(),
                        ])->save();
                        app(SubscriptionService::class)->syncPremiumFlag($record->user);
                        $record->loadMissing('user');
                        app(SubscriptionNotificationService::class)->notifyExpired($record);
                        Notification::make()->title('Subscription expired')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UserSubscription $record): bool => $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->action(function (UserSubscription $record): void {
                        $record->forceFill([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ])->save();
                        app(SubscriptionService::class)->syncPremiumFlag($record->user);
                        $record->loadMissing('user');
                        app(SubscriptionNotificationService::class)->notifyCancelled($record);
                        Notification::make()->title('Subscription cancelled')->success()->send();
                    }),
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
            'index' => ManageUserSubscriptions::route('/'),
        ];
    }
}
