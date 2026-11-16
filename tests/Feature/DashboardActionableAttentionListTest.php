<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Dr. Administrator',
        'username' => 'dr.admin',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Nurse Joy',
        'username' => 'nurse.joy',
        'role' => 'staff',
        'must_change_password' => false,
    ]);
});

test('dashboard displays actionable attention links with real counts linking to filtered lists', function () {
    // 1. Under repair asset
    Asset::create([
        'asset_no' => 'BBH-REP-01',
        'asset_name' => 'Ventilator Main Unit',
        'status' => 'Under Repair',
        'location' => 'Biomed Workshop',
    ]);

    // 2. Damaged asset
    Asset::create([
        'asset_no' => 'BBH-DMG-01',
        'asset_name' => 'Patient Monitor',
        'status' => 'In Use',
        'asset_condition' => 'Damage',
        'location' => 'ICU Bed 2',
    ]);

    // 3. No location asset
    Asset::create([
        'asset_no' => 'BBH-NOLOC-01',
        'asset_name' => 'Syringe Pump',
        'status' => 'In Use',
        'location' => null,
    ]);

    // 4. Asset below minimum cost
    Setting::set('depreciation_min_cost', '50000');
    Asset::create([
        'asset_no' => 'BBH-LOW-01',
        'asset_name' => 'Stethoscope Electronic',
        'status' => 'In Use',
        'cost' => 15000.00,
        'location' => 'OPD Room 1',
    ]);

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Needs attention:');

    // 1. Under repair link
    $response->assertSee(route('assets.index', ['status' => 'Under Repair']));
    $response->assertSee('under repair');

    // 2. Damage condition link
    $response->assertSee(route('assets.index', ['condition' => 'Damage']));
    $response->assertSee('in Damage condition');

    // 3. No location link
    $response->assertSee(route('assets.index', ['location' => 'unassigned']));
    $response->assertSee('without location');

    // 4. Below minimum cost link (admin only)
    $response->assertSee(route('assets.index', ['below_min_cost' => 1]));
    $response->assertSee('below minimum cost');
});

test('filtered assets list accurately filters by status, condition, unassigned location, and below min cost', function () {
    Setting::set('depreciation_min_cost', '50000');

    $assetUnderRepair = Asset::create([
        'asset_no' => 'BBH-TST-REP',
        'asset_name' => 'Defibrillator Unit Under Repair',
        'status' => 'Under Repair',
        'location' => 'Maintenance Bay',
    ]);

    $assetDamaged = Asset::create([
        'asset_no' => 'BBH-TST-DMG',
        'asset_name' => 'Damaged Surgical Lamp',
        'status' => 'In Use',
        'asset_condition' => 'Damage',
        'location' => 'OR 1',
    ]);

    $assetNoLocation = Asset::create([
        'asset_no' => 'BBH-TST-NOLOC',
        'asset_name' => 'Unlocated Pulse Oximeter',
        'status' => 'In Use',
        'location' => null,
    ]);

    $assetBelowMinCost = Asset::create([
        'asset_no' => 'BBH-TST-LOW',
        'asset_name' => 'Nebulizer Low Cost',
        'status' => 'In Use',
        'cost' => 12000.00,
        'location' => 'Ward A',
    ]);

    $assetNormal = Asset::create([
        'asset_no' => 'BBH-TST-NORM',
        'asset_name' => 'Standard Hospital Bed',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 150000.00,
        'location' => 'Ward B',
    ]);

    // Test Under Repair filter
    $respRep = $this->actingAs($this->admin)->get(route('assets.index', ['status' => 'Under Repair']));
    $respRep->assertOk();
    $respRep->assertSee('BBH-TST-REP');
    $respRep->assertDontSee('BBH-TST-NORM');

    // Test Damage Condition filter
    $respDmg = $this->actingAs($this->admin)->get(route('assets.index', ['condition' => 'Damage']));
    $respDmg->assertOk();
    $respDmg->assertSee('BBH-TST-DMG');
    $respDmg->assertDontSee('BBH-TST-NORM');

    // Test Unassigned Location filter
    $respLoc = $this->actingAs($this->admin)->get(route('assets.index', ['location' => 'unassigned']));
    $respLoc->assertOk();
    $respLoc->assertSee('BBH-TST-NOLOC');
    $respLoc->assertDontSee('BBH-TST-NORM');

    // Test Below Min Cost filter (Admin)
    $respLow = $this->actingAs($this->admin)->get(route('assets.index', ['below_min_cost' => 1]));
    $respLow->assertOk();
    $respLow->assertSee('BBH-TST-LOW');
    $respLow->assertDontSee('BBH-TST-NORM');
});

