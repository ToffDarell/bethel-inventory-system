<?php

namespace App\Services;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
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
            // Run pending additive migrations (BackupDatabaseBeforeMigration listener backs up existing DB first)
            Artisan::call('migrate', ['--force' => true]);

            // Seed initial admin user and default hospital settings if users table is empty
            if (Schema::hasTable('users') && User::count() === 0) {
                app(DatabaseSeeder::class)->run();
            }

            // Run automatic startup backup if latest backup is older than 24 hours (skip during unit tests)
            if (! app()->runningUnitTests()) {
                $this->backupService->runStartupBackupIfDue();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
