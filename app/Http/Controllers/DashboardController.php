<?php

namespace App\Http\Controllers;

use App\Models\Asset;

class DashboardController extends Controller
{
    public function __invoke()
    {
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
            ->latest('id')
            ->take(8)
            ->get();

        return view('dashboard', [
            'totalAssets' => $totalAssets,
            'statusCounts' => $statusCounts,
            'departmentCounts' => $departmentCounts,
            'recentAssets' => $recentAssets,
        ]);
    }
}
