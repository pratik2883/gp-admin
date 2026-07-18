<?php

namespace App\Filament\Resources\SystemBackups\Pages;

use App\Filament\Resources\SystemBackups\SystemBackupResource;
use App\Models\SystemBackup;
use App\Services\SystemBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\URL;
use Throwable;

class ManageSystemBackups extends ManageRecords
{
    protected static string $resource = SystemBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_full')
                ->label('Create Full Backup')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runCreate('full');
                }),
            Action::make('create_db')
                ->label('Create DB-only Backup')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runCreate('db');
                }),
            Action::make('create_files')
                ->label('Create Files-only Backup')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->runCreate('files');
                }),
            Action::make('download_latest')
                ->label('Download Latest Backup')
                ->visible(fn () => SystemBackup::where('status', 'completed')->whereNotNull('path')->exists())
                ->action(function () {
                    $backup = SystemBackup::where('status', 'completed')
                        ->whereNotNull('path')
                        ->orderByDesc('id')
                        ->first();

                    if (! $backup) {
                        Notification::make()
                            ->title('No backups available')
                            ->danger()
                            ->send();
                        return null;
                    }

                    $url = URL::temporarySignedRoute(
                        'system-backups.download',
                        now()->addMinutes(5),
                        ['backup' => $backup->id]
                    );

                    return redirect()->to($url);
                }),
            Action::make('import_backup')
                ->label('Import Backup (.zip)')
                ->modalHeading('Import backup archive')
                ->modalDescription("Use this to restore a backup that was downloaded from another server.\n\nSteps:\n1) Download the backup .zip from the source admin panel.\n2) Upload it here.\n3) Run Verify on the imported backup.\n4) Restore DB / Files / Full restore as needed.")
                ->form([
                    FileUpload::make('archive')
                        ->label('Backup archive (.zip)')
                        ->disk('backups')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $path = (string) ($data['archive'] ?? '');
                        if ($path === '') {
                            throw new \RuntimeException('No file uploaded.');
                        }

                        $backup = app(SystemBackupService::class)->importFromDisk('backups', $path, auth()->user());

                        Notification::make()
                            ->title("Backup imported (#{$backup->id})")
                            ->body('Verification: '.($backup->verification_status ?? '-').' '.($backup->verification_message ?? ''))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Import failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function runCreate(string $type): void
    {
        try {
            app(SystemBackupService::class)->create($type, auth()->user());
            Notification::make()
                ->title('Backup created')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Backup failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
