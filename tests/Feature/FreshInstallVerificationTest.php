<?php

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\DesktopBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('fresh install bootstrap creates baseline categories and settings with zero users in production', function () {
    // Set production environment
    config(['app.env' => 'production']);
    expect(config('app.env') === 'local')->toBeFalse();

    User::truncate();
    Setting::truncate();
    Category::truncate();

    expect(User::count())->toBe(0);
    expect(Setting::count())->toBe(0);
    expect(Category::count())->toBe(0);

    // Run DesktopBootstrapService boot sequence
    app(DesktopBootstrapService::class)->boot();

    // Verify zero users created
    expect(User::count())->toBe(0);

    // Verify baseline settings and categories are created
    expect(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.');
    expect(Setting::get('hospital_short_name'))->toBe('BBH');
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1'); // ON for fresh install
    expect(Category::count())->toBeGreaterThan(0);

    // Verify setup screen is ready for first launch
    $response = $this->get('/');
    $response->assertRedirect(route('setup'));

    $setupRes = $this->get('/setup');
    $setupRes->assertOk();
    $setupRes->assertSee('Create administrator account');
});

test('nativephp config explicitly excludes all sqlite files and test folders from installer package', function () {
    $excluded = config('nativephp.cleanup_exclude_files');

    expect($excluded)->toBeArray();
    expect($excluded)->toContain('*.sqlite');
    expect($excluded)->toContain('database/*.sqlite*');
    expect($excluded)->toContain('Downloads');
    expect($excluded)->toContain('public/Downloads');
    expect($excluded)->toContain('scratch');
});
