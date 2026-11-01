<?php

namespace App\Listeners;

use App\Services\BackupService;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class BackupDatabaseBeforeMigration
{
    public function handle(MigrationsStarted $event): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $dbPath = (string) DB::connection()->getDatabaseName();

            if ($dbPath === '' || $dbPath === ':memory:') {
                return;
            }

            if (! File::exists($dbPath) || ! File::isFile($dbPath) || (int) @filesize($dbPath) === 0) {
                return;
            }

            app(BackupService::class)->createBackup('pre-migration');
        } catch (Throwable) {
            // Never block migrations if the settings table is not yet created on a brand-new database
        }
    }
}
