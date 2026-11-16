<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DesktopBootstrapService
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    public function boot(): void
    {
        try {
            // Write active database path, file size, and asset count to the application log
            $dbPath = $this->backupService->getDatabasePath();
            $fileSize = file_exists($dbPath) ? filesize($dbPath) : 0;
            $assetCount = Schema::hasTable('assets') ? Asset::count() : 0;
            Log::info('Desktop application started', [
                'database_path' => $dbPath,
                'file_size_bytes' => $fileSize,
                'asset_count' => $assetCount,
            ]);

            // Run pending additive migrations (BackupDatabaseBeforeMigration listener backs up existing DB first)
            Artisan::call('migrate', ['--force' => true]);

            // Seed default settings and categories if settings table is empty (dev accounts only seeded if local)
            if (Schema::hasTable('settings')) {
                app(DatabaseSeeder::class)->run();
            }

            // Seed default categories if categories table is empty
            if (Schema::hasTable('categories') && \App\Models\Category::count() === 0) {
                app(\Database\Seeders\CategorySeeder::class)->run();
            }

            // Seed default vendors only when vendors table is empty
            if (Schema::hasTable('vendors') && \App\Models\Vendor::count() === 0) {
                app(\Database\Seeders\VendorSeeder::class)->run();
            }

            // Seed default asset templates only when asset_templates table is empty
            if (Schema::hasTable('asset_templates') && \App\Models\AssetTemplate::count() === 0) {
                app(\Database\Seeders\AssetTemplateSeeder::class)->run();
            }

            // Initialize depreciation category rules switch:
            // ON (1) for fresh installs (0 assets), OFF (0) for existing databases with assets until approved by admin
            if (Schema::hasTable('settings') && Schema::hasTable('assets')) {
                if (\App\Models\Setting::get('depreciation_use_category_rules') === null) {
                    $hasExistingAssets = Asset::count() > 0;
                    \App\Models\Setting::set('depreciation_use_category_rules', $hasExistingAssets ? '0' : '1');
                }
            }

            // Backfill departments from legacy asset text if unlinked assets exist
            if (Schema::hasTable('departments') && Schema::hasColumn('assets', 'department_id')) {
                if (Asset::whereNotNull('department')->where('department', '!=', '')->whereNull('department_id')->exists()) {
                    app(DepartmentBackfillService::class)->runBackfill();
                }
            }

            // Backfill sections from legacy asset text if unlinked assets exist
            if (Schema::hasTable('sections') && Schema::hasColumn('assets', 'section_id')) {
                if (Asset::whereNotNull('department_id')->whereNotNull('section')->where('section', '!=', '')->whereNull('section_id')->exists()) {
                    app(SectionBackfillService::class)->runBackfill();
                }
            }

            // Seed standard hospital departments and sections once (without resurrecting deleted items on later boots)
            if (Schema::hasTable('departments') && Schema::hasTable('sections') && Schema::hasTable('settings')) {
                $seeder = app(\Database\Seeders\DepartmentSectionSeeder::class);

                if (\App\Models\Setting::get('hospital_departments_seeded') !== '1') {
                    $seeder->run();
                    \App\Models\Setting::set('hospital_departments_seeded', '1');
                    \App\Models\Setting::set('hospital_departments_seed_version', '2');
                } elseif (\App\Models\Setting::get('hospital_departments_seed_version') !== '2') {
                    $seeder->seedByCode('IT');
                    \App\Models\Setting::set('hospital_departments_seed_version', '2');
                }
            }

            // Run automatic startup backup if latest backup is older than 24 hours (skip during unit tests)
            if (! app()->runningUnitTests()) {
                $this->backupService->runStartupBackupIfDue();
            }

            // Clean up temporary Laravel Excel export files older than 1 day
            $this->cleanupLegacyExcelTempFiles();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Clean up temporary Laravel Excel files older than specified seconds (default: 86400 / 1 day).
     */
    public function cleanupLegacyExcelTempFiles(int $olderThanSeconds = 86400): int
    {
        $appData = getenv('APPDATA');
        $directories = array_unique(array_filter([
            config('excel.temporary_files.local_path'),
            storage_path('framework/cache/laravel-excel'),
            $appData ? $appData . DIRECTORY_SEPARATOR . 'bethel-asset-tracker' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'laravel-excel' : null,
            $appData ? $appData . DIRECTORY_SEPARATOR . 'bethel-asset-tracker-dev' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'laravel-excel' : null,
        ]));

        $deletedCount = 0;
        $cutoffTime = time() - $olderThanSeconds;

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . 'laravel-excel-*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    $mtime = filemtime($file);
                    if ($mtime !== false && $mtime < $cutoffTime) {
                        if (@unlink($file)) {
                            $deletedCount++;
                        }
                    }
                }
            }
        }

        if ($deletedCount > 0) {
            Log::info("Cleaned up {$deletedCount} temporary Excel export file(s) older than 1 day.");
        }

        return $deletedCount;
    }
}
