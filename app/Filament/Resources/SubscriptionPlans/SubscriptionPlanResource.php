<?php

namespace App\Filament\Resources\SubscriptionPlans;

use App\Filament\Resources\SubscriptionPlans\Pages\ManageSubscriptionPlans;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Subscriptions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Plans';
    }

    public static function getModelLabel(): string
    {
        return 'Plan';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Plans';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        Select::make('category')
                            ->options([
                                'specialist' => 'Specialist',
                                'hospital' => 'Hospital',
                                'diagnostic_center' => 'Diagnostic Center',
                            ])
                            ->required()
                            ->live(),
                        Select::make('plan_family')
                            ->label('Plan Family')
                            ->options([
                                'normal' => 'Normal',
                                'premium' => 'Premium',
                            ])
                            ->required(fn (Get $get): bool => $get('category') === 'specialist')
                            ->visible(fn (Get $get): bool => $get('category') === 'specialist'),
                        Select::make('bed_slab')
                            ->label('Bed Slab')
                            ->options([
                                'upto_25' => '25 Beds',
                                'upto_50' => '50 Beds',
                                'above_100' => '100+ Beds',
                            ])
                            ->required(fn (Get $get): bool => $get('category') === 'hospital')
                            ->visible(fn (Get $get): bool => $get('category') === 'hospital'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->helperText('Optional. Leave empty to auto-generate.')
                            ->maxLength(255),
                        TextInput::make('duration_months')
                            ->label('Duration (Months)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('trial_days')
                            ->label('Free Trial Days')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Number of free trial days (0 = no trial)'),
                        TextInput::make('bonus_months')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Additional free months after paid duration'),
                        TextInput::make('offer_label')
                            ->label('Offer Badge Label')
                            ->maxLength(100)
                            ->placeholder('e.g. Save 20%, Limited Time')
                            ->helperText('Shown as a badge on the plan card'),
                        ColorPicker::make('offer_badge_color')
                            ->label('Offer Badge Color')
                            ->placeholder('#10B981'),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->prefix('Rs'),
                        TextInput::make('currency')
                            ->default('INR')
                            ->required()
                            ->maxLength(10),
                        Toggle::make('is_trialable')
                            ->label('Trial Available')
                            ->default(false)
                            ->helperText('Allow new users to start a free trial'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TagsInput::make('feature_points')
                            ->label('Feature Points')
                            ->placeholder('Type feature and press Enter')
                            ->columnSpanFull(),
                        KeyValue::make('metadata')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('category')->badge(),
                TextEntry::make('plan_family')
                    ->placeholder('-')
                    ->badge(),
                TextEntry::make('bed_slab')
                    ->placeholder('-')
                    ->badge(),
                TextEntry::make('duration_months')
                    ->label('Duration (Months)'),
                TextEntry::make('trial_days')
                    ->label('Trial Days')
                    ->placeholder('0'),
                TextEntry::make('bonus_months')
                    ->label('Bonus Months')
                    ->placeholder('0'),
                IconEntry::make('is_trialable')
                    ->label('Trial Available')
                    ->boolean(),
                TextEntry::make('offer_label')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn (?string $state, SubscriptionPlan $record) => $record->offer_badge_color ?? 'gray'),
                TextEntry::make('price')
                    ->money(fn (SubscriptionPlan $record) => $record->currency ?: 'INR'),
                TextEntry::make('currency'),
                TextEntry::make('sort_order')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('feature_points')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('plan_family')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('bed_slab')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('duration_months')
                    ->label('Duration')
                    ->suffix(' months')
                    ->sortable(),
                TextColumn::make('trial_days')
                    ->label('Trial')
                    ->suffix('d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bonus_months')
                    ->label('Bonus')
                    ->suffix('m')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('offer_label')
                    ->label('Offer')
                    ->badge()
                    ->color(fn (?string $state, SubscriptionPlan $record) => $record->offer_badge_color ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->money(fn (SubscriptionPlan $record) => $record->currency ?: 'INR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'specialist' => 'Specialist',
                        'hospital' => 'Hospital',
                        'diagnostic_center' => 'Diagnostic Center',
                    ]),
                SelectFilter::make('plan_family')
                    ->options([
                        'normal' => 'Normal',
                        'premium' => 'Premium',
                    ]),
                SelectFilter::make('bed_slab')
                    ->options([
                        'upto_25' => '25 Beds',
                        'upto_50' => '50 Beds',
                        'above_100' => '100+ Beds',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active?'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ManageSubscriptionPlans::route('/'),
        ];
    }
}
