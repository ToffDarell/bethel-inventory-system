<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use App\Services\DepreciationService;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    $this->category = Category::create([
        'name' => 'Ultrasound Systems',
        'default_useful_life_years' => 7,
        'default_salvage_percent' => 5.0,
    ]);
});

test('admin can create and update asset with category and financial attributes', function () {
    $response = $this->actingAs($this->admin)->post('/assets', [
        'asset_no' => 'BBH-US-001',
        'asset_name' => 'Cart Ultrasound',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 350000.00,
        'useful_life_years' => 7,
        'salvage_value' => 17500.00,
        'date_acquired' => '2023-01-15',
    ]);
    $response->assertRedirect();

    $asset = Asset::where('asset_no', 'BBH-US-001')->first();
    expect($asset)->not->toBeNull();
    expect($asset->category_id)->toBe($this->category->id);
    expect((float) $asset->cost)->toEqualWithDelta(350000.00, 0.01);
    expect($asset->useful_life_years)->toBe(7);
    expect((float) $asset->salvage_value)->toEqualWithDelta(17500.00, 0.01);

    // Update
    $this->actingAs($this->admin)->put("/assets/{$asset->id}", [
        'asset_no' => 'BBH-US-001',
        'asset_name' => 'Cart Ultrasound Upgraded',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 380000.00,
        'useful_life_years' => 8,
        'salvage_value' => 19000.00,
        'date_acquired' => '2023-01-15',
    ])->assertRedirect();

    expect((float) $asset->fresh()->cost)->toEqualWithDelta(380000.00, 0.01);
    expect($asset->fresh()->useful_life_years)->toBe(8);
});

test('staff can assign category but submitted monetary fields are discarded by server', function () {
    $response = $this->actingAs($this->staff)->post('/assets', [
        'asset_no' => 'BBH-WC-001',
        'asset_name' => 'Heavy Duty Wheelchair',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 25000.00, // Staff attempting to submit cost
        'useful_life_years' => 5,
        'salvage_value' => 2500.00,
    ]);
    $response->assertRedirect();

    $asset = Asset::where('asset_no', 'BBH-WC-001')->first();
    expect($asset->category_id)->toBe($this->category->id);
    expect($asset->cost)->toBeNull();
    expect($asset->useful_life_years)->toBeNull();
    expect($asset->salvage_value)->toBeNull();

    // Staff editing an asset with existing cost cannot modify or tamper with cost
    $adminAsset = Asset::create([
        'asset_no' => 'BBH-ADM-01',
        'asset_name' => 'Admin Registered Monitor',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 150000.00,
        'useful_life_years' => 10,
        'salvage_value' => 15000.00,
    ]);

    $this->actingAs($this->staff)->put("/assets/{$adminAsset->id}", [
        'asset_no' => 'BBH-ADM-01',
        'asset_name' => 'Admin Registered Monitor Renamed',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 999.00, // Staff attempt to overwrite cost
    ])->assertRedirect();

    // The existing cost is preserved intact
    expect((float) $adminAsset->fresh()->cost)->toEqualWithDelta(150000.00, 0.01);
});

test('admin sees category badge and value card with progress on asset detail', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-VAL-01',
        'asset_name' => 'Defibrillator Monitor',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 120000.00,
        'salvage_value' => 10000.00,
        'useful_life_years' => 10,
        'date_acquired' => now()->subYears(3)->format('Y-m-d'),
    ]);

    $response = $this->actingAs($this->admin)->get("/assets/{$asset->id}");
    $response->assertOk();
    $response->assertSee('Ultrasound Systems'); // Category badge
    $response->assertSee('Value');
    $response->assertSee('₱120,000.00'); // Cost
    $response->assertSee('Current value');
    $response->assertSee('depreciable life elapsed');
});

