<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use App\Services\DesktopBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'admin_test',
    ]);

    $this->category = Category::create([
        'name' => 'Biomedical Equipment',
        'code' => 'BIO',
        'useful_life_years' => 5,
        'salvage_percentage' => 10,
    ]);

    $this->asset = Asset::create([
        'asset_no' => 'BBH-EXP-001',
        'asset_name' => 'High-End MRI Scanner',
        'category_id' => $this->category->id,
        'cost' => 15000000.00,
        'salvage_value' => 1500000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2020-01-15',
        'status' => 'In Use',
        'department' => 'Radiology',
    ]);
});

test('admin downloads depreciation report and temp file is deleted right after send without sensitive data on disk', function () {
    $response = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=xlsx');
    $response->assertOk();

    $tempFilePath = $response->getFile()->getPathname();
    expect(file_exists($tempFilePath))->toBeTrue();

    // Stream download to client (simulating download completion)
    ob_start();
    $response->baseResponse->sendContent();
    ob_end_clean();

    // Confirm temp file is deleted right after download
    expect(file_exists($tempFilePath))->toBeFalse();

    // Confirm no file with cost or depreciation values remains on disk
    $cacheDir = storage_path('framework/cache/laravel-excel');
    if (is_dir($cacheDir)) {
        $remainingFiles = glob($cacheDir . DIRECTORY_SEPARATOR . 'laravel-excel-*') ?: [];
        foreach ($remainingFiles as $file) {
            $content = file_get_contents($file);
            expect($content)->not->toContain('15000000');
            expect($content)->not->toContain('15,000,000');
            expect($content)->not->toContain('High-End MRI Scanner');
        }
    }
});

test('admin downloads csv depreciation report and temp file is deleted right after send', function () {
    $response = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=csv');
    $response->assertOk();

    $tempFilePath = $response->getFile()->getPathname();
    expect(file_exists($tempFilePath))->toBeTrue();

    // Stream download to client
    ob_start();
    $response->baseResponse->sendContent();
    ob_end_clean();

    // Confirm temp file is deleted right after download
    expect(file_exists($tempFilePath))->toBeFalse();
});

test('startup cleanup removes temp files older than 1 day and preserves recent files', function () {
    $cacheDir = storage_path('framework/cache/laravel-excel');
    if (! is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $oldFile = $cacheDir . DIRECTORY_SEPARATOR . 'laravel-excel-unit-test-old.xlsx';
    $newFile = $cacheDir . DIRECTORY_SEPARATOR . 'laravel-excel-unit-test-new.xlsx';

    file_put_contents($oldFile, 'legacy test export');
    touch($oldFile, time() - (2 * 86400)); // 2 days old

    file_put_contents($newFile, 'recent test export');
    touch($newFile, time()); // brand new

    expect(file_exists($oldFile))->toBeTrue();
    expect(file_exists($newFile))->toBeTrue();

    $bootstrap = app(DesktopBootstrapService::class);
    $deletedCount = $bootstrap->cleanupLegacyExcelTempFiles(86400);

    expect(file_exists($oldFile))->toBeFalse();
    expect(file_exists($newFile))->toBeTrue();
    expect($deletedCount)->toBeGreaterThanOrEqual(1);

    // Clean up temporary test file
    @unlink($newFile);
});
