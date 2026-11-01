<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            if (Schema::hasTable('users') && User::count() === 0) {
                Artisan::call('db:seed', [
                    '--class' => DatabaseSeeder::class,
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        Window::open()
            ->title('Bethel Asset Tracker')
            ->width(1280)
            ->height(820)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'upload_max_filesize' => '32M',
            'post_max_size' => '32M',
        ];
    }
}
