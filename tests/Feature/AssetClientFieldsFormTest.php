<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Staff Nurse',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('asset_no_prefix', 'BBH');
});

test('store asset requires asset_condition from allowed list', function () {
    $response = $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Defibrillator',
        'status' => 'In Use',
        'asset_condition' => '',
    ]);

    $response->assertSessionHasErrors(['asset_condition']);

    $invalidConditionResponse = $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Defibrillator',
        'status' => 'In Use',
        'asset_condition' => 'Unknown Condition',
    ]);

    $invalidConditionResponse->assertSessionHasErrors(['asset_condition']);
});

test('store asset saves brand, section, assigned_personnel, and asset_condition', function () {
    $response = $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Hematology Analyzer',
        'brand' => 'Sysmex',
        'model' => 'XN-550',
        'serial_no' => 'SN-SYS-001',
        'department' => 'Laboratory',
        'section' => 'Hematology',
        'assigned_personnel' => 'Dr. Cruz',
        'location' => 'Lab Room 2',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
        'year_bought' => 2023,
        'notes' => 'Primary automated analyzer',
    ]);

    $asset = Asset::where('asset_name', 'Hematology Analyzer')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->brand)->toBe('Sysmex')
        ->and($asset->model)->toBe('XN-550')
        ->and($asset->section)->toBe('Hematology')
        ->and($asset->assigned_personnel)->toBe('Dr. Cruz')
        ->and($asset->asset_condition)->toBe('Brand-new')
        ->and($asset->date_acquired->format('Y-m-d'))->toBe('2023-01-01')
        ->and($asset->notes)->toBe('Primary automated analyzer');

    $response->assertRedirect(route('assets.show', $asset));
});

test('submitting year_bought: 2023 stores 2023-01-01 in date_acquired', function () {
    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'year_bought' => 2023,
    ]);

    $asset = Asset::where('asset_name', 'Infusion Pump')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->date_acquired->format('Y-m-d'))->toBe('2023-01-01');
});

test('updating an asset without changing year preserves existing full date', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-PRESERVE-01',
        'asset_name' => 'Ultrasound System',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'date_acquired' => '2021-08-14',
    ]);

    // Update with year_bought = 2021 (same year)
    $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_no' => 'BBH-PRESERVE-01',
        'asset_name' => 'Ultrasound System Pro',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'year_bought' => 2021,
    ]);

    $asset->refresh();
    expect($asset->asset_name)->toBe('Ultrasound System Pro')
        ->and($asset->date_acquired->format('Y-m-d'))->toBe('2021-08-14');
});

test('updating an asset with a changed year updates date_acquired to January 1 of new year', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-YEARCHANGE-01',
        'asset_name' => 'ECG Machine',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'date_acquired' => '2020-05-12',
    ]);

    // Change year to 2024
    $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_no' => 'BBH-YEARCHANGE-01',
        'asset_name' => 'ECG Machine',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'year_bought' => 2024,
    ]);

    $asset->refresh();
    expect($asset->date_acquired->format('Y-m-d'))->toBe('2024-01-01');
});

test('staff submissions discard monetary fields', function () {
    $this->actingAs($this->staff)->post(route('assets.store'), [
        'asset_name' => 'Stretcher',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => '999999.00',
        'salvage_value' => '50000.00',
        'useful_life_years' => 10,
    ]);

    $asset = Asset::where('asset_name', 'Stretcher')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->cost)->toBeNull()
        ->and($asset->salvage_value)->toBeNull()
        ->and($asset->useful_life_years)->toBeNull();
});

test('create and edit views supply distinct, trimmed, case-insensitive datalists for department, section, and assigned personnel', function () {
    Asset::create([
        'asset_no' => 'BBH-DATA-01',
        'asset_name' => 'Item A',
        'department' => '  ICU  ',
        'section' => 'Station 3',
        'assigned_personnel' => 'Nurse Joy',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    Asset::create([
        'asset_no' => 'BBH-DATA-02',
        'asset_name' => 'Item B',
        'department' => 'icu',
        'section' => 'station 3',
        'assigned_personnel' => 'nurse joy',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    $createResponse = $this->actingAs($this->admin)->get(route('assets.create'));
    $createResponse->assertOk();

    $sections = $createResponse->viewData('sections');
    $departments = $createResponse->viewData('departments');
    $assignedPersonnel = $createResponse->viewData('assignedPersonnel');

    expect($sections)->toHaveCount(1)
        ->and($departments)->toHaveCount(1)
        ->and($assignedPersonnel)->toHaveCount(1);
});

test('asset detail page renders asset condition badge, year bought as 4-digit year, and client labels', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-DETAIL-01',
        'asset_name' => 'Defibrillator Plus',
        'brand' => 'Philips',
        'model' => 'HeartStart',
        'serial_no' => 'SN-DETAIL-01',
        'department' => 'Emergency',
        'section' => 'Triage',
        'assigned_personnel' => 'Dr. Reyes',
        'location' => 'Bay 1',
        'status' => 'In Use',
        'asset_condition' => 'Damage',
        'date_acquired' => '2022-11-20',
        'notes' => 'Awaiting biomedical maintenance check',
    ]);

    $response = $this->actingAs($this->admin)->get(route('assets.show', $asset));
    $response->assertOk();

    // Check client labels
    $response->assertSee('Asset Description');
    $response->assertSee('Defibrillator Plus');
    $response->assertSee('Brand');
    $response->assertSee('Philips');
    $response->assertSee('Model');
    $response->assertSee('HeartStart');
    $response->assertSee('Serial Number');
    $response->assertSee('SN-DETAIL-01');
    $response->assertSee('Section');
    $response->assertSee('Triage');
    $response->assertSee('Assigned Personnel');
    $response->assertSee('Dr. Reyes');
    $response->assertSee('Year Bought');
    $response->assertSee('2022');
    $response->assertDontSee('Nov 20, 2022');
    $response->assertSee('Remarks');
    $response->assertSee('Awaiting biomedical maintenance check');
    $response->assertSee('Damage');
});

test('asset detail page renders Not set neutral badge for legacy assets with null condition', function () {
    $legacyAsset = Asset::create([
        'asset_no' => 'BBH-LEGACY-01',
        'asset_name' => 'Old Wheelchair',
        'status' => 'In Use',
        'asset_condition' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('assets.show', $legacyAsset));
    $response->assertOk();
    $response->assertSee('Not set');
});

test('staff users cannot see cost, salvage value, or depreciation on asset detail page', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-STAFF-01',
        'asset_name' => 'Centrifuge',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 125000,
        'salvage_value' => 15000,
        'useful_life_years' => 8,
        'date_acquired' => '2023-01-01',
    ]);

    $response = $this->actingAs($this->staff)->get(route('assets.show', $asset));
    $response->assertOk();
    $response->assertDontSee('125,000');
    $response->assertDontSee('15,000');
    $response->assertDontSee('Useful life');
    $response->assertDontSee('Salvage value');
});
