<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
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
});

test('editing Section, Brand, Assigned Personnel, and Condition creates activity log entry with old and new values', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-ACT-001',
        'asset_name' => 'Cardiology Monitor',
        'brand' => 'Mindray',
        'section' => 'Station A',
        'assigned_personnel' => 'Nurse Joy',
        'asset_condition' => 'Brand-new',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_no' => 'BBH-ACT-001',
        'asset_name' => 'Cardiology Monitor',
        'brand' => 'Philips Healthcare',
        'section' => 'Station B',
        'assigned_personnel' => 'Dr. Brock',
        'asset_condition' => 'In Good Condition',
        'status' => 'In Use',
    ])->assertRedirect(route('assets.show', $asset));

    $log = Activity::forSubject($asset)->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->description)->toContain('updated');

    $old = $log->properties['old'] ?? [];
    $attributes = $log->properties['attributes'] ?? [];

    expect($old['brand'])->toBe('Mindray');
    expect($attributes['brand'])->toBe('Philips Healthcare');

    expect($old['section'])->toBe('Station A');
    expect($attributes['section'])->toBe('Station B');

    expect($old['assigned_personnel'])->toBe('Nurse Joy');
    expect($attributes['assigned_personnel'])->toBe('Dr. Brock');

    expect($old['asset_condition'])->toBe('Brand-new');
    expect($attributes['asset_condition'])->toBe('In Good Condition');
});

test('staff is blocked from activity log and depreciation pages', function () {
    $this->actingAs($this->staff)
        ->get('/settings/activity-log')
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->get('/reports/depreciation')
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->get('/reports/depreciation/export')
        ->assertForbidden();
});

test('no money values appear on any inventory page visible to staff', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-CONF-999',
        'asset_name' => 'High Cost MRI Scanner',
        'brand' => 'Siemens',
        'model' => 'Magnetom',
        'cost' => 15750000.00,
        'salvage_value' => 1500000.00,
        'useful_life_years' => 10,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'date_acquired' => '2022-01-01',
    ]);

    // 1. Asset index list page
    $indexResponse = $this->actingAs($this->staff)->get(route('assets.index'));
    $indexResponse->assertOk();
    $indexResponse->assertDontSee('15,750,000');
    $indexResponse->assertDontSee('1,500,000');
    $indexResponse->assertDontSee('Current Value');

    // 2. Asset detail page
    $showResponse = $this->actingAs($this->staff)->get(route('assets.show', $asset));
    $showResponse->assertOk();
    $showResponse->assertDontSee('15,750,000');
    $showResponse->assertDontSee('1,500,000');
    $showResponse->assertDontSee('Acquisition Cost');
    $showResponse->assertDontSee('Estimated Salvage');
    $showResponse->assertDontSee('Current Book Value');
    $showResponse->assertDontSee('Accumulated Depreciation');

    // 3. Asset edit page
    $editResponse = $this->actingAs($this->staff)->get(route('assets.edit', $asset));
    $editResponse->assertOk();
    $editResponse->assertDontSee('15750000');
    $editResponse->assertDontSee('name="cost"', false);
    $editResponse->assertDontSee('name="salvage_value"', false);
    $editResponse->assertDontSee('name="useful_life_years"', false);

    // 4. Staff cannot inject or update monetary values via PUT
    $this->actingAs($this->staff)->put(route('assets.update', $asset), [
        'asset_no' => 'BBH-CONF-999',
        'asset_name' => 'High Cost MRI Scanner Modified',
        'status' => 'In Use',
        'cost' => 999.00,
        'salvage_value' => 111.00,
    ])->assertRedirect(route('assets.show', $asset));

    $fresh = $asset->fresh();
    expect((float) $fresh->cost)->toBe(15750000.00);
    expect((float) $fresh->salvage_value)->toBe(1500000.00);
});
