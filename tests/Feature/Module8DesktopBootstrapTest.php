<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\DesktopBootstrapService;

test('desktop bootstrap seeds default admin and settings when database is empty and configures update-safe nativephp settings', function () {
    config(['app.env' => 'local']);
    expect(User::count())->toBe(0);

    app(DesktopBootstrapService::class)->boot();

    expect(User::where('email', 'admin@hospital.local')->exists())->toBeTrue()
        ->and(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.');

    expect(config('nativephp.cleanup_exclude_files'))
        ->toContain('database/*.sqlite')
        ->and(config('nativephp.nsis.delete_app_data_on_uninstall'))->toBeFalse()
        ->and(config('nativephp.updater.enabled'))->toBeFalse();
});
