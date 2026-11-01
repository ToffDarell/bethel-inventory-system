<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\File;

function createValidSqliteFile(string $path, string $markerTable = 'marker_default'): void
{
    File::ensureDirectoryExists(dirname($path));
    if (File::exists($path)) {
        File::delete($path);
    }
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE {$markerTable} (id INTEGER PRIMARY KEY, note TEXT);");
    $pdo->exec("INSERT INTO {$markerTable} (note) VALUES ('ok');");
    $pdo = null;
}

beforeEach(function () {
    $this->tempBackupDir = storage_path('framework/testing/backups_' . uniqid());
    $this->tempSourceDb = storage_path('framework/testing/source_' . uniqid() . '.sqlite');

    File::ensureDirectoryExists($this->tempBackupDir);
    createValidSqliteFile($this->tempSourceDb, 'source_table');

    Setting::set('backup_folder', $this->tempBackupDir);
});

afterEach(function () {
    File::deleteDirectory($this->tempBackupDir);
    File::delete($this->tempSourceDb);
    File::delete($this->tempSourceDb . '-wal');
    File::delete($this->tempSourceDb . '-shm');
});

test('creates timestamped backup, checks 24h auto-backup and 7-day warning thresholds, and prunes beyond 30 backups', function () {
    $service = new BackupService($this->tempSourceDb);

    // No backups yet -> 7-day warning should be true
    expect($service->isBackupOverdue(7))->toBeTrue();

    // Seed 31 old backup files (8 to 38 days old)
    for ($i = 1; $i <= 31; $i++) {
        $file = $this->tempBackupDir . sprintf('/backup-2026-01-%02d_120000.sqlite', $i);
        createValidSqliteFile($file, 'old_backup_' . $i);
        $daysAgo = $i + 7;
        touch($file, strtotime("-{$daysAgo} days"));
    }

    // Newest backup is 8 days old -> 7-day warning is still true
    expect($service->isBackupOverdue(7))->toBeTrue();

    $createdPath = $service->createBackup('manual');
    expect(File::exists($createdPath))->toBeTrue();

    // Pruned to 30 total backups
    $files = glob($this->tempBackupDir . '/*.sqlite');
    expect(count($files))->toBe(30);

    // Now that a backup was just created, 7-day warning is false and 24h startup backup skips
    expect($service->isBackupOverdue(7))->toBeFalse()
        ->and($service->runStartupBackupIfDue())->toBeNull();
});

test('rejects invalid non-SQLite file on restore and auto-backs up current DB before restoring valid SQLite backup', function () {
    $service = new BackupService($this->tempSourceDb);

    $corruptFile = $this->tempBackupDir . '/corrupt.sqlite';
    File::put($corruptFile, 'NOT A SQLITE FILE');

    expect(fn () => $service->restoreFromFile($corruptFile))
        ->toThrow(InvalidArgumentException::class);

    $validBackup = storage_path('framework/testing/valid_restore_' . uniqid() . '.sqlite');
    createValidSqliteFile($validBackup, 'restored_marker_table');

    $result = $service->restoreFromFile($validBackup);

    expect($result['requires_restart'])->toBeTrue()
        ->and(File::exists($result['pre_restore_backup']))->toBeTrue()
        ->and(basename($result['pre_restore_backup']))->toStartWith('pre-restore-');

    // Verify the active database now has the restored table
    $pdo = new PDO('sqlite:' . $this->tempSourceDb);
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='restored_marker_table'")->fetchAll();
    $pdo = null;

    expect(count($tables))->toBe(1);

    File::delete($validBackup);
});

test('settings page shows 7-day warning when no backup exists, updates settings, and restricts restore to admin', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($admin)
        ->get('/settings')
        ->assertOk()
        ->assertSee('No database backup has been created in the last 7 days');

    $this->actingAs($admin)
        ->post('/settings', [
            'hospital_name' => 'Bethel Baptist Hospital, Inc.',
            'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
            'asset_no_prefix' => 'BBH',
            'tag_width_cm' => '10',
            'tag_height_cm' => '6',
            'backup_folder' => $this->tempBackupDir,
        ])
        ->assertRedirect('/settings');

    $this->actingAs($staff)
        ->post('/settings/restore', ['backup_filename' => 'any.sqlite'])
        ->assertForbidden();
});
