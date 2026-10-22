<?php

namespace App\Filament\Resources\MrCollaterals;

use App\Filament\Resources\MrCollaterals\Pages\ManageCollaterals;
use App\Models\Collateral;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The sales kit the MR app shows in the field: brochures, videos and links.
 * Admin can now publish and retire them instead of editing the table by hand.
 */
class CollateralResource extends Resource
{
    protected static ?string $model = Collateral::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Collaterals';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MR Collaterals';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Collateral')->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(190),
                Select::make('category')
                    ->options([
                        'brochure' => 'Brochure',
                        'presentation' => 'Presentation',
                        'video' => 'Video',
                        'demo' => 'Demo',
                        'link' => 'Link',
                    ])
                    ->default('brochure')
                    ->required(),
                Select::make('file_type')
                    ->options([
                        'pdf' => 'PDF',
                        'video' => 'Video',
                        'link' => 'Link',
                    ])
                    ->default('pdf')
                    ->required(),
                TextInput::make('file_url')
                    ->label('File / video URL')
                    ->helperText('Public storage URL or an external link.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('thumbnail_url')
                    ->label('Thumbnail URL')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Visible in MR app')
                    ->default(true),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('file_type')
                    ->label('Type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'brochure' => 'Brochure',
                        'presentation' => 'Presentation',
                        'video' => 'Video',
                        'demo' => 'Demo',
                        'link' => 'Link',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Live', 0 => 'Hidden']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCollaterals::route('/'),
        ];
    }
}
