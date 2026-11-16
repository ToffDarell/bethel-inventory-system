<?php

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
});

test('admin can create department with valid code and sequence', function () {
    $response = $this->actingAs($this->admin)->post('/settings/departments', [
        'name' => 'Cardiology Complex',
        'code' => 'CARD',
        'next_number' => 10,
        'active' => 1,
    ]);

    $response->assertRedirect('/settings?tab=departments');
    $response->assertSessionHas('success');

    $dept = Department::where('code', 'CARD')->first();
    expect($dept)->not->toBeNull();
    expect($dept->name)->toBe('Cardiology Complex');
    expect($dept->next_number)->toBe(10);
    expect($dept->code_confirmed)->toBeTrue();
});

test('admin can update department and confirm code', function () {
    $dept = Department::create([
        'name' => 'Pediatrics Unit',
        'code' => 'PED',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => false, // Needs review
    ]);

    // Confirm code
    $response = $this->actingAs($this->admin)->post("/settings/departments/{$dept->id}/confirm-code");
    $response->assertRedirect('/settings?tab=departments');
    expect($dept->fresh()->code_confirmed)->toBeTrue();

    // Update details
    $response = $this->actingAs($this->admin)->put("/settings/departments/{$dept->id}", [
        'name' => 'Pediatric Intensive Care Unit',
        'code' => 'PICU',
        'next_number' => 5,
        'active' => 1,
        'code_confirmed' => 1,
    ]);

    $response->assertRedirect('/settings?tab=departments');
    $fresh = $dept->fresh();
    expect($fresh->name)->toBe('Pediatric Intensive Care Unit');
    expect($fresh->code)->toBe('PICU');
    expect($fresh->next_number)->toBe(5);
});

test('admin can update asset numbering settings', function () {
    $response = $this->actingAs($this->admin)->post('/settings/numbering', [
        'reassign_number_on_dept_change' => '1',
        'asset_number_digits' => 5,
        'asset_number_scope' => 'department',
        'asset_no_prefix' => 'BBH',
    ]);

    $response->assertRedirect('/settings?tab=departments');
    expect(Setting::get('asset_number_digits'))->toBe('5');
    expect(Setting::get('asset_number_scope'))->toBe('department');
    expect(Setting::get('reassign_number_on_dept_change'))->toBe('1');
    expect(Setting::get('asset_no_prefix'))->toBe('BBH');
});

test('staff is forbidden from managing departments or numbering settings', function () {
    $dept = Department::create([
        'name' => 'Radiology',
        'code' => 'RAD',
        'next_number' => 1,
        'code_confirmed' => false,
    ]);

    $this->actingAs($this->staff)->post('/settings/departments', [
        'name' => 'Surgery',
        'code' => 'SURG',
    ])->assertForbidden();

    $this->actingAs($this->staff)->put("/settings/departments/{$dept->id}", [
        'name' => 'Radiology Dept',
        'code' => 'RAD',
        'next_number' => 2,
    ])->assertForbidden();

    $this->actingAs($this->staff)->post("/settings/departments/{$dept->id}/confirm-code")
        ->assertForbidden();

    $this->actingAs($this->staff)->post('/settings/numbering', [
        'asset_number_digits' => 4,
        'asset_number_scope' => 'department',
        'asset_no_prefix' => 'BBH',
    ])->assertForbidden();
});
