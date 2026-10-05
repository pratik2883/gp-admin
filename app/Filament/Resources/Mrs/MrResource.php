<?php

namespace App\Filament\Resources\Mrs;

use App\Filament\Resources\Mrs\Pages\ManageMrs;
use App\Models\Mr;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class MrResource extends Resource
{
    protected static ?string $model = Mr::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static \UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 4;

    public static function getLabel(): ?string
    {
        return 'MR Representative';
    }

    public static function getPluralLabel(): ?string
    {
        return 'MR Representatives';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('MR User Account')
                ->schema([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(190)
                        ->afterStateHydrated(fn ($component, $state, ?Mr $record) => $component->state($record?->user?->name)),
                    TextInput::make('mobile')
                        ->label('Mobile Number')
                        ->required()
                        ->maxLength(20)
                        ->afterStateHydrated(fn ($component, $state, ?Mr $record) => $component->state($record?->user?->mobile)),
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->maxLength(190)
                        ->afterStateHydrated(fn ($component, $state, ?Mr $record) => $component->state($record?->user?->email)),
                    TextInput::make('password')
                        ->label('Login Password')
                        ->password()
                        ->required(fn (?Mr $record) => $record === null)
                        ->dehydrated(fn ($state) => filled($state)),
                ])->columns(2),

            Section::make('Territory & Field Targets')
                ->schema([
                    TextInput::make('employee_code')
                        ->label('Employee Code')
                        ->default(fn () => 'MR-'.rand(1000, 9999)),
                    TextInput::make('territory_zone')
                        ->label('Territory / Zone')
                        ->default('Mumbai & Thane'),
                    TextInput::make('headquarters_city')
                        ->label('Headquarters City')
                        ->default('Mumbai'),
                    TextInput::make('daily_visit_target')
                        ->label('Daily Visit Target')
                        ->numeric()
                        ->default(10),
                    TextInput::make('monthly_subscription_target')
                        ->label('Monthly Subscription Target')
                        ->numeric()
                        ->default(5),
                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('active')
                        ->required(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')
                    ->label('Emp Code')
                    ->bold()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('MR Name')
                    ->searchable(),
                TextColumn::make('user.mobile')
                    ->label('Mobile Number')
                    ->searchable(),
                TextColumn::make('territory_zone')
                    ->label('Territory')
                    ->searchable(),
                TextColumn::make('daily_visit_target')
                    ->label('Daily Target'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (Mr $record, array $data): Mr {
                        $user = $record->user;
                        if ($user) {
                            $userData = array_filter([
                                'name' => $data['name'] ?? null,
                                'mobile' => $data['mobile'] ?? null,
                                'email' => $data['email'] ?? null,
                            ]);
                            if (filled($data['password'] ?? null)) {
                                $userData['password'] = Hash::make($data['password']);
                            }
                            $user->update($userData);
                        }

                        $record->update([
                            'employee_code' => $data['employee_code'] ?? $record->employee_code,
                            'territory_zone' => $data['territory_zone'] ?? $record->territory_zone,
                            'headquarters_city' => $data['headquarters_city'] ?? $record->headquarters_city,
                            'daily_visit_target' => $data['daily_visit_target'] ?? $record->daily_visit_target,
                            'monthly_subscription_target' => $data['monthly_subscription_target'] ?? $record->monthly_subscription_target,
                            'status' => $data['status'] ?? $record->status,
                        ]);

                        return $record;
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMrs::route('/'),
        ];
    }
}
