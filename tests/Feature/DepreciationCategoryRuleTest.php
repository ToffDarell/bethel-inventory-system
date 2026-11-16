<?php

use App\Exports\AssetsTemplateExport;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\AssetImportService;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $this->staff = User::factory()->create([
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_min_cost', '0');
    Setting::set('depreciation_default_salvage_percent', '0');
    Setting::set('depreciation_use_category_rules', '1');
});

test('DepreciationService calculates straight-line depreciation strictly from category life and disposal percent', function () {
    $service = app(DepreciationService::class);

    // Prompt example: cost 120,000, category life 10 years, disposal 10%, 46 months gives:
    // disposal value 12,000, accumulated 41,400, current value 78,600.
    $category = Category::create([
        'name' => 'Clinical ICU Monitoring ' . uniqid(),
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 10.0,
    ]);

    $acquiredDate = Carbon::parse('2020-01-01');
    $asOfDate = Carbon::parse('2023-11-01'); // Exactly 46 months

    $asset = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => 120000.00,
        // Legacy per-asset columns populated with different numbers to prove they are ignored
        'salvage_value' => 50000.00,
        'useful_life_years' => 3,
        'date_acquired' => $acquiredDate,
        'acquired_year_only' => false,
    ]);

    $result = $service->calculateForAsset($asset, $asOfDate);

    expect($result->isCalculable())->toBeTrue();
    expect($result->getDisposalValue())->toBe(12000.00);
    expect($result->getSalvageValue())->toBe(12000.00);
    expect($result->getUsefulLifeYears())->toBe(10);
    expect($result->getMonthsElapsed())->toBe(46);
    expect($result->getAccumulatedDepreciation())->toBe(41400.00);
    expect($result->getCurrentValue())->toBe(78600.00);
    expect($service->formatPeso($result->getCurrentValue()))->toBe('₱78,600.00');
});

test('asset without category or cost returns "Select a category and enter a cost to calculate"', function () {
    $service = app(DepreciationService::class);

    $category = Category::create([
        'name' => 'Diagnostic Category ' . uniqid(),
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 5.0,
    ]);

    // 1. Asset with category but no cost
    $assetNoCost = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => null,
        'date_acquired' => Carbon::now()->subYears(2),
    ]);
    $resNoCost = $service->calculateForAsset($assetNoCost);
    expect($resNoCost->isCalculable())->toBeFalse();
    expect($resNoCost->getMissingReason())->toBe('Select a category and enter a cost to calculate');

    // 2. Asset with cost but no category
    $assetNoCat = Asset::factory()->create([
        'category_id' => null,
        'cost' => 100000.00,
        'date_acquired' => Carbon::now()->subYears(2),
    ]);
    $resNoCat = $service->calculateForAsset($assetNoCat);
    expect($resNoCat->isCalculable())->toBeFalse();
    expect($resNoCat->getMissingReason())->toBe('Select a category and enter a cost to calculate');

    // 3. Asset with neither category nor cost
    $assetNeither = Asset::factory()->create([
        'category_id' => null,
        'cost' => null,
        'date_acquired' => Carbon::now()->subYears(2),
    ]);
    $resNeither = $service->calculateForAsset($assetNeither);
    expect($resNeither->isCalculable())->toBeFalse();
    expect($resNeither->getMissingReason())->toBe('Select a category and enter a cost to calculate');
});

