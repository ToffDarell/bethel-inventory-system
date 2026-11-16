<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use App\Support\ActivityChangeFormatter;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Dr. Administrator',
        'username' => 'dr.admin',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Nurse Joy',
        'username' => 'nurse.joy',
        'role' => 'staff',
        'must_change_password' => false,
    ]);
});

test('admin sees all six filter chips and search box on activity log page', function () {
    $response = $this->actingAs($this->admin)->get(route('settings.activity-log'));

    $response->assertOk();
    $response->assertSee('Activity Log');
    $response->assertSee('Audit Trail Active');
    $response->assertSee('All');
    $response->assertSee('Archived and restored');
    $response->assertSee('Location moves');
    $response->assertSee('Depreciation policy changes');
    $response->assertSee('Backups and restores');
    $response->assertSee('Logins');
    $response->assertSee('Filter by user, action, or asset number...');
});

test('location moves and condition changes are rendered as readable before and after chips', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-CHIP-01',
        'asset_name' => 'Mobile Ultrasound Scanner',
        'brand' => 'Mindray',
        'location' => 'ER',
        'department' => 'Emergency',
        'asset_condition' => 'In Good Condition',
        'status' => 'In Use',
    ]);

    // Update location and condition
    $this->actingAs($this->admin)->put(route('assets.update', $asset), [
        'asset_no' => 'BBH-CHIP-01',
        'asset_name' => 'Mobile Ultrasound Scanner',
        'brand' => 'Mindray',
        'location' => 'ICU',
        'department' => 'Emergency',
        'asset_condition' => 'Damage',
        'status' => 'In Use',
    ])->assertRedirect(route('assets.show', $asset));

    $response = $this->actingAs($this->admin)->get(route('settings.activity-log'));

    $response->assertOk();
    // Test exact readable before and after text strings
    $response->assertSee('Location:');
    $response->assertSee('ER');
    $response->assertSee('ICU');
    $response->assertSee('Condition:');
    $response->assertSee('In Good Condition');
    $response->assertSee('Damage');
});

test('filter chips accurately slice activities by category', function () {
    // 1. Location move activity
    $asset = Asset::create([
        'asset_no' => 'BBH-FLT-01',
        'asset_name' => 'Infusion Pump',
        'location' => 'Station 1',
        'status' => 'In Use',
    ]);
    activity()
        ->performedOn($asset)
        ->causedBy($this->admin)
        ->withProperties(['from_location' => 'Station 1', 'to_location' => 'Station 2'])
        ->log('Moved asset BBH-FLT-01 from "Station 1" to "Station 2"');

    // 2. Backup activity
    activity()
        ->causedBy($this->admin)
        ->withProperties(['backup_file' => 'bethel-backup-2026-11-07.sqlite'])
        ->log('Created manual database backup: bethel-backup-2026-11-07.sqlite');

    // 3. Login activity
    activity()
        ->causedBy($this->admin)
        ->log('User Dr. Administrator (dr.admin) signed in');

    // 4. Depreciation policy activity
    activity()
        ->causedBy($this->admin)
        ->log("Depreciation start convention changed from 'january_1' to 'july_1' by Dr. Administrator");

    // Test location filter
    $locResponse = $this->actingAs($this->admin)->get(route('settings.activity-log', ['filter' => 'location']));
    $locResponse->assertOk();
    $locResponse->assertSee('Moved asset BBH-FLT-01');
    $locResponse->assertDontSee('Created manual database backup:');
    $locResponse->assertDontSee('signed in');

    // Test backup filter
    $backupResponse = $this->actingAs($this->admin)->get(route('settings.activity-log', ['filter' => 'backup']));
    $backupResponse->assertOk();
    $backupResponse->assertSee('Created manual database backup:');
    $backupResponse->assertDontSee('Moved asset BBH-FLT-01');

    // Test login filter
    $loginResponse = $this->actingAs($this->admin)->get(route('settings.activity-log', ['filter' => 'login']));
    $loginResponse->assertOk();
    $loginResponse->assertSee('signed in');
    $loginResponse->assertDontSee('Created manual database backup:');

    // Test depreciation filter
    $depResponse = $this->actingAs($this->admin)->get(route('settings.activity-log', ['filter' => 'depreciation']));
    $depResponse->assertOk();
    $depResponse->assertSee('Depreciation start convention changed');
    $depResponse->assertDontSee('signed in');
});

