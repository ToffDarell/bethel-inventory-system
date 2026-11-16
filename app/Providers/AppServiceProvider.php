<?php

namespace App\Providers;

use App\Listeners\BackupDatabaseBeforeMigration;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! class_exists('XMLWriter')) {
            require_once app_path('Support/Polyfills/XmlWriterPolyfill.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MigrationsStarted::class, BackupDatabaseBeforeMigration::class);

        if (config('database.default') === 'sqlite') {
            try {
                \Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 5000;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA cache_size = -64000;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA temp_store = MEMORY;');
            } catch (\Throwable) {
                // Ignore in testing or unsupported drivers
            }
        }
    }
}