test('staff sees category badge but cost and current value are hidden everywhere from staff', function () {
    $secretCost = 456789.00;
    $asset = Asset::create([
        'asset_no' => 'BBH-SEC-01',
        'asset_name' => 'Confidential MRI Component',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => $secretCost,
        'salvage_value' => 45000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    // 1. Asset Detail page
    $showResponse = $this->actingAs($this->staff)->get("/assets/{$asset->id}");
    $showResponse->assertOk();
    $showResponse->assertSee('Ultrasound Systems'); // Staff can see Category
    $showResponse->assertDontSee('456,789');
    $showResponse->assertDontSee('456789');
    $showResponse->assertDontSee('Current value');
    $showResponse->assertDontSee('Accumulated depreciation');

    // 2. Asset Edit form
    $editResponse = $this->actingAs($this->staff)->get("/assets/{$asset->id}/edit");
    $editResponse->assertOk();
    $editResponse->assertSee('Ultrasound Systems');
    $editResponse->assertDontSee('456,789');
    $editResponse->assertDontSee('456789');
    $editResponse->assertDontSee('name="cost"', false);

    // 3. Quick search JSON API
    $searchResponse = $this->actingAs($this->staff)->get('/api/assets/quick-search?q=Confidential');
    $searchResponse->assertOk();
    $searchResponse->assertDontSee('456,789');
    $searchResponse->assertDontSee('456789');
    $searchResponse->assertDontSee('cost');

    // 4. Print Tag View
    $tagResponse = $this->actingAs($this->staff)->get("/tags/print?ids[]={$asset->id}");
    $tagResponse->assertOk();
    $tagResponse->assertDontSee('456,789');
    $tagResponse->assertDontSee('456789');
});

test('assets list filters by category and shows category column', function () {
    $catA = Category::create(['name' => 'IT Equipment']);
    $catB = Category::create(['name' => 'Vehicles']);

    Asset::create(['asset_no' => 'BBH-IT-1', 'asset_name' => 'Dell Workstation', 'status' => 'In Use', 'category_id' => $catA->id]);
    Asset::create(['asset_no' => 'BBH-VH-1', 'asset_name' => 'Ambulance Van', 'status' => 'In Use', 'category_id' => $catB->id]);

    $response = $this->actingAs($this->admin)->get('/assets?category=' . $catA->id);
    $response->assertOk();
    $response->assertSee('Dell Workstation');
    $response->assertDontSee('Ambulance Van');
    $response->assertSee('IT Equipment');
});

test('assets list shows current value column for admin and hides it from staff', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-LST-01',
        'asset_name' => 'Computed Tomography Scanner',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 1200000.00,
        'salvage_value' => 100000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    $adminResp = $this->actingAs($this->admin)->get('/assets');
    $adminResp->assertOk();
    $adminResp->assertSee('Current Value');
    $adminResp->assertSee('Ultrasound Systems');

    $staffResp = $this->actingAs($this->staff)->get('/assets');
    $staffResp->assertOk();
    $staffResp->assertSee('Category');
    $staffResp->assertDontSee('Current Value');
    $staffResp->assertDontSee('1,200,000');
});

test('dashboard shows total asset value and category breakdown for admin, counts only for staff', function () {
    Asset::create([
        'asset_no' => 'BBH-DSH-01',
        'asset_name' => 'Patient Monitor Pro',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 100000.00,
        'salvage_value' => 10000.00,
        'useful_life_years' => 5,
        'date_acquired' => now()->subYear()->format('Y-m-d'),
    ]);

    $adminResp = $this->actingAs($this->admin)->get('/dashboard');
    $adminResp->assertOk();
    $adminResp->assertSee('Total Asset Value');
    $adminResp->assertSee('By Category');
    $adminResp->assertSee('Ultrasound Systems');

    $staffResp = $this->actingAs($this->staff)->get('/dashboard');
    $staffResp->assertOk();
    $staffResp->assertDontSee('Total Asset Value');
    $staffResp->assertSee('By Category');
    $staffResp->assertSee('Ultrasound Systems');
    $staffResp->assertDontSee('₱');
});

test('admin can view depreciation report with totals and category summary', function () {
    Asset::create([
        'asset_no' => 'BBH-REP-01',
        'asset_name' => 'Hemodialysis Machine',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 500000.00,
        'salvage_value' => 50000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/depreciation');
    $response->assertOk();
    $response->assertSee('Depreciation Report');
    $response->assertSee('Hemodialysis Machine');
    $response->assertSee('Totals');
});

test('staff cannot access depreciation report or export', function () {
    $this->actingAs($this->staff)->get('/reports/depreciation')->assertForbidden();
    $this->actingAs($this->staff)->get('/reports/depreciation/export')->assertForbidden();
});

test('admin can export depreciation report as csv or excel', function () {
    $responseCsv = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=csv');
    $responseCsv->assertOk();
    $responseCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $responseXlsx = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=xlsx');
    $responseXlsx->assertOk();
});
