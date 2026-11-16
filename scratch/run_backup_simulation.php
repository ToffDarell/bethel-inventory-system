<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\DepreciationService;
use Carbon\Carbon;

$sourceFile = 'Downloads/pre-migration-2026-11-05_193355.sqlite';
$tempCopy = sys_get_temp_dir() . '/bethel_backup_simulation_' . time() . '.sqlite';
copy($sourceFile, $tempCopy);

$pdo = new PDO("sqlite:" . $tempCopy);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$depService = new DepreciationService();
$asOfDate = Carbon::now();

echo "=== SIMULATION REPORT ON TEMPORARY BACKUP CLONE ===" . PHP_EOL;
echo "Source Backup: " . $sourceFile . PHP_EOL;
echo "Temp Clone: " . $tempCopy . PHP_EOL;
echo "As-Of Date: " . $asOfDate->format('F j, Y') . " (" . (int)$asOfDate->diffInMonths(Carbon::create(2023, 1, 1)) . " months from Jan 2023)" . PHP_EOL . PHP_EOL;

// Fetch categories
$stmtCat = $pdo->query("SELECT * FROM categories");
$categories = [];
while ($row = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
    $categories[$row['id']] = $row;
}

// Fetch all non-deleted assets
$stmt = $pdo->query("SELECT * FROM assets WHERE deleted_at IS NULL ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$oldTotal = 0.0;
$newTotal = 0.0;
$diffTotal = 0.0;

$report = [];

foreach ($rows as $asset) {
    $catId = $asset['category_id'];
    $category = $categories[$catId] ?? null;
    $cost = $asset['cost'] !== null && $asset['cost'] !== '' ? (float)$asset['cost'] : null;
    $acquired = $asset['date_acquired'];
    $yearOnly = (bool)$asset['acquired_year_only'];

    // 1. OLD calculation: per-asset salvage and life
    $oldRes = $depService->calculate(
        $cost,
        $asset['salvage_value'],
        $asset['useful_life_years'],
        $acquired,
        $asOfDate,
        $yearOnly
    );

    // 2. NEW calculation: category life and category disposal %
    $newLife = $category ? (int)$category['default_useful_life_years'] : null;
    $newDisposalPct = $category ? (float)$category['default_salvage_percent'] : 0.0;
    $newDisposalVal = ($cost !== null && $newDisposalPct > 0) ? round($cost * ($newDisposalPct / 100), 2) : 0.0;

    $newRes = $depService->calculate(
        $cost,
        $newDisposalVal,
        $newLife,
        $acquired,
        $asOfDate,
        $yearOnly
    );

    $oldVal = $oldRes->isCalculable() ? $oldRes->getCurrentValue() : null;
    $newVal = $newRes->isCalculable() ? $newRes->getCurrentValue() : null;

    if ($oldVal !== null) $oldTotal += $oldVal;
    if ($newVal !== null) $newTotal += $newVal;

    $diff = ($oldVal !== null && $newVal !== null) ? round($newVal - $oldVal, 2) : null;
    if ($diff !== null) $diffTotal += $diff;

    $report[] = [
        'asset_no' => $asset['asset_no'],
        'asset_name' => $asset['asset_name'],
        'category' => $category ? $category['name'] : 'No category',
        'cost' => $cost !== null ? '₱' . number_format($cost, 2) : 'No cost',
        'old_value' => $oldVal !== null ? '₱' . number_format($oldVal, 2) : 'Select a category and enter a cost to calculate',
        'new_value' => $newVal !== null ? '₱' . number_format($newVal, 2) : 'Select a category and enter a cost to calculate',
        'diff' => $diff !== null ? ($diff >= 0 ? '+₱' : '-₱') . number_format(abs($diff), 2) : 'N/A',
        'old_num' => $oldVal,
        'new_num' => $newVal,
        'diff_num' => $diff,
    ];
}

@unlink($tempCopy);

echo json_encode([
    'assets' => $report,
    'total_old' => '₱' . number_format($oldTotal, 2),
    'total_new' => '₱' . number_format($newTotal, 2),
    'total_diff' => ($diffTotal >= 0 ? '+₱' : '-₱') . number_format(abs($diffTotal), 2),
    'old_total_num' => $oldTotal,
    'new_total_num' => $newTotal,
    'diff_total_num' => $diffTotal,
], JSON_PRETTY_PRINT);
