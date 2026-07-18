<?php

namespace App\Filament\Resources\Hospitals\Pages;

use App\Filament\Resources\Hospitals\HospitalResource;
use App\Models\Hospital;
use App\Models\Specialist;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageHospitalSpecialists extends ManageRelatedRecords
{
    protected static string $resource = HospitalResource::class;

    protected static string $relationship = 'specialists';

    public static function getNavigationLabel(): string
    {
        return 'Specialists';
    }

    public function getTitle(): string
    {
        return 'Hospital Specialists';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['user']);
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Specialist')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primary_specialization')
                    ->label('Primary Specialization')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('pivot.is_super_specialist')
                    ->label('Super Specialist')
                    ->boolean(),
                TextColumn::make('pivot.department')
                    ->label('Department')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Specialist')
                    ->preloadRecordSelect()
                    ->recordTitle(function (Specialist $record): string {
                        $name = $record->user?->name ?? 'Specialist #'.$record->id;
                        $specialty = $record->primary_specialization ? " ({$record->primary_specialization})" : '';

                        return $name.$specialty;
                    })
                    ->recordSelectOptionsQuery(function (Builder $query): Builder {
                        return $query->with(['user'])->orderBy('id', 'desc');
                    })
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Toggle::make('is_super_specialist')
                            ->label('Mark as Super Specialist')
                            ->default(false),
                        TextInput::make('department')
                            ->maxLength(150),
                        TextInput::make('role')
                            ->label('Designation / Role')
                            ->maxLength(150),
                    ]),
            ])
            ->recordActions([
                Action::make('editLink')
                    ->label('Edit Link')
                    ->schema([
                        Toggle::make('is_super_specialist')
                            ->label('Super Specialist'),
                        TextInput::make('department')
                            ->maxLength(150),
                        TextInput::make('role')
                            ->label('Designation / Role')
                            ->maxLength(150),
                    ])
                    ->fillForm(function (Specialist $record): array {
                        return [
                            'is_super_specialist' => (bool) ($record->pivot?->is_super_specialist ?? false),
                            'department' => $record->pivot?->department,
                            'role' => $record->pivot?->role,
                        ];
                    })
                    ->action(function (Specialist $record, array $data): void {
                        /** @var Hospital $hospital */
                        $hospital = $this->getOwnerRecord();

                        $hospital->specialists()->updateExistingPivot($record->id, [
                            'is_super_specialist' => (bool) ($data['is_super_specialist'] ?? false),
                            'department' => $data['department'] ?? null,
                            'role' => $data['role'] ?? null,
                        ]);
                    }),
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
