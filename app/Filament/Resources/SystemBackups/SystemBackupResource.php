<?php

namespace App\Filament\Resources\SystemBackups;

use App\Filament\Resources\SystemBackups\Pages\ManageSystemBackups;
use App\Models\SystemBackup;
use App\Services\SystemBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class SystemBackupResource extends Resource
{
    protected static ?string $model = SystemBackup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function getPluralLabel(): ?string
    {
        return 'Backups';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'full',
                        'info' => 'db',
                        'warning' => 'files',
                        'success' => 'safety',
                    ]),
                BadgeColumn::make('status')
                    ->colors([
                        'info' => 'pending',
                        'warning' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(function (?int $state): string {
                        if (! $state || $state <= 0) return '-';
                        $kb = $state / 1024;
                        if ($kb < 1024) return number_format($kb, 1).' KB';
                        $mb = $kb / 1024;
                        if ($mb < 1024) return number_format($mb, 1).' MB';
                        return number_format($mb / 1024, 2).' GB';
                    })
                    ->toggleable(),
                TextColumn::make('sha256')
                    ->label('SHA256')
                    ->limit(12)
                    ->tooltip(fn (?string $state) => $state ?: null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Created by')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable(),
                BadgeColumn::make('verification_status')
                    ->label('Verify')
                    ->colors([
                        'success' => 'ok',
                        'danger' => 'failed',
                    ])
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verified_at')->dateTime()->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->visible(fn (SystemBackup $record) => $record->status === 'completed' && $record->path)
                    ->action(function (SystemBackup $record) {
                        $url = URL::temporarySignedRoute(
                            'system-backups.download',
                            now()->addMinutes(5),
                            ['backup' => $record->id]
                        );

                        return redirect()->to($url);
                    }),
                Action::make('restore_instructions')
                    ->label('Instructions')
                    ->modalHeading('Restore using a downloaded backup')
                    ->modalDescription("To restore this backup on another server:\n\n1) Download the backup .zip.\n2) On the target server, open Admin → System → Backups.\n3) Click Import Backup (.zip) and upload the file.\n4) Run Verify on the imported backup.\n5) Run Restore (DB / Files / Full restore).\n\nFull restore requires a super-admin and will enable maintenance mode during restore.")
                    ->action(fn () => null),
                Action::make('verify')
                    ->label('Verify')
                    ->visible(fn (SystemBackup $record) => $record->status === 'completed' && $record->path)
                    ->requiresConfirmation()
                    ->action(function (SystemBackup $record): void {
                        $svc = app(SystemBackupService::class);
                        $svc->verify($record, auth()->user());
                    }),
                Action::make('restore_db')
                    ->label('Restore DB')
                    ->color('danger')
                    ->visible(fn (SystemBackup $record) => $record->status === 'completed' && in_array($record->type, ['full', 'db', 'safety'], true))
                    ->modalHeading('Restore database')
                    ->modalDescription('This will overwrite the current database. A safety DB backup will be created first.')
                    ->form([
                        TextInput::make('confirm')
                            ->label('Type RESTORE DB to confirm')
                            ->required(),
                    ])
                    ->action(function (array $data, SystemBackup $record): void {
                        $confirm = strtoupper(trim((string) ($data['confirm'] ?? '')));
                        if ($confirm !== 'RESTORE DB') {
                            Notification::make()
                                ->title('Confirmation phrase mismatch')
                                ->danger()
                                ->send();
                            return;
                        }
                        app(SystemBackupService::class)->restore($record, 'db', auth()->user());
                    }),
                Action::make('restore_files')
                    ->label('Restore Files')
                    ->color('danger')
                    ->visible(fn (SystemBackup $record) => $record->status === 'completed' && in_array($record->type, ['full', 'files', 'safety'], true))
                    ->modalHeading('Restore files')
                    ->modalDescription('This will overwrite storage/public uploads. A safety Files backup will be created first.')
                    ->form([
                        TextInput::make('confirm')
                            ->label('Type RESTORE FILES to confirm')
                            ->required(),
                    ])
                    ->action(function (array $data, SystemBackup $record): void {
                        $confirm = strtoupper(trim((string) ($data['confirm'] ?? '')));
                        if ($confirm !== 'RESTORE FILES') {
                            Notification::make()
                                ->title('Confirmation phrase mismatch')
                                ->danger()
                                ->send();
                            return;
                        }
                        app(SystemBackupService::class)->restore($record, 'files', auth()->user());
                    }),
                Action::make('full_restore')
                    ->label('Full Restore')
                    ->color('danger')
                    ->visible(fn (SystemBackup $record) => ($record->status === 'completed' && in_array($record->type, ['full', 'safety'], true)) && (auth()->user()?->is_super_admin === true))
                    ->modalHeading('Full restore (super-admin)')
                    ->modalDescription('This will restore database + files + encrypted .env snapshot, and temporarily put the app in maintenance mode. A safety backup will be created first.')
                    ->form([
                        TextInput::make('confirm')
                            ->label('Type FULL RESTORE to confirm')
                            ->required(),
                    ])
                    ->action(function (array $data, SystemBackup $record): void {
                        $confirm = strtoupper(trim((string) ($data['confirm'] ?? '')));
                        if ($confirm !== 'FULL RESTORE') {
                            Notification::make()
                                ->title('Confirmation phrase mismatch')
                                ->danger()
                                ->send();
                            return;
                        }
                        app(SystemBackupService::class)->restore($record, 'full', auth()->user());
                    }),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->is_super_admin === true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSystemBackups::route('/'),
        ];
    }
}
