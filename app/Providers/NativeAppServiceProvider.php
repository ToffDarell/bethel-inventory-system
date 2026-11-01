<?php

namespace App\Providers;

use App\Services\DesktopBootstrapService;
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
        app(DesktopBootstrapService::class)->boot();

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
