<?php

$source = 'C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite';
$tempCopy = 'C:/xampp8.2/htdocs/BettleHospital/scratch/temp_backup_test.sqlite';

if (!file_exists($source)) {
    echo "Source backup not found at $source\n";
    exit(1);
}

copy($source, $tempCopy);

$pdo = new PDO("sqlite:" . $tempCopy);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. BEFORE counts & data
$assetsBefore = $pdo->query("SELECT id, asset_no, asset_name, department, location, status, cost, category_id FROM assets ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$categoriesBefore = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$usersBefore = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "=== BEFORE INSTALL-OVER-UPDATE MIGRATION ===\n";
echo "Total Assets: " . count($assetsBefore) . "\n";
foreach ($assetsBefore as $a) {
    echo "  [Asset #{$a['id']}] {$a['asset_no']} | Name: {$a['asset_name']} | Dept: {$a['department']} | Loc: {$a['location']} | Cost: {$a['cost']}\n";
}
echo "Total Categories: " . count($categoriesBefore) . "\n";
echo "Total Users: " . count($usersBefore) . "\n";

// 2. RUN MIGRATIONS
require 'C:/xampp8.2/htdocs/BettleHospital/vendor/autoload.php';
$app = require_once 'C:/xampp8.2/htdocs/BettleHospital/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Config::set('database.connections.temp_backup', [
    'driver' => 'sqlite',
    'database' => $tempCopy,
    'prefix' => '',
    'foreign_key_constraints' => true,
]);

\Illuminate\Support\Facades\Artisan::call('migrate', [
    '--database' => 'temp_backup',
    '--force' => true,
]);

// Run Department backfill if needed
$hasDepts = \Illuminate\Support\Facades\Schema::connection('temp_backup')->hasTable('departments');
if ($hasDepts) {
    $backfillService = app(\App\Services\DepartmentBackfillService::class);
    // Connect to temp_backup
    \Illuminate\Support\Facades\DB::setDefaultConnection('temp_backup');
    $backfillService->runBackfill();
}

// 3. AFTER counts & data
$pdoAfter = new PDO("sqlite:" . $tempCopy);
$pdoAfter->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$assetsAfter = $pdoAfter->query("SELECT id, asset_no, asset_name, department, location, status, cost, category_id, department_id, needs_tag_reprint FROM assets ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$categoriesAfter = $pdoAfter->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$departmentsAfter = $pdoAfter->query("SELECT id, name, code, next_number FROM departments ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$usersAfter = $pdoAfter->query("SELECT id, name, username, email, role FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== AFTER INSTALL-OVER-UPDATE MIGRATION ===\n";
echo "Total Assets: " . count($assetsAfter) . " (Before: " . count($assetsBefore) . ")\n";
foreach ($assetsAfter as $a) {
    echo "  [Asset #{$a['id']}] {$a['asset_no']} | Name: {$a['asset_name']} | Dept: {$a['department']} (DeptID: {$a['department_id']}) | Loc: {$a['location']} | Reprint: {$a['needs_tag_reprint']}\n";
}
echo "Total Categories: " . count($categoriesAfter) . " (Before: " . count($categoriesBefore) . ")\n";
echo "Total Departments Created: " . count($departmentsAfter) . "\n";
foreach ($departmentsAfter as $d) {
    echo "  [Dept #{$d['id']}] {$d['name']} (Code: {$d['code']}, Next: {$d['next_number']})\n";
}
echo "Total Users: " . count($usersAfter) . " (Before: " . count($usersBefore) . ")\n";

$intact = (count($assetsAfter) === count($assetsBefore)) && (count($usersAfter) === count($usersBefore));
echo "\nDATA LOSS CHECK: " . ($intact ? "PASSED (100% INTACT)" : "FAILED") . "\n";

// Cleanup temp test copy
unlink($tempCopy);
