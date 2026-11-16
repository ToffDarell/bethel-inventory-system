<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

test('category disposal percent can be null and renders "Disposal % not set" in settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $category = Category::create([
        'name' => 'Custom Null Salvage Category',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => null,
    ]);

    expect($category->default_salvage_percent)->toBeNull();

    $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'categories']));
    $response->assertOk();
    $response->assertSee('Disposal % not set');
});

test('category with 0 percent disposal is flagged for review in settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $category = Category::create([
        'name' => 'Zero Percent Category',
        'default_useful_life_years' => 5,
        'default_salvage_percent' => 0.00,
    ]);

    $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'categories']));
    $response->assertOk();
    $response->assertSee('Needs review (0%)');
});

test('asset in category with null disposal percent returns Category rule incomplete instead of calculating with 0 percent', function () {
    Setting::set('depreciation_use_category_rules', '1');
    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_min_cost', '0');

    $category = Category::create([
        'name' => 'Incomplete Category',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => null,
    ]);

    $asset = Asset::factory()->create([
        'asset_no' => 'BBH-INCOMPLETE-001',
        'asset_name' => 'Equipment With Incomplete Category Rule',
        'category_id' => $category->id,
        'cost' => 100000.00,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => false,
        'status' => 'In Service',
    ]);

    $service = app(DepreciationService::class);
    $result = $service->calculateForAsset($asset, Carbon::create(2025, 1, 1));

    expect($result->isCalculable())->toBeFalse();
    expect($result->getMissingReason())->toBe('Category rule incomplete');
});

test('bulk set category updates category for selected assets and logs activity for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $catA = Category::create(['name' => 'Category A', 'default_useful_life_years' => 5, 'default_salvage_percent' => 10.00]);
    $catB = Category::create(['name' => 'Category B', 'default_useful_life_years' => 8, 'default_salvage_percent' => 10.00]);

    $a1 = Asset::factory()->create(['category_id' => $catA->id]);
    $a2 = Asset::factory()->create(['category_id' => null]);
    $a3 = Asset::factory()->create(['category_id' => $catA->id]);

    $response = $this->actingAs($admin)->post(route('assets.bulk-category'), [
        'ids' => [$a1->id, $a2->id, $a3->id],
        'category_id' => $catB->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Category set to "Category B" for 3 assets.');

    expect($a1->fresh()->category_id)->toBe($catB->id);
    expect($a2->fresh()->category_id)->toBe($catB->id);
    expect($a3->fresh()->category_id)->toBe($catB->id);

    $latestLog = Activity::latest('id')->first();
    expect($latestLog)->not->toBeNull();
    expect($latestLog->description)->toBe("Set category to 'Category B' for 3 assets");
});

test('staff can bulk set category and staff financial shield is preserved with no monetary leak', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $cat = Category::create(['name' => 'Surgical Equipment', 'default_useful_life_years' => 10, 'default_salvage_percent' => 10.00]);

    $a1 = Asset::factory()->create(['category_id' => null, 'cost' => 85000.00]);
    $a2 = Asset::factory()->create(['category_id' => null, 'cost' => 125000.00]);

    $response = $this->actingAs($staff)->post(route('assets.bulk-category'), [
        'ids' => [$a1->id, $a2->id],
        'category_id' => $cat->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($a1->fresh()->category_id)->toBe($cat->id);
    expect($a2->fresh()->category_id)->toBe($cat->id);

    // Verify staff view of assets list does NOT contain cost or financial numbers
    $listResponse = $this->actingAs($staff)->get(route('assets.index'));
    $listResponse->assertOk();
    $listResponse->assertDontSee('85,000');
    $listResponse->assertDontSee('125,000');
    $listResponse->assertDontSee('Current Value');
});

test('assets list filter ?category=none returns only assets without a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $cat = Category::create(['name' => 'Diagnostic', 'default_useful_life_years' => 5, 'default_salvage_percent' => 10.00]);

    $withCat = Asset::factory()->create(['asset_no' => 'BBH-WITH-CAT', 'category_id' => $cat->id]);
    $noCat = Asset::factory()->create(['asset_no' => 'BBH-NO-CAT', 'category_id' => null]);

    $response = $this->actingAs($admin)->get(route('assets.index', ['category' => 'none']));
    $response->assertOk();
    $response->assertSee('BBH-NO-CAT');
    $response->assertDontSee('BBH-WITH-CAT');
});

test('dashboard displays "N assets without a category" in needs attention linking to filtered list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Ensure at least one uncategorized active asset
    Asset::factory()->create([
        'asset_no' => 'BBH-UNCAT-99',
        'category_id' => null,
        'status' => 'In Use',
    ]);

    $uncatCount = Asset::whereNull('category_id')->where('status', '!=', 'Disposed')->count();
    expect($uncatCount)->toBeGreaterThan(0);

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee("{$uncatCount} " . \Illuminate\Support\Str::plural('asset', $uncatCount) . ' without a category');
    $response->assertSee(route('assets.index', ['category' => 'none']));
});

test('settings depreciation switch shows warning when rule is off and assets have no category', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Setting::set('depreciation_use_category_rules', '0');

    Asset::factory()->create(['category_id' => null]);

    $uncatCount = Asset::whereNull('category_id')->count();
    expect($uncatCount)->toBeGreaterThan(0);

    $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'depreciation']));
    $response->assertOk();
    $response->assertSee("{$uncatCount} " . \Illuminate\Support\Str::plural('asset', $uncatCount) . ' have no category and will not be valued');
});
