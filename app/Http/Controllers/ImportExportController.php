<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Exports\AssetsTemplateExport;
use App\Exports\DepreciationExport;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\AssetImportService;
use App\Services\DepreciationService;
use App\Services\TagAssetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'vendors' => \Illuminate\Support\Facades\Schema::hasTable('vendors')
                ? Vendor::orderBy('name')->get(['id', 'name', 'is_donation'])
                : collect(),
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

    public function printReport(Request $request, DepreciationService $depreciationService)
    {
        $reportType = strtolower(trim((string) $request->query('type', 'category')));
        if (! in_array($reportType, ['category', 'department', 'vendor', 'date_acquired'], true)) {
            $reportType = 'category';
        }

        $categoryId = $request->query('category_id');
        $department = trim((string) $request->query('department', ''));
        $vendorId = $request->query('vendor_id');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $yearFilter = trim((string) $request->query('year', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $query = Asset::query()
            ->with(['category:id,name', 'vendor:id,name,is_donation'])
            ->orderBy('asset_no');

        if ($categoryId !== null && $categoryId !== '') {
            if ($categoryId === 'uncategorized') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', (int) $categoryId);
            }
        }

        if ($department !== '') {
            $query->where('department', $department);
        }

        if ($vendorId !== null && $vendorId !== '') {
            if ($vendorId === 'none') {
                $query->whereNull('vendor_id');
            } elseif ($vendorId === 'donations') {
                $query->whereHas('vendor', fn ($q) => $q->where('is_donation', true));
            } else {
                $query->where('vendor_id', (int) $vendorId);
            }
        }

        if ($yearFilter !== '' && preg_match('/^\d{4}$/', $yearFilter)) {
            $query->whereYear('date_acquired', (int) $yearFilter);
        } else {
            if ($dateFrom !== '') {
                $query->whereDate('date_acquired', '>=', $dateFrom);
            }
            if ($dateTo !== '') {
                $query->whereDate('date_acquired', '<=', $dateTo);
            }
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $assets = $query->get();
        $isAdmin = (bool) $request->user()?->isAdmin();

        $reportTitles = [
            'category' => 'ASSET BY CATEGORY LIST',
            'department' => 'ASSET BY DEPARTMENT LIST',
            'vendor' => 'ASSET BY VENDOR LIST',
            'date_acquired' => 'ASSET BY DATE ACQUIRED LIST',
        ];

        $primaryPrefix = [
            'category' => 'ASSET CATEGORY',
            'department' => 'DEPARTMENT',
            'vendor' => 'VENDOR / SUPPLIER',
            'date_acquired' => 'YEAR ACQUIRED',
        ][$reportType];

        $secondaryPrefix = $reportType === 'department' ? 'ASSET CATEGORY' : 'DEPARTMENT';

        $groups = [];
        $grandCount = 0;
        $grandPurchase = 0.0;
        $grandCurrent = 0.0;

        foreach ($assets as $asset) {
            $catName = strtoupper($asset->category?->name ?? 'UNCATEGORIZED');
            $deptName = strtoupper(trim((string) ($asset->department ?: 'UNASSIGNED')));
            if ($asset->section) {
                $deptName .= ' - ' . strtoupper(trim((string) $asset->section));
            }
            $vendorName = strtoupper($asset->vendor?->name ?? 'NO VENDOR RECORDED');
            if ($asset->vendor?->is_donation) {
                $vendorName .= ' (DONATION)';
            }
            $yearAcquired = $asset->date_acquired ? $asset->date_acquired->format('Y') : 'NO DATE RECORDED';

            $primaryKey = match ($reportType) {
                'category' => $catName,
                'department' => $deptName,
                'vendor' => $vendorName,
                'date_acquired' => $yearAcquired,
            };

            $secondaryKey = $reportType === 'department' ? $catName : $deptName;

            if (! isset($groups[$primaryKey])) {
                $groups[$primaryKey] = [
                    'label' => $primaryKey,
                    'count' => 0,
                    'purchase_total' => 0.0,
                    'current_total' => 0.0,
                    'subgroups' => [],
                ];
            }

            if (! isset($groups[$primaryKey]['subgroups'][$secondaryKey])) {
                $groups[$primaryKey]['subgroups'][$secondaryKey] = [
                    'label' => $secondaryKey,
                    'count' => 0,
                    'purchase_total' => 0.0,
                    'current_total' => 0.0,
                    'rows' => [],
                ];
            }

            $row = [
                'id' => $asset->id,
                'asset_no' => $asset->asset_no,
                'asset_name' => $asset->asset_name,
                'brand_model' => trim(($asset->brand ?? '') . ($asset->model ? ' / ' . $asset->model : '')),
                'serial_no' => $asset->serial_no ?: '-',
                'acquired' => $asset->date_acquired ? $asset->date_acquired->format('M d, Y') : '-',
                'status' => $asset->status ?? 'Active',
                'condition' => $asset->asset_condition ?? 'In Good Condition',
                'vendor_name' => $asset->vendor?->name ?? '-',
                'is_donation' => (bool) $asset->vendor?->is_donation,
            ];

            if ($isAdmin) {
                $dep = $depreciationService->calculateForAsset($asset);
                $purchase = ($asset->cost !== null && $asset->cost !== '') ? (float) $asset->cost : 0.0;
                $current = $dep->isCalculable() ? (float) $dep->getCurrentValue() : $purchase;

                $row['purchase'] = $purchase;
                $row['current'] = $current;

                $groups[$primaryKey]['purchase_total'] += $purchase;
                $groups[$primaryKey]['current_total'] += $current;
                $groups[$primaryKey]['subgroups'][$secondaryKey]['purchase_total'] += $purchase;
                $groups[$primaryKey]['subgroups'][$secondaryKey]['current_total'] += $current;
                $grandPurchase += $purchase;
                $grandCurrent += $current;
            }

            $groups[$primaryKey]['count']++;
            $groups[$primaryKey]['subgroups'][$secondaryKey]['count']++;
            $groups[$primaryKey]['subgroups'][$secondaryKey]['rows'][] = $row;
            $grandCount++;
        }

        if ($reportType === 'date_acquired') {
            krsort($groups);
        } else {
            ksort($groups);
        }

        foreach ($groups as &$primaryGroup) {
            ksort($primaryGroup['subgroups']);
        }
        unset($primaryGroup);

        $departmentsList = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $yearsList = Asset::query()
            ->whereNotNull('date_acquired')
            ->selectRaw("strftime('%Y', date_acquired) as yr")
            ->distinct()
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter();

        return view('reports.print-report', [
            'reportType' => $reportType,
            'reportTitle' => $reportTitles[$reportType],
            'primaryPrefix' => $primaryPrefix,
            'secondaryPrefix' => $secondaryPrefix,
            'groups' => $groups,
            'grandCount' => $grandCount,
            'grandPurchase' => $grandPurchase,
            'grandCurrent' => $grandCurrent,
            'isAdmin' => $isAdmin,
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'logoDataUri' => app(TagAssetService::class)->hospitalLogoBase64(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'departments' => $departmentsList,
            'vendors' => \Illuminate\Support\Facades\Schema::hasTable('vendors')
                ? Vendor::orderBy('name')->get(['id', 'name', 'is_donation'])
                : collect(),
            'years' => $yearsList,
            'statuses' => Asset::STATUSES,
            'filters' => [
                'category_id' => $categoryId,
                'department' => $department,
                'vendor_id' => $vendorId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'year' => $yearFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function template(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $response = Excel::download(
                new AssetsTemplateExport('csv'),
                'bethel-assets-import-template.csv',
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        } else {
            $response = Excel::download(
                new AssetsTemplateExport('xlsx'),
                'bethel-assets-import-template.xlsx'
            );
        }

        $this->ensureDeleteAfterSend($response);

        return $response;
    }

    public function export(Request $request)
    {
        $department = $request->query('department');
        $status = $request->query('status');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            return $this->exportPdf($request);
        }

        if ($format === 'csv') {
            $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.csv';

            $response = Excel::download(
                new AssetsExport($department, $status, $request->user(), 'csv'),
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        } else {
            $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.xlsx';

            $response = Excel::download(
                new AssetsExport($department, $status, $request->user(), 'xlsx'),
                $filename
            );
        }

        $this->ensureDeleteAfterSend($response);

        return $response;
    }

    public function exportPdf(Request $request)
    {
        $department = $request->query('department');
        $status = $request->query('status');

        $query = Asset::query()
            ->with('category')
            ->orderBy('asset_no');

        if ($department !== null && $department !== '') {
            $query->where('department', $department);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $count = $query->count();
        if ($count > 1000) {
            return back()->with('error', "The selected export exceeds the 1,000-record PDF limit ({$count} assets found). Please narrow your filters or download as Excel.");
        }

        $assets = $query->get();
        $isAdmin = (bool) $request->user()?->isAdmin();
        $depService = app(DepreciationService::class);

        $items = [];
        $totalCost = 0.0;
        $totalCurrentValue = 0.0;

        foreach ($assets as $asset) {
            $categoryName = $asset->category?->name ?? 'Uncategorized';
            $yearBought = $asset->date_acquired?->format('Y') ?? '-';
            $condition = $asset->asset_condition ?? 'In Good Condition';

            if ($isAdmin) {
                $dep = $depService->calculateForAsset($asset);
                $costVal = ($asset->cost !== null && $asset->cost !== '') ? (float) $asset->cost : null;

                if ($costVal !== null) {
                    $totalCost += $costVal;
                }

                if ($dep->isCalculable()) {
                    $totalCurrentValue += $dep->getCurrentValue();
                    $currentFormatted = '₱' . number_format($dep->getCurrentValue(), 2);
                } elseif ($costVal !== null) {
                    $totalCurrentValue += $costVal;
                    $currentFormatted = '₱' . number_format($costVal, 2);
                } else {
                    $currentFormatted = '-';
                }

                $items[] = [
                    'asset_no' => $asset->asset_no,
                    'asset_name' => $asset->asset_name,
                    'brand' => $asset->brand ?? '-',
                    'model' => $asset->model ?? '-',
                    'serial_no' => $asset->serial_no ?? '-',
                    'department' => $asset->department ?? '-',
                    'section' => $asset->section ?? '-',
                    'assigned_personnel' => $asset->assigned_personnel ?? '-',
                    'category' => $categoryName,
                    'year_bought' => $yearBought,
                    'condition' => $condition,
                    'status' => $asset->status,
                    'cost_raw' => $costVal,
                    'cost_formatted' => $costVal !== null ? '₱' . number_format($costVal, 2) : 'No cost entered',
                    'current_formatted' => $currentFormatted,
                ];
            } else {
                $items[] = [
                    'asset_no' => $asset->asset_no,
                    'asset_name' => $asset->asset_name,
                    'brand' => $asset->brand ?? '-',
                    'model' => $asset->model ?? '-',
                    'serial_no' => $asset->serial_no ?? '-',
                    'department' => $asset->department ?? '-',
                    'section' => $asset->section ?? '-',
                    'assigned_personnel' => $asset->assigned_personnel ?? '-',
                    'category' => $categoryName,
                    'year_bought' => $yearBought,
                    'condition' => $condition,
                    'status' => $asset->status,
                ];
            }
        }

        $paperSize = strtolower((string) Setting::get('pdf_paper_size', 'a4'));
        if (! in_array($paperSize, ['a4', 'letter', 'legal'], true)) {
            $paperSize = 'a4';
        }

        $data = [
            'items' => $items,
            'isAdmin' => $isAdmin,
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'logoDataUri' => app(TagAssetService::class)->hospitalLogoBase64(),
            'username' => $request->user()?->username ?? 'staff',
            'deptFilter' => $department ?: 'All Departments',
            'statusFilter' => $status ?: 'All Statuses',
            'paperSize' => $paperSize,
            'totalCost' => $totalCost,
            'totalCurrentValue' => $totalCurrentValue,
            'conventionLabel' => $depService->getConventionLabel(),
            'minCostText' => $depService->getMinCost() > 0 ? '₱' . number_format($depService->getMinCost(), 2) : 'None',
        ];

        activity()
            ->causedBy($request->user())
            ->log('Exported asset inventory as PDF');

        $pdf = Pdf::loadView('reports.pdf.inventory', $data)->setPaper($paperSize, 'landscape');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(40, $h - 26, 'Bethel Baptist Hospital, Inc. - Asset Inventory Report', $font, 7.5, [0.39, 0.45, 0.55]);
        $canvas->page_text($w - 95, $h - 26, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7.5, [0.39, 0.45, 0.55]);

        $filename = 'bethel-assets-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
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
                $asOfDate = Carbon::parse($asOfInput)->startOfDay();
            } catch (\Throwable) {
                $asOfDate = Carbon::today();
            }
        } else {
            $asOfDate = Carbon::today();
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
                if ($dep->isCalculable() && $dep->getDisposalValue() !== null) {
                    $totalSalvage += (float) $dep->getDisposalValue();
                }
            }

            $catName = $asset->category?->name;
            if ($catName && strtolower($catName) !== 'uncategorized') {
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

        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $categories = Category::orderBy('name')->get();

        return view('reports.depreciation', [
            'assets' => $assets,
            'allFilteredAssets' => $allFiltered,
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'logoDataUri' => app(TagAssetService::class)->hospitalLogoBase64(),
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
                $asOfDate = Carbon::parse($asOfInput)->startOfDay();
            } catch (\Throwable) {
                $asOfDate = Carbon::today();
            }
        } else {
            $asOfDate = Carbon::today();
        }

        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            return $this->exportDepreciationPdf($request, app(DepreciationService::class));
        }

        if ($format === 'csv') {
            $filename = 'bethel-depreciation-report-' . $asOfDate->format('Y-m-d') . '.csv';

            $response = Excel::download(
                new DepreciationExport($category, $department, $asOfDate, 'csv', $request->user()),
                $filename,
                \Maatwebsite\Excel\Excel::CSV,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        } else {
            $filename = 'bethel-depreciation-report-' . $asOfDate->format('Y-m-d') . '.xlsx';

            $response = Excel::download(
                new DepreciationExport($category, $department, $asOfDate, 'xlsx', $request->user()),
                $filename
            );
        }

        $this->ensureDeleteAfterSend($response);

        return $response;
    }

    public function exportDepreciationPdf(Request $request, DepreciationService $depreciationService)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can export depreciation reports.');
        }

        $category = $request->query('category');
        $department = $request->query('department');
        $asOfInput = $request->query('as_of');
        if ($asOfInput) {
            try {
                $asOfDate = Carbon::parse($asOfInput)->startOfDay();
            } catch (\Throwable) {
                $asOfDate = Carbon::today();
            }
        } else {
            $asOfDate = Carbon::today();
        }

        $query = Asset::query()
            ->with('category')
            ->where('status', '!=', 'Disposed')
            ->orderBy('asset_no');

        if ($category !== null && $category !== '') {
            $query->where('category_id', $category);
        }

        if ($department !== null && $department !== '') {
            $query->where('department', $department);
        }

        $count = $query->count();
        if ($count > 1000) {
            return back()->with('error', "The selected depreciation export exceeds the 1,000-record PDF limit ({$count} assets found). Please narrow your filters or download as Excel.");
        }

        $assets = $query->get();

        $items = [];
        $totalCost = 0.0;
        $totalSalvage = 0.0;
        $totalAccumulated = 0.0;
        $totalCurrentValue = 0.0;

        foreach ($assets as $asset) {
            $dep = $depreciationService->calculateForAsset($asset, $asOfDate);
            $hasCost = ($asset->cost !== null && $asset->cost !== '');
            $costVal = $hasCost ? (float) $asset->cost : null;
            $salvageVal = ($dep->isCalculable() && $dep->getDisposalValue() !== null) ? (float) $dep->getDisposalValue() : null;

            if ($costVal !== null) {
                $totalCost += $costVal;
            }
            if ($salvageVal !== null) {
                $totalSalvage += $salvageVal;
            }

            $accumIsMuted = false;
            if ($dep->isCalculable()) {
                $acc = (float) $dep->getAccumulatedDepreciation();
                $cur = (float) $dep->getCurrentValue();
                $totalAccumulated += $acc;
                $totalCurrentValue += $cur;
                $accumFormatted = '₱' . number_format($acc, 2);
                $currentFormatted = '₱' . number_format($cur, 2);
            } elseif ($dep->getMissingReason() === 'Below minimum cost') {
                $accumFormatted = 'Below min cost';
                $accumIsMuted = true;
                if ($costVal !== null) {
                    $totalCurrentValue += $costVal;
                    $currentFormatted = '₱' . number_format($costVal, 2);
                } else {
                    $currentFormatted = '-';
                }
            } else {
                $accumFormatted = '-';
                if ($costVal !== null) {
                    $totalCurrentValue += $costVal;
                    $currentFormatted = '₱' . number_format($costVal, 2);
                } else {
                    $currentFormatted = '-';
                }
            }

            $items[] = [
                'asset_no' => $asset->asset_no,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category?->name ?? 'Uncategorized',
                'department' => $asset->department ?? '-',
                'year_bought' => $asset->date_acquired ? $asset->date_acquired->format('Y') : '-',
                'start_date' => $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '-',
                'months' => $dep->isCalculable() ? $dep->getMonthsElapsed() : '-',
                'useful_life' => $dep->isCalculable() ? $dep->getUsefulLifeYears() . ' yrs' : ($asset->category?->default_useful_life_years ? $asset->category->default_useful_life_years . ' yrs' : '-'),
                'cost_raw' => $costVal,
                'cost_formatted' => $costVal !== null ? '₱' . number_format($costVal, 2) : 'No cost entered',
                'salvage_formatted' => $salvageVal !== null ? '₱' . number_format($salvageVal, 2) : '-',
                'accum_formatted' => $accumFormatted,
                'accum_is_muted' => $accumIsMuted,
                'current_formatted' => $currentFormatted,
            ];
        }

        $paperSize = strtolower((string) Setting::get('pdf_paper_size', 'a4'));
        if (! in_array($paperSize, ['a4', 'letter', 'legal'], true)) {
            $paperSize = 'a4';
        }

        $data = [
            'items' => $items,
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'logoDataUri' => app(TagAssetService::class)->hospitalLogoBase64(),
            'username' => $request->user()?->username ?? 'admin',
            'asOfDate' => $asOfDate,
            'paperSize' => $paperSize,
            'totalCost' => $totalCost,
            'totalSalvage' => $totalSalvage,
            'totalAccumulated' => $totalAccumulated,
            'totalCurrentValue' => $totalCurrentValue,
            'conventionLabel' => $depreciationService->getConventionLabel(),
            'minCostText' => $depreciationService->getMinCost() > 0 ? '₱' . number_format($depreciationService->getMinCost(), 2) : 'None',
        ];

        activity()
            ->causedBy($request->user())
            ->log('Exported depreciation report as PDF');

        $pdf = Pdf::loadView('reports.pdf.depreciation', $data)->setPaper($paperSize, 'landscape');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(40, $h - 26, 'Bethel Baptist Hospital, Inc. - Estimated values for tracking', $font, 7.5, [0.39, 0.45, 0.55]);
        $canvas->page_text($w - 95, $h - 26, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7.5, [0.39, 0.45, 0.55]);

        $filename = 'bethel-depreciation-report-' . $asOfDate->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Ensure the temporary Excel/CSV file is deleted immediately after sending to client.
     */
    protected function ensureDeleteAfterSend(mixed $response): void
    {
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $response->deleteFileAfterSend(true);
            $filePath = $response->getFile()->getPathname();

            if (! app()->runningUnitTests()) {
                app()->terminating(function () use ($filePath) {
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                });
            }
        }
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
