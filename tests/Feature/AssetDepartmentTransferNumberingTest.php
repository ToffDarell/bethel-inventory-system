<?php

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $this->staff = User::factory()->create([
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('asset_number_digits', '4');
    Setting::set('asset_number_scope', 'department');
    Setting::set('reassign_number_on_dept_change', '1');

    $this->deptER = Department::create([
        'name' => 'Emergency Room',
        'code' => 'ER',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $this->deptBio = Department::create([
        'name' => 'Biomedical Engineering',
        'code' => 'BIOMED',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);
});

test('moving asset to a different department reassigns number, records history, and sets reprint flag', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Defibrillator Unit',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Bay 1',
        'status' => 'In Use',
    ]);
    $this->deptER->update(['next_number' => 2]);

    // Move asset to Biomedical Engineering
    $response = $this->actingAs($this->admin)->post("/assets/{$asset->id}/move", [
        'department_id' => $this->deptBio->id,
        'to_location' => 'Biomedical Workshop',
        'notes' => 'Transferred for scheduled servicing',
    ]);

    $response->assertRedirect("/assets/{$asset->id}");
    $response->assertSessionHas('reassigned_new_no', 'BBHBIOMED-0001');

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHBIOMED-0001');
    expect($fresh->department_id)->toBe($this->deptBio->id);
    expect($fresh->department)->toBe($this->deptBio->name);
    expect($fresh->location)->toBe('Biomedical Workshop');
    expect($fresh->needs_tag_reprint)->toBeTrue();

    // Check history was recorded
    expect($fresh->numberHistories()->count())->toBe(1);
    $history = $fresh->numberHistories->first();
    expect($history->old_no)->toBe('BBHER-0001');
    expect($history->new_no)->toBe('BBHBIOMED-0001');
    expect($history->changed_by)->toBe($this->admin->id);
});

test('moving asset to another room in the same department keeps the number', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Portable Suction',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Trauma Room 1',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->staff)->post("/assets/{$asset->id}/move", [
        'department_id' => $this->deptER->id,
        'to_location' => 'Triage Station',
    ]);

    $response->assertRedirect("/assets/{$asset->id}");

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHER-0001');
    expect($fresh->location)->toBe('Triage Station');
    expect($fresh->needs_tag_reprint)->toBeFalse();
    expect($fresh->numberHistories()->count())->toBe(0);
});

test('legacy paper tag number: staff move keeps current number, admin can choose to assign new or keep', function () {
    $asset = Asset::create([
        'asset_no' => 'OLD-PAPER-99', // Does NOT match pattern
        'asset_name' => 'Vintage Centrifuge',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'ER Lab Corner',
        'status' => 'In Use',
    ]);

    // Staff move defaults to keep current number
    $this->actingAs($this->staff)->post("/assets/{$asset->id}/move", [
        'department_id' => $this->deptBio->id,
        'to_location' => 'Biomed Workshop',
    ]);

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('OLD-PAPER-99'); // Kept!
    expect($fresh->department_id)->toBe($this->deptBio->id);
    expect($fresh->needs_tag_reprint)->toBeFalse();

    // Admin move with assign_new
    $this->actingAs($this->admin)->post("/assets/{$asset->id}/move", [
        'department_id' => $this->deptER->id,
        'to_location' => 'ER Bay 2',
        'paper_tag_number_action' => 'assign_new',
    ]);

    $freshAdmin = $asset->fresh();
    expect($freshAdmin->asset_no)->toBe('BBHER-0001');
    expect($freshAdmin->needs_tag_reprint)->toBeTrue();
    expect($freshAdmin->numberHistories()->count())->toBe(1);
    expect($freshAdmin->numberHistories->first()->old_no)->toBe('OLD-PAPER-99');
});

test('tag printing clears the needs_tag_reprint flag', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0005',
        'asset_name' => 'Nebulizer',
        'department_id' => $this->deptER->id,
        'status' => 'In Use',
        'needs_tag_reprint' => true,
    ]);

    $response = $this->actingAs($this->staff)->get(route('tags.print', ['id' => $asset->id]));
    $response->assertOk();

    expect($asset->fresh()->needs_tag_reprint)->toBeFalse();
});

test('editing asset section, location, personnel, or station in same department never renumbers or flags reprint', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Television',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Original Room',
        'section' => 'Original Station',
        'status' => 'In Use',
    ]);
    $this->deptER->update(['next_number' => 2]);

    // Admin edits asset details (changing section and location, keeping same department)
    $response = $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_name' => 'Television Updated',
        'department_id' => (string) $this->deptER->id, // string form submission
        'section' => 'STATION 3',
        'location' => 'STATION 3 GROUND FLOOR',
        'status' => 'In Use',
        'cost' => '25000.00',
    ]);

    $response->assertRedirect(route('assets.show', $asset));

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHER-0001'); // Unchanged!
    expect($fresh->needs_tag_reprint)->toBeFalse();
    expect($fresh->section)->toBe('STATION 3');
    expect($fresh->location)->toBe('STATION 3 GROUND FLOOR');
    expect($fresh->numberHistories()->count())->toBe(0);
    expect($this->deptER->fresh()->next_number)->toBe(2); // Counter untouched
});

