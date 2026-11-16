<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetTemplateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisplayPreferenceController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    if (Schema::hasTable('users') && \App\Models\User::count() === 0) {
        return redirect()->route('setup');
    }

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/setup', [SetupController::class, 'show'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:10,1')->name('setup.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'resetWithRecoveryCode'])->middleware('throttle:10,1')->name('password.forgot.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.change.update');

    Route::middleware('force.password')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/dashboard/dismiss-checklist', [DashboardController::class, 'dismissChecklist'])->name('dashboard.dismiss-checklist');
        Route::post('/preferences/display', [DisplayPreferenceController::class, 'update'])->name('preferences.display.update');

        Route::get('/api/assets/quick-search', function (\Illuminate\Http\Request $request) {
            $q = trim((string) $request->query('q', ''));
            if (strlen($q) < 1) {
                return response()->json([]);
            }
            $historyMatches = \App\Models\AssetNumberHistory::where('old_no', 'like', "%{$q}%")
                ->orWhere('new_no', 'like', "%{$q}%")
                ->get()
                ->keyBy('asset_id');

            $historyAssetIds = $historyMatches->keys()->all();

            $assets = \App\Models\Asset::query()
                ->with('vendor:id,name')
                ->where(function ($sub) use ($q, $historyAssetIds) {
                    $sub->where('asset_no', 'like', "%{$q}%")
                        ->orWhereIn('id', $historyAssetIds)
                        ->orWhere('asset_name', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%")
                        ->orWhere('serial_no', 'like', "%{$q}%")
                        ->orWhere('department', 'like', "%{$q}%")
                        ->orWhere('section', 'like', "%{$q}%")
                        ->orWhere('assigned_personnel', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%")
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$q}%"));
                })
                ->limit(8)
                ->get(['id', 'asset_no', 'asset_name', 'brand', 'model', 'department', 'section', 'assigned_personnel', 'location', 'status', 'asset_condition', 'vendor_id'])
                ->map(function ($item) use ($historyMatches) {
                    $res = $item->toArray();
                    $res['vendor_name'] = $item->vendor?->name;
                    unset($res['vendor']);
                    if (isset($historyMatches[$item->id])) {
                        $res['old_number_matched'] = $historyMatches[$item->id]->old_no;
                    }
                    return $res;
                });
            return response()->json($assets);
        })->name('assets.quick-search');

        Route::get('/api/asset-templates/search', [AssetTemplateController::class, 'search'])->name('api.asset-templates.search');

        Route::get('/api/audit/locations', function () {
            $locations = \App\Models\Asset::query()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location');
            return response()->json($locations);
        })->name('audit.locations');

        Route::get('/api/audit/location-assets', function (\Illuminate\Http\Request $request) {
            $loc = trim((string) $request->query('location', ''));
            if ($loc === '') {
                return response()->json([]);
            }
            $assets = \App\Models\Asset::query()
                ->where('location', $loc)
                ->orderBy('asset_no')
                ->get(['id', 'asset_no', 'asset_name', 'department', 'location', 'status', 'model', 'serial_no']);
            return response()->json($assets);
        })->name('audit.location-assets');

        Route::resource('assets', AssetController::class)->except(['destroy'])->withTrashed(['show', 'edit']);
        Route::post('/assets/bulk-category', [AssetController::class, 'bulkSetCategory'])->name('assets.bulk-category');

        Route::get('/tags/print', [TagController::class, 'print'])->name('tags.print');
        Route::get('/tags/pdf', [TagController::class, 'pdf'])->name('tags.pdf');

        Route::get('/find', [ScanController::class, 'index'])->name('assets.find');
        Route::get('/find/lookup', [ScanController::class, 'lookup'])->name('assets.find.lookup');

        Route::get('/scan', function () {
            return redirect()->route('assets.find');
        })->name('scan.index');
        Route::get('/scan/lookup', function (\Illuminate\Http\Request $request) {
            return redirect()->route('assets.find.lookup', $request->query());
        })->name('scan.lookup');

        Route::get('/assets/{asset}/move', [TransferController::class, 'create'])->name('assets.move.create');
        Route::post('/assets/{asset}/move', [TransferController::class, 'store'])->name('assets.move.store');
        Route::get('/departments/{department}/preview-next-number', [DepartmentController::class, 'previewNextNumber'])->name('departments.preview-next-number');

        Route::get('/reports', [ImportExportController::class, 'index'])->name('reports.index');
        Route::get('/reports/print', [ImportExportController::class, 'printReport'])->name('reports.print');
        Route::get('/reports/template', [ImportExportController::class, 'template'])->name('reports.template');
        Route::get('/reports/export', [ImportExportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export-pdf', [ImportExportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::post('/reports/import/preview', [ImportExportController::class, 'preview'])->name('reports.import.preview');
        Route::post('/reports/import/commit', [ImportExportController::class, 'commit'])->name('reports.import.commit');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/pick-folder', [SettingController::class, 'pickFolder'])->name('settings.pick-folder');
        Route::post('/settings/backup', [SettingController::class, 'backup'])->name('settings.backup');

        Route::middleware('admin')->group(function () {
            Route::post('/assets/bulk-archive', [AssetController::class, 'bulkArchive'])->name('assets.bulk-archive');
            Route::post('/assets/bulk-restore', [AssetController::class, 'bulkRestore'])->name('assets.bulk-restore');
            Route::post('/assets/bulk-force-delete', [AssetController::class, 'bulkForceDelete'])->name('assets.bulk-force-delete');
            Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy')->withTrashed();
            Route::post('/assets/{asset}/restore', [AssetController::class, 'restore'])->name('assets.restore')->withTrashed();
            Route::delete('/assets/{asset}/force-delete', [AssetController::class, 'forceDelete'])->name('assets.force-delete')->withTrashed();
            Route::post('/assets/{asset}/revert-number', [AssetController::class, 'revertNumber'])->name('assets.revert-number');
            Route::get('/reports/depreciation', [ImportExportController::class, 'depreciation'])->name('reports.depreciation');
            Route::get('/reports/depreciation/export', [ImportExportController::class, 'exportDepreciation'])->name('reports.depreciation.export');
            Route::get('/reports/depreciation/export-pdf', [ImportExportController::class, 'exportDepreciationPdf'])->name('reports.depreciation.export-pdf');
            Route::get('/settings/activity-log', [ActivityLogController::class, 'index'])->name('settings.activity-log');
            Route::post('/settings/restore', [SettingController::class, 'restore'])->name('settings.restore');
            Route::post('/settings/relaunch', [SettingController::class, 'relaunch'])->name('settings.relaunch');
            Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
            Route::post('/settings/users/{user}/reset-password', [SettingController::class, 'resetUserPassword'])->name('settings.users.reset-password');
            Route::patch('/settings/users/{user}/role', [SettingController::class, 'updateUserRole'])->name('settings.users.role');
            Route::delete('/settings/users/{user}', [SettingController::class, 'destroyUser'])->name('settings.users.destroy');
            Route::post('/settings/users/{user}/recovery-code', [SettingController::class, 'regenerateRecoveryCode'])->name('settings.users.recovery-code');
            Route::post('/settings/vendors', [VendorController::class, 'store'])->name('settings.vendors.store');
            Route::put('/settings/vendors/{vendor}', [VendorController::class, 'update'])->name('settings.vendors.update');
            Route::post('/settings/vendors/{vendor}/deactivate', [VendorController::class, 'deactivate'])->name('settings.vendors.deactivate');
            Route::post('/settings/vendors/{vendor}/reactivate', [VendorController::class, 'reactivate'])->name('settings.vendors.reactivate');
            Route::delete('/settings/vendors/{vendor}', [VendorController::class, 'destroy'])->name('settings.vendors.destroy');
            Route::post('/settings/asset-templates', [AssetTemplateController::class, 'store'])->name('settings.asset-templates.store');
            Route::put('/settings/asset-templates/{assetTemplate}', [AssetTemplateController::class, 'update'])->name('settings.asset-templates.update');
            Route::post('/settings/asset-templates/{assetTemplate}/deactivate', [AssetTemplateController::class, 'deactivate'])->name('settings.asset-templates.deactivate');
            Route::post('/settings/asset-templates/{assetTemplate}/reactivate', [AssetTemplateController::class, 'reactivate'])->name('settings.asset-templates.reactivate');
            Route::delete('/settings/asset-templates/{assetTemplate}', [AssetTemplateController::class, 'destroy'])->name('settings.asset-templates.destroy');
            Route::get('/settings/asset-templates/import-sample', [AssetTemplateController::class, 'importSample'])->name('settings.asset-templates.import-sample');
            Route::post('/settings/asset-templates/import/preview', [AssetTemplateController::class, 'importPreview'])->name('settings.asset-templates.import.preview');
            Route::post('/settings/asset-templates/import/commit', [AssetTemplateController::class, 'importCommit'])->name('settings.asset-templates.import.commit');
            Route::post('/settings/depreciation', [SettingController::class, 'updateDepreciation'])->name('settings.depreciation.update');
            Route::post('/settings/categories', [CategoryController::class, 'store'])->name('settings.categories.store');
            Route::put('/settings/categories/{category}', [CategoryController::class, 'update'])->name('settings.categories.update');
            Route::delete('/settings/categories/{category}', [CategoryController::class, 'destroy'])->name('settings.categories.destroy');
            Route::post('/settings/categories/{category}/mark-reviewed', [CategoryController::class, 'markReviewed'])->name('settings.categories.mark-reviewed');

            Route::post('/settings/departments', [DepartmentController::class, 'store'])->name('settings.departments.store');
            Route::put('/settings/departments/{department}', [DepartmentController::class, 'update'])->name('settings.departments.update');
            Route::delete('/settings/departments/{department}', [DepartmentController::class, 'destroy'])->name('settings.departments.destroy');
            Route::post('/settings/departments/{department}/confirm-code', [DepartmentController::class, 'confirmCode'])->name('settings.departments.confirm-code');
            Route::post('/settings/sections', [SectionController::class, 'store'])->name('settings.sections.store');
            Route::put('/settings/sections/{section}', [SectionController::class, 'update'])->name('settings.sections.update');
            Route::delete('/settings/sections/{section}', [SectionController::class, 'destroy'])->name('settings.sections.destroy');
            Route::post('/settings/sections/{section}/confirm-code', [SectionController::class, 'confirmCode'])->name('settings.sections.confirm-code');
            Route::post('/settings/numbering', [DepartmentController::class, 'updateNumberingSettings'])->name('settings.numbering.update');
        });
    });
});
