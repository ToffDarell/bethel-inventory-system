<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use App\Services\DesktopBootstrapService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

test('asset created on new form without per-asset life or disposal inputs calculates valid depreciation when rule switch is OFF', function () {
    // 1. Switch rule OFF (legacy mode)
    Setting::set('depreciation_use_category_rules', '0');
    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_min_cost', '0');

    $category = Category::firstOrCreate(
        ['name' => 'Medical Equipment'],
        ['default_useful_life_years' => 10, 'default_salvage_percent' => 10.00]
    );

    // New form creates asset with cost and category, but NULL salvage_value and NULL useful_life_years
    $asset = Asset::factory()->create([
        'asset_no' => 'BBH-NEW-001',
        'asset_name' => 'Portable Ultrasound Unit',
        'category_id' => $category->id,
        'cost' => 50000.00,
        'salvage_value' => null,
        'useful_life_years' => null,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => false,
        'status' => 'In Service',
    ]);

    $asOfDate = Carbon::create(2025, 1, 1); // 24 months elapsed
    $service = app(DepreciationService::class);

    expect($service->isCategoryRuleMode())->toBeFalse();

    $result = $service->calculateForAsset($asset, $asOfDate);

    // Must still produce a valid, calculable result!
    expect($result->isCalculable())->toBeTrue();
    expect($result->getUsefulLifeYears())->toBe(10); // Derived from category default
    expect($result->getDisposalValue())->toBe(5000.00); // 10% of 50000 = 5000.00
    expect($result->getMonthsElapsed())->toBe(24);
    // (50000 - 5000) / 120 = 375/month * 24 = 9000
    expect($result->getAccumulatedDepreciation())->toBe(9000.00);
    expect($result->getCurrentValue())->toBe(41000.00);
});

test('rule switch defaults to ON for fresh installs with zero assets', function () {
    // Clear the setting and its cache
    Setting::forget('depreciation_use_category_rules');
    Setting::clearRuntimeCache();

    // Ensure 0 assets
    Asset::query()->forceDelete();

    expect(Asset::count())->toBe(0);

    $service = app(DepreciationService::class);

    // Dynamic auto-detect: Fresh install (0 assets) defaults to true (ON)
    expect($service->isCategoryRuleMode())->toBeTrue();

    // Desktop bootstrap sets '1'
    app(DesktopBootstrapService::class)->boot();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');
    expect($service->isCategoryRuleMode())->toBeTrue();
});

test('rule switch defaults to OFF for existing database with assets until admin turns it on', function () {
    // Clear the setting and its cache
    Setting::forget('depreciation_use_category_rules');
    Setting::clearRuntimeCache();

    // Create an existing asset
    Asset::factory()->create([
        'asset_no' => 'BBH-EXISTING-001',
        'asset_name' => 'Pre-existing Defibrillator',
        'cost' => 100000.00,
    ]);

    expect(Asset::count())->toBeGreaterThan(0);

    $service = app(DepreciationService::class);

    // Dynamic auto-detect: Existing database with assets defaults to false (OFF)
    expect($service->isCategoryRuleMode())->toBeFalse();

    // Desktop bootstrap sets '0'
    app(DesktopBootstrapService::class)->boot();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('0');
    expect($service->isCategoryRuleMode())->toBeFalse();

    // When admin explicitly switches it on in Settings, it turns ON
    Setting::set('depreciation_use_category_rules', '1');
    expect($service->isCategoryRuleMode())->toBeTrue();
});

test('fresh database, boot, create an asset, the rule is still ON', function () {
    Setting::forget('depreciation_use_category_rules');
    Setting::clearRuntimeCache();
    Asset::query()->forceDelete();

    expect(Asset::count())->toBe(0);

    // First boot on fresh database
    app(DesktopBootstrapService::class)->boot();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeTrue();

    // Create an asset
    Asset::factory()->create([
        'asset_no' => 'BBH-NEW-001',
        'asset_name' => 'Newly Created Ultrasound',
        'cost' => 50000.00,
    ]);

    expect(Asset::count())->toBe(1);

    // Rule must STILL be ON because it was persisted at first boot
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeTrue();
});

test('fresh database, import 10 assets with the Excel import, still ON', function () {
    Setting::forget('depreciation_use_category_rules');
    Setting::clearRuntimeCache();
    Asset::query()->forceDelete();

    expect(Asset::count())->toBe(0);

    // Boot on fresh database
    app(DesktopBootstrapService::class)->boot();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');

    // Import 10 assets
    for ($i = 1; $i <= 10; $i++) {
        Asset::create([
            'asset_no' => sprintf('BBH-IMP-%04d', $i),
            'asset_name' => "Imported Asset {$i}",
            'status' => 'In Use',
            'cost' => 10000.00 * $i,
        ]);
    }

    expect(Asset::count())->toBe(10);

    // Setting remains persisted as '1' (ON)
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeTrue();
});

test('existing database stays OFF until an admin switches it on', function () {
    Setting::forget('depreciation_use_category_rules');
    Setting::clearRuntimeCache();

    // Existing database containing assets before first boot
    Asset::factory()->create([
        'asset_no' => 'BBH-LEGACY-001',
        'asset_name' => 'Legacy Monitor',
        'cost' => 75000.00,
    ]);

    expect(Asset::count())->toBeGreaterThan(0);

    // Boot on existing database persists '0' (OFF)
    app(DesktopBootstrapService::class)->boot();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('0');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeFalse();

    // Additional asset creation does not flip it
    Asset::factory()->create([
        'asset_no' => 'BBH-LEGACY-002',
        'asset_name' => 'Legacy Ventilator',
        'cost' => 120000.00,
    ]);
    expect(Setting::get('depreciation_use_category_rules'))->toBe('0');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeFalse();

    // Admin switches it on in Settings
    $admin = User::factory()->create(['role' => 'admin']);
    $response = $this->actingAs($admin)->post(route('settings.depreciation.update'), [
        'depreciation_start_convention' => 'january_1',
        'depreciation_default_salvage_percent' => '10',
        'depreciation_min_cost' => '0',
        'depreciation_use_category_rules' => '1',
    ]);

    $response->assertRedirect();
    expect(Setting::get('depreciation_use_category_rules'))->toBe('1');
    expect(app(DepreciationService::class)->isCategoryRuleMode())->toBeTrue();
});
