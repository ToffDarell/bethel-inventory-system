<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use App\Services\DesktopBootstrapService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$source = $argv[1] ?? 'C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite';
$label = $argv[2] ?? 'Desktop Backup';

if (!file_exists($source)) {
    echo "Source not found: $source\n";
    exit(1);
}

$tempDir = sys_get_temp_dir() . '/bethel_test_' . uniqid();
mkdir($tempDir, 0777, true);
$tempDb = $tempDir . '/database.sqlite';
copy($source, $tempDb);

// Inspect BEFORE
$pdoBefore = new PDO("sqlite:" . $tempDb);
$countBefore = (int)$pdoBefore->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$activeBefore = (int)$pdoBefore->query("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL")->fetchColumn();
$assetsBefore = $pdoBefore->query("SELECT asset_no, asset_name, cost FROM assets WHERE deleted_at IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "--- $label ---\n";
echo "Source: $source\n";
echo "BEFORE UPDATE:\n";
echo "  Total Assets:  $countBefore\n";
echo "  Active Assets: $activeBefore\n";
foreach ($assetsBefore as $a) {
    $costStr = $a['cost'] !== null ? '₱' . number_format((float)$a['cost'], 2) : 'No cost';
    echo "  - [{$a['asset_no']}] {$a['asset_name']} ({$costStr})\n";
}

// Switch DB and run bootstrap
config(['database.connections.sqlite.database' => $tempDb]);
DB::purge('sqlite');
DB::reconnect('sqlite');
Setting::clearRuntimeCache();

Artisan::call('migrate', ['--force' => true]);
app(DesktopBootstrapService::class)->boot();

// Inspect AFTER
$pdoAfter = new PDO("sqlite:" . $tempDb);
$countAfter = (int)$pdoAfter->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$activeAfter = (int)$pdoAfter->query("SELECT COUNT(*) FROM assets WHERE deleted_at IS NULL")->fetchColumn();

// Check if department_id column exists
$hasDeptCol = false;
$cols = $pdoAfter->query("PRAGMA table_info(assets)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    if ($c['name'] === 'department_id') $hasDeptCol = true;
}

$selectSql = $hasDeptCol 
    ? "SELECT asset_no, asset_name, cost, department_id FROM assets WHERE deleted_at IS NULL ORDER BY id ASC"
    : "SELECT asset_no, asset_name, cost FROM assets WHERE deleted_at IS NULL ORDER BY id ASC";
$assetsAfter = $pdoAfter->query($selectSql)->fetchAll(PDO::FETCH_ASSOC);

$deptCount = (int)$pdoAfter->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$ruleSetting = Setting::get('depreciation_use_category_rules');

echo "\nAFTER UPDATE:\n";
echo "  Total Assets:  $countAfter (Preserved: " . ($countBefore === $countAfter ? "YES" : "NO - DATA LOSS") . ")\n";
echo "  Active Assets: $activeAfter (Preserved: " . ($activeBefore === $activeAfter ? "YES" : "NO - DATA LOSS") . ")\n";
echo "  Departments:   $deptCount departments in system\n";
echo "  Rule Setting:  depreciation_use_category_rules = " . var_export($ruleSetting, true) . " (" . ($ruleSetting === '0' ? "OFF - Safe default for existing DB" : "ON") . ")\n";
foreach ($assetsAfter as $a) {
    $costStr = $a['cost'] !== null ? '₱' . number_format((float)$a['cost'], 2) : 'No cost';
    $deptStr = isset($a['department_id']) ? "Dept ID: " . ($a['department_id'] ?? 'null') : '';
    echo "  - [{$a['asset_no']}] {$a['asset_name']} ({$costStr}) $deptStr\n";
}

$success = ($countBefore === $countAfter && $activeBefore === $activeAfter);
echo "\nStatus: " . ($success ? "PASSED (ZERO DATA LOSS)" : "FAILED") . "\n";
echo str_repeat('-', 70) . "\n\n";

@unlink($tempDb);
@rmdir($tempDir);
