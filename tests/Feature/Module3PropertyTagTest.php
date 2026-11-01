<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    Setting::set('tag_width_cm', '10');
    Setting::set('tag_height_cm', '6');
});

test('renders single and bulk property tags with hospital header, fields, base64 logo, and QR code', function () {
    // Create a temporary logo image and set logo_path to verify Adjustment #6 (base64 embedding)
    $logoRelativePath = 'logos/test-hospital-logo.png';
    $logoFullPath = storage_path('app/public/' . $logoRelativePath);
    File::ensureDirectoryExists(dirname($logoFullPath));
    // 1x1 transparent PNG
    File::put($logoFullPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    Setting::set('logo_path', $logoRelativePath);

    $asset1 = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Defibrillator',
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

    $svg = app(QrCodeService::class)->svg('BBHIND-S300002315');
    expect($svg)->toContain('<svg');

    $this->actingAs($this->user)
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
        ->assertSee('data:image/png;base64,', false)
        ->assertSee('<svg', false);

    // Also verify the asset detail page includes the live QR property tag preview
    $this->actingAs($this->user)
        ->get(route('assets.show', $asset1))
        ->assertOk()
        ->assertSee('Property Tag Preview')
        ->assertSee('<svg', false);

    File::delete($logoFullPath);
});

test('downloads PDF of selected property tags via DomPDF', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Defibrillator',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->user)->get('/tags/pdf?ids[]=' . $asset->id);
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
