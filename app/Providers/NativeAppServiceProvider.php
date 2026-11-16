<?php

namespace App\Providers;

use App\Services\DesktopBootstrapService;
use Illuminate\Support\Facades\Log;
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
        $bootStart = hrtime(true);
        app(DesktopBootstrapService::class)->boot();
        $bootstrapMs = round((hrtime(true) - $bootStart) / 1e6, 2);

        $windowStart = hrtime(true);
        Window::open()
            ->title('Bethel Asset Tracker')
            ->width(1280)
            ->height(820)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState();
        $windowMs = round((hrtime(true) - $windowStart) / 1e6, 2);

        Log::info('[PERF][WINDOW] Packaged desktop window opened', [
            'bootstrap_ms' => $bootstrapMs,
            'window_open_ms' => $windowMs,
            'total_boot_ms' => round($bootstrapMs + $windowMs, 2),
        ]);
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
            'opcache.enable' => '1',
            'opcache.enable_cli' => '1',
            'opcache.memory_consumption' => '128',
            'opcache.interned_strings_buffer' => '16',
            'opcache.max_accelerated_files' => '10000',
            'opcache.validate_timestamps' => '0',
            'opcache.save_comments' => '1',
        ];
    }
}
