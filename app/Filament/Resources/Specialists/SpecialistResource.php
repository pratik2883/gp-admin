<?php

namespace App\Filament\Resources\Specialists;

use App\Filament\Resources\Specialists\Pages\ManageSpecialists;
use App\Models\Specialist;
use App\Models\Specialty;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class SpecialistResource extends Resource
{
    protected static ?string $model = Specialist::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Directory';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'location', 'specialty']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->schema([
                    TextInput::make('user_name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(190)
                        ->afterStateHydrated(function (TextInput $component, $state, ?Specialist $record) {
                            if (! $record) {
                                return;
                            }
                            $component->state($record->user?->name);
                        }),
                    TextInput::make('user_email')
                        ->required()
                        ->email()
                        ->maxLength(190)
                        ->rule(fn (?Specialist $record) => Rule::unique('users', 'email')->ignore($record?->user_id))
                        ->afterStateHydrated(function (TextInput $component, $state, ?Specialist $record) {
                            if (! $record) {
                                return;
                            }
                            $component->state($record->user?->email);
                        }),
                    TextInput::make('user_mobile')
                        ->required()
                        ->maxLength(20)
                        ->rule(fn (?Specialist $record) => Rule::unique('users', 'mobile')->ignore($record?->user_id))
                        ->afterStateHydrated(function (TextInput $component, $state, ?Specialist $record) {
                            if (! $record) {
                                return;
                            }
                            $component->state($record->user?->mobile);
                        }),
                ])
                ->columns(3),
            Section::make('Core Profile')
                ->schema([
                    Select::make('specialty_id')
                        ->label('Specialty')
                        ->required()
                        ->options(function (?Specialist $record) {
                            return Specialty::query()
                                ->when(
                                    filled($record?->specialty_id),
                                    fn ($q) => $q->where('is_active', true)->orWhere('id', $record->specialty_id),
                                    fn ($q) => $q->where('is_active', true)
                                )
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Specialty $s) => [$s->id => ($s->plain_label ?: $s->name)])
                                ->all();
                        })
                        ->searchable()
                        ->optionsLimit(50),
                    Select::make('additional_specialty_ids')
                        ->label('Additional Specialties')
                        ->multiple()
                        ->searchable()
                        ->optionsLimit(50)
                        ->options(function (?Specialist $record) {
                            return Specialty::query()
                                ->when(
                                    filled($record?->specialty_id),
                                    fn ($q) => $q->where('is_active', true)->orWhere('id', $record->specialty_id),
                                    fn ($q) => $q->where('is_active', true)
                                )
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Specialty $s) => [$s->id => ($s->plain_label ?: $s->name)])
                                ->all();
                        })
                        ->afterStateHydrated(function (Select $component, $state, ?Specialist $record) {
                            if (! $record) {
                                return;
                            }
                            $ids = $record->additionalSpecialties()->pluck('specialties.id')->values()->all();
                            $component->state($ids);
                        })
                        ->dehydrated(false)
                        ->saveRelationshipsUsing(function ($record, $state) {
                            if (! $record instanceof Specialist) {
                                return;
                            }
                            $ids = collect($state ?? [])
                                ->map(fn ($v) => (int) $v)
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();
                            $primaryId = (int) ($record->specialty_id ?? 0);
                            $ids = array_values(array_filter($ids, fn ($id) => $primaryId <= 0 || $id !== $primaryId));
                            $record->additionalSpecialties()->sync($ids);
                        }),
                    TextInput::make('hospital_name')
                        ->label('Hospital Name')
                        ->required()
                        ->maxLength(190),
                    TextInput::make('medical_council_registration_no')
                        ->label('Registration No.')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('medical_council_name')
                        ->label('Council Name')
                        ->required()
                        ->maxLength(150),
                    Toggle::make('is_active')
                        ->label('Active'),
                ])
                ->columns(3),
            Section::make('Clinic Details')
                ->schema([
                    TextInput::make('clinic_street')
                        ->label('Street')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('clinic_area')
                        ->label('Area')
                        ->required()
                        ->maxLength(190),
                    TextInput::make('clinic_city')
                        ->label('City')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('clinic_pincode')
                        ->label('Pincode')
                        ->required()
                        ->maxLength(12),
                    TextInput::make('clinic_address')
                        ->label('Address (Legacy)')
                        ->dehydrated(false)
                        ->default(fn (?Specialist $record) => trim(implode(', ', array_filter([
                            $record?->clinic_street,
                            $record?->clinic_area,
                            $record?->clinic_city,
                            $record?->clinic_pincode,
                        ]))))
                        ->disabled(),
                    Select::make('location_id')
                        ->label('Area')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->optionsLimit(50),
                ])
                ->columns(2),
            Section::make('Professional')
                ->schema([
                    TagsInput::make('additional_qualifications')
                        ->label('Qualifications'),
                    TextInput::make('years_of_experience')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(80),
                    Textarea::make('sub_specialties')
                        ->label('Sub-specialties')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('key_procedures')
                        ->label('Key Procedures')
                        ->rows(3)
                        ->columnSpanFull(),
                    TagsInput::make('languages')
                        ->label('Languages'),
                    Toggle::make('consultation_in_person')
                        ->label('In-person Consultation')
                        ->default(true),
                    Toggle::make('consultation_teleconsult')
                        ->label('Teleconsultation')
                        ->default(false),
                ])
                ->columns(2),
            Section::make('Profile Content')
                ->schema([
                    Textarea::make('bio')
                        ->rows(4)
                        ->columnSpanFull(),
                    TagsInput::make('videos')
                        ->label('Videos (URLs)')
                        ->placeholder('https://...')
                        ->visible(fn () => app(GeneralSettings::class)->allow_profile_videos_for_specialists)
                        ->columnSpanFull(),
                    FileUpload::make('certificates')
                        ->multiple()
                        ->disk('public')
                        ->directory('specialists/certificates')
                        ->visible(fn () => app(GeneralSettings::class)->allow_profile_certificates_for_specialists)
                        ->columnSpanFull(),
                    FileUpload::make('profile_photo_path')
                        ->label('Profile Photo')
                        ->image()
                        ->disk('public')
                        ->directory('specialists/photos'),
                ])
                ->columns(2),
            Section::make('Recommendations')
                ->schema([
                    Toggle::make('is_premium')
                        ->label('Premium (Paid)'),
                    TextInput::make('priority_order')
                        ->label('Priority Order')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Lower number = higher priority'),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('profile_photo_path')
                    ->placeholder('-'),
                TextEntry::make('whatsapp_number')
                    ->placeholder('-'),
                TextEntry::make('primary_specialization')
                    ->placeholder('-'),
                TextEntry::make('medical_council_registration_no')
                    ->placeholder('-'),
                TextEntry::make('medical_council_name')
                    ->placeholder('-'),
                TextEntry::make('hospital_name')
                    ->placeholder('-'),
                TextEntry::make('clinic_name')
                    ->placeholder('-'),
                TextEntry::make('clinic_street')
                    ->placeholder('-'),
                TextEntry::make('clinic_area')
                    ->placeholder('-'),
                TextEntry::make('clinic_address')
                    ->placeholder('-'),
                TextEntry::make('clinic_city')
                    ->placeholder('-'),
                TextEntry::make('clinic_pincode')
                    ->placeholder('-'),
                TextEntry::make('years_of_experience')
                    ->label('Experience (Years)')
                    ->placeholder('-'),
                TextEntry::make('languages')
                    ->badge()
                    ->separator(', ')
                    ->placeholder('-'),
                TextEntry::make('location.name')
                    ->label('Area')
                    ->placeholder('-'),
                IconEntry::make('is_premium')
                    ->label('Premium')
                    ->boolean(),
                TextEntry::make('priority_order')
                    ->label('Priority Order')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
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
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_specialization')
                    ->label('Specialty')
                    ->formatStateUsing(fn (?string $state, Specialist $s) => $s->specialty?->plain_label ?: ($s->specialty?->name ?: $state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('clinic_city')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hospital_name')
                    ->label('Hospital')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Area')
                    ->searchable(),
                IconColumn::make('is_premium')
                    ->label('Premium')
                    ->boolean(),
                TextColumn::make('priority_order')
                    ->label('Priority')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('referrals_count')
                    ->label('Total Referrals')
                    ->counts('referrals')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added On')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('clinic_city')
                    ->label('City')
                    ->options(
                        Specialist::query()
                            ->whereNotNull('clinic_city')
                            ->distinct()
                            ->orderBy('clinic_city')
                            ->pluck('clinic_city', 'clinic_city')
                            ->toArray()
                    ),
                SelectFilter::make('specialty_id')
                    ->label('Specialty')
                    ->relationship('specialty', 'name', fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name'))
                    ->searchable()
                    ->optionsLimit(50),
                SelectFilter::make('location_id')
                    ->label('Area')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->optionsLimit(50),
                TernaryFilter::make('is_active')
                    ->label('Active?')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                TernaryFilter::make('is_premium')
                    ->label('Premium?')
                    ->trueLabel('Premium')
                    ->falseLabel('Non-premium'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->using(function (array $data, $livewire, Specialist $record): void {
                        $userData = [
                            'name' => data_get($data, 'user_name') ?? data_get($data, 'user.name'),
                            'email' => data_get($data, 'user_email') ?? data_get($data, 'user.email'),
                            'mobile' => data_get($data, 'user_mobile') ?? data_get($data, 'user.mobile'),
                        ];

                        unset($data['user_name'], $data['user_email'], $data['user_mobile'], $data['user']);

                        if ($record->user) {
                            $record->user->update($userData);
                        }

                        $record->update($data);
                    }),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Specialist $s) => ! $s->is_active)
                    ->requiresConfirmation()
                    ->action(fn (Specialist $s) => $s->update(['is_active' => true])),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Specialist $s) => $s->is_active)
                    ->requiresConfirmation()
                    ->action(fn (Specialist $s) => $s->update(['is_active' => false])),
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
            'index' => ManageSpecialists::route('/'),
        ];
    }
}