test('assets with missing cost or category do not break reports or exports', function () {
    $category = Category::create([
        'name' => 'General Equipment ' . uniqid(),
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 5.0,
    ]);

    Asset::factory()->create([
        'asset_no' => 'BBH-VALID-01',
        'asset_name' => 'Calculable Infusion Pump',
        'category_id' => $category->id,
        'cost' => 50000.00,
        'status' => 'In Use',
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    Asset::factory()->create([
        'asset_no' => 'BBH-NOCAT-01',
        'asset_name' => 'Uncategorized Microscope',
        'category_id' => null,
        'cost' => 80000.00,
        'status' => 'In Use',
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    Asset::factory()->create([
        'asset_no' => 'BBH-NOCOST-01',
        'asset_name' => 'Stretcher Without Cost',
        'category_id' => $category->id,
        'cost' => null,
        'status' => 'In Use',
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    // 1. Web report renders cleanly with 200 OK
    $response = $this->actingAs($this->admin)->get(route('reports.depreciation'));
    $response->assertOk();
    $response->assertSee('BBH-VALID-01');
    $response->assertSee('BBH-NOCAT-01');
    $response->assertSee('BBH-NOCOST-01');

    // 2. PDF export generates cleanly with 200 OK
    $pdfResponse = $this->actingAs($this->admin)->get(route('reports.depreciation.export-pdf'));
    $pdfResponse->assertOk();
    $pdfResponse->assertHeader('content-type', 'application/pdf');

    // 3. CSV export generates cleanly with 200 OK
    $csvResponse = $this->actingAs($this->admin)->get(route('reports.depreciation.export', ['format' => 'csv']));
    $csvResponse->assertOk();
});

test('category settings require useful life on store and update', function () {
    // 1. Missing useful life on store is rejected (disposal percent is nullable per v1.3.2)
    $resp = $this->actingAs($this->admin)->post('/settings/categories', [
        'name' => 'Incomplete Category',
    ]);
    $resp->assertSessionHasErrors(['default_useful_life_years']);

    // 2. Successful store with valid required fields
    $this->actingAs($this->admin)->post('/settings/categories', [
        'name' => 'Radiology Devices',
        'default_useful_life_years' => 8,
        'default_salvage_percent' => 12.5,
    ])->assertRedirect();

    $cat = Category::where('name', 'Radiology Devices')->first();
    expect($cat)->not->toBeNull();
    expect($cat->default_useful_life_years)->toBe(8);
    expect((float) $cat->default_salvage_percent)->toBe(12.5);

    // 3. Updating requires useful life
    $updateResp = $this->actingAs($this->admin)->put("/settings/categories/{$cat->id}", [
        'name' => 'Radiology Devices Updated',
        'default_useful_life_years' => '',
        'default_salvage_percent' => '',
    ]);
    $updateResp->assertSessionHasErrors(['default_useful_life_years']);
});

test('updating category depreciation rules logs activity entry with affected asset count', function () {
    $cat = Category::create([
        'name' => 'Surgery Units ' . uniqid(),
        'default_useful_life_years' => 7,
        'default_salvage_percent' => 5.0,
    ]);

    // Create 3 assets in this category
    Asset::factory()->count(3)->create([
        'category_id' => $cat->id,
        'cost' => 100000.00,
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    $this->actingAs($this->admin)->put("/settings/categories/{$cat->id}", [
        'name' => $cat->name,
        'default_useful_life_years' => 12,
        'default_salvage_percent' => 8.0,
    ])->assertRedirect();

    $activity = Activity::where('subject_type', Category::class)
        ->where('subject_id', $cat->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('depreciation rules updated');
    expect($activity->description)->toContain('affecting 3 assets');
    expect($activity->properties['affected_assets_count'])->toBe(3);
    expect($activity->properties['old']['useful_life_years'])->toBe(7);
    expect($activity->properties['new']['useful_life_years'])->toBe(12);
    expect((float) $activity->properties['old']['disposal_percent'])->toBe(5.0);
    expect((float) $activity->properties['new']['disposal_percent'])->toBe(8.0);
});

test('disposal terminology is used everywhere and old salvage headers are still accepted on import', function () {
    // 1. Template export contains disposal_value heading
    $template = new AssetsTemplateExport();
    expect($template->headings())->toContain('disposal_value');

    // 2. AssetImportService accepts disposal_value
    $importService = app(AssetImportService::class);
    $normalizedHeader = $importService->normalizeHeaderKey('disposal_value');
    expect($normalizedHeader)->toBe('salvage_value');

    // 3. AssetImportService still accepts legacy salvage_value and salvage
    expect($importService->normalizeHeaderKey('salvage_value'))->toBe('salvage_value');
    expect($importService->normalizeHeaderKey('salvage'))->toBe('salvage_value');

    // 4. Asset Add/Edit views do not contain editable salvage_value input
    $createView = $this->actingAs($this->admin)->get(route('assets.create'));
    $createView->assertOk();
    $createView->assertDontSee('name="salvage_value"', false);
    $createView->assertDontSee('name="useful_life_years"', false);
    $createView->assertSee('Disposal %');
    $createView->assertSee('Disposal Value Floor');
    $createView->assertSee('Select a category and enter a cost to calculate');
});

test('staff users cannot view or manipulate financial data in Phase A views and endpoints', function () {
    $category = Category::create([
        'name' => 'Staff Test Cat ' . uniqid(),
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10.0,
    ]);

    $asset = Asset::factory()->create([
        'asset_no' => 'BBH-SEC-01',
        'asset_name' => 'Secret Cost Defibrillator',
        'category_id' => $category->id,
        'cost' => 350000.00,
        'date_acquired' => Carbon::now()->subYear(),
    ]);

    // Staff cannot view depreciation report
    $this->actingAs($this->staff)->get(route('reports.depreciation'))->assertForbidden();
    $this->actingAs($this->staff)->get(route('reports.depreciation.export-pdf'))->assertForbidden();
    $this->actingAs($this->staff)->get(route('reports.depreciation.export', ['format' => 'csv']))->assertForbidden();

    // Staff cannot see money or disposal floor on asset detail
    $showResp = $this->actingAs($this->staff)->get(route('assets.show', $asset));
    $showResp->assertOk();
    $showResp->assertDontSee('350,000');
    $showResp->assertDontSee('Disposal value floor');
    $showResp->assertDontSee('Depreciation & Valuation');

    // Staff cannot see or submit cost on asset edit
    $editResp = $this->actingAs($this->staff)->get(route('assets.edit', $asset));
    $editResp->assertOk();
    $editResp->assertDontSee('name="cost"', false);
    $editResp->assertDontSee('350,000');
    $editResp->assertDontSee('Disposal Value Floor');

    // Staff submit cannot overwrite cost or salvage
    $this->actingAs($this->staff)->put(route('assets.update', $asset), [
        'asset_name' => 'Defibrillator Renamed By Staff',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => '1.00',
        'salvage_value' => '1.00',
        'disposal_value' => '1.00',
    ])->assertRedirect();

    $asset->refresh();
    expect((float) $asset->cost)->toBe(350000.00);
});

test('depreciation rule mode is switchable: uses legacy per-asset overrides when disabled, category rules when enabled', function () {
    $service = app(DepreciationService::class);

    $category = Category::create([
        'name' => 'Switchable Test Cat ' . uniqid(),
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 10.0,
    ]);

    $asset = Asset::factory()->create([
        'category_id' => $category->id,
        'cost' => 120000.00,
        'salvage_value' => 50000.00,
        'useful_life_years' => 3,
        'date_acquired' => Carbon::parse('2020-01-01'),
        'acquired_year_only' => false,
    ]);

    $asOfDate = Carbon::parse('2023-11-01');

    // 1. When switch is OFF (default until approved): uses per-asset overrides (salvage 50k, life 3)
    Setting::set('depreciation_use_category_rules', '0');
    expect($service->isCategoryRuleMode())->toBeFalse();

    $resOff = $service->calculateForAsset($asset, $asOfDate);
    expect($resOff->getDisposalValue())->toBe(50000.00);
    expect($resOff->getUsefulLifeYears())->toBe(3);

    // 2. When switch is ON: strictly derives from category (salvage 12k, life 10)
    Setting::set('depreciation_use_category_rules', '1');
    expect($service->isCategoryRuleMode())->toBeTrue();

    $resOn = $service->calculateForAsset($asset, $asOfDate);
    expect($resOn->getDisposalValue())->toBe(12000.00);
    expect($resOn->getUsefulLifeYears())->toBe(10);
    expect($resOn->getCurrentValue())->toBe(78600.00);
});
