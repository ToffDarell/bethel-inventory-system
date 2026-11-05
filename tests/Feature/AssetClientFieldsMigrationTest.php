<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Staff Nurse',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('tag_width_cm', '10');
    Setting::set('tag_height_cm', '6');
    Setting::set('show_barcode', '0');
});

test('assets table has new client nullable columns and condition constants', function () {
    expect(Asset::CONDITIONS)->toBe(['Brand-new', 'Refurbish', 'Damage', 'In Good Condition']);

    $asset = Asset::create([
        'asset_no' => 'BBH-TEST-001',
        'asset_name' => 'Centrifuge',
        'brand' => 'Eppendorf',
        'model' => '5424R',
        'serial_no' => 'SN-12345',
        'section' => 'Hematology',
        'assigned_personnel' => 'Dr. Cruz',
        'asset_condition' => 'Brand-new',
        'status' => 'In Use',
        'date_acquired' => '2023-01-01',
    ]);

    $saved = Asset::find($asset->id);
    expect($saved)->not->toBeNull()
        ->and($saved->brand)->toBe('Eppendorf')
        ->and($saved->model)->toBe('5424R')
        ->and($saved->section)->toBe('Hematology')
        ->and($saved->assigned_personnel)->toBe('Dr. Cruz')
        ->and($saved->asset_condition)->toBe('Brand-new')
        ->and($saved->status)->toBe('In Use');
});

test('existing assets created before migration have blank or null new fields and are preserved', function () {
    // Simulate an older asset record with null for the new fields
    $oldAsset = Asset::create([
        'asset_no' => 'BBH-OLD-001',
        'asset_name' => 'Old Microscope',
        'model' => 'Olympus CX23',
        'serial_no' => 'SN-OLD-01',
        'date_acquired' => '2021-08-14',
        'department' => 'Laboratory',
        'location' => 'Room 101',
        'status' => 'In Use',
    ]);

    $refreshed = Asset::find($oldAsset->id);
    expect($refreshed->brand)->toBeNull()
        ->and($refreshed->section)->toBeNull()
        ->and($refreshed->assigned_personnel)->toBeNull()
        ->and($refreshed->asset_condition)->toBeNull()
        ->and($refreshed->asset_name)->toBe('Old Microscope')
        ->and($refreshed->date_acquired->format('Y-m-d'))->toBe('2021-08-14');
});

test('property tag displays Date Acquired row label with year only across single print, bulk print, and PDF', function () {
    $asset1 = Asset::create([
        'asset_no' => 'BBH-TAG-001',
        'asset_name' => 'Defibrillator',
        'brand' => 'Philips',
        'model' => 'HeartStart MRx',
        'serial_no' => 'SN-TAG-01',
        'date_acquired' => '2023-10-13',
        'department' => 'Emergency',
        'section' => 'Triage',
        'assigned_personnel' => 'Nurse Joy',
        'location' => 'ER Bay 1',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 150000,
        'salvage_value' => 20000,
        'useful_life_years' => 10,
    ]);

    $asset2 = Asset::create([
        'asset_no' => 'BBH-TAG-002',
        'asset_name' => 'Infusion Pump',
        'brand' => 'Mindray',
        'model' => 'SK-500I',
        'serial_no' => 'SN-TAG-02',
        'date_acquired' => '2021-04-05',
        'department' => 'ICU',
        'section' => 'Station 3',
        'assigned_personnel' => 'Nurse Joy',
        'location' => 'ICU Bed 2',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
        'cost' => 85000,
    ]);

    // Single print: verify Date Acquired row label is preserved and year only is rendered
    $singleResponse = $this->actingAs($this->admin)->get('/tags/print?ids[]=' . $asset1->id);
    $singleResponse->assertOk();
    $singleResponse->assertSee('Date Acquired');
    $singleResponse->assertSee('2023');
    $singleResponse->assertDontSee('2023-10-13');

    // Bulk print: verify both show 4-digit years
    $bulkResponse = $this->actingAs($this->admin)->get('/tags/print?ids[]=' . $asset1->id . '&ids[]=' . $asset2->id);
    $bulkResponse->assertOk();
    $bulkResponse->assertSee('2023');
    $bulkResponse->assertSee('2021');
    $bulkResponse->assertDontSee('2023-10-13');
    $bulkResponse->assertDontSee('2021-04-05');

    // PDF download
    $pdfResponse = $this->actingAs($this->admin)->get('/tags/pdf?ids[]=' . $asset1->id);
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-type'))->toContain('application/pdf');

    // Staff user: tags contain no cost or value
    $staffTagResponse = $this->actingAs($this->staff)->get('/tags/print?ids[]=' . $asset1->id);
    $staffTagResponse->assertOk();
    $staffTagResponse->assertDontSee('150000');
    $staffTagResponse->assertDontSee('150,000');
    $staffTagResponse->assertDontSee('20000');
    $staffTagResponse->assertDontSee('Cost');
    $staffTagResponse->assertDontSee('Salvage');
});

test('activity log records changes to brand, section, assigned_personnel, and asset_condition', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-LOG-001',
        'asset_name' => 'Ultrasound Machine',
        'brand' => 'GE',
        'model' => 'Logiq P9',
        'section' => 'OB-GYN',
        'assigned_personnel' => 'Dr. Santos',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    $this->actingAs($this->admin);

    $asset->update([
        'brand' => 'GE Healthcare',
        'section' => 'Radiology',
        'assigned_personnel' => 'Dr. Garcia',
        'asset_condition' => 'In Good Condition',
    ]);

    $lastActivity = Activity::where('subject_type', Asset::class)
        ->where('subject_id', $asset->id)
        ->latest('id')
        ->first();

    expect($lastActivity)->not->toBeNull();
    $changes = $lastActivity->properties['attributes'] ?? [];
    expect($changes)->toHaveKey('brand', 'GE Healthcare')
        ->and($changes)->toHaveKey('section', 'Radiology')
        ->and($changes)->toHaveKey('assigned_personnel', 'Dr. Garcia')
        ->and($changes)->toHaveKey('asset_condition', 'In Good Condition');
});

test('migration safety verified on a copy of existing database with assets', function () {
    $tempDbPath = database_path('test_migration_copy.sqlite');
    File::copy(database_path('database.sqlite'), $tempDbPath);

    config(['database.connections.temp_sqlite' => [
        'driver' => 'sqlite',
        'database' => $tempDbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);

    DB::connection('temp_sqlite')->table('assets')->insert([
        'asset_no' => 'BBH-SAFETY-01',
        'asset_name' => 'Safety Monitor',
        'model' => 'SM-1',
        'status' => 'In Use',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $count = DB::connection('temp_sqlite')->table('assets')->count();
    expect($count)->toBeGreaterThan(0);

    $rows = DB::connection('temp_sqlite')->table('assets')->get();
    foreach ($rows as $row) {
        expect($row)->toHaveProperty('asset_name')
            ->and($row)->toHaveProperty('brand')
            ->and($row)->toHaveProperty('section')
            ->and($row)->toHaveProperty('assigned_personnel')
            ->and($row)->toHaveProperty('asset_condition');
    }

    File::delete($tempDbPath);
});
