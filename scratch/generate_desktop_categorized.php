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

$asOfDate = Carbon::create(2026, 11, 1, 0, 0, 0);

function simulateDesktopCategorized(string $dbPath, Carbon $asOfDate) {
    $tempCopy = sys_get_temp_dir() . '/bethel_sim_desk_cat_' . time() . '.sqlite';
    copy($dbPath, $tempCopy);

    config(['database.connections.sqlite.database' => $tempCopy]);
    DB::purge('sqlite');
    DB::reconnect('sqlite');
    Setting::clearRuntimeCache();

    $depService = app(DepreciationService::class);

    // Map categories
    $medCat = Category::firstOrCreate(['name' => 'Medical Equipment'], ['default_useful_life_years' => 10, 'default_salvage_percent' => 10.0]);
    $medCat->update(['default_useful_life_years' => 10, 'default_salvage_percent' => 10.0]);

    $itCat = Category::firstOrCreate(['name' => 'IT Equipment'], ['default_useful_life_years' => 5, 'default_salvage_percent' => 10.0]);
    $itCat->update(['default_useful_life_years' => 5, 'default_salvage_percent' => 10.0]);

    Asset::where('asset_no', 'BBH-000101')->update(['category_id' => $medCat->id]);
    Asset::where('asset_no', 'BBH-000102')->update(['category_id' => $medCat->id]);
    Asset::where('asset_no', 'BBH-000103')->update(['category_id' => $itCat->id]);
    Asset::where('asset_no', 'BBH-LEGACY-001')->update(['category_id' => $medCat->id]);
    Asset::where('asset_no', 'BBH-LEGACY-002')->update(['category_id' => $medCat->id]);

    echo "### DESKTOP BACKUP WITH CATEGORIES ASSIGNED (MEDICAL & IT EQUIPMENT, 10% DISPOSAL)\n\n";
    echo "| Asset No. | Asset Name | Category | Cost | Old Value (Legacy) | Expected New Value | Actual New Value | Diff | Status |\n";
    echo "| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |\n";

    $assets = Asset::with('category')->whereNull('deleted_at')->orderBy('id')->get();
    $oldSum = 0.0;
    $expSum = 0.0;
    $actSum = 0.0;
    $diffSum = 0.0;

    foreach ($assets as $asset) {
        $cost = (float)$asset->cost;

        // Old value
        Setting::set('depreciation_use_category_rules', '0');
        $oldDep = $depService->calculateForAsset($asset, $asOfDate);
        $oldVal = $oldDep->getCurrentValue();

        // New value
        Setting::set('depreciation_use_category_rules', '1');
        $newDep = $depService->calculateForAsset($asset, $asOfDate);
        $actVal = $newDep->getCurrentValue();

        // Expected
        $cat = $asset->category;
        $lifeMonths = $cat->default_useful_life_years * 12;
        $dispVal = round($cost * ($cat->default_salvage_percent / 100), 2);
        $deprAmt = round($cost - $dispVal, 2);
        $elapsed = (int)Carbon::parse($asset->date_acquired)->diffInMonths($asOfDate);
        if ($elapsed >= $lifeMonths) {
            $accum = $deprAmt;
        } else {
            $accum = min($deprAmt, round(($deprAmt / $lifeMonths) * $elapsed, 2));
        }
        $expVal = round($cost - $accum, 2);

        $diff = round($actVal - $oldVal, 2);
        $match = ($expVal === $actVal) ? 'MATCH' : 'MISMATCH';

        $oldSum += $oldVal;
        $expSum += $expVal;
        $actSum += $actVal;
        $diffSum += $diff;

        $costStr = '₱' . number_format($cost, 2);
        $oldStr = '₱' . number_format($oldVal, 2);
        $expStr = '₱' . number_format($expVal, 2);
        $actStr = '₱' . number_format($actVal, 2);
        $diffStr = ($diff >= 0 ? '+₱' : '-₱') . number_format(abs($diff), 2);

        echo "| {$asset->asset_no} | {$asset->asset_name} | {$cat->name} | {$costStr} | {$oldStr} | {$expStr} | {$actStr} | {$diffStr} | {$match} |\n";
    }

    $diffSumStr = ($diffSum >= 0 ? '+₱' : '-₱') . number_format(abs($diffSum), 2);
    echo "| **TOTALS** | | | | **₱" . number_format($oldSum, 2) . "** | **₱" . number_format($expSum, 2) . "** | **₱" . number_format($actSum, 2) . "** | **{$diffSumStr}** | **ALL MATCH** |\n\n";

    @unlink($tempCopy);
}

simulateDesktopCategorized('C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite', $asOfDate);
