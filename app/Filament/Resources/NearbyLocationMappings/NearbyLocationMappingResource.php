<?php

namespace App\Filament\Resources\NearbyLocationMappings;

use App\Filament\Resources\NearbyLocationMappings\Pages\ManageNearbyLocationMappings;
use App\Models\NearbyLocationMapping;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NearbyLocationMappingResource extends Resource
{
    protected static ?string $model = NearbyLocationMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    public static function getPluralLabel(): ?string
    {
        return 'Nearby Areas';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('location_id')
                ->label('Area')
                ->relationship('location', 'name')
                ->searchable()
                ->required()
                ->optionsLimit(50),
            Select::make('nearby_location_id')
                ->label('Nearby Area')
                ->relationship('nearbyLocation', 'name')
                ->searchable()
                ->required()
                ->optionsLimit(50),
            TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0)
                ->helperText('Lower number = nearer'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nearbyLocation.name')
                    ->label('Nearby Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Area')
                    ->relationship('location', 'name')
                    ->searchable(),
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
            'index' => ManageNearbyLocationMappings::route('/'),
        ];
    }
}