test('department rename or case change never counts as a transfer because department_id is compared by integer ID', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Trauma Bed',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Trauma Room 1',
        'status' => 'In Use',
    ]);
    $this->deptER->update(['next_number' => 2]);

    // Rename department from 'Emergency Room' to 'Emergency Department'
    $this->deptER->update(['name' => 'Emergency Department']);

    // Admin saves form with the updated department name but same ID
    $response = $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_name' => 'Trauma Bed',
        'department_id' => (string) $this->deptER->id,
        'location' => 'Trauma Room 2',
        'status' => 'In Use',
    ]);

    $response->assertRedirect(route('assets.show', $asset));

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHER-0001'); // Kept!
    expect($fresh->needs_tag_reprint)->toBeFalse();
    expect($fresh->numberHistories()->count())->toBe(0);
});

test('moving or editing asset with department_number_action=keep retains current number, does not burn counter, and does not flag reprint', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Ultrasound Machine',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Room A',
        'status' => 'In Use',
    ]);
    $initialNext = $this->deptBio->next_number;

    // Move with department_number_action=keep
    $response = $this->actingAs($this->admin)->post(route('assets.move.store', $asset), [
        'department_id' => $this->deptBio->id,
        'to_location' => 'Bio Lab',
        'department_number_action' => 'keep',
    ]);

    $response->assertRedirect(route('assets.show', $asset));

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHER-0001'); // Kept!
    expect($fresh->department_id)->toBe($this->deptBio->id);
    expect($fresh->needs_tag_reprint)->toBeFalse();
    expect($fresh->numberHistories()->count())->toBe(0);
    expect($this->deptBio->fresh()->next_number)->toBe($initialNext); // Counter NOT burned!
});

test('revert number action restores old number, clears reprint flag, records history with reason Reverted by admin, and keeps counters unchanged', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Defibrillator',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'location' => 'Room 1',
        'status' => 'In Use',
    ]);

    // Transfer asset to Bio and renumber
    $this->actingAs($this->admin)->post(route('assets.move.store', $asset), [
        'department_id' => $this->deptBio->id,
        'to_location' => 'Bio Room',
        'department_number_action' => 'assign_new',
    ]);

    $transferred = $asset->fresh();
    expect($transferred->asset_no)->toBe('BBHBIOMED-0001');
    expect($transferred->needs_tag_reprint)->toBeTrue();
    expect($transferred->numberHistories()->count())->toBe(1);
    $bioNext = $this->deptBio->fresh()->next_number;

    // Staff cannot revert (403)
    $this->actingAs($this->staff)->post(route('assets.revert-number', $asset))->assertForbidden();

    // Admin reverts number
    $revertRes = $this->actingAs($this->admin)->post(route('assets.revert-number', $asset));
    $revertRes->assertRedirect(route('assets.show', $asset));
    $revertRes->assertSessionHas('success');

    $reverted = $asset->fresh();
    expect($reverted->asset_no)->toBe('BBHER-0001'); // Restored!
    expect($reverted->needs_tag_reprint)->toBeFalse(); // Cleared!

    // History contains Reverted by admin
    expect($reverted->numberHistories()->count())->toBe(2);
    $latest = $reverted->numberHistories()->first();
    expect($latest->old_no)->toBe('BBHBIOMED-0001');
    expect($latest->new_no)->toBe('BBHER-0001');
    expect($latest->reason)->toBe('Reverted by admin');
    expect($latest->changed_by)->toBe($this->admin->id);

    // Department counter was NOT rolled back, allocated number is NOT reused
    expect($this->deptBio->fresh()->next_number)->toBe($bioNext);
});

test('banner displays exact wording with old and new numbers when department changes', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'Patient Monitor',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'status' => 'In Use',
    ]);

    // Move to Bio with assign_new
    $this->actingAs($this->admin)->post(route('assets.move.store', $asset), [
        'department_id' => $this->deptBio->id,
        'to_location' => 'Bio Station',
        'department_number_action' => 'assign_new',
    ]);

    $showRes = $this->actingAs($this->admin)->get(route('assets.show', $asset));
    $showRes->assertOk();
    $showRes->assertSee("This asset's number changed from", false);
    $showRes->assertSee("BBHER-0001");
    $showRes->assertSee("BBHBIOMED-0001");
    $showRes->assertSee("because its department changed. Print the new tag so the equipment matches the register.", false);
    $showRes->assertSee("Print Tag");
});

