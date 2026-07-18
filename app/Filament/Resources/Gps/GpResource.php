<?php

namespace App\Filament\Resources\Gps;

use App\Filament\Resources\Gps\Pages\ManageGps;
use App\Models\Gp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GpResource extends Resource
{
    protected static ?string $model = Gp::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static \UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function getLabel(): ?string
    {
        return 'GP Signup';
    }

    public static function getPluralLabel(): ?string
    {
        return 'GP Signups';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'defaultLocation']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('registration_number')
                    ->label('Registration Number')
                    ->maxLength(100),
                TextInput::make('designation')
                    ->label('Designation / Specialty')
                    ->maxLength(100),
                TextInput::make('registration_type')
                    ->label('Registration Type')
                    ->maxLength(50),
                TextInput::make('registration_council')
                    ->label('Registration Council / State')
                    ->maxLength(150),
                DatePicker::make('registration_valid_until')
                    ->label('Registration Valid Until'),
                TextInput::make('clinic_name'),
                TextInput::make('address_line'),
                TextInput::make('city'),
                Select::make('default_location_id')
                    ->label('Default Area')
                    ->relationship('defaultLocation', 'name')
                    ->searchable()
                    ->optionsLimit(50),
                TextInput::make('pincode'),
                TextInput::make('state'),
                TextInput::make('country')
                    ->required()
                    ->default('India'),
                TextInput::make('preferred_specialist_id')
                    ->numeric(),
                TextInput::make('preferred_location'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('registration_number')
                    ->label('Registration Number')
                    ->placeholder('-'),
                TextEntry::make('designation')
                    ->label('Designation / Specialty')
                    ->placeholder('-'),
                TextEntry::make('registration_type')
                    ->label('Registration Type')
                    ->placeholder('-'),
                TextEntry::make('registration_council')
                    ->label('Registration Council / State')
                    ->placeholder('-'),
                TextEntry::make('registration_valid_until')
                    ->label('Registration Valid Until')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('clinic_name')
                    ->placeholder('-'),
                TextEntry::make('address_line')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('defaultLocation.name')
                    ->label('Default Area')
                    ->placeholder('-'),
                TextEntry::make('pincode')
                    ->placeholder('-'),
                TextEntry::make('state')
                    ->placeholder('-'),
                TextEntry::make('country'),
                TextEntry::make('preferred_specialist_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('preferred_location')
                    ->placeholder('-'),
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('GP Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('user.mobile')
                    ->label('Mobile')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('registration_number')
                    ->label('Reg No.')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('designation')
                    ->label('Designation')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('defaultLocation.name')
                    ->label('Default Area')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status'),
                TextColumn::make('created_at')
                    ->label('Signed Up')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Gp $gp) => $gp->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(fn (Gp $gp) => $gp->update(['status' => 'approved'])),
                Action::make('block')
                    ->label('Block')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Gp $gp) => $gp->status !== 'blocked')
                    ->requiresConfirmation()
                    ->action(fn (Gp $gp) => $gp->update(['status' => 'blocked'])),
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
            'index' => ManageGps::route('/'),
        ];
    }
}
