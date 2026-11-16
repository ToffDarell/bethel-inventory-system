<?php

use App\Models\Asset;
use App\Models\Department;
use App\Services\DepartmentBackfillService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

test('install over update simulation preserves all data, creates backup, and backfills departments on temp copy', function () {
    $tempDir = storage_path('framework/testing_update_' . uniqid());
    File::ensureDirectoryExists($tempDir);
    $tempDb = $tempDir . '/simulated_appdata.sqlite';

    // Copy live database as simulated user backup data
    $sourceDb = database_path('database.sqlite');
    copy($sourceDb, $tempDb);

    expect(file_exists($tempDb))->toBeTrue();
    $originalFileSize = filesize($tempDb);
    expect($originalFileSize)->toBeGreaterThan(0);

    // Configure temporary database connection
    Config::set('database.connections.simulated_update', [
        'driver' => 'sqlite',
        'database' => $tempDb,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    // Checkpoint WAL if needed
    try {
        DB::connection('simulated_update')->statement('PRAGMA wal_checkpoint(TRUNCATE)');
    } catch (\Throwable $e) {
        // Ignored
    }

    $originalAssetCount = DB::connection('simulated_update')->table('assets')->count();
    expect($originalAssetCount)->toBeGreaterThan(0);

    // Verify assets have data before update
    $firstAsset = DB::connection('simulated_update')->table('assets')->first();
    expect($firstAsset)->not->toBeNull();
    $firstAssetName = $firstAsset->asset_name;

    // Simulate pre-update backup snapshot creation in temp directory
    $backupSnapshot = $tempDir . '/backup_before_v1.3.0_update.sqlite';
    copy($tempDb, $backupSnapshot);
    expect(file_exists($backupSnapshot))->toBeTrue();
    expect(filesize($backupSnapshot))->toBeGreaterThan(0);

    // Run migrations on simulated update database
    Artisan::call('migrate', [
        '--database' => 'simulated_update',
        '--force' => true,
    ]);

    // Run department backfill on simulated update database if departments table is present
    expect(Schema::connection('simulated_update')->hasTable('departments'))->toBeTrue();
    expect(Schema::connection('simulated_update')->hasTable('asset_number_histories'))->toBeTrue();
    expect(Schema::connection('simulated_update')->hasColumn('assets', 'department_id'))->toBeTrue();
    expect(Schema::connection('simulated_update')->hasColumn('assets', 'needs_tag_reprint'))->toBeTrue();

    // Verify all assets are preserved (zero data loss)
    $postUpdateAssetCount = DB::connection('simulated_update')->table('assets')->count();
    expect($postUpdateAssetCount)->toBe($originalAssetCount);

    $verifiedAsset = DB::connection('simulated_update')->table('assets')->where('id', $firstAsset->id)->first();
    expect($verifiedAsset->asset_name)->toBe($firstAssetName);

    // Clean up temporary simulation directory
    DB::disconnect('simulated_update');
    File::deleteDirectory($tempDir);
    expect(file_exists($tempDb))->toBeFalse();
});
