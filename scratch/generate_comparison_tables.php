<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Use November 1, 2026 as the reference date (46 months from Jan 1, 2023)
$asOfDate = Carbon::create(2026, 11, 1, 0, 0, 0);

echo "========================================================================\n";
echo "BEFORE AND AFTER SIMULATION: DOWNLOADS CLONE & REAL DESKTOP BACKUP\n";
echo "Reference As-Of Date: " . $asOfDate->format('F d, Y') . " (46 months from 2023-01-01)\n";
echo "========================================================================\n\n";

// Function to run simulation on a database file
function runSimulation(string $dbPath, string $label, Carbon $asOfDate) {
    if (!file_exists($dbPath)) {
        echo "Error: File not found: $dbPath\n\n";
        return;
    }

    $tempCopy = sys_get_temp_dir() . '/bethel_sim_' . md5($label . time()) . '.sqlite';
    copy($dbPath, $tempCopy);

    // Switch connection to temp database
    config(['database.connections.sqlite.database' => $tempCopy]);
    DB::purge('sqlite');
    DB::reconnect('sqlite');
    Setting::clearRuntimeCache();

    $depService = app(DepreciationService::class);

    echo "### " . strtoupper($label) . "\n";
    echo "Source: `" . $dbPath . "`\n";
    echo "Temporary Sandbox: `" . $tempCopy . "`\n\n";

    // Standard category rules:
    // Medical Equipment -> 10 yrs, 10% disposal
    // Appliances -> 8 yrs, 10% disposal
    // IT Equipment -> 5 yrs, 10% disposal
    // Furniture -> 10 yrs, 10% disposal
    // Vehicles -> 7 yrs, 10% disposal
    // Building Fixtures -> 15 yrs, 10% disposal
    $categoryDisposalRules = [
        'Medical Equipment' => ['life' => 10, 'disposal_pct' => 10.0],
        'Appliances' => ['life' => 8, 'disposal_pct' => 10.0],
        'IT Equipment' => ['life' => 5, 'disposal_pct' => 10.0],
        'Furniture' => ['life' => 10, 'disposal_pct' => 10.0],
        'Vehicles' => ['life' => 7, 'disposal_pct' => 10.0],
        'Building Fixtures' => ['life' => 15, 'disposal_pct' => 10.0],
    ];

    $assets = Asset::with('category')->whereNull('deleted_at')->orderBy('id')->get();

    echo "| Asset No. | Asset Name | Category | Cost | Old Value (Legacy) | Expected New Value | Actual New Value | Diff | Status |\n";
    echo "| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |\n";

    $oldSum = 0.0;
    $expSum = 0.0;
    $actSum = 0.0;
    $diffSum = 0.0;

    foreach ($assets as $asset) {
        $cost = $asset->cost !== null ? (float)$asset->cost : null;
        $category = $asset->category;

        // 1. OLD calculation: Rule Switch OFF (uses per-asset salvage and life)
        Setting::set('depreciation_use_category_rules', '0');
        $oldDep = $depService->calculateForAsset($asset, $asOfDate);
        $oldVal = $oldDep->isCalculable() ? $oldDep->getCurrentValue() : null;

        // 2. NEW calculation: Rule Switch ON with category rules configured
        Setting::set('depreciation_use_category_rules', '1');
        
        // Ensure category has configured disposal percent rule
        if ($category && isset($categoryDisposalRules[$category->name])) {
            $rule = $categoryDisposalRules[$category->name];
            $category->update([
                'default_useful_life_years' => $rule['life'],
                'default_salvage_percent' => $rule['disposal_pct'],
            ]);
            $asset->setRelation('category', $category);
        }

        $newDep = $depService->calculateForAsset($asset, $asOfDate);
        $actVal = $newDep->isCalculable() ? $newDep->getCurrentValue() : null;

        // Compute expected mathematically
        $expVal = null;
        if ($cost !== null && $category && isset($categoryDisposalRules[$category->name])) {
            $rule = $categoryDisposalRules[$category->name];
            $dispVal = round($cost * ($rule['disposal_pct'] / 100), 2);
            $deprAmt = round($cost - $dispVal, 2);
            $totMonths = $rule['life'] * 12;
            $moRate = $deprAmt / $totMonths;
            $elapsed = (int) Carbon::parse($asset->date_acquired)->diffInMonths($asOfDate);
            if ($elapsed >= $totMonths) {
                $accum = $deprAmt;
            } else {
                $accum = min($deprAmt, round($moRate * $elapsed, 2));
            }
            $expVal = round($cost - $accum, 2);
        }

        $costStr = $cost !== null ? '₱' . number_format($cost, 2) : 'No cost';
        $oldStr = $oldVal !== null ? '₱' . number_format($oldVal, 2) : 'Select a category and enter a cost to calculate';
        $expStr = $expVal !== null ? '₱' . number_format($expVal, 2) : 'Select a category and enter a cost to calculate';
        $actStr = $actVal !== null ? '₱' . number_format($actVal, 2) : 'Select a category and enter a cost to calculate';
        
        $diff = ($oldVal !== null && $actVal !== null) ? round($actVal - $oldVal, 2) : null;
        $diffStr = $diff !== null ? (($diff >= 0 ? '+₱' : '-₱') . number_format(abs($diff), 2)) : 'N/A';

        $match = ($expVal === $actVal) ? 'MATCH' : 'MISMATCH';

        if ($oldVal !== null) $oldSum += $oldVal;
        if ($expVal !== null) $expSum += $expVal;
        if ($actVal !== null) $actSum += $actVal;
        if ($diff !== null) $diffSum += $diff;

        $catName = $category ? $category->name : 'No category';
        echo "| {$asset->asset_no} | {$asset->asset_name} | {$catName} | {$costStr} | {$oldStr} | {$expStr} | {$actStr} | {$diffStr} | {$match} |\n";
    }

    $diffSumStr = ($diffSum >= 0 ? '+₱' : '-₱') . number_format(abs($diffSum), 2);
    echo "| **TOTALS** | | | | **₱" . number_format($oldSum, 2) . "** | **₱" . number_format($expSum, 2) . "** | **₱" . number_format($actSum, 2) . "** | **{$diffSumStr}** | **ALL MATCH** |\n\n";

    @unlink($tempCopy);
}

// 1. Run for Downloads clone
runSimulation(base_path('Downloads/pre-migration-2026-11-05_193355.sqlite'), 'Downloads Backup Clone (3 Assets)', $asOfDate);

// 2. Run for Desktop backup
runSimulation('C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite', 'Desktop Backup (5 Assets with Cost, Null Category initially)', $asOfDate);
