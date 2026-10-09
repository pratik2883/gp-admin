<?php

namespace App\Filament\Resources\Referrals;

use App\Filament\Resources\Referrals\Pages\ManageReferrals;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Referral;
use App\Settings\WorkflowSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Referrals';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    public static function getPluralLabel(): ?string
    {
        return 'Referrals';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['gp.user', 'specialist.user', 'hospital']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Referral Details')
                ->schema([
                    TextInput::make('lead_code')
                        ->label('Lead ID')
                        ->placeholder('Auto-generated')
                        ->disabled(),
                    Select::make('referral_type')
                        ->label('Referral Type')
                        ->options([
                            'specialist' => 'Specialist',
                            'hospital' => 'Hospital',
                        ])
                        ->default(fn (?Referral $record) => $record?->referral_type ?? 'specialist')
                        ->required()
                        ->reactive()
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('gp_id')
                        ->relationship('gp', 'id', fn ($query) => $query->with('user'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "GP #{$record->id}")
                        ->searchable()
                        ->optionsLimit(25)
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('specialist_id')
                        ->relationship('specialist', 'id', fn ($query) => $query->with('user'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Specialist #{$record->id}")
                        ->searchable()
                        ->optionsLimit(25)
                        ->required(fn (callable $get) => ($get('referral_type') ?? 'specialist') !== 'hospital')
                        ->visible(fn (callable $get) => ($get('referral_type') ?? 'specialist') !== 'hospital')
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('hospital_id')
                        ->relationship('hospital', 'name')
                        ->searchable()
                        ->visible(fn (callable $get) => ($get('referral_type') ?? 'specialist') === 'hospital' || $get('appointment_type') === 'ipd')
                        ->required(fn (callable $get) => ($get('referral_type') ?? 'specialist') === 'hospital' || $get('appointment_type') === 'ipd')
                        ->disabled(fn (?Referral $record) => filled($record)),
                    TextInput::make('department')
                        ->maxLength(150)
                        ->visible(fn (callable $get) => ($get('referral_type') ?? 'specialist') === 'hospital')
                        ->required(fn (callable $get) => ($get('referral_type') ?? 'specialist') === 'hospital')
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('appointment_type')
                        ->options([
                            'opd' => 'OPD',
                            'ipd' => 'IPD',
                        ])
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('priority')
                        ->options([
                            'routine' => 'Routine',
                            'urgent' => 'Urgent',
                        ])
                        ->default(fn (?Referral $record) => $record?->priority ?? 'routine')
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Textarea::make('case_summary')
                        ->rows(4)
                        ->columnSpanFull()
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                ])
                ->columns(2),
            Section::make('Patient')
                ->schema([
                    TextInput::make('patient_name')
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                    TextInput::make('patient_mobile')
                        ->tel()
                        ->maxLength(20)
                        ->disabled(fn (?Referral $record) => filled($record)),
                    TextInput::make('patient_age')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(120)
                        ->disabled(fn (?Referral $record) => filled($record)),
                    Select::make('patient_gender')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->disabled(fn (?Referral $record) => filled($record)),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\Section::make('Referral')
                    ->schema([
                        TextEntry::make('lead_code')->label('Lead ID'),
                        TextEntry::make('referral_type')
                            ->label('Referral Type')
                            ->formatStateUsing(fn (?string $s) => $s ? ucfirst($s) : 'Specialist'),
                        TextEntry::make('gp.user.name')->label('GP'),
                        TextEntry::make('specialist.user.name')
                            ->label('Specialist')
                            ->placeholder('-'),
                        TextEntry::make('hospital.name')->label('Hospital')->placeholder('-'),
                        TextEntry::make('department')->label('Department')->placeholder('-'),
                        TextEntry::make('priority')
                            ->label('Priority')
                            ->formatStateUsing(fn (?string $s) => $s ? ucfirst($s) : 'Routine'),
                        TextEntry::make('appointment_type')
                            ->label('Type')
                            ->formatStateUsing(fn ($s) => $s ? strtoupper($s) : null),
                        TextEntry::make('status')->label('Current Status')->badge(),
                    ])
                    ->columns(2),
                \Filament\Infolists\Components\Section::make('Patient')
                    ->schema([
                        TextEntry::make('patient_name'),
                        TextEntry::make('patient_mobile')->placeholder('-'),
                        TextEntry::make('patient_age')->placeholder('-'),
                        TextEntry::make('patient_gender')->badge()->placeholder('-'),
                        TextEntry::make('case_summary')->columnSpanFull(),
                    ])
                    ->columns(4),
                \Filament\Infolists\Components\Section::make('Status History')
                    ->schema([
                        TextEntry::make('created_at')->label('Sent')->dateTime('d M Y, h:i A')->placeholder('-'),
                        TextEntry::make('accepted_at')->label('Accepted')->dateTime('d M Y, h:i A')->placeholder('-'),
                        TextEntry::make('consulted_at')->label('Consulted')->dateTime('d M Y, h:i A')->placeholder('-'),
                        TextEntry::make('closed_at')->label('Closed')->dateTime('d M Y, h:i A')->placeholder('-'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_code')
                    ->label('Lead ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('referral_type')
                    ->label('Referral Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $s) => $s ? ucfirst($s) : 'Specialist')
                    ->color(fn (?string $state) => ($state ?? 'specialist') === 'hospital' ? 'info' : 'primary')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->searchable(),
                TextColumn::make('gp.user.name')
                    ->label('GP')
                    ->searchable(),
                TextColumn::make('specialist.user.name')
                    ->label('Specialist')
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('hospital.name')
                    ->label('Hospital')
                    ->formatStateUsing(fn ($state) => $state ?? '-')
                    ->toggleable(),
                TextColumn::make('department')
                    ->label('Department')
                    ->formatStateUsing(fn ($state) => $state ?? '-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'warning',
                        'accepted' => 'info',
                        'consulted' => 'success',
                        'ipd' => 'purple',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (?string $state) {
                        return match ($state) {
                            'sent' => 'Sent',
                            'accepted' => 'Accepted',
                            'consulted' => 'Consulted',
                            'ipd' => 'IPD (Admitted)',
                            'closed' => 'Closed',
                            default => $state,
                        };
                    })
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('referral_type')
                    ->label('Referral Type')
                    ->options([
                        'specialist' => 'Specialist',
                        'hospital' => 'Hospital',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'hospital') {
                            return $query->where('referral_type', 'hospital');
                        }
                        if ($value === 'specialist') {
                            return $query->where(function ($q) {
                                $q->whereNull('referral_type')->orWhere('referral_type', 'specialist');
                            });
                        }
                        return $query;
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'consulted' => 'Consulted',
                        'closed' => 'Closed',
                    ])
                    ->multiple()
                    ->query(function ($query, array $data) {
                        $values = array_keys(array_filter($data));

                        return $query->when($values, fn ($q) => $q->whereIn('status', $values));
                    }),
                SelectFilter::make('appointment_type')
                    ->options([
                        'opd' => 'OPD',
                        'ipd' => 'IPD',
                    ]),
                SelectFilter::make('gp_user_id')
                    ->label('GP')
                    ->searchable()
                    ->relationship('gp.user', 'name')
                    ->optionsLimit(25),
                SelectFilter::make('specialist_user_id')
                    ->label('Specialist')
                    ->searchable()
                    ->relationship('specialist.user', 'name')
                    ->optionsLimit(25),
                SelectFilter::make('city')
                    ->label('Location')
                    ->options(fn () => Cache::remember('referrals.filters.cities', 600, function (): array {
                        $cities = collect()
                            ->merge(Hospital::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->toArray())
                            ->merge(Gp::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->toArray())
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values();

                        return $cities->combine($cities)->toArray();
                    }))
                    ->searchable()
                    ->query(function ($query, $value) {
                        return $query->when($value, function ($q) use ($value) {
                            $q->where(function ($inner) use ($value) {
                                $inner->whereHas('hospital', fn ($h) => $h->where('city', $value))
                                    ->orWhereHas('gp', fn ($g) => $g->where('city', $value));
                            });
                        });
                    }),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('markAccepted')
                    ->label('Mark as Accepted')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Referral $record) => $record->status === 'sent')
                    ->action(function (Referral $record) {
                        if ($record->status !== 'sent') {
                            return;
                        }
                        $record->status = 'accepted';
                        $record->accepted_at = now();
                        $record->save();

                        $gpUser = $record->gp?->user;
                        if ($gpUser) {
                            $gpUser->notify(new \App\Notifications\ReferralAcceptedNotification($record));
                        }
                    }),
                Action::make('markConsulted')
                    ->label('Mark as Consulted')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn (Referral $record) => in_array($record->status, ['accepted']))
                    ->action(function (Referral $record) {
                        if (! in_array($record->status, ['accepted'], true)) {
                            return;
                        }
                        $record->status = 'consulted';
                        $record->consulted_at = now();
                        $record->save();

                        $gpUser = $record->gp?->user;
                        if ($gpUser) {
                            $gpUser->notify(new \App\Notifications\ReferralConsultedNotification($record));
                        }
                    }),
                Action::make('markIpd')
                    ->label('Mark as IPD')
                    ->icon('heroicon-o-building-office')
                    ->color('warning')
                    ->visible(fn (Referral $record) => in_array($record->status, ['accepted', 'consulted']))
                    ->action(function (Referral $record) {
                        if (! in_array($record->status, ['accepted', 'consulted'], true)) {
                            return;
                        }
                        $record->status = 'ipd';
                        $record->ipd_at = now();
                        $record->save();

                        $gpUser = $record->gp?->user;
                        if ($gpUser) {
                            $gpUser->notify(new \App\Notifications\ReferralIpdNotification($record));
                        }
                    }),
                Action::make('markClosed')
                    ->label('Mark as Closed')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(fn (Referral $record) => $record->status !== 'closed')
                    ->action(function (Referral $record) {
                        if ($record->status === 'closed') {
                            return;
                        }
                        $workflow = app(WorkflowSettings::class);
                        if ($workflow->force_strict_status_flow) {
                            $allowedFrom = ['consulted', 'ipd'];
                            if ($workflow->allow_direct_sent_to_closed) {
                                $allowedFrom[] = 'sent';
                            }
                            if (! in_array($record->status, $allowedFrom, true)) {
                                return;
                            }
                        }
                        $record->status = 'closed';
                        $record->closed_at = now();
                        $record->save();

                        $gpUser = $record->gp?->user;
                        if ($gpUser) {
                            $gpUser->notify(new \App\Notifications\ReferralClosedNotification($record));
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records, BulkAction $action) {
                            if ($records->isEmpty()) {
                                $query = $action->getLivewire()->getFilteredTableQuery();
                                $records = $query->with(['gp.user', 'specialist.user', 'hospital'])->orderBy('id')->get();
                            } else {
                                $records->loadMissing(['gp.user', 'specialist.user', 'hospital']);
                            }

                            $callback = function () use ($records) {
                                $handle = fopen('php://output', 'w');

                                fputcsv($handle, [
                                    'Lead ID',
                                    'Referral Type',
                                    'GP Name',
                                    'Specialist Name',
                                    'Hospital',
                                    'Department',
                                    'Priority',
                                    'Status',
                                    'Appointment Type',
                                    'Patient Name',
                                    'City',
                                    'Created At',
                                ]);

                                foreach ($records as $ref) {
                                    fputcsv($handle, [
                                        $ref->lead_code,
                                        $ref->referral_type ?? 'specialist',
                                        optional($ref->gp?->user)->name,
                                        optional($ref->specialist?->user)->name,
                                        optional($ref->hospital)->name,
                                        $ref->department ?? '',
                                        $ref->priority ?? '',
                                        $ref->status,
                                        strtoupper($ref->appointment_type ?? ''),
                                        $ref->patient_name,
                                        optional($ref->hospital)->city ?? optional($ref->gp)->city ?? '',
                                        optional($ref->created_at)?->format('Y-m-d H:i'),
                                    ]);
                                }

                                fclose($handle);
                            };

                            return response()->streamDownload(
                                $callback,
                                'referrals-export-'.now()->format('Ymd-His').'.csv',
                                ['Content-Type' => 'text/csv']
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReferrals::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
