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

test('find asset page renders autofocused input, scan redirects to find, and lookup handles exact and unknown code', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'ECG Machine',
        'location' => 'ER Room 1',
        'status' => 'In Use',
    ]);

    // /scan redirects to /find
    $this->actingAs($this->user)
        ->get('/scan')
        ->assertRedirect(route('assets.find'));

    // /find renders Find Asset page with autofocus
    $this->actingAs($this->user)
        ->get('/find')
        ->assertOk()
        ->assertSee('Find Asset')
        ->assertSee('autofocus', false);

    // /scan/lookup redirects to /find/lookup which opens the asset
    $this->actingAs($this->user)
        ->followingRedirects()
        ->get('/scan/lookup?q=' . urlencode('  bbhind-s300002315 '))
        ->assertOk()
        ->assertSee('ECG Machine');

    // /find/lookup with direct query opens the asset
    $this->actingAs($this->user)
        ->get('/find/lookup?q=' . urlencode('  bbhind-s300002315 '))
        ->assertRedirect(route('assets.show', $asset));

    // Unknown code handles gracefully
    $this->actingAs($this->user)
        ->followingRedirects()
        ->get('/find/lookup?q=UNKNOWN-999')
        ->assertOk()
        ->assertSee('UNKNOWN-999');
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
