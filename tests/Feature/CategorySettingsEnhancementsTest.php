<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $this->staff = User::factory()->create([
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('depreciation_use_category_rules', '1');
});

test('category with 0% disposal shows Needs review (0%) until marked as reviewed, then shows 0% confirmed', function () {
    $cat = Category::create([
        'name' => 'Zero Disposal Test Equipment',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 0.0,
        'disposal_reviewed' => false,
    ]);

    // Admin visits settings categories tab
    $res = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'categories']));
    $res->assertOk();
    $res->assertSee('Needs review (0%)');
    $res->assertSee('Mark as reviewed');

    // Admin marks as reviewed
    $postRes = $this->actingAs($this->admin)->post(route('settings.categories.mark-reviewed', $cat));
    $postRes->assertRedirect(route('settings.index') . '#categories');

    expect($cat->fresh()->disposal_reviewed)->toBeTrue();

    // Re-visit settings: now shows neutral "0% confirmed" badge
    $resAfter = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'categories']));
    $resAfter->assertOk();
    $resAfter->assertSee('0% confirmed');
});

test('editing a category and saving automatically marks it as reviewed', function () {
    $cat = Category::create([
        'name' => 'Auto Review Category',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 0.0,
        'disposal_reviewed' => false,
    ]);

    $this->actingAs($this->admin)->put(route('settings.categories.update', $cat), [
        'name' => 'Auto Review Category Updated',
        'default_useful_life_years' => 6,
        'default_salvage_percent' => 0.0,
    ]);

    expect($cat->fresh()->disposal_reviewed)->toBeTrue();
});

test('uncategorized row is protected from creation, edit, and deletion', function () {
    // Attempt to create a category named Uncategorized
    $storeRes = $this->actingAs($this->admin)->post(route('settings.categories.store'), [
        'name' => 'Uncategorized',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);
    $storeRes->assertSessionHasErrors('name');

    // Attempt to create case-insensitive uncategorized
    $storeResLower = $this->actingAs($this->admin)->post(route('settings.categories.store'), [
        'name' => 'uncategorized',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);
    $storeResLower->assertSessionHasErrors('name');

    // Attempt to rename an existing category to Uncategorized
    $cat = Category::create([
        'name' => 'Temp Category',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);

    $updateRes = $this->actingAs($this->admin)->put(route('settings.categories.update', $cat), [
        'name' => 'Uncategorized',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 10,
    ]);
    $updateRes->assertSessionHasErrors('name');
});

test('assets in uncategorized display Select a category to calculate and are not counted as real category for depreciation', function () {
    $uncat = Category::firstOrCreate(['name' => 'Uncategorized'], [
        'default_useful_life_years' => null,
        'default_salvage_percent' => null,
    ]);

    $asset = Asset::create([
        'asset_no' => 'BBH-UNCAT-01',
        'asset_name' => 'Uncategorized Test Device',
        'category_id' => $uncat->id,
        'cost' => 50000.00,
        'date_acquired' => Carbon::now()->subYears(2),
        'status' => 'In Use',
    ]);

    $service = app(DepreciationService::class);
    $result = $service->calculateForAsset($asset);

    expect($result->isCalculable())->toBeFalse();
    expect($result->getMissingReason())->toBe('Select a category to calculate');

    // View on asset detail page
    $showRes = $this->actingAs($this->admin)->get(route('assets.show', $asset));
    $showRes->assertOk();
    $showRes->assertSee('Select a category to calculate');

    // View on reports depreciation
    $reportRes = $this->actingAs($this->admin)->get(route('reports.depreciation'));
    $reportRes->assertOk();
    $reportRes->assertSee('Select a category to calculate');
});

test('assets in Other category with null useful life display Useful life not set', function () {
    $other = Category::where('name', 'Other')->first();
    if (! $other) {
        $other = Category::create([
            'name' => 'Other',
            'default_useful_life_years' => null,
            'default_salvage_percent' => null,
        ]);
    } else {
        $other->update(['default_useful_life_years' => null]);
    }

    $asset = Asset::create([
        'asset_no' => 'BBH-OTHER-01',
        'asset_name' => 'Custom Specialty Artifact',
        'category_id' => $other->id,
        'cost' => 75000.00,
        'date_acquired' => Carbon::now()->subYears(2),
        'status' => 'In Use',
    ]);

    $service = app(DepreciationService::class);
    $result = $service->calculateForAsset($asset);

    expect($result->isCalculable())->toBeFalse();
    expect($result->getMissingReason())->toBe('Useful life not set');

    $showRes = $this->actingAs($this->admin)->get(route('assets.show', $asset));
    $showRes->assertOk();
    $showRes->assertSee('Useful life not set');
});
