<?php

use App\Models\Asset;
use App\Models\User;

test('dashboard displays total assets, counts by status and department, and recent assets', function () {
    $user = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    Asset::create([
        'asset_no' => 'BBH-000001',
        'asset_name' => 'ICU Ventilator',
        'department' => 'ICU',
        'location' => 'ICU Room 2',
        'status' => 'In Use',
    ]);
    Asset::create([
        'asset_no' => 'BBH-000002',
        'asset_name' => 'ER Stretcher',
        'department' => 'Emergency Room',
        'location' => 'ER Bay 4',
        'status' => 'Under Repair',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Assets by Department')
        ->assertSee('Recently Added Assets')
        ->assertSee('ICU Ventilator')
        ->assertSee('ER Stretcher')
        ->assertSee('ICU')
        ->assertSee('Emergency Room')
        ->assertSee('Under Repair');
});

test('activity log page is accessible to admin and forbidden to staff', function () {
    $admin = User::factory()->create(['name' => 'Hospital Admin', 'role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['name' => 'Staff Member', 'role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($admin)
        ->post('/assets', [
            'asset_no' => 'BBH-000100',
            'asset_name' => 'Logged Microscope',
            'status' => 'In Use',
        ]);

    $this->actingAs($staff)
        ->get('/settings/activity-log')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/settings/activity-log')
        ->assertOk()
        ->assertSee('Activity Log')
        ->assertSee('Logged Microscope');
});
