<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Services\DepreciationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DepreciationService $depreciationService)
    {
        $isAdmin = (bool) $request->user()?->isAdmin();

        $totalAssets = Asset::count();

        $statusCounts = Asset::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $departmentCounts = Asset::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(department), ''), 'Unassigned') as dept_name, COUNT(*) as total")
            ->groupBy('dept_name')
            ->orderByDesc('total')
            ->orderBy('dept_name')
            ->get();

        $recentAssets = Asset::query()
            ->with('category')
            ->latest('id')
            ->take(8)
            ->get();

        // Calculate Category breakdown and Total Asset Value
        $activeAssets = Asset::query()
            ->where('status', '!=', 'Disposed')
            ->get();

        $totalAssetValue = 0.0;
        $categoryValues = [];

        if ($isAdmin) {
            foreach ($activeAssets as $asset) {
                if ($asset->cost !== null) {
                    $dep = $depreciationService->calculateForAsset($asset);
                    $val = $dep->isCalculable() ? ($dep->getCurrentValue() ?? (float) $asset->cost) : (float) $asset->cost;
                    $totalAssetValue += $val;

                    $catKey = $asset->category_id ?: 0;
                    $categoryValues[$catKey] = ($categoryValues[$catKey] ?? 0.0) + $val;
                }
            }
        }

        $allCategories = Category::withCount(['assets' => function ($query) {
            $query->where('status', '!=', 'Disposed');
        }])->orderBy('name')->get();

        $uncategorizedCount = Asset::whereNull('category_id')->where('status', '!=', 'Disposed')->count();

        $categoryBreakdown = [];
        foreach ($allCategories as $cat) {
            $categoryBreakdown[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'count' => $cat->assets_count,
                'value' => $categoryValues[$cat->id] ?? 0.0,
            ];
        }

        if ($uncategorizedCount > 0) {
            $categoryBreakdown[] = [
                'id' => null,
                'name' => 'Uncategorized',
                'count' => $uncategorizedCount,
                'value' => $categoryValues[0] ?? 0.0,
            ];
        }

        return view('dashboard', [
            'totalAssets' => $totalAssets,
            'statusCounts' => $statusCounts,
            'departmentCounts' => $departmentCounts,
            'recentAssets' => $recentAssets,
            'totalAssetValue' => $totalAssetValue,
            'categoryBreakdown' => $categoryBreakdown,
            'isAdmin' => $isAdmin,
        ]);
    }
}
