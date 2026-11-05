<?php

use App\Models\Asset;
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
});

test('assets list can be filtered by section', function () {
    $item1 = Asset::create([
        'asset_no' => 'BBH-SEC-01',
        'asset_name' => 'Centrifuge Alpha',
        'section' => 'Hematology',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    $item2 = Asset::create([
        'asset_no' => 'BBH-SEC-02',
        'asset_name' => 'Cash Register Pro',
        'section' => 'Cashier',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    $response = $this->actingAs($this->admin)->get(route('assets.index', ['section' => 'Hematology']));
    $response->assertOk();
    $response->assertSee('Centrifuge Alpha');
    $response->assertDontSee('Cash Register Pro');
});

test('assets list can be filtered by asset condition including Not set', function () {
    $damaged = Asset::create([
        'asset_no' => 'BBH-COND-01',
        'asset_name' => 'Broken Defibrillator',
        'status' => 'Under Repair',
        'asset_condition' => 'Damage',
    ]);

    $brandNew = Asset::create([
        'asset_no' => 'BBH-COND-02',
        'asset_name' => 'Brand New Monitor',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    $unset = Asset::create([
        'asset_no' => 'BBH-COND-03',
        'asset_name' => 'Legacy Stretcher',
        'status' => 'In Use',
        'asset_condition' => null,
    ]);

    // Filter by Damage
    $damageResponse = $this->actingAs($this->admin)->get(route('assets.index', ['condition' => 'Damage']));
    $damageResponse->assertOk();
    $damageResponse->assertSee('Broken Defibrillator');
    $damageResponse->assertDontSee('Brand New Monitor');
    $damageResponse->assertDontSee('Legacy Stretcher');

    // Filter by Not set
    $unsetResponse = $this->actingAs($this->admin)->get(route('assets.index', ['condition' => 'Not set']));
    $unsetResponse->assertOk();
    $unsetResponse->assertSee('Legacy Stretcher');
    $unsetResponse->assertDontSee('Broken Defibrillator');
    $unsetResponse->assertDontSee('Brand New Monitor');
});

test('quick search matches brand, section, and assigned_personnel', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-SEARCH-01',
        'asset_name' => 'Ultrasound System',
        'brand' => 'Sonosite',
        'section' => 'Obstetrics',
        'assigned_personnel' => 'Dr. Santos',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    // Search by brand
    $this->actingAs($this->admin)->get(route('assets.index', ['search' => 'Sonosite']))
        ->assertOk()
        ->assertSee('Ultrasound System');

    // Search by section
    $this->actingAs($this->admin)->get(route('assets.index', ['search' => 'Obstetrics']))
        ->assertOk()
        ->assertSee('Ultrasound System');

    // Search by assigned personnel
    $this->actingAs($this->admin)->get(route('assets.index', ['search' => 'Dr. Santos']))
        ->assertOk()
        ->assertSee('Ultrasound System');
});

test('api quick search matches brand, section, and assigned personnel without exposing money to staff', function () {
    Asset::create([
        'asset_no' => 'BBH-API-01',
        'asset_name' => 'Portable X-Ray',
        'brand' => 'Shimadzu',
        'section' => 'Radiology Bay',
        'assigned_personnel' => 'Tech Mark',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
        'cost' => 500000,
    ]);

    $response = $this->actingAs($this->staff)->getJson(route('assets.quick-search', ['q' => 'Shimadzu']));
    $response->assertOk();
    $response->assertJsonFragment(['asset_no' => 'BBH-API-01']);

    // Assert cost and salvage are never returned in quick-search
    $content = $response->getContent();
    expect($content)->not->toContain('500000')
        ->and($content)->not->toContain('cost');
});

test('find asset lookup finds asset by brand, section, or assigned personnel', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-LOOKUP-01',
        'asset_name' => 'Autoclave Sterilizer',
        'brand' => 'Tuttnauer',
        'section' => 'CSSD',
        'assigned_personnel' => 'Nurse Clara',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    $response = $this->actingAs($this->admin)->get(route('assets.find.lookup', ['q' => 'Tuttnauer']));
    $response->assertRedirect(route('assets.show', $asset));
});

test('dashboard displays damaged condition alert and links to filtered assets list', function () {
    Asset::create([
        'asset_no' => 'BBH-DASH-01',
        'asset_name' => 'Damaged Monitor',
        'status' => 'Under Repair',
        'asset_condition' => 'Damage',
    ]);

    Asset::create([
        'asset_no' => 'BBH-DASH-02',
        'asset_name' => 'Good Monitor',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    Asset::create([
        'asset_no' => 'BBH-DASH-03',
        'asset_name' => 'Unset Monitor',
        'status' => 'In Use',
        'asset_condition' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('1 asset in Damage condition');
    $response->assertSee(route('assets.index', ['condition' => 'Damage']));
    $response->assertDontSee('3 assets in Damage condition');
});
