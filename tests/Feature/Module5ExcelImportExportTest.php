<?php

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
});

test('downloads sample excel template and exports assets list', function () {
    Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Ultrasound Machine',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get('/reports')
        ->assertOk()
        ->assertSee('Reports & Excel Import / Export');

    $this->actingAs($this->user)
        ->get('/reports/template')
        ->assertOk();

    $this->actingAs($this->user)
        ->get('/reports/export')
        ->assertOk();
});

test('previews uploaded spreadsheet, flags duplicate asset_no including soft-deleted, allows blank date_acquired/serial_no, and imports non-duplicates', function () {
    Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Existing Asset',
        'status' => 'In Use',
    ]);

    $trashed = Asset::create([
        'asset_no' => 'BBH-TRASHED-01',
        'asset_name' => 'Archived Asset',
        'status' => 'Disposed',
    ]);
    $trashed->delete();

    $csvContent = implode("\n", [
        'asset_no,asset_name,model,serial_no,date_acquired,department,location,status,notes',
        'BBHIND-S300002315,Duplicate Asset,Model X,SN1,2026-01-01,ER,Room 1,In Use,Should skip active duplicate',
        'BBH-TRASHED-01,Trashed Duplicate Asset,Model Y,SN2,2026-01-02,ER,Room 2,In Use,Should skip soft-deleted duplicate',
        'BBH-000500,New Bed,Stryker,,2026-02-15,Ward 2,Room 12,In Use,No serial',
        ',Auto Numbered AC,Carrier,,,Admin,Office 1,In Use,Blank asset_no and date_acquired',
    ]);

    $file = UploadedFile::fake()->createWithContent('assets.csv', $csvContent);

    $previewResponse = $this->actingAs($this->user)
        ->post('/reports/import/preview', ['file' => $file]);

    $previewResponse->assertOk()
        ->assertSee('New Bed')
        ->assertSee('Auto Numbered AC')
        ->assertSee('Duplicate (Skipped)');

    $this->actingAs($this->user)
        ->post('/reports/import/commit')
        ->assertRedirect('/reports');

    expect(Asset::count())->toBe(3)
        ->and(Asset::where('asset_name', 'Duplicate Asset')->exists())->toBeFalse()
        ->and(Asset::where('asset_name', 'Trashed Duplicate Asset')->exists())->toBeFalse()
        ->and(Asset::where('asset_name', 'New Bed')->first()->serial_no)->toBeNull()
        ->and(Asset::where('asset_name', 'Auto Numbered AC')->first()->date_acquired)->toBeNull();
});
