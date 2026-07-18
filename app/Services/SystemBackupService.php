<?php

namespace App\Services;

use App\Models\SystemBackup;
use App\Models\SystemBackupAuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class SystemBackupService
{
    private Filesystem $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    public function create(string $type, ?Authenticatable $actor = null, array $options = []): SystemBackup
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['full', 'db', 'files', 'safety'], true)) {
            throw new \InvalidArgumentException('Invalid backup type.');
        }

        $disk = (string) ($options['disk'] ?? env('SYSTEM_BACKUP_DISK', 'backups'));
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            $disk = 'backups';
        }

        $now = CarbonImmutable::now();

        /** @var User|null $user */
        $user = $actor instanceof User ? $actor : null;

        $backup = SystemBackup::create([
            'type' => $type,
            'status' => 'running',
            'disk' => $disk,
            'started_at' => $now,
            'created_by' => $user?->id,
        ]);

        $this->log($backup, $user, 'backup_create_requested', [
            'type' => $type,
            'disk' => $disk,
        ]);

        try {
            $filename = $this->buildFilename($type, $backup->id, $now);
            $tmpDir = storage_path('app/private/tmp/system_backups/'.$backup->id);
            $this->fs->ensureDirectoryExists($tmpDir);

            $tmpZipPath = $tmpDir.'/'.$filename;
            $zip = new ZipArchive();
            if ($zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Failed to create zip archive.');
            }

            $manifest = [
                'version' => 1,
                'id' => $backup->id,
                'type' => $type,
                'created_at' => $now->toIso8601String(),
                'app' => [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                    'url' => config('app.url'),
                    'php' => PHP_VERSION,
                    'laravel' => app()->version(),
                ],
                'database' => null,
                'includes' => [
                    'db' => in_array($type, ['full', 'db', 'safety'], true),
                    'files' => in_array($type, ['full', 'files', 'safety'], true),
                    'env' => in_array($type, ['full', 'safety'], true),
                ],
                'entries' => [],
            ];

            $backup->update([
                'status' => 'completed',
                'path' => $filename,
            ]);

            if ($manifest['includes']['db']) {
                $dbRelPath = 'db/dump.sql';
                $dbPath = $tmpDir.'/dump.sql';
                $this->dumpDatabase($dbPath);
                $zip->addFile($dbPath, $dbRelPath);
                $manifest['database'] = [
                    'connection' => config('database.default'),
                    'database' => (string) config('database.connections.'.config('database.default').'.database'),
                ];
                $manifest['entries'][] = $this->entryFromFile($dbRelPath, $dbPath);
            }

            if ($manifest['includes']['env']) {
                $envPath = base_path('.env');
                $envRelPath = 'env/.env.enc';
                if ($this->fs->exists($envPath)) {
                    $this->encrypter();
                    $encrypted = $this->encryptString($this->fs->get($envPath));
                    $zip->addFromString($envRelPath, $encrypted);
                    $manifest['entries'][] = $this->entryFromString($envRelPath, $encrypted);
                    $manifest['env'] = [
                        'encrypted' => true,
                        'encrypter' => 'backup-key',
                    ];
                }
            }

            if ($manifest['includes']['files']) {
                $fileSets = $this->fileSets();
                $manifest['files'] = ['sets' => $fileSets];
                foreach ($fileSets as $set) {
                    $src = $set['path'];
                    $prefix = $set['prefix'];
                    if (! $this->fs->exists($src)) {
                        continue;
                    }
                    $this->addPathToZip($zip, $src, $prefix, $manifest, $set['exclude'] ?? []);
                }
            }

            $manifestJson = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $zip->addFromString('manifest.json', $manifestJson);
            $zip->close();

            $sha256 = hash_file('sha256', $tmpZipPath);
            $size = filesize($tmpZipPath) ?: null;

            $stream = fopen($tmpZipPath, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Failed to open backup archive for upload.');
            }
            Storage::disk($disk)->put($filename, $stream);
            fclose($stream);

            $backup->update([
                'status' => 'completed',
                'path' => $filename,
                'sha256' => $sha256,
                'size_bytes' => $size,
                'manifest' => $manifest,
                'completed_at' => CarbonImmutable::now(),
            ]);

            $this->log($backup, $user, 'backup_created', [
                'type' => $type,
                'disk' => $disk,
                'path' => $filename,
                'sha256' => $sha256,
                'size_bytes' => $size,
            ]);

            $this->fs->deleteDirectory($tmpDir);

            return $backup->fresh();
        } catch (Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'verification_status' => null,
                'verification_message' => $e->getMessage(),
                'completed_at' => CarbonImmutable::now(),
            ]);

            $this->log($backup, $user, 'backup_failed', [
                'message' => $e->getMessage(),
                'type' => $type,
            ]);

            throw $e;
        }
    }

    public function importFromDisk(string $disk, string $path, ?Authenticatable $actor = null): SystemBackup
    {
        /** @var User|null $user */
        $user = $actor instanceof User ? $actor : null;

        $disk = trim($disk);
        $path = trim(str_replace('\\', '/', $path));

        if ($disk === '' || $path === '') {
            throw new \InvalidArgumentException('Disk and path are required.');
        }

        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            throw new \RuntimeException('Invalid storage disk.');
        }

        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Uploaded backup archive not found.');
        }

        $now = CarbonImmutable::now();

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw new \RuntimeException('Only .zip archives can be imported.');
        }

        $dest = 'imports/import_'.$now->format('Ymd_His').'_'.bin2hex(random_bytes(8)).'.zip';
        if ($path !== $dest) {
            Storage::disk($disk)->move($path, $dest);
            $path = $dest;
        }

        $tmpDir = storage_path('app/private/tmp/system_backup_import/'.bin2hex(random_bytes(8)));
        $this->fs->ensureDirectoryExists($tmpDir);
        $tmpZipPath = $tmpDir.'/archive.zip';

        $in = Storage::disk($disk)->readStream($path);
        if ($in === false || $in === null) {
            throw new \RuntimeException('Failed to read backup archive stream.');
        }
        $out = fopen($tmpZipPath, 'wb');
        if ($out === false) {
            throw new \RuntimeException('Failed to create temp archive file.');
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        $sha256 = hash_file('sha256', $tmpZipPath);
        $size = filesize($tmpZipPath) ?: null;

        $zip = new ZipArchive();
        if ($zip->open($tmpZipPath) !== true) {
            throw new \RuntimeException('Cannot open backup archive.');
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        if (! is_string($manifestRaw) || trim($manifestRaw) === '') {
            $zip->close();
            throw new \RuntimeException('manifest.json missing.');
        }
        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest)) {
            $zip->close();
            throw new \RuntimeException('manifest.json is invalid JSON.');
        }

        $type = strtolower(trim((string) ($manifest['type'] ?? '')));
        if (! in_array($type, ['full', 'db', 'files', 'safety'], true)) {
            $type = 'full';
        }

        $zip->close();

        $backup = SystemBackup::create([
            'type' => $type,
            'status' => 'completed',
            'disk' => $disk,
            'path' => $path,
            'sha256' => $sha256,
            'size_bytes' => $size,
            'manifest' => $manifest,
            'started_at' => $now,
            'completed_at' => $now,
            'created_by' => $user?->id,
        ]);

        $this->log($backup, $user, 'backup_imported', [
            'disk' => $disk,
            'path' => $path,
            'sha256' => $sha256,
            'size_bytes' => $size,
        ]);

        try {
            $this->verify($backup, $actor);
        } catch (Throwable $e) {
            $backup->update([
                'verified_at' => CarbonImmutable::now(),
                'verification_status' => 'failed',
                'verification_message' => $e->getMessage(),
            ]);
            $this->log($backup, $user, 'backup_verify_failed', [
                'reason' => 'verify_exception',
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->fs->deleteDirectory($tmpDir);
        }

        return $backup->fresh();
    }

    public function verify(SystemBackup $backup, ?Authenticatable $actor = null): SystemBackup
    {
        /** @var User|null $user */
        $user = $actor instanceof User ? $actor : null;

        if ($backup->status !== 'completed' || ! $backup->disk || ! $backup->path) {
            throw new \RuntimeException('Backup is not ready for verification.');
        }

        $disk = $backup->disk;
        $path = $backup->path;
        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Backup archive not found on disk.');
        }

        $tmpDir = storage_path('app/private/tmp/system_backup_verify/'.$backup->id);
        $this->fs->ensureDirectoryExists($tmpDir);
        $tmpZipPath = $tmpDir.'/archive.zip';
        $in = Storage::disk($disk)->readStream($path);
        if ($in === false || $in === null) {
            throw new \RuntimeException('Failed to read backup archive stream.');
        }
        $out = fopen($tmpZipPath, 'wb');
        if ($out === false) {
            throw new \RuntimeException('Failed to create temp archive file.');
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        $archiveSha = hash_file('sha256', $tmpZipPath);
        if ($backup->sha256 && $archiveSha !== $backup->sha256) {
            $backup->update([
                'verified_at' => CarbonImmutable::now(),
                'verification_status' => 'failed',
                'verification_message' => 'Archive checksum mismatch.',
            ]);
            $this->log($backup, $user, 'backup_verify_failed', [
                'reason' => 'archive_sha256_mismatch',
                'expected' => $backup->sha256,
                'actual' => $archiveSha,
            ]);
            $this->fs->deleteDirectory($tmpDir);

            return $backup->fresh();
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpZipPath) !== true) {
            throw new \RuntimeException('Cannot open backup archive.');
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        if (! is_string($manifestRaw) || trim($manifestRaw) === '') {
            throw new \RuntimeException('manifest.json missing.');
        }
        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest)) {
            throw new \RuntimeException('manifest.json is invalid JSON.');
        }

        $entries = Arr::get($manifest, 'entries', []);
        if (! is_array($entries)) {
            $entries = [];
        }

        foreach ($entries as $entry) {
            $rel = (string) ($entry['path'] ?? '');
            $expected = (string) ($entry['sha256'] ?? '');
            if ($rel === '' || $expected === '') {
                continue;
            }
            $stream = $zip->getStream($rel);
            if ($stream === false) {
                throw new \RuntimeException("Missing archive entry: {$rel}");
            }
            $h = hash_init('sha256');
            $ok = @hash_update_stream($h, $stream);
            if ($ok === false) {
                $err = error_get_last();
                $zip->close();
                fclose($stream);
                $backup->update([
                    'verified_at' => CarbonImmutable::now(),
                    'verification_status' => 'failed',
                    'verification_message' => "Failed to read {$rel}: ".((string) ($err['message'] ?? 'Zip stream error')),
                ]);
                $this->log($backup, $user, 'backup_verify_failed', [
                    'reason' => 'zip_stream_error',
                    'path' => $rel,
                    'message' => (string) ($err['message'] ?? ''),
                ]);
                $this->fs->deleteDirectory($tmpDir);

                return $backup->fresh();
            }
            fclose($stream);
            $actual = hash_final($h);
            if ($actual !== $expected) {
                $zip->close();
                $backup->update([
                    'verified_at' => CarbonImmutable::now(),
                    'verification_status' => 'failed',
                    'verification_message' => "Checksum mismatch for {$rel}",
                ]);
                $this->log($backup, $user, 'backup_verify_failed', [
                    'reason' => 'entry_sha256_mismatch',
                    'path' => $rel,
                    'expected' => $expected,
                    'actual' => $actual,
                ]);
                $this->fs->deleteDirectory($tmpDir);

                return $backup->fresh();
            }
        }

        $zip->close();

        $backup->update([
            'verified_at' => CarbonImmutable::now(),
            'verification_status' => 'ok',
            'verification_message' => null,
        ]);

        $this->log($backup, $user, 'backup_verified', [
            'sha256' => $backup->sha256,
        ]);

        $this->fs->deleteDirectory($tmpDir);

        return $backup->fresh();
    }

    public function restore(SystemBackup $backup, string $mode, ?Authenticatable $actor = null): void
    {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['full', 'db', 'files'], true)) {
            throw new \InvalidArgumentException('Invalid restore mode.');
        }

        /** @var User|null $user */
        $user = $actor instanceof User ? $actor : null;

        if ($mode === 'full' && ! ($user?->isSuperAdmin() ?? false)) {
            throw new \RuntimeException('Full restore is super-admin only.');
        }

        if ($backup->status !== 'completed' || ! $backup->disk || ! $backup->path) {
            throw new \RuntimeException('Backup is not restorable.');
        }

        $this->log($backup, $user, 'restore_requested', [
            'mode' => $mode,
        ]);

        $safetyType = $mode === 'full' ? 'safety' : $mode;
        $safety = $this->create($safetyType, $actor, ['disk' => $backup->disk]);
        $this->log($backup, $user, 'safety_backup_created', [
            'safety_backup_id' => $safety->id,
            'safety_path' => $safety->path,
            'safety_type' => $safety->type,
        ]);

        $disk = $backup->disk;
        $path = $backup->path;
        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Backup archive not found on disk.');
        }

        $tmpDir = storage_path('app/private/tmp/system_backup_restore/'.$backup->id.'/'.uniqid('', true));
        $this->fs->ensureDirectoryExists($tmpDir);

        $tmpZipPath = $tmpDir.'/archive.zip';
        $in = Storage::disk($disk)->readStream($path);
        if ($in === false || $in === null) {
            throw new \RuntimeException('Failed to read backup archive stream.');
        }
        $out = fopen($tmpZipPath, 'wb');
        if ($out === false) {
            throw new \RuntimeException('Failed to create temp archive file.');
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        $zip = new ZipArchive();
        if ($zip->open($tmpZipPath) !== true) {
            throw new \RuntimeException('Cannot open backup archive.');
        }

        $maintenance = ($mode === 'full');

        try {
            if ($maintenance) {
                Artisan::call('down');
            }

            if ($mode === 'db' || $mode === 'full') {
                $dumpStream = $zip->getStream('db/dump.sql');
                if ($dumpStream === false) {
                    throw new \RuntimeException('DB dump not found in archive.');
                }
                $dumpPath = $tmpDir.'/dump.sql';
                $dumpOut = fopen($dumpPath, 'wb');
                if ($dumpOut === false) {
                    throw new \RuntimeException('Failed to create temp DB dump file.');
                }
                stream_copy_to_stream($dumpStream, $dumpOut);
                fclose($dumpStream);
                fclose($dumpOut);

                $this->restoreDatabase($dumpPath);
            }

            if ($mode === 'files' || $mode === 'full') {
                $this->restoreFilesFromZip($zip);
            }

            if ($mode === 'full') {
                $encEnv = $zip->getFromName('env/.env.enc');
                if (is_string($encEnv) && trim($encEnv) !== '') {
                    $plain = $this->decryptString($encEnv);
                    $this->fs->put(base_path('.env'), $plain);
                }
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            $this->log($backup, $user, 'restore_completed', [
                'mode' => $mode,
            ]);
        } catch (Throwable $e) {
            $this->log($backup, $user, 'restore_failed', [
                'mode' => $mode,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            $zip->close();
            if ($maintenance) {
                try {
                    Artisan::call('up');
                } catch (Throwable) {}
            }
            $this->fs->deleteDirectory($tmpDir);
        }
    }

    public function log(?SystemBackup $backup, ?User $user, string $action, array $meta = []): void
    {
        SystemBackupAuditLog::create([
            'system_backup_id' => $backup?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'meta' => $meta,
        ]);
    }

    private function buildFilename(string $type, int $id, CarbonImmutable $now): string
    {
        return 'backup_'.$type.'_'.$now->format('Ymd_His').'_id'.$id.'.zip';
    }

    private function entryFromFile(string $relPath, string $absPath): array
    {
        return [
            'path' => $relPath,
            'size' => filesize($absPath) ?: null,
            'sha256' => hash_file('sha256', $absPath),
        ];
    }

    private function entryFromString(string $relPath, string $data): array
    {
        return [
            'path' => $relPath,
            'size' => strlen($data),
            'sha256' => hash('sha256', $data),
        ];
    }

    private function fileSets(): array
    {
        $sets = [
            [
                'name' => 'storage_app_public',
                'path' => storage_path('app/public'),
                'prefix' => 'files/storage/app/public',
                'exclude' => [],
            ],
            [
                'name' => 'storage_app_private',
                'path' => storage_path('app/private'),
                'prefix' => 'files/storage/app/private',
                'exclude' => [
                    storage_path('app/private/backups'),
                    storage_path('app/private/tmp'),
                ],
            ],
        ];

        $publicUploads = public_path('uploads');
        if ($this->fs->exists($publicUploads)) {
            $sets[] = [
                'name' => 'public_uploads',
                'path' => $publicUploads,
                'prefix' => 'files/public/uploads',
                'exclude' => [],
            ];
        }

        return $sets;
    }

    private function addPathToZip(ZipArchive $zip, string $srcPath, string $prefix, array &$manifest, array $exclude = []): void
    {
        $srcPath = rtrim($srcPath, DIRECTORY_SEPARATOR);
        $prefix = trim(str_replace('\\', '/', $prefix), '/');

        $exclude = array_map(fn ($p) => rtrim((string) $p, DIRECTORY_SEPARATOR), $exclude);

        if (is_file($srcPath)) {
            $rel = $prefix.'/'.basename($srcPath);
            $zip->addFile($srcPath, $rel);
            $manifest['entries'][] = $this->entryFromFile($rel, $srcPath);
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            $full = $file->getPathname();
            if ($this->isExcluded($full, $exclude)) {
                continue;
            }

            if ($file->isDir()) {
                continue;
            }

            $relativeFromSrc = ltrim(str_replace($srcPath, '', $full), DIRECTORY_SEPARATOR);
            $relativeFromSrc = str_replace('\\', '/', $relativeFromSrc);
            $rel = $prefix.'/'.$relativeFromSrc;
            $zip->addFile($full, $rel);
            $manifest['entries'][] = $this->entryFromFile($rel, $full);
        }
    }

    private function isExcluded(string $path, array $exclude): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        foreach ($exclude as $ex) {
            if ($ex === '') {
                continue;
            }
            if (str_starts_with($path, $ex)) {
                return true;
            }
        }
        return false;
    }

    private function dumpDatabase(string $outputPath): void
    {
        $connection = config('database.default');
        $cfg = config("database.connections.{$connection}");
        $driver = (string) ($cfg['driver'] ?? '');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Only MySQL/MariaDB dump is supported.');
        }

        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('DB connection is missing credentials.');
        }

        $cmd = [
            'mysqldump',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            '--add-drop-table',
            $database,
        ];

        $process = new Process($cmd);
        $process->setTimeout(600);
        if ($password !== '') {
            $process->setEnv(['MYSQL_PWD' => $password]);
        }

        $fh = fopen($outputPath, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('Failed to create DB dump file.');
        }

        $process->run(function ($type, $buffer) use ($fh) {
            fwrite($fh, $buffer);
        });

        fclose($fh);

        if ($process->isSuccessful()) {
            return;
        }

        $this->dumpDatabaseViaPdo($outputPath);
    }

    private function restoreDatabase(string $dumpPath): void
    {
        $connection = config('database.default');
        $cfg = config("database.connections.{$connection}");
        $driver = (string) ($cfg['driver'] ?? '');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Only MySQL/MariaDB restore is supported.');
        }

        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('DB connection is missing credentials.');
        }

        if (! $this->fs->exists($dumpPath)) {
            throw new \RuntimeException('DB dump file not found.');
        }

        $this->dropAllDatabaseObjectsViaPdo();

        DB::disconnect($connection);

        $cmd = [
            'mysql',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            $database,
        ];

        $process = new Process($cmd);
        $process->setTimeout(600);
        if ($password !== '') {
            $process->setEnv(['MYSQL_PWD' => $password]);
        }

        $input = fopen($dumpPath, 'rb');
        if ($input === false) {
            throw new \RuntimeException('Failed to open DB dump file for restore.');
        }
        $process->setInput($input);
        $process->run();
        fclose($input);
        if ($process->isSuccessful()) {
            return;
        }

        $this->restoreDatabaseViaPdoFile($dumpPath);
    }

    private function dumpDatabaseViaPdo(string $outputPath): void
    {
        $connection = config('database.default');
        $pdo = DB::connection($connection)->getPdo();

        $fh = fopen($outputPath, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('Failed to create DB dump file.');
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fh, "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");

        $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
        foreach ($tables as $row) {
            $name = (string) ($row[0] ?? '');
            $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
            if ($name === '') continue;

            if ($type === 'VIEW') {
                $create = $pdo->query('SHOW CREATE VIEW `'.$name.'`')->fetch(\PDO::FETCH_ASSOC);
                $createSql = (string) ($create['Create View'] ?? '');
                if ($createSql === '') continue;
                fwrite($fh, 'DROP VIEW IF EXISTS `'.$name."`;\n");
                fwrite($fh, $createSql.";\n");
                continue;
            }

            $create = $pdo->query('SHOW CREATE TABLE `'.$name.'`')->fetch(\PDO::FETCH_ASSOC);
            $createSql = (string) ($create['Create Table'] ?? '');
            if ($createSql === '') continue;

            fwrite($fh, 'DROP TABLE IF EXISTS `'.$name."`;\n");
            fwrite($fh, $createSql.";\n");

            $stmt = $pdo->query('SELECT * FROM `'.$name.'`');
            if (! $stmt) {
                continue;
            }

            $firstRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($firstRow === false || $firstRow === null) {
                continue;
            }

            $cols = array_keys($firstRow);
            $colSql = implode(', ', array_map(fn ($c) => '`'.$c.'`', $cols));

            $batch = [];
            $r = $firstRow;
            while ($r !== false && $r !== null) {
                $vals = [];
                foreach ($cols as $c) {
                    $v = $r[$c];
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } elseif (is_int($v) || is_float($v)) {
                        $vals[] = (string) $v;
                    } else {
                        $vals[] = $pdo->quote((string) $v);
                    }
                }
                $batch[] = '('.implode(', ', $vals).')';
                if (count($batch) >= 200) {
                    fwrite($fh, 'INSERT INTO `'.$name.'` ('.$colSql.') VALUES '.implode(', ', $batch).";\n");
                    $batch = [];
                }

                $r = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            if (! empty($batch)) {
                fwrite($fh, 'INSERT INTO `'.$name.'` ('.$colSql.') VALUES '.implode(', ', $batch).";\n");
            }
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    private function restoreDatabaseViaPdo(string $sql): void
    {
        $connection = config('database.default');
        DB::purge($connection);
        $pdo = DB::connection($connection)->getPdo();
        if (! $pdo) {
            throw new \RuntimeException('Database connection is not available for PDO restore.');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') continue;
            $pdo->exec($statement);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function restoreDatabaseViaPdoFile(string $dumpPath): void
    {
        $connection = config('database.default');
        DB::purge($connection);
        $pdo = DB::connection($connection)->getPdo();
        if (! $pdo) {
            throw new \RuntimeException('Database connection is not available for PDO restore.');
        }

        $fh = fopen($dumpPath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('Failed to open DB dump file.');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $prev = '';

        while (! feof($fh)) {
            $chunk = fread($fh, 1024 * 64);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $len = strlen($chunk);
            for ($i = 0; $i < $len; $i++) {
                $ch = $chunk[$i];
                $next = $i + 1 < $len ? $chunk[$i + 1] : '';

                if ($inLineComment) {
                    if ($ch === "\n") {
                        $inLineComment = false;
                    }
                    $prev = $ch;
                    continue;
                }

                if ($inBlockComment) {
                    if ($prev === '*' && $ch === '/') {
                        $inBlockComment = false;
                    }
                    $prev = $ch;
                    continue;
                }

                if (! $inSingle && ! $inDouble && ! $inBacktick) {
                    if ($ch === '-' && $next === '-') {
                        $inLineComment = true;
                        $i++;
                        $prev = '';
                        continue;
                    }
                    if ($ch === '/' && $next === '*') {
                        $inBlockComment = true;
                        $i++;
                        $prev = '';
                        continue;
                    }
                }

                if ($ch === "'" && ! $inDouble && ! $inBacktick) {
                    $escaped = ($prev === '\\');
                    if (! $escaped) $inSingle = ! $inSingle;
                } elseif ($ch === '"' && ! $inSingle && ! $inBacktick) {
                    $escaped = ($prev === '\\');
                    if (! $escaped) $inDouble = ! $inDouble;
                } elseif ($ch === '`' && ! $inSingle && ! $inDouble) {
                    $inBacktick = ! $inBacktick;
                }

                if ($ch === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                    $statement = trim($buffer);
                    $buffer = '';
                    if ($statement !== '') {
                        $pdo->exec($statement);
                    }
                    $prev = '';
                    continue;
                }

                $buffer .= $ch;
                $prev = $ch;
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $pdo->exec($tail);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        fclose($fh);
    }

    private function dropAllDatabaseObjectsViaPdo(): void
    {
        $connection = config('database.default');
        DB::purge($connection);
        $pdo = DB::connection($connection)->getPdo();
        if (! $pdo) {
            throw new \RuntimeException('Database connection is not available for DB wipe.');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $items = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
        $tables = [];
        $views = [];
        foreach ($items as $row) {
            $name = (string) ($row[0] ?? '');
            $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
            if ($name === '') continue;
            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        foreach ($views as $v) {
            $pdo->exec('DROP VIEW IF EXISTS `'.$v.'`');
        }
        foreach ($tables as $t) {
            $pdo->exec('DROP TABLE IF EXISTS `'.$t.'`');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function splitSqlStatements(string $sql): \Generator
    {
        $len = strlen($sql);
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if (! $inSingle && ! $inDouble && ! $inBacktick) {
                if ($ch === '-' && $next === '-') {
                    while ($i < $len && $sql[$i] !== "\n") $i++;
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    $i += 2;
                    while ($i < $len) {
                        if ($sql[$i] === '*' && ($i + 1 < $len ? $sql[$i + 1] : '') === '/') {
                            $i++;
                            break;
                        }
                        $i++;
                    }
                    continue;
                }
            }

            if ($ch === "'" && ! $inDouble && ! $inBacktick) {
                $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                if (! $escaped) $inSingle = ! $inSingle;
            } elseif ($ch === '"' && ! $inSingle && ! $inBacktick) {
                $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                if (! $escaped) $inDouble = ! $inDouble;
            } elseif ($ch === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            }

            if ($ch === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                yield $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        if (trim($buffer) !== '') {
            yield $buffer;
        }
    }

    private function restoreFilesFromZip(ZipArchive $zip): void
    {
        $targets = [
            'files/storage/app/public' => storage_path('app/public'),
            'files/storage/app/private' => storage_path('app/private'),
            'files/public/uploads' => public_path('uploads'),
        ];

        foreach ($targets as $prefix => $targetDir) {
            $prefix = rtrim(str_replace('\\', '/', $prefix), '/').'/';
            $this->fs->ensureDirectoryExists($targetDir);

            if (realpath($targetDir) === realpath(storage_path('app/private'))) {
                $this->wipeDirectoryExcept($targetDir, ['backups', 'tmp']);
            } else {
                $this->fs->deleteDirectory($targetDir);
                $this->fs->ensureDirectoryExists($targetDir);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (! is_array($stat)) {
                    continue;
                }
                $name = (string) ($stat['name'] ?? '');
                $name = str_replace('\\', '/', $name);
                if (! str_starts_with($name, $prefix)) {
                    continue;
                }
                if (str_ends_with($name, '/')) {
                    continue;
                }

                $rel = substr($name, strlen($prefix));
                $rel = ltrim($rel, '/');
                if ($rel === '' || str_contains($rel, '..')) {
                    continue;
                }

                $dest = rtrim($targetDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
                $this->fs->ensureDirectoryExists(dirname($dest));

                $data = $zip->getFromIndex($i);
                if (! is_string($data)) {
                    continue;
                }
                $this->fs->put($dest, $data);
            }
        }
    }

    private function wipeDirectoryExcept(string $dir, array $keepTopLevelNames): void
    {
        $keep = array_flip($keepTopLevelNames);

        foreach ($this->fs->directories($dir) as $subDir) {
            $name = basename($subDir);
            if (isset($keep[$name])) {
                continue;
            }
            $this->fs->deleteDirectory($subDir);
        }

        foreach ($this->fs->files($dir) as $file) {
            $this->fs->delete($file->getPathname());
        }
    }

    private function encrypter(): Encrypter
    {
        $rawKey = (string) env('BACKUP_ENCRYPTION_KEY', '');
        if ($rawKey === '') {
            throw new \RuntimeException('BACKUP_ENCRYPTION_KEY is not set.');
        }

        $key = $rawKey;
        if (str_starts_with($rawKey, 'base64:')) {
            $key = base64_decode(substr($rawKey, 7), true);
        }

        if (! is_string($key) || strlen($key) !== 32) {
            throw new \RuntimeException('BACKUP_ENCRYPTION_KEY must be 32 bytes (or base64:... for 32 bytes).');
        }

        return new Encrypter($key, 'AES-256-CBC');
    }

    private function encryptString(string $plain): string
    {
        return $this->encrypter()->encryptString($plain);
    }

    private function decryptString(string $cipher): string
    {
        return $this->encrypter()->decryptString($cipher);
    }
}
