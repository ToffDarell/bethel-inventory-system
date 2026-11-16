<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asset;
use App\Models\Department;
use App\Models\Setting;
use App\Services\BackupService;
use App\Services\DesktopBootstrapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== INSTALL-OVER-UPDATE SIMULATION ON TEMPORARY BACKUP DATA ===\n\n";

$sourceBackups = [
    'Desktop Backup (v1.0.0 Real Data)' => 'C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite',
    'Downloads Backup Clone' => base_path('Downloads/pre-migration-2026-11-05_193355.sqlite'),
];

foreach ($sourceBackups as $label => $sourcePath) {
    if (!file_exists($sourcePath)) {
        echo "Skip: $sourcePath not found.\n\n";
        continue;
    }

    echo "--- Testing: $label ---\n";
    echo "Source: $sourcePath\n";

    $tempDir = sys_get_temp_dir() . '/bethel_update_test_' . uniqid();
    mkdir($tempDir, 0777, true);
    $tempDb = $tempDir . '/database.sqlite';
    copy($sourcePath, $tempDb);

    // 1. Inspect BEFORE update
    $pdoBefore = new PDO("sqlite:" . $tempDb);
    $stmtCount = $pdoBefore->query("SELECT COUNT(*) FROM assets");
    $countBefore = (int)$stmtCount->fetchColumn();

    $stmtActive = $pdoBefore->query("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL");
    $activeBefore = (int)$stmtActive->fetchColumn();

    $stmtNames = $pdoBefore->query("SELECT asset_no, asset_name, cost FROM assets WHERE deleted_at IS NULL ORDER BY id ASC");
    $assetsBefore = $stmtNames->fetchAll(PDO::FETCH_ASSOC);

    echo "BEFORE UPDATE:\n";
    echo "  Total Assets:  $countBefore\n";
    echo "  Active Assets: $activeBefore\n";
    foreach ($assetsBefore as $a) {
        $costStr = $a['cost'] !== null ? '₱' . number_format((float)$a['cost'], 2) : 'No cost';
        echo "  - [{$a['asset_no']}] {$a['asset_name']} ({$costStr})\n";
    }

    // 2. Perform Update Simulation
    config(['database.connections.sqlite.database' => $tempDb]);
    DB::purge('sqlite');
    DB::reconnect('sqlite');
    Setting::clearRuntimeCache();

    // Run DesktopBootstrapService boot sequence
    $bootstrap = app(DesktopBootstrapService::class);
    $bootstrap->boot();

    // 3. Inspect AFTER update
    $pdoAfter = new PDO("sqlite:" . $tempDb);
    $stmtCountAfter = $pdoAfter->query("SELECT COUNT(*) FROM assets");
    $countAfter = (int)$stmtCountAfter->fetchColumn();

    $stmtActiveAfter = $pdoAfter->query("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL");
    $activeAfter = (int)$stmtActiveAfter->fetchColumn();

    $stmtNamesAfter = $pdoAfter->query("SELECT asset_no, asset_name, cost, department_id FROM assets WHERE deleted_at IS NULL ORDER BY id ASC");
    $assetsAfter = $stmtNamesAfter->fetchAll(PDO::FETCH_ASSOC);

    $deptCount = (int)$pdoAfter->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    $ruleSetting = Setting::get('depreciation_use_category_rules');

    echo "\nAFTER UPDATE (v1.3.1 Installed Over Existing Data):\n";
    echo "  Total Assets:  $countAfter (Preserved: " . ($countBefore === $countAfter ? "YES" : "NO - DATA LOSS!") . ")\n";
    echo "  Active Assets: $activeAfter (Preserved: " . ($activeBefore === $activeAfter ? "YES" : "NO - DATA LOSS!") . ")\n";
    echo "  Backfilled Departments: $deptCount departments\n";
    echo "  Category Rules Switch:  " . ($ruleSetting === '0' ? "OFF (Preserved legacy data until admin approval)" : "ON ($ruleSetting)") . "\n";
    foreach ($assetsAfter as $a) {
        $costStr = $a['cost'] !== null ? '₱' . number_format((float)$a['cost'], 2) : 'No cost';
        echo "  - [{$a['asset_no']}] {$a['asset_name']} ({$costStr}) -> Dept ID: " . ($a['department_id'] ?? 'null') . "\n";
    }

    echo "\nVerification Result: " . ($countBefore === $countAfter && $activeBefore === $activeAfter ? "PASSED (ZERO DATA LOSS)" : "FAILED") . "\n";
    echo str_repeat('=', 70) . "\n\n";

    // Cleanup temp dir
    @unlink($tempDb);
    @rmdir($tempDir);
}
