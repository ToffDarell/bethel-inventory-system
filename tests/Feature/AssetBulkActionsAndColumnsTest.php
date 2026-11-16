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

test('admin can bulk archive active assets', function () {
    $asset1 = Asset::create([
        'asset_no' => 'BBH-BULK-001',
        'asset_name' => 'Ultrasound Probe A',
        'status' => 'In Use',
    ]);
    $asset2 = Asset::create([
        'asset_no' => 'BBH-BULK-002',
        'asset_name' => 'Ultrasound Probe B',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('assets.bulk-archive'), [
            'ids' => [$asset1->id, $asset2->id],
        ]);

    $response->assertRedirect(route('assets.index'));
    $response->assertSessionHas('success', '2 assets have been moved to the archive.');

    expect(Asset::find($asset1->id))->toBeNull();
    expect(Asset::find($asset2->id))->toBeNull();
    expect(Asset::onlyTrashed()->find($asset1->id))->not->toBeNull();
    expect(Asset::onlyTrashed()->find($asset2->id))->not->toBeNull();
});

test('staff cannot bulk archive active assets and receives 403', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-BULK-003',
        'asset_name' => 'Defibrillator Unit',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->staff)
        ->post(route('assets.bulk-archive'), [
            'ids' => [$asset->id],
        ]);

    $response->assertForbidden();
    expect(Asset::find($asset->id))->not->toBeNull();
});

test('admin can bulk restore archived assets', function () {
    $asset1 = Asset::create([
        'asset_no' => 'BBH-BULK-004',
        'asset_name' => 'Infusion Pump A',
        'status' => 'Stored',
    ]);
    $asset1->delete();

    $asset2 = Asset::create([
        'asset_no' => 'BBH-BULK-005',
        'asset_name' => 'Infusion Pump B',
        'status' => 'Stored',
    ]);
    $asset2->delete();

    $response = $this->actingAs($this->admin)
        ->post(route('assets.bulk-restore'), [
            'ids' => [$asset1->id, $asset2->id],
        ]);

    $response->assertRedirect(route('assets.index', ['tab' => 'archived']));
    $response->assertSessionHas('success', '2 assets have been restored to active inventory.');

    expect(Asset::find($asset1->id))->not->toBeNull();
    expect(Asset::find($asset2->id))->not->toBeNull();
});

test('staff cannot bulk restore archived assets and receives 403', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-BULK-006',
        'asset_name' => 'Infusion Pump C',
        'status' => 'Stored',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->staff)
        ->post(route('assets.bulk-restore'), [
            'ids' => [$asset->id],
        ]);

    $response->assertForbidden();
    expect(Asset::find($asset->id))->toBeNull();
    expect(Asset::onlyTrashed()->find($asset->id))->not->toBeNull();
});

test('admin can bulk permanently delete assets', function () {
    $asset1 = Asset::create([
        'asset_no' => 'BBH-BULK-007',
        'asset_name' => 'Scrap Monitor A',
        'status' => 'Disposed',
    ]);
    $asset1->delete();

    $asset2 = Asset::create([
        'asset_no' => 'BBH-BULK-008',
        'asset_name' => 'Scrap Monitor B',
        'status' => 'Disposed',
    ]);
    $asset2->delete();

    $response = $this->actingAs($this->admin)
        ->post(route('assets.bulk-force-delete'), [
            'ids' => [$asset1->id, $asset2->id],
            'tab' => 'archived',
        ]);

    $response->assertRedirect(route('assets.index', ['tab' => 'archived']));
    $response->assertSessionHas('success', '2 assets have been permanently deleted from the system.');

    expect(Asset::withTrashed()->find($asset1->id))->toBeNull();
    expect(Asset::withTrashed()->find($asset2->id))->toBeNull();
});

test('staff cannot bulk permanently delete assets and receives 403', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-BULK-009',
        'asset_name' => 'Scrap Monitor C',
        'status' => 'Disposed',
    ]);
    $asset->delete();

    $response = $this->actingAs($this->staff)
        ->post(route('assets.bulk-force-delete'), [
            'ids' => [$asset->id],
            'tab' => 'archived',
        ]);

    $response->assertForbidden();
    expect(Asset::onlyTrashed()->find($asset->id))->not->toBeNull();
});

test('assets view renders bulk archive, permanent delete, and uncheck all columns for admin', function () {
    Asset::create([
        'asset_no' => 'BBH-BULK-010',
        'asset_name' => 'Dialysis Station',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)->get(route('assets.index'));
    $response->assertOk();

    // Verify bulk buttons exist for admin
    $response->assertSee('bulk-archive-btn');
    $response->assertSee('bulk-force-delete-btn');
    $response->assertSee('bulk-archive-modal');
    $response->assertSee('bulk-force-delete-modal');

    // Verify columns toggle options and clear selection button
    $response->assertSee('Clear selection');
    $response->assertSee('toggle-col-brand');
    $response->assertSee('toggle-col-personnel');
});

test('assets view hides bulk archive, permanent delete, and financial column toggle from staff', function () {
    Asset::create([
        'asset_no' => 'BBH-BULK-011',
        'asset_name' => 'Centrifuge Unit',
        'status' => 'In Use',
        'cost' => 50000,
    ]);

    $response = $this->actingAs($this->staff)->get(route('assets.index'));
    $response->assertOk();

    // Staff sees print selected tags and clear selection
    $response->assertSee('Print Selected Tags');
    $response->assertSee('Clear selection');

    // Staff strictly does NOT see archive or force-delete options
    $response->assertDontSee('id="bulk-archive-btn"', false);
    $response->assertDontSee('id="bulk-force-delete-btn"', false);
    $response->assertDontSee('id="bulk-archive-modal"', false);
    $response->assertDontSee('id="bulk-force-delete-modal"', false);

    // Staff strictly does NOT see financial column toggle or cost
    $response->assertDontSee('toggle-col-value');
    $response->assertDontSee('50,000');
    $response->assertDontSee('Current Value');
});
