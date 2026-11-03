<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.change.update');

    Route::middleware('force.password')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/api/assets/quick-search', function (\Illuminate\Http\Request $request) {
            $q = trim((string) $request->query('q', ''));
            if (strlen($q) < 1) {
                return response()->json([]);
            }
            $assets = \App\Models\Asset::query()
                ->where('asset_no', 'like', "%{$q}%")
                ->orWhere('asset_name', 'like', "%{$q}%")
                ->orWhere('department', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
                ->limit(8)
                ->get(['id', 'asset_no', 'asset_name', 'department', 'location', 'status']);
            return response()->json($assets);
        })->name('assets.quick-search');

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

        Route::resource('assets', AssetController::class);

        Route::get('/tags/print', [TagController::class, 'print'])->name('tags.print');
        Route::get('/tags/pdf', [TagController::class, 'pdf'])->name('tags.pdf');

        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::get('/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');

        Route::get('/assets/{asset}/move', [TransferController::class, 'create'])->name('assets.move.create');
        Route::post('/assets/{asset}/move', [TransferController::class, 'store'])->name('assets.move.store');

        Route::get('/reports', [ImportExportController::class, 'index'])->name('reports.index');
        Route::get('/reports/template', [ImportExportController::class, 'template'])->name('reports.template');
        Route::get('/reports/export', [ImportExportController::class, 'export'])->name('reports.export');
        Route::post('/reports/import/preview', [ImportExportController::class, 'preview'])->name('reports.import.preview');
        Route::post('/reports/import/commit', [ImportExportController::class, 'commit'])->name('reports.import.commit');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/pick-folder', [SettingController::class, 'pickFolder'])->name('settings.pick-folder');
        Route::post('/settings/backup', [SettingController::class, 'backup'])->name('settings.backup');

        Route::middleware('admin')->group(function () {
            Route::get('/settings/activity-log', [ActivityLogController::class, 'index'])->name('settings.activity-log');
            Route::post('/settings/restore', [SettingController::class, 'restore'])->name('settings.restore');
            Route::post('/settings/relaunch', [SettingController::class, 'relaunch'])->name('settings.relaunch');
            Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
        });
    });
});
