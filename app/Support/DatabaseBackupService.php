<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public const BACKUP_DIRECTORY = 'app/backups/database';
    private const SETTINGS_FILE = 'app/database-backup-settings.json';

    public function createBackup(?string $connection = null): string
    {
        $connectionName = (string) ($connection ?: config('database.default', ''));

        return match ($connectionName) {
            'mysql', 'mariadb' => $this->createMysqlBackup($connectionName),
            'sqlite' => $this->createSqliteBackup($connectionName),
            default => throw new \RuntimeException("Connection [{$connectionName}] belum didukung untuk backup database."),
        };
    }

    public function listBackups(): array
    {
        $dir = $this->backupStoragePath();
        if (!is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn (\SplFileInfo $file) => $file->isFile())
            ->map(function (\SplFileInfo $file) {
                return [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_human' => $this->formatBytes((int) $file->getSize()),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
                ];
            })
            ->sortByDesc('modified_at')
            ->values()
            ->all();
    }

    public function resolveBackupPath(string $fileName): string
    {
        $safeName = basename($fileName);
        if ($safeName === '' || $safeName !== $fileName) {
            abort(404);
        }

        return $this->backupStoragePath() . DIRECTORY_SEPARATOR . $safeName;
    }

    public function backupStoragePath(): string
    {
        $settings = $this->readSettings();
        $configuredPath = trim((string) ($settings['backup_directory'] ?? ''));
        if ($configuredPath === '') {
            return storage_path(self::BACKUP_DIRECTORY);
        }

        return $this->normalizeDirectoryPath($configuredPath);
    }

    public function backupStoragePathInput(): string
    {
        $settings = $this->readSettings();
        return trim((string) ($settings['backup_directory'] ?? ''));
    }

    public function updateBackupStoragePath(?string $path): string
    {
        $raw = trim((string) $path);
        $settings = $this->readSettings();

        if ($raw === '') {
            unset($settings['backup_directory']);
            $resolved = storage_path(self::BACKUP_DIRECTORY);
        } else {
            $resolved = $this->normalizeDirectoryPath($raw);
            $settings['backup_directory'] = $raw;
        }

        File::ensureDirectoryExists($resolved);
        File::ensureDirectoryExists(dirname($this->settingsFilePath()));
        if ($settings === []) {
            File::delete($this->settingsFilePath());
        } else {
            File::put($this->settingsFilePath(), json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return $resolved;
    }

    public function databaseName(?string $connection = null): string
    {
        $config = config('database.connections.' . ($connection ?: config('database.default')));
        return (string) ($config['database'] ?? '');
    }

    public function schedulerTaskName(): string
    {
        return (string) env('DB_BACKUP_SCHEDULER_TASK_NAME', 'ColdKeyGMI Laravel Scheduler');
    }

    public function schedulerStatus(): array
    {
        if (!$this->isWindows()) {
            return [
                'supported' => false,
                'installed' => false,
                'enabled' => false,
                'running' => false,
                'status_label' => 'Tidak Didukung',
                'status_tone' => 'slate',
                'message' => 'Kontrol scheduler via halaman ini hanya tersedia untuk server Windows.',
                'task_name' => $this->schedulerTaskName(),
                'raw' => [],
                'scheduler_log' => $this->schedulerLogMeta(),
            ];
        }

        $process = new Process([
            'schtasks',
            '/Query',
            '/TN',
            $this->schedulerTaskName(),
            '/V',
            '/FO',
            'LIST',
        ]);
        $process->setTimeout(20);
        $process->run();

        if (!$process->isSuccessful()) {
            return [
                'supported' => true,
                'installed' => false,
                'enabled' => false,
                'running' => false,
                'status_label' => 'Belum Terpasang',
                'status_tone' => 'rose',
                'message' => 'Task Scheduler untuk backup database belum terdaftar di server ini.',
                'task_name' => $this->schedulerTaskName(),
                'raw' => [],
                'scheduler_log' => $this->schedulerLogMeta(),
            ];
        }

        $raw = $this->parseSchedulerListOutput($process->getOutput());
        $state = strtolower((string) ($raw['Scheduled Task State'] ?? ''));
        $status = strtolower((string) ($raw['Status'] ?? ''));
        $enabled = $state === 'enabled';
        $logMeta = $this->schedulerLogMeta();
        $heartbeatFresh = (bool) ($logMeta['is_recent'] ?? false);
        $running = $enabled && $heartbeatFresh;

        $statusLabel = $running ? 'Jalan' : ($enabled ? 'Aktif' : 'Mati');
        $statusTone = $running ? 'emerald' : ($enabled ? 'amber' : 'rose');
        $message = $running
            ? 'Scheduler terdeteksi aktif dan log masih update.'
            : ($enabled
                ? 'Task aktif, tetapi log scheduler belum update dalam beberapa menit terakhir.'
                : 'Task scheduler sedang dinonaktifkan.');

        return [
            'supported' => true,
            'installed' => true,
            'enabled' => $enabled,
            'running' => $running,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'message' => $message,
            'task_name' => (string) ($raw['TaskName'] ?? ('\\' . $this->schedulerTaskName())),
            'task_status' => $raw['Status'] ?? null,
            'task_state' => $raw['Scheduled Task State'] ?? null,
            'next_run_time' => $raw['Next Run Time'] ?? null,
            'last_run_time' => $raw['Last Run Time'] ?? null,
            'last_result' => $raw['Last Result'] ?? null,
            'logon_mode' => $raw['Logon Mode'] ?? null,
            'raw' => $raw,
            'scheduler_log' => $logMeta,
        ];
    }

    public function enableScheduler(): void
    {
        $this->ensureWindowsSchedulerSupport();
        $this->runSchedulerCommand(['schtasks', '/Change', '/TN', $this->schedulerTaskName(), '/ENABLE']);
    }

    public function disableScheduler(): void
    {
        $this->ensureWindowsSchedulerSupport();
        $this->runSchedulerCommand(['schtasks', '/Change', '/TN', $this->schedulerTaskName(), '/DISABLE']);
    }

    private function createMysqlBackup(string $connectionName): string
    {
        $config = config('database.connections.' . $connectionName);
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Konfigurasi database MySQL tidak lengkap.');
        }

        $dumpBinary = $this->resolveMysqlDumpBinary();
        $backupPath = $this->makeBackupFilePath($database, 'sql');

        $command = [
            $dumpBinary,
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            $database,
        ];

        $process = new Process($command, base_path(), [
            'MYSQL_PWD' => $password,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Gagal menjalankan mysqldump: ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        File::ensureDirectoryExists(dirname($backupPath));
        File::put($backupPath, $process->getOutput());

        if (!is_file($backupPath) || filesize($backupPath) === 0) {
            throw new \RuntimeException('File backup berhasil dibuat, tetapi hasilnya kosong.');
        }

        return $backupPath;
    }

    private function createSqliteBackup(string $connectionName): string
    {
        $config = config('database.connections.' . $connectionName);
        $sourcePath = (string) ($config['database'] ?? '');

        if ($sourcePath === '') {
            throw new \RuntimeException('Path database SQLite tidak ditemukan.');
        }

        $resolvedSourcePath = Str::startsWith($sourcePath, [DIRECTORY_SEPARATOR, 'C:\\', 'c:\\'])
            ? $sourcePath
            : database_path($sourcePath);

        if (!is_file($resolvedSourcePath)) {
            throw new \RuntimeException('File database SQLite tidak ditemukan di ' . $resolvedSourcePath);
        }

        $backupPath = $this->makeBackupFilePath(pathinfo($resolvedSourcePath, PATHINFO_FILENAME), pathinfo($resolvedSourcePath, PATHINFO_EXTENSION) ?: 'sqlite');
        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($resolvedSourcePath, $backupPath);

        return $backupPath;
    }

    private function resolveMysqlDumpBinary(): string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            base_path('mysql/bin/mysqldump.exe'),
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysqldump') {
                return $candidate;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('mysqldump tidak ditemukan. Set env MYSQLDUMP_PATH atau pastikan XAMPP MySQL terpasang.');
    }

    private function makeBackupFilePath(string $databaseName, string $extension): string
    {
        $safeDatabaseName = Str::of($databaseName)->replaceMatches('/[^A-Za-z0-9_\-]+/', '_')->trim('_')->value() ?: 'database';
        $timestamp = Carbon::now()->format('Ymd_His');
        $directory = $this->backupStoragePath();

        return $directory . DIRECTORY_SEPARATOR . "{$safeDatabaseName}_backup_{$timestamp}.{$extension}";
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes;
        $unit = 'B';

        foreach ($units as $nextUnit) {
            $value /= 1024;
            $unit = $nextUnit;
            if ($value < 1024) {
                break;
            }
        }

        return number_format($value, 2) . ' ' . $unit;
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    private function ensureWindowsSchedulerSupport(): void
    {
        if (!$this->isWindows()) {
            throw new \RuntimeException('Kontrol scheduler backup hanya tersedia di server Windows.');
        }
    }

    private function runSchedulerCommand(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(20);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Gagal menjalankan perintah scheduler Windows.'));
        }
    }

    private function parseSchedulerListOutput(string $output): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $output) ?: [];
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $parsed[trim($key)] = trim($value);
        }

        return $parsed;
    }

    private function schedulerLogMeta(): array
    {
        $path = storage_path('logs/scheduler.log');
        if (!is_file($path)) {
            return [
                'exists' => false,
                'path' => $path,
                'last_modified' => null,
                'size' => 0,
                'size_human' => '0 B',
                'is_recent' => false,
            ];
        }

        $lastModified = Carbon::createFromTimestamp(filemtime($path));

        return [
            'exists' => true,
            'path' => $path,
            'last_modified' => $lastModified->toDateTimeString(),
            'size' => filesize($path) ?: 0,
            'size_human' => $this->formatBytes((int) (filesize($path) ?: 0)),
            'is_recent' => $lastModified->greaterThanOrEqualTo(now()->subMinutes(3)),
        ];
    }

    private function readSettings(): array
    {
        $path = $this->settingsFilePath();
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function settingsFilePath(): string
    {
        return storage_path(self::SETTINGS_FILE);
    }

    private function normalizeDirectoryPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path));

        if ($path === '') {
            return storage_path(self::BACKUP_DIRECTORY);
        }

        if ($this->isAbsolutePath($path)) {
            return rtrim($path, "\\/");
        }

        return rtrim(base_path($path), "\\/");
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }
}
