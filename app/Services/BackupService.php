<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PDO;
use Throwable;

class BackupService
{
    public function __construct(
        protected ?string $databasePath = null
    ) {}

    public function getDatabasePath(): string
    {
        if ($this->databasePath !== null && $this->databasePath !== '') {
            return $this->databasePath;
        }

        $configured = (string) DB::connection()->getDatabaseName();

        if ($configured === '' || $configured === ':memory:') {
            return database_path('database.sqlite');
        }

        return $configured;
    }

    public function getBackupFolder(): string
    {
        $folder = Setting::get('backup_folder');

        if (empty($folder) || trim((string) $folder) === '') {
            $folder = Setting::defaultBackupFolder();
        }

        $folder = rtrim((string) $folder, "/\\");
        File::ensureDirectoryExists($folder);

        return $folder;
    }

    /**
     * Return all .sqlite backup files in the backup folder sorted newest first.
     *
     * @return array<int, array{filename: string, path: string, size_kb: float, modified_at: Carbon, timestamp: int}>
     */
    public function listBackups(): array
    {
        $folder = $this->getBackupFolder();
        $files = File::glob($folder . DIRECTORY_SEPARATOR . '*.sqlite') ?: [];

        $items = [];
        foreach ($files as $path) {
            if (! File::isFile($path)) {
                continue;
            }
            clearstatcache(true, $path);
            $mtime = @filemtime($path) ?: time();
            $size = @filesize($path) ?: 0;

            $items[] = [
                'filename' => basename($path),
                'path' => $path,
                'size_kb' => round($size / 1024, 1),
                'modified_at' => Carbon::createFromTimestamp($mtime),
                'timestamp' => $mtime,
            ];
        }

        usort($items, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        return $items;
    }

    public function getLatestBackupTimestamp(): ?int
    {
        $backups = $this->listBackups();

        return $backups[0]['timestamp'] ?? null;
    }

    /**
     * Return true if no backup exists or the newest backup is older than $days (Adjustment #3: 7 days).
     */
    public function isBackupOverdue(int $days = 7): bool
    {
        $latest = $this->getLatestBackupTimestamp();

        if ($latest === null) {
            return true;
        }

        return $latest < now()->subDays($days)->timestamp;
    }

    /**
     * Create a timestamped SQLite backup copy in the backup folder and prune to the last 30 backups.
     */
    public function createBackup(string $prefix = 'backup'): string
    {
        $sourceDb = $this->getDatabasePath();

        if (! File::exists($sourceDb) || ! File::isFile($sourceDb)) {
            throw new InvalidArgumentException("Active SQLite database file not found at: {$sourceDb}");
        }

        // Flush WAL into main SQLite file when backing up the live connection
        if ($this->databasePath === null) {
            try {
                DB::statement('PRAGMA wal_checkpoint(FULL);');
            } catch (Throwable) {
                // Ignore if not in WAL mode
            }
        }

        $folder = $this->getBackupFolder();
        $safePrefix = preg_replace('/[^a-zA-Z0-9\-_]/', '', $prefix) ?: 'backup';
        $timestamp = now()->format('Y-m-d_His');
        $destination = $folder . DIRECTORY_SEPARATOR . "{$safePrefix}-{$timestamp}.sqlite";

        if (File::exists($destination)) {
            $destination = $folder . DIRECTORY_SEPARATOR . "{$safePrefix}-{$timestamp}-" . substr((string) microtime(true), -4) . '.sqlite';
        }

        File::copy($sourceDb, $destination);
        touch($destination, time());

        $this->pruneOldBackups(30);

        return $destination;
    }

    /**
     * Automatically create a backup if none exists or the newest backup is older than 24 hours.
     */
    public function runStartupBackupIfDue(): ?string
    {
        $sourceDb = $this->getDatabasePath();

        if (! File::exists($sourceDb) || @filesize($sourceDb) === 0) {
            return null;
        }

        $latest = $this->getLatestBackupTimestamp();

        if ($latest !== null && $latest >= now()->subHours(24)->timestamp) {
            return null;
        }

        return $this->createBackup('auto');
    }

    /**
     * Keep the newest $keep backups and delete older ones.
     */
    public function pruneOldBackups(int $keep = 30): int
    {
        $backups = $this->listBackups();

        if (count($backups) <= $keep) {
            return 0;
        }

        $toDelete = array_slice($backups, $keep);
        $deleted = 0;

        foreach ($toDelete as $item) {
            if (File::exists($item['path']) && @File::delete($item['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Validate that a file is a genuine, uncorrupted SQLite 3 database (Adjustment #4).
     */
    public function validateSqliteFile(string $sourceFilePath): void
    {
        if (! File::exists($sourceFilePath) || ! File::isFile($sourceFilePath)) {
            throw new InvalidArgumentException('Invalid SQLite backup file: file does not exist.');
        }

        clearstatcache(true, $sourceFilePath);
        if ((int) @filesize($sourceFilePath) < 16) {
            throw new InvalidArgumentException('Invalid SQLite backup file: file is too small.');
        }

        $handle = @fopen($sourceFilePath, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('Invalid SQLite backup file: unable to read file.');
        }

        $header = fread($handle, 16);
        fclose($handle);

        if ($header !== "SQLite format 3\0") {
            throw new InvalidArgumentException('Invalid SQLite backup file: missing SQLite 3 header.');
        }

        try {
            $pdo = new PDO('sqlite:' . $sourceFilePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $pdo->query('PRAGMA integrity_check;');
            $result = $stmt ? strtolower(trim((string) $stmt->fetchColumn())) : '';
            $pdo = null;

            if ($result !== 'ok') {
                throw new InvalidArgumentException('Invalid SQLite backup file: integrity check failed.');
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid SQLite backup file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate the backup file, auto-backup the current DB first, copy the backup into place,
     * and signal that an application restart is required (Adjustment #4).
     *
     * @return array{pre_restore_backup: string, restored_path: string, requires_restart: bool}
     */
    public function restoreFromFile(string $sourceFilePath): array
    {
        // Step 1: Validate candidate file BEFORE touching current DB
        $this->validateSqliteFile($sourceFilePath);

        $targetDb = $this->getDatabasePath();

        // Step 2: Auto-backup current DB first
        $preRestoreBackup = $this->createBackup('pre-restore');

        // Step 3: Disconnect active SQLite handle if restoring live connection
        if ($this->databasePath === null) {
            try {
                DB::purge();
            } catch (Throwable) {
                // Ignore
            }
        }

        // Step 4: Copy validated backup over target DB and remove stale WAL/SHM files
        File::copy($sourceFilePath, $targetDb);
        File::delete([$targetDb . '-wal', $targetDb . '-shm']);

        if ($this->databasePath === null) {
            try {
                DB::reconnect();
            } catch (Throwable) {
                // Ignore
            }
        }

        return [
            'pre_restore_backup' => $preRestoreBackup,
            'restored_path' => $targetDb,
            'requires_restart' => true,
        ];
    }
}
