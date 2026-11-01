<?php

use App\Models\Asset;
use App\Models\Transfer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Nurse Joy',
        'role' => 'staff',
        'must_change_password' => false,
    ]);
});

test('scanner page renders autofocused input and lookup trims input, matches case-insensitively, and handles unknown code', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'ECG Machine',
        'location' => 'ER Room 1',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get('/scan')
        ->assertOk()
        ->assertSee('Scan Property Tag')
        ->assertSee('autofocus', false);

    $this->actingAs($this->user)
        ->get('/scan/lookup?q=' . urlencode('  bbhind-s300002315 '))
        ->assertRedirect(route('assets.show', $asset));

    $this->actingAs($this->user)
        ->followingRedirects()
        ->get('/scan/lookup?q=UNKNOWN-999')
        ->assertOk()
        ->assertSee('No asset found with Asset No. "UNKNOWN-999"');
});

test('moving an asset updates its location and records transfer history with user and timestamp', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000010',
        'asset_name' => 'Wheelchair #4',
        'department' => 'ER',
        'location' => 'ER Triage',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get(route('assets.move.create', $asset))
        ->assertOk()
        ->assertSee('Move Asset')
        ->assertSee('ER Triage');

    $this->actingAs($this->user)
        ->post(route('assets.move.store', $asset), [
            'to_location' => 'ND-Station 3 Room 39',
            'department' => 'Nursing Station 3',
            'notes' => 'Transferred for patient admission',
        ])
        ->assertRedirect(route('assets.show', $asset));

    expect($asset->fresh()->location)->toBe('ND-Station 3 Room 39')
        ->and($asset->fresh()->department)->toBe('Nursing Station 3');

    $transfer = Transfer::where('asset_id', $asset->id)->first();
    expect($transfer)->not->toBeNull()
        ->and($transfer->from_location)->toBe('ER Triage')
        ->and($transfer->to_location)->toBe('ND-Station 3 Room 39')
        ->and($transfer->moved_by)->toBe($this->user->id)
        ->and($transfer->moved_at)->not->toBeNull()
        ->and($transfer->notes)->toBe('Transferred for patient admission');
});
