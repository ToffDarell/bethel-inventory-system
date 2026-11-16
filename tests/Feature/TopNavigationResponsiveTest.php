<?php

use App\Models\User;

test('top navigation bar displays institutional branding and all main navigation buttons', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Bethel Asset Tracker');
    $response->assertSee('BBH');
    $response->assertSee('title="Bethel Baptist Hospital, Inc."', false);
    $response->assertSee('<title>Bethel Asset Tracker - Bethel Baptist Hospital, Inc.</title>', false);
    $response->assertSeeInOrder(['Dashboard', 'Assets', 'Find Asset', 'Reports', 'Settings']);
    $response->assertSee('Add Asset');
    $response->assertSee('Sign out');
});

test('hospital short name setting defaults to BBH, validates 2 to 10 alphanumeric chars, and updates top nav subtitle only', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    // Default is BBH
    expect(\App\Models\Setting::get('hospital_short_name', 'BBH'))->toBe('BBH');

    // Settings > Hospital shows Short name field
    $settingsPage = $this->actingAs($admin)->get('/settings');
    $settingsPage->assertOk();
    $settingsPage->assertSee('Short name');
    $settingsPage->assertSee('name="hospital_short_name"', false);

    // Reject 1 character
    $this->actingAs($admin)->post('/settings', [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_short_name' => 'B',
        'hospital_address' => 'Fortich St., Malaybalay City',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
    ])->assertSessionHasErrors('hospital_short_name');

    // Reject 11 characters
    $this->actingAs($admin)->post('/settings', [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_short_name' => 'BBH12345678',
        'hospital_address' => 'Fortich St., Malaybalay City',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
    ])->assertSessionHasErrors('hospital_short_name');

    // Reject non-alphanumeric characters
    $this->actingAs($admin)->post('/settings', [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_short_name' => 'BBH-1',
        'hospital_address' => 'Fortich St., Malaybalay City',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
    ])->assertSessionHasErrors('hospital_short_name');

    // Accept trimmed 2 to 10 alphanumeric characters
    $this->actingAs($admin)->post('/settings', [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_short_name' => '  BBH2026  ',
        'hospital_address' => 'Fortich St., Malaybalay City',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
    ])->assertSessionHasNoErrors()->assertRedirect(route('settings.index'));

    expect(\App\Models\Setting::get('hospital_short_name'))->toBe('BBH2026');

    // Top nav shows BBH2026 and keeps full name in tooltip and title tag
    $dash = $this->actingAs($admin)->get('/dashboard');
    $dash->assertOk();
    $dash->assertSee('BBH2026');
    $dash->assertSee('title="Bethel Baptist Hospital, Inc."', false);
    $dash->assertSee('<title>Bethel Asset Tracker - Bethel Baptist Hospital, Inc.</title>', false);

    // Login page still shows full name
    $this->post('/logout');
    $loginResp = $this->get('/login');
    $loginResp->assertOk();
    $loginResp->assertSee('Bethel Baptist Hospital, Inc.');
});

test('each top navigation button links to the expected route', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertSee(route('dashboard'), false);
    $response->assertSee(route('assets.index'), false);
    $response->assertSee(route('assets.find'), false);
    $response->assertSee(route('reports.index'), false);
    $response->assertSee(route('settings.index'), false);
    $response->assertSee(route('assets.create'), false);
});

test('active top navigation button has distinct dark slate styling', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    // When on Dashboard, Dashboard is active
    $dashboardResp = $this->actingAs($admin)->get('/dashboard');
    $dashboardResp->assertOk();
    $dashboardResp->assertSee('bg-slate-900 text-white font-semibold text-xs', false);

    // When on Assets, Assets is active
    $assetsResp = $this->actingAs($admin)->get('/assets');
    $assetsResp->assertOk();
    $assetsResp->assertSee('bg-slate-900 text-white font-semibold text-xs', false);

    // When on Reports, Reports is active
    $reportsResp = $this->actingAs($admin)->get('/reports');
    $reportsResp->assertOk();
    $reportsResp->assertSee('bg-slate-900 text-white font-semibold text-xs', false);

    // When on Settings, Settings is active
    $settingsResp = $this->actingAs($admin)->get('/settings');
    $settingsResp->assertOk();
    $settingsResp->assertSee('bg-slate-900 text-white font-semibold text-xs', false);
});

test('admin sees privileged administrative tabs in settings while staff does not', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $adminResp = $this->actingAs($admin)->get('/settings');
    $adminResp->assertOk();
    $adminResp->assertSee('tab-btn-categories', false);
    $adminResp->assertSee('tab-btn-departments', false);
    $adminResp->assertSee('tab-btn-depreciation', false);
    $adminResp->assertSee('tab-btn-users', false);
    $adminResp->assertSee('View Activity Log');

    $staffResp = $this->actingAs($staff)->get('/settings');
    $staffResp->assertOk();
    $staffResp->assertDontSee('tab-btn-categories', false);
    $staffResp->assertDontSee('tab-btn-departments', false);
    $staffResp->assertDontSee('tab-btn-depreciation', false);
    $staffResp->assertDontSee('tab-btn-users', false);
    $staffResp->assertDontSee('View Activity Log');
});

test('top navigation contains no external CDNs or Google Fonts', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk();
    $response->assertDontSee('fonts.googleapis.com', false);
    $response->assertDontSee('cdnjs.cloudflare.com', false);
    $response->assertDontSee('cdn.jsdelivr.net', false);
});
