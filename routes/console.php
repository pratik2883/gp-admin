<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('system:make-super-admin {email}', function (string $email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error('User not found');
        return 1;
    }
    $user->is_super_admin = true;
    $user->save();
    $this->info("User {$user->email} is now super-admin.");
    return 0;
})->purpose('Promote an admin user to super-admin (required for full restore)');

Artisan::command('system:backup {type=full} {--disk=}', function (string $type) {
    $disk = $this->option('disk');
    $backup = app(\App\Services\SystemBackupService::class)->create($type, null, [
        'disk' => $disk ?: null,
    ]);
    $this->info("Backup created: #{$backup->id} {$backup->type} {$backup->disk}:{$backup->path}");
    return 0;
})->purpose('Create a system backup (full|db|files)');

Artisan::command('system:backup:verify {backupId}', function (string $backupId) {
    $backup = \App\Models\SystemBackup::findOrFail($backupId);
    $backup = app(\App\Services\SystemBackupService::class)->verify($backup);
    $this->info("Verification: {$backup->verification_status} ".($backup->verification_message ?? ''));
    return $backup->verification_status === 'ok' ? 0 : 2;
})->purpose('Verify a system backup integrity');

Artisan::command('system:restore {backupId} {mode=full} {--as=}', function (string $backupId, string $mode) {
    $backup = \App\Models\SystemBackup::findOrFail($backupId);

    $as = (string) ($this->option('as') ?? '');
    $as = trim($as);

    $actor = null;
    if ($as !== '') {
        $actor = is_numeric($as)
            ? \App\Models\User::find((int) $as)
            : \App\Models\User::where('email', $as)->first();

        if (! $actor) {
            $this->error('Actor user not found for --as.');
            return 1;
        }
    }

    app(\App\Services\SystemBackupService::class)->restore($backup, $mode, $actor);
    $this->info('Restore completed.');
    return 0;
})->purpose('Restore from a system backup (full|db|files). Use --as=<user_id|email> for full restores (super-admin required).');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:token {phone}', function (string $phone) {
    $user = \App\Models\User::where('mobile', $phone)->first();
    if (! $user) {
        $this->error('User not found');

        return 1;
    }
    $token = $user->createToken('postman-test')->plainTextToken;
    $this->info("User: {$user->name} ({$user->role})");
    $this->line($token);

    return 0;
})->purpose('Generate Sanctum token for testing by phone/mobile');
