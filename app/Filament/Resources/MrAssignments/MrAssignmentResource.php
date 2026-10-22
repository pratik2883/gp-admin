<?php

namespace App\Filament\Resources\MrAssignments;

use App\Filament\Resources\MrAssignments\Pages\ManageMrAssignments;
use App\Models\Hospital;
use App\Models\MrAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Assigning work to a field officer. A target is either an existing doctor,
 * an existing hospital, or a walk-in lead that is not registered yet.
 *
 * The MR app reads these through GET /api/mr/assignments.
 */
class MrAssignmentResource extends Resource
{
    protected static ?string $model = MrAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return 'Assignments';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MR Assignments';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['mrUser', 'assignable', 'assigner']);
    }

    public static function getNavigationBadge(): ?string
    {
        $open = MrAssignment::whereIn('status', ['pending', 'in_progress'])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    /** Fields the form shows but the model does not store. */
    private const FORM_ONLY = ['mr_id', 'target_mode', 'doctor_user_id', 'hospital_id'];

    public static function payload(array $data, ?int $assignedBy = null): array
    {
        $mode = $data['target_mode'] ?? 'lead';

        $payload = Arr::only($data, [
            'mr_user_id',
            'title',
            'purpose',
            'priority',
            'due_date',
            'status',
            'notes',
            'lead_name',
            'lead_mobile',
            'lead_city',
        ]);

        // The form hides fields per target mode, so normalise every optional
        // key rather than relying on what Livewire happened to send.
        foreach (['lead_name', 'lead_mobile', 'lead_city', 'notes', 'due_date'] as $key) {
            $payload[$key] = $payload[$key] ?? null;
        }

        $payload['assignable_type'] = null;
        $payload['assignable_id'] = null;

        if ($mode === 'doctor' && filled($data['doctor_user_id'] ?? null)) {
            $payload['assignable_type'] = User::class;
            $payload['assignable_id'] = (int) $data['doctor_user_id'];
        } elseif ($mode === 'hospital' && filled($data['hospital_id'] ?? null)) {
            $payload['assignable_type'] = Hospital::class;
            $payload['assignable_id'] = (int) $data['hospital_id'];
        }

        $payload['status'] = $payload['status'] ?? 'pending';
        $payload['completed_at'] = $payload['status'] === 'done' ? now() : null;

        if ($assignedBy) {
            $payload['assigned_by'] = $assignedBy;
        }

        return $payload;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assignment')->schema([
                Select::make('mr_user_id')
                    ->label('Assign to MR')
                    ->options(fn () => User::query()
                        ->where('role', 'mr')
                        ->orderBy('name')
                        ->limit(300)
                        ->get()
                        ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name}".($u->mobile ? " ({$u->mobile})" : '')])
                        ->toArray())
                    ->searchable()
                    ->required(),
                TextInput::make('title')
                    ->label('What should the MR do?')
                    ->placeholder('e.g. Visit and onboard Dr. Patil at Andheri')
                    ->required()
                    ->maxLength(190),
                Select::make('purpose')
                    ->options([
                        'follow_up' => 'Follow-up',
                        'onboard' => 'Onboard new doctor',
                        'renewal' => 'Subscription renewal',
                        'feedback' => 'Collect feedback',
                        'complaint' => 'Resolve complaint',
                        'other' => 'Other',
                    ])
                    ->default('follow_up')
                    ->required(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ])
                    ->default('normal')
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Due date'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In progress',
                        'done' => 'Done',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('notes')
                    ->label('Instructions for the MR')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Target')->schema([
                Select::make('target_mode')
                    ->label('Assign against')
                    ->options([
                        'doctor' => 'Existing doctor',
                        'hospital' => 'Existing hospital',
                        'lead' => 'New lead (not registered)',
                    ])
                    ->default('lead')
                    ->required()
                    ->live(),

                Select::make('doctor_user_id')
                    ->label('Doctor')
                    ->options(fn () => User::query()
                        ->whereIn('role', ['gp', 'specialist'])
                        ->orderBy('name')
                        ->limit(300)
                        ->get()
                        ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} — {$u->mobile}"])
                        ->toArray())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('target_mode') === 'doctor')
                    ->required(fn (Get $get): bool => $get('target_mode') === 'doctor'),

                Select::make('hospital_id')
                    ->label('Hospital')
                    ->options(fn () => Hospital::query()
                        ->orderBy('name')
                        ->limit(300)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('target_mode') === 'hospital')
                    ->required(fn (Get $get): bool => $get('target_mode') === 'hospital'),

                TextInput::make('lead_name')
                    ->label('Lead name')
                    ->visible(fn (Get $get): bool => $get('target_mode') === 'lead')
                    ->required(fn (Get $get): bool => $get('target_mode') === 'lead')
                    ->maxLength(190),
                TextInput::make('lead_mobile')
                    ->label('Lead mobile')
                    ->tel()
                    ->maxLength(20)
                    ->visible(fn (Get $get): bool => $get('target_mode') === 'lead'),
                TextInput::make('lead_city')
                    ->label('Area / city')
                    ->maxLength(120)
                    ->visible(fn (Get $get): bool => $get('target_mode') === 'lead'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mrUser.name')
                    ->label('MR')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Assignment')
                    ->searchable()
                    ->wrap()
                    ->limit(45),
                TextColumn::make('target')
                    ->label('Target')
                    ->state(fn (MrAssignment $record): string => $record->targetName()),
                TextColumn::make('purpose')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'low' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'in_progress' => 'info',
                        'cancelled' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('assigner.name')
                    ->label('Assigned by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('mr_user_id')
                    ->label('MR')
                    ->relationship('mrUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In progress',
                        'done' => 'Done',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'normal' => 'Normal',
                        'low' => 'Low',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->using(function (MrAssignment $record, array $data): MrAssignment {
                        $record->update(static::payload($data));
                        $record->refresh();

                        Notification::make()
                            ->title('Assignment updated')
                            ->success()
                            ->send();

                        return $record;
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMrAssignments::route('/'),
        ];
    }
}
