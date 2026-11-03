<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\TagAssetService;
use Illuminate\Support\Facades\File;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Test',
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

test('property tag barcode setting defaults to off', function () {
    expect(Setting::get('show_barcode', '0'))->toBe('0');
    expect(app(TagAssetService::class)->showBarcode())->toBeFalse();
});

test('tag HTML has no barcode strip when show_barcode is off', function () {
    Setting::set('show_barcode', '0');

    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Automated External Defibrillator with Monitor',
        'model' => 'Philips HeartStart MRx',
        'serial_no' => 'SN-998877',
        'date_acquired' => '2026-05-10',
        'location' => 'ICU Bed 1',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)->get('/tags/print?ids[]=' . $asset->id);
    $response->assertOk();
    $response->assertSee('Bethel Baptist Hospital, Inc.');
    $response->assertSee('Automated External Defibrillator with Monitor');
    $response->assertSee('BBHIND-S300002315');
    // Barcode strip tr is NOT rendered when show_barcode is 0
    $response->assertDontSee('barcode-strip');
});

test('tag HTML renders Code 128 barcode SVG when show_barcode is on', function () {
    Setting::set('show_barcode', '1');

    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Automated External Defibrillator with Monitor',
        'model' => 'Philips HeartStart MRx',
        'serial_no' => 'SN-998877',
        'date_acquired' => '2026-05-10',
        'location' => 'ICU Bed 1',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)->get('/tags/print?ids[]=' . $asset->id);
    $response->assertOk();
    $response->assertSee('barcode-strip');
    $response->assertSee('<svg', false);
    $response->assertSee('*BBHIND-S300002315*');
});

test('staff cannot change the show_barcode setting and receives 403', function () {
    $response = $this->actingAs($this->staff)->post(route('settings.update'), [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
        'show_barcode' => '1',
    ]);

    $response->assertForbidden();
    expect(Setting::get('show_barcode', '0'))->toBe('0');
});

test('admin can turn barcode setting on and change is logged in activity log', function () {
    Setting::set('show_barcode', '0');

    $response = $this->actingAs($this->admin)->post(route('settings.update'), [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
        'show_barcode' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    expect(Setting::get('show_barcode'))->toBe('1');

    $lastLog = Activity::latest()->first();
    expect($lastLog)->not->toBeNull();
    expect($lastLog->description)->toContain('Barcode on tag turned ON by Admin Test');
});

test('admin can turn barcode setting off and change is logged in activity log', function () {
    Setting::set('show_barcode', '1');

    $response = $this->actingAs($this->admin)->post(route('settings.update'), [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
        'asset_no_prefix' => 'BBH',
        'tag_width_cm' => '10',
        'tag_height_cm' => '6',
        'show_barcode' => '0',
    ]);

    $response->assertRedirect(route('settings.index'));
    expect(Setting::get('show_barcode'))->toBe('0');

    $lastLog = Activity::latest()->first();
    expect($lastLog)->not->toBeNull();
    expect($lastLog->description)->toContain('Barcode on tag turned OFF by Admin Test');
});

test('renders bulk property tags with hospital header, dimensions, and base64 logo', function () {
    $logoRelativePath = 'logos/test-hospital-logo.png';
    $logoFullPath = storage_path('app/public/' . $logoRelativePath);
    File::ensureDirectoryExists(dirname($logoFullPath));
    File::put($logoFullPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    Setting::set('logo_path', $logoRelativePath);

    $asset1 = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Automated External Defibrillator with Monitor',
        'model' => 'Philips HeartStart',
        'serial_no' => 'SN-998877',
        'date_acquired' => '2026-05-10',
        'location' => 'ND-Station 3 Room 39',
        'status' => 'In Use',
    ]);
    $asset2 = Asset::create([
        'asset_no' => 'BBH-000002',
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->admin)
        ->get('/tags/print?ids[]=' . $asset1->id . '&ids[]=' . $asset2->id)
        ->assertOk()
        ->assertSee('Bethel Baptist Hospital, Inc.')
        ->assertSee('Fortich St., Sumpong, Malaybalay City, Bukidnon 8700')
        ->assertSee('BBHIND-S300002315')
        ->assertSee('Philips HeartStart')
        ->assertSee('SN-998877')
        ->assertSee('ND-Station 3 Room 39')
        ->assertSee('BBH-000002')
        ->assertSee('width: 10cm')
        ->assertSee('height: 6cm')
        ->assertSee('data:image/png;base64,', false);

    // Detail page renders live property tag preview
    $this->actingAs($this->admin)
        ->get(route('assets.show', $asset1))
        ->assertOk()
        ->assertSee('Property Tag Preview')
        ->assertSee('BBHIND-S300002315');

    File::delete($logoFullPath);
});

test('downloads PDF of selected property tags via DomPDF', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Defibrillator',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)->get('/tags/pdf?ids[]=' . $asset->id);
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
