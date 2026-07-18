<?php

namespace App\Filament\Resources\Specialties;

use App\Filament\Resources\Specialties\Pages\ManageSpecialties;
use App\Models\Specialty;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SpecialtyResource extends Resource
{
    protected static ?string $model = Specialty::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('specialists');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Specialty')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true),
                    TextInput::make('code')
                        ->label('Slug')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true),
                    TextInput::make('icon_key')
                        ->label('Icon Key')
                        ->maxLength(80)
                        ->placeholder('e.g. cardiology'),
                    TextInput::make('plain_label')
                        ->label('Client Label')
                        ->maxLength(160),
                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(3)
                        ->maxLength(255),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('icon_key')
                    ->label('Icon')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('plain_label')
                    ->label('Client Label')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('specialists_count')
                    ->label('Specialists')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index' => ManageSpecialties::route('/'),
        ];
    }
}
