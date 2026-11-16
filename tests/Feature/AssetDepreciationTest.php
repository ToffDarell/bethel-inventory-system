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

test('dashboard shows category breakdown counts and contains no peso amounts for admin and for staff', function () {
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
    $adminResp->assertDontSee('Total Asset Value');
    $adminResp->assertDontSee('View Depreciation Report');
    $adminResp->assertDontSee('₱');
    $adminResp->assertDontSee('100,000');
    $adminResp->assertSee('By Category');
    $adminResp->assertSee('Ultrasound Systems');

    $staffResp = $this->actingAs($this->staff)->get('/dashboard');
    $staffResp->assertOk();
    $staffResp->assertDontSee('Total Asset Value');
    $staffResp->assertDontSee('View Depreciation Report');
    $staffResp->assertSee('By Category');
    $staffResp->assertSee('Ultrasound Systems');
    $staffResp->assertDontSee('₱');
    $staffResp->assertDontSee('100,000');

    // Verify Depreciation Report stays reachable from Reports page for admin
    $reportsResp = $this->actingAs($this->admin)->get('/reports');
    $reportsResp->assertOk();
    $reportsResp->assertSee(route('reports.depreciation'), false);
    $reportsResp->assertSee('View Depreciation Report');
});

test('admin can view depreciation report with totals and category summary', function () {
    $assetWithCost = Asset::create([
        'asset_no' => 'BBH-REP-01',
        'asset_name' => 'Hemodialysis Machine',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 500000.00,
        'salvage_value' => 50000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    $assetWithoutCost = Asset::create([
        'asset_no' => 'BBH-REP-02',
        'asset_name' => 'Ancient Hospital Stretcher',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => null,
        'useful_life_years' => 5,
        'date_acquired' => '2021-01-01',
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/depreciation');
    $response->assertOk();
    $response->assertSee('Depreciation Report');
    $response->assertSee('Back to Reports');
    $response->assertSee('Total Acquisition Cost');
    $response->assertSee('What the hospital paid');
    $response->assertSee('Accumulated Depreciation');
    $response->assertSee('Value lost so far');
    $response->assertSee('Net Book Value');
    $response->assertSee('Straight-line');
    $response->assertSee('Counted from January 1 of the Year Bought');
    $response->assertSee('Useful Life');
    $response->assertSee('Disposal Value');
    $response->assertSee('Asset Values');
    $response->assertSee('Hemodialysis Machine');
    $response->assertSee('Ancient Hospital Stretcher');
    $response->assertSee('Missing cost');
    $response->assertSee(route('assets.edit', $assetWithoutCost));
    $response->assertSee('1 asset excluded from totals because no cost was entered.');
    $response->assertSee('Totals');
    $response->assertSee('₱500,000.00');
    $response->assertSee('1 (1 without cost)');
});

test('depreciation report and export respect optional as_of date filter with a past date', function () {
    // Machine acquired 2020-01-01 with cost 120,000, 10-year category useful life, 0 disposal.
    // Yearly straight-line rate = 12,000 (1,000/month).
    $zeroCategory = Category::create([
        'name' => 'Historical Ventilators ' . uniqid(),
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 0.0,
    ]);

    $asset = Asset::create([
        'asset_no' => 'BBH-HIST-01',
        'asset_name' => 'Historical Ventilator',
        'status' => 'In Use',
        'category_id' => $zeroCategory->id,
        'cost' => 120000.00,
        'salvage_value' => null, // Missing salvage -> should show '-'
        'useful_life_years' => 10,
        'date_acquired' => '2020-01-01',
    ]);

    // Query as of 2022-01-01 (exactly 24 months elapsed = 24,000 depreciation, 96,000 net book value)
    $response = $this->actingAs($this->admin)->get('/reports/depreciation?as_of=2022-01-01');
    $response->assertOk();
    $response->assertSee('January 1, 2022');
    $response->assertSee('₱24,000.00'); // Accumulated as of Jan 1, 2022
    $response->assertSee('₱96,000.00'); // Net Book Value as of Jan 1, 2022
    $response->assertSee('name="as_of" value="2022-01-01"', false);

    // CSV export respecting past as_of date
    $csvResponse = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=csv&as_of=2022-01-01');
    $csvResponse->assertOk();
    $csvContent = file_get_contents($csvResponse->getFile()->getPathname());
    expect($csvContent)->toContain('BBH-HIST-01');
    expect($csvContent)->toContain('120000');
    expect($csvContent)->toContain('24000');
    expect($csvContent)->toContain('96000');
    // Verify Disposal column exists and shows '-' for missing disposal
    expect($csvContent)->toContain('Disposal');
    expect($csvContent)->toContain('"-"');
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

test('assets template export includes category and financial headings', function () {
    $export = new \App\Exports\AssetsTemplateExport();
    $headings = $export->headings();
    expect($headings)->toContain('category');
    expect($headings)->toContain('cost');
    expect($headings)->toContain('useful_life_years');
    expect($headings)->toContain('disposal_value');
});

test('assets export includes financial columns and totals row for admin only', function () {
    Asset::create([
        'asset_no' => 'BBH-EXP-01',
        'asset_name' => 'Computed Tomography Scanner',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => 1500000.00,
        'salvage_value' => 150000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    // Admin export
    $adminExport = new \App\Exports\AssetsExport(null, null, $this->admin);
    $adminHeadings = $adminExport->headings();
    expect($adminHeadings)->toContain('category');
    expect($adminHeadings)->toContain('cost');
    expect($adminHeadings)->toContain('accumulated_depreciation');
    expect($adminHeadings)->toContain('current_value');

    $adminRows = $adminExport->array();
    expect($adminRows)->not->toBeEmpty();
    // Check totals row is at the end
    $lastRow = end($adminRows);
    expect($lastRow[0])->toBe('Total asset value');
    $costIndex = array_search('cost', $adminHeadings);
    expect($lastRow[$costIndex])->toContain('₱1,500,000.00');

    // Staff export
    $staffExport = new \App\Exports\AssetsExport(null, null, $this->staff);
    $staffHeadings = $staffExport->headings();
    expect($staffHeadings)->toContain('category');
    expect($staffHeadings)->not->toContain('cost');
    expect($staffHeadings)->not->toContain('disposal_value');
    expect($staffHeadings)->not->toContain('salvage_value');
    expect($staffHeadings)->not->toContain('accumulated_depreciation');
    expect($staffHeadings)->not->toContain('current_value');

    $staffRows = $staffExport->array();
    expect($staffRows)->not->toBeEmpty();
    $lastStaffRow = end($staffRows);
    expect($lastStaffRow[0])->not->toBe('Total asset value');
    expect(implode(' ', $lastStaffRow))->not->toContain('1,500,000');
    expect(implode(' ', $lastStaffRow))->not->toContain('₱');
});

test('asset import auto-creates new categories and persists cost properly', function () {
    $importService = app(\App\Services\AssetImportService::class);

    $rowsToCommit = [
        [
            'row_number' => 2,
            'asset_no' => 'BBH-IMP-001',
            'asset_name' => 'Defibrillator Unit',
            'category' => 'Emergency Equipment', // New category not yet seeded
            'cost' => 85000.00,
            'salvage_value' => 8500.00,
            'useful_life_years' => 8,
            'model' => 'Zoll AED Plus',
            'serial_no' => 'AED-9988',
            'date_acquired' => '2024-06-01',
            'department' => 'Emergency',
            'location' => 'ER Bay 1',
            'status' => 'In Use',
            'notes' => 'Imported test item',
            'is_duplicate' => false,
            'is_valid' => true,
        ],
        [
            'row_number' => 3,
            'asset_no' => 'BBH-IMP-002',
            'asset_name' => 'IV Pole Stand',
            'category' => 'Furniture',
            'cost' => null, // Blank cost
            'salvage_value' => null,
            'useful_life_years' => null,
            'model' => 'Standard 4-Hook',
            'serial_no' => null,
            'date_acquired' => null,
            'department' => 'Ward A',
            'location' => 'Room 101',
            'status' => 'In Use',
            'notes' => 'No cost item',
            'is_duplicate' => false,
            'is_valid' => true,
        ],
    ];

    $result = $importService->commit($rowsToCommit, $this->admin);
    expect($result['imported'])->toBe(2);

    // Verify Emergency Equipment was auto-created
    $newCat = Category::where('name', 'Emergency Equipment')->first();
    expect($newCat)->not->toBeNull();

    $asset1 = Asset::where('asset_no', 'BBH-IMP-001')->first();
    expect($asset1->category_id)->toBe($newCat->id);
    expect((float) $asset1->cost)->toEqualWithDelta(85000.00, 0.01);
    expect((float) $asset1->salvage_value)->toEqualWithDelta(8500.00, 0.01);
    expect($asset1->useful_life_years)->toBe(8);

    $asset2 = Asset::where('asset_no', 'BBH-IMP-002')->first();
    expect($asset2->cost)->toBeNull();
    expect($asset2->useful_life_years)->toBeNull();
    expect($asset2->salvage_value)->toBeNull();
});

test('security: cost and monetary values are never leaked to staff across any endpoint or output', function () {
    // Unique distinct cost figure to detect any leakage
    $distinctCost = 789456.12;
    $distinctCostStr = '789456';

    $sensitiveAsset = Asset::create([
        'asset_no' => 'BBH-SEC-99',
        'asset_name' => 'Magnetic Resonance Imager',
        'status' => 'In Use',
        'category_id' => $this->category->id,
        'cost' => $distinctCost,
        'salvage_value' => 78945.61,
        'useful_life_years' => 12,
        'date_acquired' => '2023-01-01',
        'department' => 'Radiology',
        'location' => 'MRI Suite',
    ]);

    // 1. Quick search endpoint (/api/assets/quick-search)
    $searchResp = $this->actingAs($this->staff)->getJson('/api/assets/quick-search?q=Magnetic');
    $searchResp->assertOk();
    expect($searchResp->getContent())->not->toContain('789456');
    expect($searchResp->getContent())->not->toContain('789,456');

    // 2. Audit location assets endpoint (/api/audit/location-assets)
    $auditResp = $this->actingAs($this->staff)->getJson('/api/audit/location-assets?location=MRI%20Suite');
    $auditResp->assertOk();
    expect($auditResp->getContent())->not->toContain('789456');
    expect($auditResp->getContent())->not->toContain('789,456');

    // 3. Assets listing page (/assets)
    $listResp = $this->actingAs($this->staff)->get('/assets');
    $listResp->assertOk();
    expect($listResp->getContent())->not->toContain('789456');
    expect($listResp->getContent())->not->toContain('789,456');

    // 4. Excel/CSV export endpoint (/reports/export?format=csv)
    $exportResp = $this->actingAs($this->staff)->get('/reports/export?format=csv');
    $exportResp->assertOk();
    $staffCsvContent = file_get_contents($exportResp->getFile()->getPathname());
    expect($staffCsvContent)->not->toContain('789456');
    expect($staffCsvContent)->not->toContain('789,456');
    expect($staffCsvContent)->not->toContain('Total asset value');

    // 5. Print preview (/tags/print)
    $printResp = $this->actingAs($this->staff)->get("/tags/print?ids[]={$sensitiveAsset->id}");
    $printResp->assertOk();
    expect($printResp->getContent())->not->toContain('789456');
    expect($printResp->getContent())->not->toContain('789,456');

    // 6. PDF export preview (/tags/pdf)
    $pdfResp = $this->actingAs($this->staff)->get("/tags/pdf?ids[]={$sensitiveAsset->id}");
    $pdfResp->assertOk();
    expect($pdfResp->headers->get('content-type'))->toContain('application/pdf');

    // 7. Activity Log page (/settings/activity-log) is completely forbidden to staff
    $activityResp = $this->actingAs($this->staff)->get('/settings/activity-log');
    $activityResp->assertForbidden();

    // Verify Admin CAN see the cost in exports
    $adminExportResp = $this->actingAs($this->admin)->get('/reports/export?format=csv');
    $adminExportResp->assertOk();
    $adminCsvContent = file_get_contents($adminExportResp->getFile()->getPathname());
    expect($adminCsvContent)->toContain('789,456.12');
    expect($adminCsvContent)->toContain('Total asset value');
});

test('admin can submit cost and salvage value formatted with commas and they are saved correctly', function () {
    $response = $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_no' => 'BBH-COMMA-01',
        'asset_name' => 'Computed Tomography Scanner',
        'category_id' => $this->category->id,
        'department' => 'Radiology',
        'location' => 'CT Room 1',
        'status' => 'In Use',
        'cost' => '1,250,000.50',
        'useful_life_years' => 10,
        'salvage_value' => '125,000.00',
    ]);

    $response->assertRedirect();
    $asset = Asset::where('asset_no', 'BBH-COMMA-01')->first();
    expect($asset)->not->toBeNull();
    expect((float) $asset->cost)->toBe(1250000.50);
    expect((float) $asset->salvage_value)->toBe(125000.00);
});