test('staff viewing dashboard never sees below minimum cost link or any money figures', function () {
    Setting::set('depreciation_min_cost', '50000');

    Asset::create([
        'asset_no' => 'BBH-STF-LOW',
        'asset_name' => 'Staff View Low Cost Item',
        'status' => 'In Use',
        'cost' => 8000.00,
        'location' => 'Clinic 1',
    ]);

    $response = $this->actingAs($this->staff)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('below minimum cost');
    $response->assertDontSee('Total Asset Value');
    $response->assertDontSee('₱');
});

test('dashboard blade view contains no em dashes in text', function () {
    $content = file_get_contents(resource_path('views/dashboard.blade.php'));
    expect($content)->not->toContain('—');
    expect($content)->not->toContain('–');
});

test('no location dashboard count and filtered list strictly exclude archived assets', function () {
    // Active asset without location
    $activeNoLoc = Asset::create([
        'asset_no' => 'BBH-ACT-NOLOC',
        'asset_name' => 'Active Item Without Location',
        'status' => 'In Use',
        'location' => null,
    ]);

    // Archived (soft-deleted) asset without location
    $archivedNoLoc = Asset::create([
        'asset_no' => 'BBH-ARC-NOLOC',
        'asset_name' => 'Archived Item Without Location',
        'status' => 'In Use',
        'location' => null,
    ]);
    $archivedNoLoc->delete();

    // Verify dashboard count only counts active assets without location
    $respDash = $this->actingAs($this->admin)->get(route('dashboard'));
    $respDash->assertOk();
    $respDash->assertSee('1 asset without location');
    $respDash->assertDontSee('2 assets without location');

    // Verify filtered active assets list excludes archived asset
    $respIndex = $this->actingAs($this->admin)->get(route('assets.index', ['location' => 'unassigned']));
    $respIndex->assertOk();
    $respIndex->assertSee('BBH-ACT-NOLOC');
    $respIndex->assertDontSee('BBH-ARC-NOLOC');
});

test('needs attention uses accurate singular and plural grammar for tags and assets', function () {
    $a1 = Asset::create([
        'asset_no' => 'BBH-GRAM-01',
        'asset_name' => 'Grammar Unit 1',
        'status' => 'Under Repair',
        'needs_tag_reprint' => true,
    ]);

    $resp1 = $this->actingAs($this->admin)->get(route('dashboard'));
    $resp1->assertOk();
    $resp1->assertSee('1 tag needs reprinting');
    $resp1->assertDontSee('1 tag need reprinting');
    $resp1->assertSee('1 asset under repair');

    Asset::create([
        'asset_no' => 'BBH-GRAM-02',
        'asset_name' => 'Grammar Unit 2',
        'status' => 'Under Repair',
        'needs_tag_reprint' => true,
    ]);
    Asset::create([
        'asset_no' => 'BBH-GRAM-03',
        'asset_name' => 'Grammar Unit 3',
        'status' => 'Under Repair',
        'needs_tag_reprint' => false,
    ]);

    $resp2 = $this->actingAs($this->admin)->get(route('dashboard'));
    $resp2->assertOk();
    $resp2->assertSee('2 tags need reprinting');
    $resp2->assertSee('3 assets under repair');
});
