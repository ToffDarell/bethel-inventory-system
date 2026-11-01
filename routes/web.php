<?php

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

        // Placeholder named routes for sidebar navigation in Module 1; replaced in Modules 2-7
        Route::get('/assets', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('assets.index');
        Route::get('/scan', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('scan.index');
        Route::get('/reports', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('reports.index');
        Route::get('/settings', fn () => view('dashboard', ['totalAssets' => Asset::count(), 'statusCounts' => []]))->name('settings.index');
    });
});
