<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransferController;
use App\Models\Asset;
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
        Route::get('/dashboard', function () {
            $totalAssets = Asset::count();
            $statusCounts = Asset::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all();

            return view('dashboard', [
                'totalAssets' => $totalAssets,
                'statusCounts' => $statusCounts,
            ]);
        })->name('dashboard');

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

        // Placeholder named route for Module 7
        Route::get('/settings', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('settings.index');
    });
});
