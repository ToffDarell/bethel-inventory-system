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

test('active assets appear on default tab and soft-deleted assets appear on archived tab', function () {
    $active = Asset::create([
        'asset_no' => 'BBH-000101',
        'asset_name' => 'Active Ultrasound Machine',
        'status' => 'In Use',
    ]);

    $archived = Asset::create([
        'asset_no' => 'BBH-000102',
        'asset_name' => 'Archived Anesthesia Cart',
        'status' => 'Stored',
    ]);
    $archived->delete();

    // Default view shows Active Inventory
    $this->actingAs($this->staff)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertSee('Active Inventory')
        ->assertSee('Active Ultrasound Machine')
        ->assertDontSee('Archived Anesthesia Cart')
        ->assertSee('BBH-000101')
        ->assertDontSee('BBH-000102');

    // Archive tab shows Archived equipment
    $this->actingAs($this->staff)
        ->get(route('assets.index', ['tab' => 'archived']))
        ->assertOk()
        ->assertSee('Archive')
        ->assertSee('Archived Anesthesia Cart')
        ->assertDontSee('Active Ultrasound Machine')
        ->assertSee('BBH-000102')
        ->assertDontSee('BBH-000101');
});

test('staff and admin can restore an archived asset to active inventory', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000201',
        'asset_name' => 'Defibrillator Unit',
        'status' => 'Stored',
    ]);
    $asset->delete();

    expect(Asset::find($asset->id))->toBeNull();
    expect(Asset::onlyTrashed()->find($asset->id))->not->toBeNull();

    $response = $this->actingAs($this->staff)
        ->post(route('assets.restore', $asset));

    $response->assertRedirect(route('assets.index', ['tab' => 'archived']));
    $response->assertSessionHas('success');

    // Confirm it is back in active inventory
    expect(Asset::find($asset->id))->not->toBeNull();
    expect(Asset::onlyTrashed()->find($asset->id))->toBeNull();

    // Confirm activity was logged
    $latestActivity = Activity::latest('id')->first();
    expect($latestActivity->description)->toContain('restored from archive');
});

test('staff cannot permanently delete an archived asset', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000301',
        'asset_name' => 'Old Examination Lamp',
        'status' => 'Disposed',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->staff)
        ->delete(route('assets.force-delete', $asset));

    $response->assertForbidden();

    // Verify asset still exists in trash
    expect(Asset::withTrashed()->find($asset->id))->not->toBeNull();
});

test('admin can permanently delete an archived asset', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000401',
        'asset_name' => 'Decommissioned X-Ray Table',
        'status' => 'Disposed',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->admin)
        ->delete(route('assets.force-delete', $asset));

    $response->assertRedirect(route('assets.index', ['tab' => 'archived']));
    $response->assertSessionHas('success');

    // Confirm asset is completely wiped from database
    expect(Asset::withTrashed()->find($asset->id))->toBeNull();

    // Confirm activity was logged
    $latestActivity = Activity::latest('id')->first();
    expect($latestActivity->description)->toContain('permanently deleted');
});

test('editing an archived asset redirects with warning to restore first', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000501',
        'asset_name' => 'Archived Patient Scale',
        'status' => 'Stored',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->staff)
        ->get(route('assets.edit', $asset));

    $response->assertRedirect(route('assets.show', $asset));
    $response->assertSessionHas('warning');
});

test('scan lookup identifies archived assets and navigates with informative note', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000601',
        'asset_name' => 'Storage Wheelchair',
        'status' => 'Stored',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->staff)
        ->get(route('assets.find.lookup', ['q' => 'BBH-000601']));

    $response->assertRedirect(route('assets.show', $asset));
    $response->assertSessionHas('info');
});

test('authenticated layout includes accessible sign out confirmation modal', function () {
    $response = $this->actingAs($this->staff)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('signout-modal');
    $response->assertSee('Confirm Sign Out');
    $response->assertSee('signout-trigger-btn');
    $response->assertSee('openSignOutModal()');
    $response->assertSee('closeSignOutModal()');
});
