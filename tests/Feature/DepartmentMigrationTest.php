<?php

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('department and asset number history migrations run additively with correct columns', function () {
    expect(Schema::hasTable('departments'))->toBeTrue();
    expect(Schema::hasColumns('departments', [
        'id', 'name', 'code', 'next_number', 'active', 'code_confirmed', 'created_at', 'updated_at'
    ]))->toBeTrue();

    expect(Schema::hasColumns('assets', [
        'department_id', 'needs_tag_reprint', 'department'
    ]))->toBeTrue();

    expect(Schema::hasTable('asset_number_histories'))->toBeTrue();
    expect(Schema::hasColumns('asset_number_histories', [
        'id', 'asset_id', 'old_no', 'new_no', 'reason', 'changed_by', 'changed_at', 'created_at', 'updated_at'
    ]))->toBeTrue();
});

test('department model relations and scopes work', function () {
    $dept = Department::create([
        'name' => 'Emergency Department ' . uniqid(),
        'code' => 'ED',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    expect($dept->next_number)->toBe(1);
    expect($dept->active)->toBeTrue();
    expect($dept->code_confirmed)->toBeTrue();

    $asset = Asset::create([
        'asset_no' => 'BBHED-0001',
        'asset_name' => 'Defibrillator',
        'department_id' => $dept->id,
        'department' => $dept->name,
        'status' => 'In Use',
        'needs_tag_reprint' => true,
    ]);

    expect($dept->assets()->count())->toBe(1);
    expect($asset->department_id)->toBe($dept->id);
    expect($asset->needs_tag_reprint)->toBeTrue();

    $user = User::factory()->create();
    $history = AssetNumberHistory::create([
        'asset_id' => $asset->id,
        'old_no' => 'BBH-OLD-001',
        'new_no' => 'BBHED-0001',
        'reason' => 'Department change to Emergency',
        'changed_by' => $user->id,
    ]);

    expect($asset->numberHistories()->count())->toBe(1);
    expect($asset->numberHistories->first()->old_no)->toBe('BBH-OLD-001');
    expect($history->user->id)->toBe($user->id);
});
