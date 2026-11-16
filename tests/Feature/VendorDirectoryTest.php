<?php

use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('admin can create, edit, deactivate, reactivate, and delete unused vendors with activity logging', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    // Create vendor
    $this->actingAs($admin)
        ->post(route('settings.vendors.store'), [
            'name' => 'MedSupply   Corp',
            'address' => 'Malaybalay City',
            'contact_person' => 'Juan Cruz',
            'phone' => '09171234567',
            'is_donation' => '1',
            'notes' => 'Primary lab supplier',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']));

    $vendor = Vendor::first();
    expect($vendor)->not->toBeNull()
        ->and($vendor->name)->toBe('MedSupply Corp')
        ->and($vendor->is_donation)->toBeTrue()
        ->and($vendor->active)->toBeTrue();

    // Case-insensitive whitespace-normalized duplicate rejected
    $this->actingAs($admin)
        ->post(route('settings.vendors.store'), ['name' => '  medsupply corp  '])
        ->assertSessionHasErrors('vendor_name');

    // Edit vendor
    $this->actingAs($admin)
        ->put(route('settings.vendors.update', $vendor), [
            'name' => 'MedSupply Enterprises',
            'address' => 'Cagayan de Oro',
            'contact_person' => 'Ana Reyes',
            'phone' => '09180001111',
            'notes' => 'Updated notes',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']));

    $vendor->refresh();
    expect($vendor->name)->toBe('MedSupply Enterprises')
        ->and($vendor->is_donation)->toBeFalse();

    // Deactivate & reactivate
    $this->actingAs($admin)
        ->post(route('settings.vendors.deactivate', $vendor))
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']));
    expect($vendor->fresh()->active)->toBeFalse();

    $this->actingAs($admin)
        ->post(route('settings.vendors.reactivate', $vendor))
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']));
    expect($vendor->fresh()->active)->toBeTrue();

    // Cannot hard delete while an asset references the vendor
    $asset = Asset::create([
        'asset_no' => 'BBH-VEND-001',
        'asset_name' => 'Analyzer',
        'status' => 'In Use',
        'vendor_id' => $vendor->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('settings.vendors.destroy', $vendor))
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']))
        ->assertSessionHas('error');
    expect(Vendor::find($vendor->id))->not->toBeNull();

    // Once asset unlinks vendor, unused vendor can be deleted
    $asset->update(['vendor_id' => null]);
    $this->actingAs($admin)
        ->delete(route('settings.vendors.destroy', $vendor))
        ->assertRedirect(route('settings.index', ['tab' => 'vendors']))
        ->assertSessionHas('success');
    expect(Vendor::find($vendor->id))->toBeNull();

    expect(Activity::where('description', 'like', '%Vendor%')->count())->toBeGreaterThanOrEqual(4);
});

test('staff cannot manage vendors (403 on all vendor CRUD routes) and views contain no money', function () {
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $vendor = Vendor::create(['name' => 'CITI HARDWARE', 'active' => true]);

    $this->actingAs($staff)->post(route('settings.vendors.store'), ['name' => 'Blocked'])->assertForbidden();
    $this->actingAs($staff)->put(route('settings.vendors.update', $vendor), ['name' => 'Blocked'])->assertForbidden();
    $this->actingAs($staff)->post(route('settings.vendors.deactivate', $vendor))->assertForbidden();
    $this->actingAs($staff)->post(route('settings.vendors.reactivate', $vendor))->assertForbidden();
    $this->actingAs($staff)->delete(route('settings.vendors.destroy', $vendor))->assertForbidden();

    // Staff settings page does not show Vendors admin tab or money values
    $this->actingAs($staff)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertDontSee('Vendor & Supplier Directory')
        ->assertDontSee('Total Cost')
        ->assertDontSee('Current Value');
});

test('asset form saves vendor, shows donation badge on detail, hides inactive vendor on create but keeps on edit, and supports search & filters', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $dept = Department::create(['name' => 'Nursing', 'code' => 'NURS', 'next_number' => 1, 'active' => true, 'code_confirmed' => true]);
    $sec = Section::create(['department_id' => $dept->id, 'name' => 'Ward 1', 'code' => 'W1', 'active' => true, 'code_confirmed' => true]);

    $activeVendor = Vendor::create(['name' => 'DONATION FROM UNILAB', 'is_donation' => true, 'active' => true]);
    $inactiveVendor = Vendor::create(['name' => 'OLD SUPPLIER', 'is_donation' => false, 'active' => false]);

    // New asset form shows active vendor, hides inactive vendor
    $this->actingAs($staff)
        ->get(route('assets.create'))
        ->assertOk()
        ->assertSee('DONATION FROM UNILAB')
        ->assertDontSee('OLD SUPPLIER');

    // Store asset with active donation vendor
    $this->actingAs($staff)
        ->post(route('assets.store'), [
            'asset_name' => 'Infusion Pump',
            'brand' => 'B. Braun',
            'model' => 'Infusomat',
            'vendor_id' => $activeVendor->id,
            'department_id' => $dept->id,
            'section_id' => $sec->id,
            'status' => 'In Use',
            'asset_condition' => 'Brand-new',
        ])
        ->assertRedirect();

    $asset = Asset::where('asset_name', 'Infusion Pump')->firstOrFail();
    expect($asset->vendor_id)->toBe($activeVendor->id);

    // Detail page as staff shows Vendor + Donation badge and no money
    $this->actingAs($staff)
        ->get(route('assets.show', $asset))
        ->assertOk()
        ->assertSee('DONATION FROM UNILAB')
        ->assertSee('Donation')
        ->assertDontSee('Acquisition Cost')
        ->assertDontSee('Current Value');

    // Blank vendor asset omits Vendor label cleanly on detail page
    $blankAsset = Asset::create([
        'asset_no' => 'BBH-BLANK-V',
        'asset_name' => 'No Vendor Chair',
        'status' => 'In Use',
        'vendor_id' => null,
    ]);
    $this->actingAs($staff)
        ->get(route('assets.show', $blankAsset))
        ->assertOk()
        ->assertDontSee('<dt class="text-slate-600 font-medium">Vendor</dt>', false);

    // Deactivate activeVendor -> hidden on /assets/create, still visible on edit and detail
    $activeVendor->update(['active' => false]);

    $this->actingAs($admin)
        ->get(route('assets.create'))
        ->assertOk()
        ->assertDontSee('DONATION FROM UNILAB');

    $this->actingAs($admin)
        ->get(route('assets.edit', $asset))
        ->assertOk()
        ->assertSee('DONATION FROM UNILAB');

    $this->actingAs($admin)
        ->get(route('assets.show', $asset))
        ->assertOk()
        ->assertSee('DONATION FROM UNILAB');

    // Assets index Vendor filter + search + Quick search + Find Asset lookup
    $this->actingAs($staff)
        ->get(route('assets.index', ['vendor' => $activeVendor->id]))
        ->assertOk()
        ->assertSee('Infusion Pump')
        ->assertDontSee('No Vendor Chair');

    $this->actingAs($staff)
        ->get(route('assets.index', ['search' => 'UNILAB']))
        ->assertOk()
        ->assertSee('Infusion Pump')
        ->assertDontSee('No Vendor Chair');

    $this->actingAs($staff)
        ->getJson(route('assets.quick-search', ['q' => 'UNILAB']))
        ->assertOk()
        ->assertJsonFragment(['asset_name' => 'Infusion Pump', 'vendor_name' => 'DONATION FROM UNILAB']);

    $this->actingAs($staff)
        ->get(route('assets.find.lookup', ['q' => 'UNILAB']))
        ->assertRedirect(route('assets.show', $asset));
});

test('excel and csv exports include vendor column for staff without money values', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $vendor = Vendor::create(['name' => '3 J MEDICAL', 'active' => true]);

    Asset::create([
        'asset_no' => 'BBH-EXP-1',
        'asset_name' => 'ECG Machine',
        'status' => 'In Use',
        'cost' => 75000,
        'vendor_id' => $vendor->id,
    ]);

    // Staff XLSX export includes Vendor in O6 and O7 and excludes Cost/Salvage/Current Value
    $rawStaffXlsx = Excel::raw(new AssetsExport(null, null, $staff, 'xlsx'), \Maatwebsite\Excel\Excel::XLSX);
    $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'staff_vendor_export_' . uniqid() . '.xlsx';
    file_put_contents($tmpPath, $rawStaffXlsx);

    $spreadsheet = IOFactory::load($tmpPath);
    @unlink($tmpPath);
    $sheet = $spreadsheet->getSheet(0);

    expect($sheet->getCell('O6')->getValue())->toBe('Vendor')
        ->and($sheet->getCell('O7')->getValue())->toBe('3 J MEDICAL');

    $headerValues = [];
    for ($col = 'A'; $col <= 'O'; $col++) {
        $headerValues[] = $sheet->getCell("{$col}6")->getValue();
    }
    expect($headerValues)->not->toContain('Cost')
        ->and($headerValues)->not->toContain('Current Value')
        ->and($headerValues)->not->toContain('Salvage Value');

    // Admin XLSX export keeps N6='Cost' and places Vendor at T6/T7
    $rawAdminXlsx = Excel::raw(new AssetsExport(null, null, $admin, 'xlsx'), \Maatwebsite\Excel\Excel::XLSX);
    $tmpAdminPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'admin_vendor_export_' . uniqid() . '.xlsx';
    file_put_contents($tmpAdminPath, $rawAdminXlsx);

    $adminSheet = IOFactory::load($tmpAdminPath)->getSheet(0);
    @unlink($tmpAdminPath);

    expect($adminSheet->getCell('N6')->getValue())->toBe('Cost')
        ->and($adminSheet->getCell('T6')->getValue())->toBe('Vendor')
        ->and($adminSheet->getCell('T7')->getValue())->toBe('3 J MEDICAL');

    // Staff CSV export includes vendor and zero money text
    $csvContent = Excel::raw(new AssetsExport(null, null, $staff, 'csv'), \Maatwebsite\Excel\Excel::CSV);
    expect($csvContent)->toContain('vendor')
        ->and($csvContent)->toContain('3 J MEDICAL')
        ->and($csvContent)->not->toContain('75,000')
        ->and($csvContent)->not->toContain('accumulated_depreciation');
});

test('import matches vendors normalized, flags unknown vendors with near matches, and creates them on commit', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $gaisano = Vendor::create(['name' => 'GAISANO', 'active' => true]);

    $csv = implode("\n", [
        'asset_no,asset_description,vendor,status,asset_condition',
        'BBH-IMP-V1,Office Fan,  gaisano  ,In Use,Brand-new',
        'BBH-IMP-V2,Extension Cord,GAISAN,In Use,In Good Condition',
        'BBH-IMP-V3,Stethoscope,Acme Medical Supply,In Use,Brand-new',
    ]);
    $file = UploadedFile::fake()->createWithContent('vendor_import.csv', $csv);

    $preview = $this->actingAs($admin)
        ->post('/reports/import/preview', ['file' => $file]);

    $preview->assertOk()
        ->assertSee('Unknown vendor, will be created')
        ->assertSee('Near match: GAISANO')
        ->assertSee('Acme Medical Supply');

    $this->actingAs($admin)
        ->post('/reports/import/commit')
        ->assertRedirect('/reports');

    $fan = Asset::where('asset_no', 'BBH-IMP-V1')->firstOrFail();
    $cord = Asset::where('asset_no', 'BBH-IMP-V2')->firstOrFail();
    $steth = Asset::where('asset_no', 'BBH-IMP-V3')->firstOrFail();

    expect($fan->vendor_id)->toBe($gaisano->id)
        ->and($cord->vendor?->name)->toBe('GAISAN')
        ->and($steth->vendor?->name)->toBe('Acme Medical Supply')
        ->and(Vendor::whereRaw('LOWER(name) = ?', ['gaisano'])->count())->toBe(1);
});
