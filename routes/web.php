<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
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

        // Placeholder named routes for Modules 3-7
        Route::get('/tags/print', fn () => redirect()->route('assets.index'))->name('tags.print');
        Route::get('/assets/{asset}/move', fn (Asset $asset) => redirect()->route('assets.show', $asset))->name('assets.move.create');
        Route::get('/scan', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('scan.index');
        Route::get('/reports', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('reports.index');
        Route::get('/settings', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('settings.index');
    });
});
