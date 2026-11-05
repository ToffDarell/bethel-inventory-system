<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Exports\AssetsTemplateExport;
use App\Exports\DepreciationExport;
use App\Models\Asset;
use App\Models\Category;
use App\Services\AssetImportService;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    public function __construct(
        protected AssetImportService $importService
    ) {}

    public function index(Request $request, DepreciationService $depreciationService)
    {
        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $isAdmin = (bool) $request->user()?->isAdmin();

        $totalCost = 0.0;
        $totalAccumulated = 0.0;
        $totalCurrentValue = 0.0;
        $categorySummary = [];
        $activeDepreciableCount = 0;

        if ($isAdmin) {
            $activeAssets = Asset::query()
                ->with('category')
                ->where('status', '!=', 'Disposed')
                ->get();

            $activeDepreciableCount = $activeAssets->count();

            $missingCostCount = 0;
            $belowMinCostCount = 0;

            foreach ($activeAssets as $asset) {
                $dep = $depreciationService->calculateForAsset($asset);
                $hasCost = ($asset->cost !== null && $asset->cost !== '');
                $c = $hasCost ? (float) $asset->cost : 0.0;
                $isBelowMin = ($dep->getMissingReason() === 'Below minimum cost');

                if (! $hasCost) {
                    $missingCostCount++;
                } elseif ($isBelowMin) {
                    $belowMinCostCount++;
                } else {
                    $acc = $dep->isCalculable() ? (float) $dep->getAccumulatedDepreciation() : 0.0;
                    $cur = $dep->isCalculable() ? (float) $dep->getCurrentValue() : $c;

                    $totalCost += $c;
                    $totalAccumulated += $acc;
                    $totalCurrentValue += $cur;
                }

                $catName = $asset->category?->name ?? 'Uncategorized';
                if (! isset($categorySummary[$catName])) {
                    $categorySummary[$catName] = [
                        'name' => $catName,
                        'count' => 0,
                        'cost_count' => 0,
                        'without_cost_count' => 0,
                        'cost' => 0.0,
                        'accumulated' => 0.0,
                        'current_value' => 0.0,
                    ];
                }
                if ($hasCost && ! $isBelowMin) {
                    $categorySummary[$catName]['cost_count']++;
                    $categorySummary[$catName]['cost'] += $c;
                    $categorySummary[$catName]['accumulated'] += ($dep->isCalculable() ? (float) $dep->getAccumulatedDepreciation() : 0.0);
                    $categorySummary[$catName]['current_value'] += ($dep->isCalculable() ? (float) $dep->getCurrentValue() : $c);
                } else {
                    $categorySummary[$catName]['without_cost_count']++;
                }
                $categorySummary[$catName]['count'] = $categorySummary[$catName]['cost_count'];
            }
        }

        return view('reports.index', [
            'totalAssets' => Asset::count(),
            'departments' => $departments,
            'statuses' => Asset::STATUSES,
            'isAdmin' => $isAdmin,
            'totalCost' => $totalCost,
            'totalAccumulated' => $totalAccumulated,
            'totalCurrentValue' => $totalCurrentValue,
            'categorySummary' => $categorySummary,
            'activeDepreciableCount' => $activeDepreciableCount,
            'belowMinCostCount' => $belowMinCostCount ?? 0,
            'policyConventionLabel' => $depreciationService->getConventionLabel(),
            'policyMinCost' => $depreciationService->getMinCost(),
        ]);
    }

    public function template(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv' || ! class_exists('XMLWriter')) {
            return Excel::download(
                new AssetsTemplateExport,
                'bethel-assets-import-template.csv',
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        return Excel::download(
            new AssetsTemplateExport,
            'bethel-assets-import-template.xlsx'
        );
    }

    public function export(Request $request)
    {
        $department = $request->query('department');
        $status = $request->query('status');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv' || ! class_exists('XMLWriter')) {
            $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.csv';

            return Excel::download(
                new AssetsExport($department, $status, $request->user()),
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AssetsExport($department, $status, $request->user()),
            $filename
        );
    }

    public function depreciation(Request $request, DepreciationService $depreciationService)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can access depreciation reports.');
        }

        $categoryId = $request->query('category');
        $department = $request->query('department');
        $asOfInput = $request->query('as_of');
        if ($asOfInput) {
            try {
                $asOfDate = \Carbon\Carbon::parse($asOfInput)->startOfDay();
            } catch (\Throwable) {
                $asOfDate = \Carbon\Carbon::today();
            }
        } else {
            $asOfDate = \Carbon\Carbon::today();
        }

        $query = Asset::query()
            ->with('category')
            ->where('status', '!=', 'Disposed')
            ->orderBy('asset_no');

        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        if ($department !== null && $department !== '') {
            $query->where('department', $department);
        }

        $assets = $query->paginate(25)->withQueryString();

        $allFilteredQuery = Asset::query()
            ->with('category')
            ->where('status', '!=', 'Disposed');

        if ($categoryId !== null && $categoryId !== '') {
            $allFilteredQuery->where('category_id', $categoryId);
        }

        if ($department !== null && $department !== '') {
            $allFilteredQuery->where('department', $department);
        }

        $allFiltered = $allFilteredQuery->get();

        $totalCost = 0.0;
        $totalSalvage = 0.0;
        $totalAccumulated = 0.0;
        $totalCurrentValue = 0.0;

        $categorySummary = [];
        $missingCostCount = 0;
        $belowMinCostCount = 0;

        foreach ($allFiltered as $asset) {
            $dep = $depreciationService->calculateForAsset($asset, $asOfDate);
            $hasCost = ($asset->cost !== null && $asset->cost !== '');
            $c = $hasCost ? (float) $asset->cost : 0.0;
            $s = ($asset->salvage_value !== null && $asset->salvage_value !== '') ? (float) $asset->salvage_value : 0.0;
            $isBelowMin = ($dep->getMissingReason() === 'Below minimum cost');

            if (! $hasCost) {
                $missingCostCount++;
            } elseif ($isBelowMin) {
                $belowMinCostCount++;
            } else {
                $acc = $dep->isCalculable() ? (float) $dep->getAccumulatedDepreciation() : 0.0;
                $cur = $dep->isCalculable() ? (float) $dep->getCurrentValue() : $c;

                $totalCost += $c;
                $totalSalvage += $s;
                $totalAccumulated += $acc;
                $totalCurrentValue += $cur;
            }

            $catName = $asset->category?->name ?? 'Uncategorized';
            if (! isset($categorySummary[$catName])) {
                $categorySummary[$catName] = [
                    'name' => $catName,
                    'count' => 0,
                    'cost_count' => 0,
                    'without_cost_count' => 0,
                    'cost' => 0.0,
                    'accumulated' => 0.0,
                    'current_value' => 0.0,
                ];
            }
            if ($hasCost && ! $isBelowMin) {
                $categorySummary[$catName]['cost_count']++;
                $categorySummary[$catName]['cost'] += $c;
                $categorySummary[$catName]['accumulated'] += ($dep->isCalculable() ? (float) $dep->getAccumulatedDepreciation() : 0.0);
                $categorySummary[$catName]['current_value'] += ($dep->isCalculable() ? (float) $dep->getCurrentValue() : $c);
            } else {
                $categorySummary[$catName]['without_cost_count']++;
            }
            $categorySummary[$catName]['count'] = $categorySummary[$catName]['cost_count'];
        }

        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $categories = Category::orderBy('name')->get();

        return view('reports.depreciation', [
            'assets' => $assets,
            'departments' => $departments,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'selectedDepartment' => $department,
            'asOfDate' => $asOfDate,
            'totalCost' => $totalCost,
            'totalSalvage' => $totalSalvage,
            'totalAccumulated' => $totalAccumulated,
            'totalCurrentValue' => $totalCurrentValue,
            'missingCostCount' => $missingCostCount,
            'belowMinCostCount' => $belowMinCostCount,
            'policyConventionLabel' => $depreciationService->getConventionLabel(),
            'policyMinCost' => $depreciationService->getMinCost(),
            'categorySummary' => $categorySummary,
            'depreciationService' => $depreciationService,
        ]);
    }

    public function exportDepreciation(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can export depreciation reports.');
        }

        $category = $request->query('category');
        $department = $request->query('department');
        $asOfInput = $request->query('as_of');
        if ($asOfInput) {
            try {
                $asOfDate = \Carbon\Carbon::parse($asOfInput)->startOfDay();
            } catch (\Throwable) {
                $asOfDate = \Carbon\Carbon::today();
            }
        } else {
            $asOfDate = \Carbon\Carbon::today();
        }

        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv' || ! class_exists('XMLWriter')) {
            $filename = 'bethel-depreciation-report-' . $asOfDate->format('Y-m-d') . '.csv';

            return Excel::download(
                new DepreciationExport($category, $department, $asOfDate),
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        $filename = 'bethel-depreciation-report-' . $asOfDate->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DepreciationExport($category, $department, $asOfDate),
            $filename
        );
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Please choose an Excel (.xlsx, .xls) or CSV (.csv) file to upload.',
            'file.mimes' => 'The file must be an Excel spreadsheet (.xlsx, .xls) or CSV (.csv).',
        ]);

        $preview = $this->importService->preview($request->file('file'));

        session(['asset_import_preview' => $preview['rows']]);

        return view('reports.import-preview', $preview);
    }

    public function commit(Request $request)
    {
        $rows = session('asset_import_preview', []);

        if (empty($rows)) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['file' => 'No import preview found. Please upload your spreadsheet again.']);
        }

        $result = $this->importService->commit($rows, $request->user());
        session()->forget('asset_import_preview');

        return redirect()
            ->route('reports.index')
            ->with(
                'success',
                "Excel import complete: {$result['imported']} asset(s) added, {$result['skipped']} duplicate or incomplete row(s) skipped."
            );
    }
}