test('staff is strictly forbidden from activity log and change formatter never reveals money to non-admins', function () {
    $this->actingAs($this->staff)->get(route('settings.activity-log'))->assertForbidden();

    $asset = Asset::create([
        'asset_no' => 'BBH-SHIELD-01',
        'asset_name' => 'High-Field MRI Scanner',
        'cost' => 12500000.00,
        'salvage_value' => 1250000.00,
        'status' => 'In Use',
    ]);

    activity()
        ->performedOn($asset)
        ->causedBy($this->admin)
        ->withProperties([
            'old' => ['cost' => 12000000.00, 'salvage_value' => 1200000.00, 'location' => 'Room A'],
            'attributes' => ['cost' => 12500000.00, 'salvage_value' => 1250000.00, 'location' => 'Room B'],
        ])
        ->log('Updated asset BBH-SHIELD-01');

    $activity = Activity::forSubject($asset)->latest('id')->first();
    expect($activity)->not->toBeNull();

    // Admin sees financial chips
    $adminChanges = ActivityChangeFormatter::format($activity, true);
    $adminLabels = array_column($adminChanges, 'label');
    expect($adminLabels)->toContain('Cost');
    expect($adminLabels)->toContain('Disposal Value');
    expect($adminLabels)->toContain('Location');

    // Non-admin NEVER sees financial chips
    $staffChanges = ActivityChangeFormatter::format($activity, false);
    $staffLabels = array_column($staffChanges, 'label');
    expect($staffLabels)->not->toContain('Cost');
    expect($staffLabels)->not->toContain('Disposal Value');
    expect($staffLabels)->not->toContain('Salvage Value');
    expect($staffLabels)->toContain('Location');

    foreach ($staffChanges as $change) {
        expect($change['label'])->not->toContain('Cost');
        expect($change['old'])->not->toContain('₱');
        expect($change['new'])->not->toContain('₱');
    }
});

test('activity log view contains no em dashes in text', function () {
    $content = file_get_contents(resource_path('views/settings/activity-log.blade.php'));
    expect($content)->not->toContain('—');
    expect($content)->not->toContain('–');
});

test('activity log search runs on the server and works across paginated pages', function () {
    // Create 30 activities matching search term
    for ($i = 1; $i <= 30; $i++) {
        activity()
            ->causedBy($this->admin)
            ->withProperties(['item_index' => $i])
            ->log("Calibrated UniqueDeviceAlpha Unit #{$i}");
    }

    // Create 5 activities with a different term
    for ($j = 1; $j <= 5; $j++) {
        activity()
            ->causedBy($this->admin)
            ->log("Inspected OtherDeviceBeta Unit #{$j}");
    }

    // Request Page 1 with search
    $responsePage1 = $this->actingAs($this->admin)->get(route('settings.activity-log', [
        'search' => 'UniqueDeviceAlpha',
    ]));

    $responsePage1->assertOk();
    $responsePage1->assertSee('Calibrated UniqueDeviceAlpha Unit #30');
    $responsePage1->assertDontSee('Inspected OtherDeviceBeta');
    // Verify query string is preserved in pagination links
    $responsePage1->assertSee('search=UniqueDeviceAlpha');

    // Request Page 2 with search
    $responsePage2 = $this->actingAs($this->admin)->get(route('settings.activity-log', [
        'search' => 'UniqueDeviceAlpha',
        'page' => 2,
    ]));

    $responsePage2->assertOk();
    $responsePage2->assertSee('Calibrated UniqueDeviceAlpha Unit #1');
    $responsePage2->assertDontSee('Inspected OtherDeviceBeta');
    $responsePage2->assertSee('search=UniqueDeviceAlpha');
});
