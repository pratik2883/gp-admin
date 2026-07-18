<?php

namespace App\Filament\Resources\DiagnosticCenters\Pages;

use App\Filament\Resources\DiagnosticCenters\DiagnosticCenterResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ManageDiagnosticCenterServices extends ManageRelatedRecords
{
    protected static string $resource = DiagnosticCenterResource::class;

    protected static string $relationship = 'services';

    public static function getNavigationLabel(): string
    {
        return 'Services';
    }

    public function getTitle(): string
    {
        return 'Diagnostic Center Services';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('diagnostic_service_type_id')
                    ->label('Service Type')
                    ->options(fn () => DB::table('diagnostic_service_types')->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->optionsLimit(50)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if (! $state) {
                            return;
                        }
                        $name = DB::table('diagnostic_service_types')->where('id', $state)->value('name');
                        if ($name) {
                            $set('name', $name);
                        }
                    }),
                TextInput::make('name')
                    ->label('Service Name')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Service Name'),
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('diagnostic_service_type_id')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state ? (DB::table('diagnostic_service_types')->where('id', $state)->value('name') ?: '-') : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added On')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
