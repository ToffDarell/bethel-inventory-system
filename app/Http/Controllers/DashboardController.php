<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Setting;
use App\Services\DepreciationService;
use Illuminate\Http\RedirectResponse;
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

        // Calculate Category breakdown (counts only, no monetary valuation on dashboard)
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
            ];
        }

        if ($uncategorizedCount > 0) {
            $categoryBreakdown[] = [
                'id' => null,
                'name' => 'Uncategorized',
                'count' => $uncategorizedCount,
            ];
        }

        // Actionable attention list counts from real queries
        $underRepairCount = (int) ($statusCounts['Under Repair'] ?? 0);
        $unassignedLocationCount = Asset::query()
            ->where(function ($q) {
                $q->whereNull('location')->orWhere('location', '');
            })
            ->count();
        $damagedCount = Asset::query()
            ->where('asset_condition', 'Damage')
            ->count();
        $reprintNeededCount = Asset::query()
            ->where('needs_tag_reprint', true)
            ->count();
        $backupOverdue = app(\App\Services\BackupService::class)->isBackupOverdue(7);
        $minCost = $depreciationService->getMinCost();
        $belowMinCostCount = ($isAdmin && $minCost > 0)
            ? Asset::query()->whereNotNull('cost')->where('cost', '<', $minCost)->count()
            : 0;

        $checklistDismissed = Setting::get('admin_checklist_dismissed', '0') === '1';
        $firstRunChecklist = [];
        $checklistCompleted = 0;

        if ($isAdmin && ! $checklistDismissed) {
            $firstRunChecklist = [
                [
                    'key' => 'hospital_profile',
                    'label' => 'Verify hospital name and address',
                    'done' => trim((string) Setting::get('hospital_name', '')) !== '',
                    'url' => route('settings.index', ['tab' => 'hospital']),
                ],
                [
                    'key' => 'recovery_code',
                    'label' => 'Save an administrator recovery code',
                    'done' => ! empty($request->user()?->recovery_code_hash),
                    'url' => route('settings.index', ['tab' => 'users']),
                ],
                [
                    'key' => 'categories_reviewed',
                    'label' => 'Review category disposal percentages',
                    'done' => Category::count() > 0 && ! Category::where('disposal_reviewed', false)->exists(),
                    'url' => route('settings.index', ['tab' => 'categories']),
                ],
                [
                    'key' => 'departments_confirmed',
                    'label' => 'Confirm department property tag codes',
                    'done' => Department::count() > 0 && ! Department::where('code_confirmed', false)->exists(),
                    'url' => route('settings.index', ['tab' => 'departments']),
                ],
                [
                    'key' => 'backup_created',
                    'label' => 'Create your first data backup',
                    'done' => trim((string) Setting::get('last_backup_at', '')) !== '',
                    'url' => route('settings.index', ['tab' => 'backup']),
                ],
                [
                    'key' => 'first_asset',
                    'label' => 'Register or import your first asset',
                    'done' => $totalAssets > 0,
                    'url' => route('assets.create'),
                ],
            ];
            $checklistCompleted = collect($firstRunChecklist)->where('done', true)->count();
        }

        return view('dashboard', [
            'totalAssets' => $totalAssets,
            'statusCounts' => $statusCounts,
            'departmentCounts' => $departmentCounts,
            'recentAssets' => $recentAssets,
            'categoryBreakdown' => $categoryBreakdown,
            'isAdmin' => $isAdmin,
            'underRepairCount' => $underRepairCount,
            'unassignedLocationCount' => $unassignedLocationCount,
            'damagedCount' => $damagedCount,
            'reprintNeededCount' => $reprintNeededCount,
            'backupOverdue' => $backupOverdue,
            'belowMinCostCount' => $belowMinCostCount,
            'noCategoryCount' => $uncategorizedCount,
            'firstRunChecklist' => $firstRunChecklist,
            'checklistCompleted' => $checklistCompleted,
            'checklistDismissed' => $checklistDismissed,
        ]);
    }

    public function dismissChecklist(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        Setting::set('admin_checklist_dismissed', '1');

        activity()
            ->causedBy($request->user())
            ->log('Dismissed first-run setup checklist on dashboard');

        return redirect()->route('dashboard');
    }
}