test('preview next number endpoint returns real next candidate number without incrementing counter', function () {
    $res = $this->actingAs($this->staff)->get(route('departments.preview-next-number', $this->deptER));
    $res->assertOk();
    $res->assertJson([
        'next_number' => 'BBHER-0001',
        'code_confirmed' => true,
    ]);

    // Counter remains 1
    expect($this->deptER->fresh()->next_number)->toBe(1);
});

test('revert fails with clear message if the old number is now used by another asset', function () {
    // Asset A transferred from ER to Biomed (old: BBHER-0001, new: BBHBIOMED-0001)
    $assetA = Asset::create([
        'asset_no' => 'BBHBIOMED-0001',
        'asset_name' => 'Cardiac Monitor',
        'department_id' => $this->deptBio->id,
        'department' => $this->deptBio->name,
        'status' => 'In Use',
        'needs_tag_reprint' => true,
    ]);
    AssetNumberHistory::create([
        'asset_id' => $assetA->id,
        'old_no' => 'BBHER-0001',
        'new_no' => 'BBHBIOMED-0001',
        'reason' => 'Transfer to Biomed',
        'changed_by' => $this->admin->id,
        'changed_at' => now(),
    ]);

    // Another Asset B was since assigned BBHER-0001
    Asset::create([
        'asset_no' => 'BBHER-0001',
        'asset_name' => 'New ER Defibrillator',
        'department_id' => $this->deptER->id,
        'department' => $this->deptER->name,
        'status' => 'In Use',
    ]);

    // Admin attempts to revert Asset A back to BBHER-0001
    $response = $this->actingAs($this->admin)->post(route('assets.revert-number', $assetA));

    $response->assertRedirect(route('assets.show', $assetA));
    $response->assertSessionHasErrors(['asset_no']);
    $errors = session('errors')->get('asset_no');
    expect($errors[0])->toContain('Cannot revert because previous number BBHER-0001 is already assigned to another asset');

    // Asset A number is untouched
    expect($assetA->fresh()->asset_no)->toBe('BBHBIOMED-0001');
    expect($assetA->fresh()->needs_tag_reprint)->toBeTrue();
});

test('revert shows a note when the reverted number no longer matches the department code', function () {
    // Asset transferred to Surgery with code SURG, but previous number was BBHER-0001
    $deptSurgery = Department::create([
        'name' => 'Surgery',
        'code' => 'SURG',
        'next_number' => 2,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $asset = Asset::create([
        'asset_no' => 'BBHSURG-0001',
        'asset_name' => 'Surgical Laser',
        'department_id' => $deptSurgery->id,
        'department' => $deptSurgery->name,
        'status' => 'In Use',
        'needs_tag_reprint' => true,
    ]);

    AssetNumberHistory::create([
        'asset_id' => $asset->id,
        'old_no' => 'BBHER-0001',
        'new_no' => 'BBHSURG-0001',
        'reason' => 'Transfer to Surgery',
        'changed_by' => $this->admin->id,
        'changed_at' => now(),
    ]);

    // Admin reverts number to BBHER-0001 while asset stays in Surgery
    $response = $this->actingAs($this->admin)->post(route('assets.revert-number', $asset));

    $response->assertRedirect(route('assets.show', $asset));
    $response->assertSessionHas('success');
    $success = session('success');
    expect($success)->toContain('Asset number reverted from BBHSURG-0001 back to BBHER-0001');
    expect($success)->toContain('Note: The reverted number (BBHER-0001) does not match current department code (SURG)');

    $fresh = $asset->fresh();
    expect($fresh->asset_no)->toBe('BBHER-0001');
    expect($fresh->needs_tag_reprint)->toBeFalse();

    // Verify detail page shows the mismatch note badge
    $showRes = $this->actingAs($this->admin)->get(route('assets.show', $asset));
    $showRes->assertOk();
    $showRes->assertSee('Note: Number does not match department code (SURG)');
});

test('two parallel asset number allocations never produce duplicate numbers', function () {
    $numberService = app(\App\Services\AssetNumberService::class);

    $allocated = [];
    for ($i = 0; $i < 5; $i++) {
        $no = $numberService->generateNextNumber($this->deptER);
        $allocated[] = $no;
        Asset::create([
            'asset_no' => $no,
            'asset_name' => "Parallel Asset {$i}",
            'department_id' => $this->deptER->id,
            'status' => 'In Use',
        ]);
    }

    expect(array_unique($allocated))->toHaveCount(5);
    expect($allocated)->toBe(['BBHER-0001', 'BBHER-0002', 'BBHER-0003', 'BBHER-0004', 'BBHER-0005']);
});


