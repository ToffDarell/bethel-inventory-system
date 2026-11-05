<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->adminA = User::factory()->create([
        'name' => 'Admin Alice',
        'email' => 'alice@hospital.local',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->adminB = User::factory()->create([
        'name' => 'Admin Bob',
        'email' => 'bob@hospital.local',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->category = Category::create([
        'name' => 'Diagnostic Imaging',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 5.0,
    ]);
});

test('assets created by admin A are immediately visible, searchable, editable, and reportable by admin B', function () {
    // Admin A creates an asset
    $this->actingAs($this->adminA)->post('/assets', [
        'asset_no' => 'BBH-DIAG-001',
        'asset_name' => 'Stationary X-Ray Unit',
        'brand' => 'Siemens',
        'model' => 'Multix Impact',
        'serial_no' => 'SN-XRAY-999',
        'department' => 'Radiology',
        'section' => 'Room 1',
        'assigned_personnel' => 'Dr. Cruz',
        'category_id' => $this->category->id,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 1250000.00,
        'useful_life_years' => 10,
        'salvage_value' => 62500.00,
        'date_acquired' => '2022-01-01',
    ])->assertRedirect();

    $assetA = Asset::where('asset_no', 'BBH-DIAG-001')->firstOrFail();

    // Verify activity log causer is Admin A
    $createActivity = Activity::forSubject($assetA)->where('event', 'created')->latest()->first();
    expect($createActivity)->not->toBeNull();
    expect((int) $createActivity->causer_id)->toBe($this->adminA->id);

    // Admin B logs in
    // 1. Admin B sees the asset on Dashboard
    $dashboardResp = $this->actingAs($this->adminB)->get('/dashboard');
    $dashboardResp->assertOk();
    $dashboardResp->assertSee('Stationary X-Ray Unit');
    $dashboardResp->assertSee('BBH-DIAG-001');

    // 2. Admin B sees the asset on the main Assets list
    $listResp = $this->actingAs($this->adminB)->get('/assets');
    $listResp->assertOk();
    $listResp->assertSee('BBH-DIAG-001');
    $listResp->assertSee('Stationary X-Ray Unit');

    // 3. Admin B can find it via Search and Quick Search
    $searchResp = $this->actingAs($this->adminB)->get('/assets?search=Siemens');
    $searchResp->assertOk();
    $searchResp->assertSee('BBH-DIAG-001');

    $quickSearchResp = $this->actingAs($this->adminB)->getJson('/api/assets/quick-search?q=Multix');
    $quickSearchResp->assertOk();
    $quickSearchResp->assertJsonFragment(['asset_no' => 'BBH-DIAG-001']);

    // 4. Admin B can view asset detail
    $showResp = $this->actingAs($this->adminB)->get("/assets/{$assetA->id}");
    $showResp->assertOk();
    $showResp->assertSee('Stationary X-Ray Unit');
    $showResp->assertSee('Siemens');

    // 5. Admin B can open the Edit form and update the asset
    $editResp = $this->actingAs($this->adminB)->get("/assets/{$assetA->id}/edit");
    $editResp->assertOk();
    $editResp->assertSee('Stationary X-Ray Unit');

    $updateResp = $this->actingAs($this->adminB)->put("/assets/{$assetA->id}", [
        'asset_no' => 'BBH-DIAG-001',
        'asset_name' => 'Stationary X-Ray Unit Updated by Bob',
        'brand' => 'Siemens Healthineers',
        'model' => 'Multix Impact v2',
        'serial_no' => 'SN-XRAY-999',
        'department' => 'Radiology',
        'section' => 'Room 2',
        'assigned_personnel' => 'Dr. Reyes',
        'category_id' => $this->category->id,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 1250000.00,
        'useful_life_years' => 10,
        'salvage_value' => 62500.00,
        'date_acquired' => '2022-01-01',
    ]);
    $updateResp->assertRedirect();

    $assetA->refresh();
    expect($assetA->asset_name)->toBe('Stationary X-Ray Unit Updated by Bob');
    expect($assetA->brand)->toBe('Siemens Healthineers');

    // Activity log records Bob as the causer of update
    $updateActivity = Activity::forSubject($assetA)->where('event', 'updated')->latest()->first();
    expect($updateActivity)->not->toBeNull();
    expect((int) $updateActivity->causer_id)->toBe($this->adminB->id);

    // 6. Admin B sees the asset in the Depreciation report
    $depReportResp = $this->actingAs($this->adminB)->get('/reports/depreciation');
    $depReportResp->assertOk();
    $depReportResp->assertSee('Stationary X-Ray Unit Updated by Bob');
    $depReportResp->assertSee('BBH-DIAG-001');

    // 7. Admin B sees the asset in Excel and CSV exports
    $exportCsvResp = $this->actingAs($this->adminB)->get('/reports/depreciation/export?format=csv');
    $exportCsvResp->assertOk();
    expect($exportCsvResp->streamedContent())->toContain('BBH-DIAG-001');
});

test('assets created by admin B are visible and manageable by admin A', function () {
    // Admin B creates an asset
    $this->actingAs($this->adminB)->post('/assets', [
        'asset_no' => 'BBH-DIAG-002',
        'asset_name' => 'Mobile C-Arm',
        'brand' => 'GE Healthcare',
        'model' => 'OEC One',
        'category_id' => $this->category->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
        'cost' => 850000.00,
        'useful_life_years' => 8,
        'salvage_value' => 42500.00,
        'date_acquired' => '2023-01-01',
    ])->assertRedirect();

    $assetB = Asset::where('asset_no', 'BBH-DIAG-002')->firstOrFail();

    // Admin A sees it on Dashboard and Assets list
    $this->actingAs($this->adminA)->get('/dashboard')->assertSee('BBH-DIAG-002');
    $this->actingAs($this->adminA)->get('/assets')->assertSee('Mobile C-Arm');

    // Admin A can edit it
    $this->actingAs($this->adminA)->put("/assets/{$assetB->id}", [
        'asset_no' => 'BBH-DIAG-002',
        'asset_name' => 'Mobile C-Arm Calibrated',
        'brand' => 'GE Healthcare',
        'model' => 'OEC One',
        'category_id' => $this->category->id,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 850000.00,
        'useful_life_years' => 8,
        'salvage_value' => 42500.00,
        'date_acquired' => '2023-01-01',
    ])->assertRedirect();

    expect($assetB->fresh()->asset_name)->toBe('Mobile C-Arm Calibrated');
});
