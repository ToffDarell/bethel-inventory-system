<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    Setting::set('asset_no_prefix', 'BBH');
});

test('creates asset with auto-generated asset_no when blank and skips existing and soft-deleted collisions', function () {
    Asset::create([
        'asset_no' => 'BBH-000001',
        'asset_name' => 'Existing Wheelchair',
        'status' => 'In Use',
    ]);

    $trashed = Asset::create([
        'asset_no' => 'BBH-000002',
        'asset_name' => 'Deleted Bed',
        'status' => 'Disposed',
    ]);
    $trashed->delete();

    $this->actingAs($this->user)->post('/assets', [
        'asset_no' => '',
        'asset_name' => 'Patient Monitor',
        'model' => 'Mindray uMEC10',
        'serial_no' => '',
        'date_acquired' => '',
        'department' => 'ICU',
        'location' => 'ND-Station 3 Room 39',
        'status' => 'In Use',
        'notes' => 'Checked by biomed',
    ])->assertRedirect();

    $asset = Asset::where('asset_name', 'Patient Monitor')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->asset_no)->toBe('BBH-000003')
        ->and($asset->serial_no)->toBeNull()
        ->and($asset->date_acquired)->toBeNull()
        ->and($asset->location)->toBe('ND-Station 3 Room 39');
});

test('allows manual asset_no like BBHIND-S300002315 and filters by search, department, and status', function () {
    $this->actingAs($this->user)->post('/assets', [
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Samsung Refrigerator',
        'department' => 'Pharmacy',
        'location' => 'Main Pharmacy',
        'status' => 'In Use',
    ])->assertRedirect();

    Asset::create([
        'asset_no' => 'BBH-000099',
        'asset_name' => 'Broken Bed',
        'department' => 'Ward 1',
        'location' => 'Storage Room',
        'status' => 'Under Repair',
    ]);

    $this->actingAs($this->user)
        ->get('/assets?search=BBHIND-S300002315&department=Pharmacy&status=In+Use')
        ->assertOk()
        ->assertSee('Samsung Refrigerator')
        ->assertDontSee('Broken Bed');
});

test('updates, shows detail page, and soft-deletes an asset', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Old Monitor',
        'department' => 'ER',
        'location' => 'ER Bay 2',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get(route('assets.show', $asset))
        ->assertOk()
        ->assertSee('BBHIND-S300002315')
        ->assertSee('Old Monitor')
        ->assertSee('Print Tag')
        ->assertSee('Move Asset');

    $this->actingAs($this->user)
        ->put(route('assets.update', $asset), [
            'asset_no' => 'BBHIND-S300002315',
            'asset_name' => 'Updated Monitor',
            'model' => 'Philips',
            'serial_no' => 'SN-123',
            'date_acquired' => '2026-03-15',
            'department' => 'ICU',
            'location' => 'ICU Bed 1',
            'status' => 'Under Repair',
            'notes' => 'Sent for calibration',
        ])
        ->assertRedirect(route('assets.show', $asset));

    expect($asset->fresh()->asset_name)->toBe('Updated Monitor')
        ->and($asset->fresh()->status)->toBe('Under Repair');

    $this->actingAs($this->user)
        ->delete(route('assets.destroy', $asset))
        ->assertRedirect(route('assets.index'));

    expect(Asset::find($asset->id))->toBeNull()
        ->and(Asset::withTrashed()->find($asset->id))->not->toBeNull();
});
