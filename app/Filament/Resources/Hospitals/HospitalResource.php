<?php

namespace App\Filament\Resources\Hospitals;

use App\Filament\Resources\Hospitals\Pages\ManageHospitals;
use App\Filament\Resources\Hospitals\Pages\ManageHospitalSpecialists;
use App\Models\Hospital;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HospitalResource extends Resource
{
    protected static ?string $model = Hospital::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Directory';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 2;

    public static function getPluralLabel(): ?string
    {
        return 'Locations & Hospitals';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('location');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location_id')
                    ->label('City')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('hospital_type'),
                TextInput::make('address'),
                TextInput::make('micro_area'),
                TextInput::make('pincode'),
                TextInput::make('state'),
                TextInput::make('country')
                    ->required()
                    ->default('India'),
                TextInput::make('contact_number'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('admin_name'),
                TextInput::make('admin_designation'),
                TextInput::make('admin_mobile'),
                TextInput::make('admin_email')
                    ->email(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending'])
                    ->default('active')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('hospital_type')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('micro_area')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('pincode')
                    ->placeholder('-'),
                TextEntry::make('state')
                    ->placeholder('-'),
                TextEntry::make('country'),
                TextEntry::make('contact_number')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('admin_name')
                    ->placeholder('-'),
                TextEntry::make('admin_designation')
                    ->placeholder('-'),
                TextEntry::make('admin_mobile')
                    ->placeholder('-'),
                TextEntry::make('admin_email')
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
                    ->label('Hospital')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
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
                        'pending' => 'Pending',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('manageSpecialists')
                    ->label('Specialists')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Hospital $record): string => static::getUrl('specialists', ['record' => $record])),
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
            'index' => ManageHospitals::route('/'),
            'specialists' => ManageHospitalSpecialists::route('/{record}/specialists'),
        ];
    }
}
